<?php

namespace Ritechoice23\FluentFFmpeg\Actions;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Ritechoice23\FluentFFmpeg\Events\FFmpegProcessCompleted;
use Ritechoice23\FluentFFmpeg\Events\FFmpegProcessFailed;
use Ritechoice23\FluentFFmpeg\Events\FFmpegProcessStarted;
use Ritechoice23\FluentFFmpeg\Exceptions\ExecutionException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class ExecuteFFmpegCommand
{
    /**
     * Execute the FFmpeg command
     */
    public function execute(
        string $command,
        ?callable $progressCallback = null,
        ?callable $errorCallback = null,
        ?string $outputDisk = null,
        ?string $outputPath = null,
        array $inputs = [],
        ?array $peaksConfig = null
    ): array {
        // Determine if we need streaming execution
        $needsStreaming = $outputDisk || $peaksConfig;

        if ($needsStreaming) {
            return $this->executeWithStreaming(
                $command,
                $progressCallback,
                $errorCallback,
                $outputDisk,
                $outputPath,
                $inputs,
                $peaksConfig
            );
        }

        // Standard execution (existing behavior)
        return $this->executeStandard(
            $command,
            $progressCallback,
            $errorCallback,
            $outputDisk,
            $outputPath,
            $inputs
        );
    }

    /**
     * Execute with streaming (for S3 output and/or peaks generation)
     */
    protected function executeWithStreaming(
        string $command,
        ?callable $progressCallback,
        ?callable $errorCallback,
        ?string $outputDisk,
        ?string $outputPath,
        array $inputs,
        ?array $peaksConfig
    ): array {
        $startTime = microtime(true);

        // Dispatch started event
        event(new FFmpegProcessStarted($command, $inputs, $outputPath));

        // Log the command
        if ($logChannel = config('fluent-ffmpeg.log_channel')) {
            Log::channel($logChannel)->info('Executing FFmpeg command with streaming', [
                'command' => $command,
            ]);
        }

        $descriptors = [
            0 => ['pipe', 'r'],  // stdin
            1 => ['pipe', 'w'],  // stdout (progress)
            2 => ['pipe', 'w'],  // stderr
        ];

        if ($peaksConfig) {
            $descriptors[3] = ['pipe', 'w'];  // PCM output for peaks
        }

        if ($outputDisk) {
            $descriptors[4] = ['pipe', 'w'];  // Transcoded output
        }

        $process = proc_open($command, $descriptors, $pipes);

        if (! is_resource($process)) {
            throw new ExecutionException('Failed to start FFmpeg process');
        }

        fclose($pipes[0]); // Close stdin

        // Set non-blocking mode
        foreach ($pipes as $i => $pipe) {
            if ($i > 0 && is_resource($pipe)) {
                stream_set_blocking($pipe, false);
            }
        }

        // Prepare output stream for disk
        $outputStream = null;
        if ($outputDisk && $outputPath) {
            $outputStream = fopen('php://temp', 'w+b');
        }

        // Initialize peaks processing
        $peaksData = null;
        $peaksProcessor = null;
        if ($peaksConfig) {
            $peaksProcessor = new PeaksStreamProcessor($peaksConfig);
        }

        $progressBuffer = '';
        $errorBuffer = '';

        // Process streams
        while (true) {
            $read = array_filter($pipes, fn ($p) => is_resource($p));
            $write = null;
            $except = null;

            if (empty($read) || stream_select($read, $write, $except, 1) === false) {
                break;
            }

            foreach ($read as $i => $stream) {
                $data = fread($stream, 8192);

                if ($data === false || $data === '') {
                    continue;
                }

                // Progress from pipe 1
                if ($i === 1) {
                    $progressBuffer .= $data;
                    $this->handleProgressBuffer($progressBuffer, $progressCallback);
                }

                // Errors from pipe 2
                if ($i === 2) {
                    $errorBuffer .= $data;
                }

                // PCM data from pipe 3
                if ($i === 3 && $peaksProcessor) {
                    $peaksProcessor->processPcmChunk($data);
                }

                // Transcoded output from pipe 4
                if ($i === 4 && $outputStream) {
                    fwrite($outputStream, $data);
                }
            }

            // Check if process is still running
            $status = proc_get_status($process);
            if (! $status['running']) {
                // Read any remaining data
                foreach ($pipes as $i => $pipe) {
                    if (! is_resource($pipe)) {
                        continue;
                    }

                    while (! feof($pipe)) {
                        $data = fread($pipe, 8192);
                        if ($data === false || $data === '') {
                            break;
                        }

                        if ($i === 3 && $peaksProcessor) {
                            $peaksProcessor->processPcmChunk($data);
                        } elseif ($i === 4 && $outputStream) {
                            fwrite($outputStream, $data);
                        }
                    }
                }
                break;
            }
        }

        // Close all pipes
        foreach ($pipes as $pipe) {
            if (is_resource($pipe)) {
                fclose($pipe);
            }
        }

        $exitCode = proc_close($process);

        if ($exitCode !== 0) {
            if ($outputStream) {
                fclose($outputStream);
            }

            event(new FFmpegProcessFailed($command, $errorBuffer, $exitCode));

            if ($errorCallback) {
                call_user_func($errorCallback, $errorBuffer);
            }

            throw new ExecutionException(
                "FFmpeg process failed: {$errorBuffer}\nCommand: {$command}",
                $exitCode
            );
        }

        // Finalize peaks
        if ($peaksProcessor) {
            $peaksData = $peaksProcessor->finalize();
        }

        // Upload to disk if needed
        if ($outputDisk && $outputPath && $outputStream) {
            rewind($outputStream);
            Storage::disk($outputDisk)->writeStream($outputPath, $outputStream);
            fclose($outputStream);
        }

        // Save peaks to JSON file if generated
        if ($peaksData && $outputPath) {
            $this->savePeaksFile($peaksData, $outputPath, $outputDisk);
        }

        // Log success
        if ($logChannel = config('fluent-ffmpeg.log_channel')) {
            Log::channel($logChannel)->info('FFmpeg command completed successfully');
        }

        // Dispatch completed event
        $duration = microtime(true) - $startTime;
        event(new FFmpegProcessCompleted($command, $outputPath ?? 'stream', $duration));

        return [
            'success' => true,
            'peaks' => $peaksData,
        ];
    }

    /**
     * Save peaks data to JSON file
     *
     * @return string The path to the saved peaks file
     */
    protected function savePeaksFile(array $peaksData, string $outputPath, ?string $outputDisk): string
    {
        // Generate peaks filename: output.m4a -> output-peaks.json
        $pathInfo = pathinfo($outputPath);
        $peaksPath = ($pathInfo['dirname'] !== '.' ? $pathInfo['dirname'].'/' : '')
            .$pathInfo['filename'].'-peaks.json';

        // Determine format based on config
        $peaksFormat = config('fluent-ffmpeg.peaks_format', 'simple');
        $peaksContent = $peaksFormat === 'full'
            ? $peaksData
            : $peaksData['data'];

        $json = json_encode($peaksContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if ($outputDisk) {
            // Save to disk
            Storage::disk($outputDisk)->put($peaksPath, $json);
        } else {
            // Save to local file
            $directory = dirname($peaksPath);
            if ($directory !== '.' && ! is_dir($directory)) {
                mkdir($directory, 0755, true);
            }
            file_put_contents($peaksPath, $json);
        }

        return $peaksPath;
    }

    /**
     * Execute standard (non-streaming)
     */
    protected function executeStandard(
        string $command,
        ?callable $progressCallback,
        ?callable $errorCallback,
        ?string $outputDisk,
        ?string $outputPath,
        array $inputs
    ): array {
        // Ensure output directory exists before running FFmpeg
        if ($outputPath && ! $outputDisk) {
            $outputDir = dirname($outputPath);
            if (! is_dir($outputDir)) {
                mkdir($outputDir, 0755, true);
            }
        }

        $startTime = microtime(true);

        // Dispatch started event
        event(new FFmpegProcessStarted($command, $inputs, $outputPath));

        try {
            // Create process
            $process = Process::fromShellCommandline($command);
            $process->setTimeout(config('fluent-ffmpeg.timeout', 3600));

            // Log the command if logging is enabled
            if ($logChannel = config('fluent-ffmpeg.log_channel')) {
                Log::channel($logChannel)->info('Executing FFmpeg command', [
                    'command' => $command,
                ]);
            }

            // Execute the process
            $process->run(function ($type, $buffer) use ($progressCallback) {
                if ($progressCallback && $type === Process::OUT) {
                    // Parse FFmpeg progress output
                    $progress = $this->parseProgress($buffer);
                    if ($progress) {
                        call_user_func($progressCallback, $progress);
                    }
                }
            });

            // Check if process was successful
            if (! $process->isSuccessful()) {
                $error = $process->getErrorOutput();

                // Include stdout as well for better debugging
                $output = $process->getOutput();

                if ($errorCallback) {
                    call_user_func($errorCallback, $error);
                }

                throw new ExecutionException(
                    "FFmpeg command failed: {$error}\nOutput: {$output}\nCommand: {$command}",
                    $process->getExitCode()
                );
            }

            // Log success
            if ($logChannel = config('fluent-ffmpeg.log_channel')) {
                Log::channel($logChannel)->info('FFmpeg command completed successfully');
            }

            // Dispatch completed event
            $duration = microtime(true) - $startTime;
            event(new FFmpegProcessCompleted($command, $outputPath ?? 'stream', $duration));

            return ['success' => true, 'peaks' => null];
        } catch (ProcessFailedException $e) {
            // Dispatch failed event
            event(new FFmpegProcessFailed($command, $e->getMessage(), $e->getCode()));

            if ($errorCallback) {
                call_user_func($errorCallback, $e->getMessage());
            }

            throw new ExecutionException(
                "FFmpeg process failed: {$e->getMessage()}",
                $e->getCode(),
                $e
            );
        } catch (ExecutionException $e) {
            // Dispatch failed event for execution exceptions
            event(new FFmpegProcessFailed($command, $e->getMessage(), $e->getCode()));

            throw $e;
        }
    }

    /**
     * Handle progress buffer
     */
    protected function handleProgressBuffer(string &$buffer, ?callable $callback): void
    {
        if (! $callback) {
            return;
        }

        $progress = $this->parseProgress($buffer);
        if ($progress) {
            call_user_func($callback, $progress);
        }
    }

    /**
     * Parse FFmpeg progress output
     */
    protected function parseProgress(string $buffer): ?array
    {
        // FFmpeg outputs progress in format: frame=  123 fps= 45 q=28.0 size=    1024kB time=00:00:05.00 bitrate=1677.7kbits/s speed=1.5x
        if (preg_match('/time=(\d+):(\d+):(\d+\.\d+)/', $buffer, $timeMatches)) {
            $hours = (int) $timeMatches[1];
            $minutes = (int) $timeMatches[2];
            $seconds = (float) $timeMatches[3];
            $timeProcessed = ($hours * 3600) + ($minutes * 60) + $seconds;

            $progress = [
                'time_processed' => $timeProcessed,
            ];

            // Extract FPS
            if (preg_match('/fps=\s*(\d+\.?\d*)/', $buffer, $fpsMatches)) {
                $progress['fps'] = (float) $fpsMatches[1];
            }

            // Extract speed
            if (preg_match('/speed=\s*(\d+\.?\d*)x/', $buffer, $speedMatches)) {
                $progress['speed'] = (float) $speedMatches[1];
            }

            return $progress;
        }

        return null;
    }

    /**
     * Get media info for peaks processing
     */
    protected function getMediaInfoForPeaks(string $ffprobePath, string $input): array
    {
        $command = sprintf(
            '%s -v quiet -print_format json -show_streams -select_streams a:0 %s',
            $ffprobePath,
            escapeshellarg($input)
        );

        $process = Process::fromShellCommandline($command);
        $process->setTimeout(30);
        $process->run();

        if (! $process->isSuccessful()) {
            // Return defaults if probe fails
            return ['channels' => 2, 'sample_rate' => 44100];
        }

        $data = json_decode($process->getOutput(), true);

        if (json_last_error() !== JSON_ERROR_NONE || empty($data['streams'][0])) {
            return ['channels' => 2, 'sample_rate' => 44100];
        }

        $audioStream = $data['streams'][0];

        return [
            'channels' => (int) ($audioStream['channels'] ?? 2),
            'sample_rate' => (int) ($audioStream['sample_rate'] ?? 44100),
        ];
    }
}
