<?php

namespace Ritechoice23\FluentFFmpeg\Concerns;

use Ritechoice23\FluentFFmpeg\Actions\GenerateAudioPeaks;

trait HasAudioPeaks
{
    /**
     * Peaks generation configuration
     */
    protected ?array $peaksConfig = null;

    /**
     * Enable peaks generation during transcoding
     *
     * @param  int  $samplesPerPixel  Number of audio samples per waveform min/max pair (higher = less detail, smaller file)
     * @param  array|null  $normalizeRange  Normalization range [min, max] or null for raw values
     *                                       Examples: [0, 1] for wavesurfer.js, [-1, 1] for signed normalized, null for raw values (-32768 to 32767)
     * @return self
     */
    public function withPeaks(int $samplesPerPixel = 512, ?array $normalizeRange = null): self
    {
        if ($normalizeRange !== null && count($normalizeRange) !== 2) {
            throw new \InvalidArgumentException('normalizeRange must be an array with exactly 2 values [min, max] or null');
        }

        $this->peaksConfig = [
            'samples_per_pixel' => $samplesPerPixel,
            'normalize_range' => $normalizeRange,
        ];

        return $this;
    }

    /**
     * Get peaks configuration
     */
    public function getPeaksConfig(): ?array
    {
        return $this->peaksConfig;
    }

    /**
     * Generate audio peaks data (standalone - processes file separately)
     *
     * @param  int  $samplesPerPixel  Number of audio samples per waveform min/max pair (higher = less detail, smaller file)
     * @param  array|null  $normalizeRange  Normalization range [min, max] or null for raw values
     *                                       Examples: [0, 1] for wavesurfer.js, [-1, 1] for signed normalized, null for raw values (-32768 to 32767)
     * @return array Peaks data array
     *
     * @throws \Ritechoice23\FluentFFmpeg\Exceptions\ExecutionException
     */
    public function generatePeaks(int $samplesPerPixel = 512, ?array $normalizeRange = null): array
    {
        $inputFile = $this->getInputs()[0] ?? null;

        if (! $inputFile) {
            throw new \RuntimeException('No input file specified. Use fromPath(), fromDisk(), or fromUrl() first.');
        }

        $action = new GenerateAudioPeaks;

        return $action->execute($inputFile, $samplesPerPixel, $normalizeRange);
    }

    /**
     * Generate audio peaks and save to a JSON file
     *
     * @param  string  $outputPath  Path where the JSON file should be saved
     * @param  int  $samplesPerPixel  Number of audio samples per waveform min/max pair
     * @param  array|null  $normalizeRange  Normalization range [min, max] or null for raw values
     * @return string The path to the saved JSON file
     *
     * @throws \Ritechoice23\FluentFFmpeg\Exceptions\ExecutionException
     */
    public function generatePeaksToFile(string $outputPath, int $samplesPerPixel = 512, ?array $normalizeRange = null): string
    {
        $peaks = $this->generatePeaks($samplesPerPixel, $normalizeRange);

        $json = json_encode($peaks, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Failed to encode peaks data as JSON: '.json_last_error_msg());
        }

        $directory = dirname($outputPath);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        if (file_put_contents($outputPath, $json) === false) {
            throw new \RuntimeException("Failed to write peaks data to file: {$outputPath}");
        }

        return $outputPath;
    }

    /**
     * Generate audio peaks and save to Laravel disk
     *
     * @param  string  $disk  The Laravel disk name
     * @param  string  $path  Path on the disk where the JSON file should be saved
     * @param  int  $samplesPerPixel  Number of audio samples per waveform min/max pair
     * @param  array|null  $normalizeRange  Normalization range [min, max] or null for raw values
     * @return string The path on the disk
     *
     * @throws \Ritechoice23\FluentFFmpeg\Exceptions\ExecutionException
     */
    public function generatePeaksToDisk(string $disk, string $path, int $samplesPerPixel = 512, ?array $normalizeRange = null): string
    {
        $peaks = $this->generatePeaks($samplesPerPixel, $normalizeRange);

        $json = json_encode($peaks, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Failed to encode peaks data as JSON: '.json_last_error_msg());
        }

        \Illuminate\Support\Facades\Storage::disk($disk)->put($path, $json);

        return $path;
    }
}
