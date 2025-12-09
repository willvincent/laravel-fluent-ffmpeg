# Transcode with Peaks Generation

Generate audio peaks while transcoding audio/video files in a single pass through FFmpeg.

## Basic Usage

### Audio Transcode with Peaks

```php
use Ritechoice23\FluentFFmpeg\Facades\FFmpeg;

$success = FFmpeg::fromPath('input.mp3')
    ->audioCodec('aac')
    ->audioBitrate('128k')
    ->withPeaks(samplesPerPixel: 512, normalizeRange: [0, 1])
    ->save('output.m4a');

// Returns: true
// Peaks automatically saved to: output-peaks.json
```

### Video Transcode with Audio Peaks

```php
$success = FFmpeg::fromPath('input.mp4')
    ->videoCodec('libx264')
    ->audioCodec('aac')
    ->withPeaks(normalizeRange: [0, 1])
    ->save('output.mp4');

// Returns: true
// Transcodes video AND generates peaks from the audio track
// Peaks automatically saved to: output-peaks.json
```

### Stream from S3, Transcode, Generate Peaks, Upload to S3

```php
$success = FFmpeg::fromDisk('s3', 'uploads/audio.mp3')
    ->audioCodec('aac')
    ->audioBitrate('128k')
    ->withPeaks(samplesPerPixel: 512, normalizeRange: [0, 1])
    ->onProgress(fn($progress) => broadcast(new TranscodeProgress($progress)))
    ->toDisk('s3', 'processed/audio.m4a');

// Returns: true
// Streams input from S3 → Transcodes → Generates peaks → Streams output to S3
// Peaks automatically saved to: processed/audio-peaks.json
// All in one pass through FFmpeg!
```

## wavesurfer.js Integration

### Backend (Laravel)

```php
use Ritechoice23\FluentFFmpeg\Facades\FFmpeg;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AudioController extends Controller
{
    public function upload(Request $request)
    {
        $file = $request->file('audio');
        $inputPath = $file->store('uploads', 's3');

        // Transcode AND generate peaks in one shot
        FFmpeg::fromDisk('s3', $inputPath)
            ->audioCodec('aac')
            ->audioBitrate('128k')
            ->withPeaks(samplesPerPixel: 512, normalizeRange: [0, 1])
            ->toDisk('s3', 'processed/audio.m4a');

        // Peaks are automatically saved to processed/audio-peaks.json

        return response()->json([
            'audio_url' => Storage::disk('s3')->url('processed/audio.m4a'),
            'peaks_url' => Storage::disk('s3')->url('processed/audio-peaks.json'),
        ]);
    }
}
```

### Frontend (JavaScript)

```javascript
// Fetch the auto-generated peaks file
const response = await fetch('/storage/processed/audio-peaks.json');
const peaksData = await response.json();

// If using 'simple' format (default):
const wavesurfer = WaveSurfer.create({
    container: '#waveform',
    waveColor: 'rgb(200, 0, 200)',
    progressColor: 'rgb(100, 0, 100)',
    url: '/storage/processed/audio.m4a',
    peaks: [peaksData], // peaksData is already an array
});

// If using 'full' format:
const wavesurfer = WaveSurfer.create({
    container: '#waveform',
    waveColor: 'rgb(200, 0, 200)',
    progressColor: 'rgb(100, 0, 100)',
    url: '/storage/processed/audio.m4a',
    peaks: [peaksData.data], // Extract the data array
});
```

## Configuration Options

### Peaks Output Format

Control the format of peaks JSON files in `config/fluent-ffmpeg.php`:

```php
// Simple format (default) - Just the data array for wavesurfer.js
'peaks_format' => 'simple',

// Full format - Complete audiowaveform format with metadata
'peaks_format' => 'full',
```

Or in `.env`:

```env
FFMPEG_PEAKS_FORMAT=simple  # or 'full'
```

**Simple Format (default):** `output-peaks.json`
```json
[0.1, 0.3, 0.2, 0.4, 0.5, 0.6, ...]
```

**Full Format:** `output-peaks.json`
```json
{
    "version": 2,
    "channels": 2,
    "sample_rate": 44100,
    "samples_per_pixel": 512,
    "bits": 32,
    "length": 1000,
    "data": [0.1, 0.3, 0.2, 0.4, 0.5, 0.6, ...]
}
```

Use **simple** format for:
- Direct use with wavesurfer.js
- Minimal payload size
- When you only need the waveform data

Use **full** format for:
- Storing complete audiowaveform files
- When you need metadata (channels, sample rate, etc.)
- Compatibility with BBC audiowaveform tools

### Normalization Ranges

```php
// No normalization - raw PCM values (-32768 to 32767)
->withPeaks(normalizeRange: null)

// Absolute normalization (0 to 1) - best for wavesurfer.js
->withPeaks(normalizeRange: [0, 1])

// Signed normalization (-1 to 1) - preserves waveform direction
->withPeaks(normalizeRange: [-1, 1])

// Custom range
->withPeaks(normalizeRange: [0, 255]) // 8-bit range
```

### Samples Per Pixel

Controls the resolution of the waveform:

```php
// High detail (more data points, larger array)
->withPeaks(samplesPerPixel: 256)

// Medium detail (balanced)
->withPeaks(samplesPerPixel: 512) // Default

// Low detail (fewer data points, smaller array)
->withPeaks(samplesPerPixel: 2048) // Good for long audio files
```

## S3 Streaming Configuration

Enable/disable S3 streaming in `config/fluent-ffmpeg.php`:

```php
's3_streaming' => env('FFMPEG_S3_STREAMING', true),
```

Or in `.env`:

```env
FFMPEG_S3_STREAMING=true
```

**When enabled:**
- `fromDisk('s3', ...)` uses pre-signed URLs to stream directly from S3
- No local temp file needed for input
- Faster processing, lower disk usage

**When disabled:**
- Files are downloaded to local temp directory first
- More reliable for very large files or slow connections

## Progress Tracking

Track both transcode AND peaks generation progress:

```php
FFmpeg::fromDisk('s3', 'large-file.mp3')
    ->audioCodec('aac')
    ->withPeaks(normalizeRange: [0, 1])
    ->onProgress(function($progress) {
        // Broadcast progress to frontend
        broadcast(new AudioProcessingProgress([
            'time_processed' => $progress['time_processed'],
            'fps' => $progress['fps'] ?? null,
            'speed' => $progress['speed'] ?? null,
        ]));
    })
    ->toDisk('s3', 'output.m4a');
```

### With Laravel Broadcasting

```php
// app/Events/AudioProcessingProgress.php
class AudioProcessingProgress implements ShouldBroadcast
{
    public function __construct(public array $progress) {}

    public function broadcastOn()
    {
        return new Channel('audio-processing');
    }
}
```

```javascript
// Frontend
Echo.channel('audio-processing')
    .listen('AudioProcessingProgress', (e) => {
        console.log(`Progress: ${e.progress.time_processed}s`);
    });
```

## Performance Tips

1. **Pre-generate peaks on upload** - Don't generate peaks on-demand for playback
2. **Use appropriate resolution** - Higher `samplesPerPixel` for longer files
3. **Enable S3 streaming** - Avoid local temp files when possible
4. **Use queues for large files** - Process in background jobs

```php
// Queue example
dispatch(function() use ($inputPath, $outputPath) {
    FFmpeg::fromDisk('s3', $inputPath)
        ->audioCodec('aac')
        ->withPeaks(samplesPerPixel: 1024, normalizeRange: [0, 1])
        ->toDisk('s3', $outputPath);
});
```

## How It Works

Under the hood, this uses FFmpeg's multiple output feature:

```bash
ffmpeg -i input.mp3 \
  -map 0:a -c:a aac -b:a 128k -f mp4 pipe:4 \      # Transcoded output
  -map 0:a -f s16le -acodec pcm_s16le pipe:3      # PCM for peaks
```

The package:
1. Streams input from S3 (or local file)
2. Decodes audio once
3. Encodes to target format (pipe:4)
4. Simultaneously generates PCM for peaks (pipe:3)
5. Processes PCM incrementally to generate peaks array
6. Streams output to S3 (or local file)
7. Automatically saves peaks to `{output}-peaks.json`

**All in one pass through FFmpeg!**

## Without Peaks

If you don't call `withPeaks()`, everything works exactly as before:

```php
// Standard transcode (no peaks)
$success = FFmpeg::fromPath('input.mp3')
    ->audioCodec('aac')
    ->save('output.m4a');

// Returns: true (boolean, backwards compatible)
```

## Multi-Channel Audio

For stereo audio, wavesurfer.js expects an array of arrays:

```php
FFmpeg::fromPath('stereo.mp3')
    ->withPeaks(normalizeRange: [0, 1])
    ->save('output.m4a');

// Peaks are saved to output-peaks.json as a flat array with interleaved channels:
// [ch1_min, ch1_max, ch2_min, ch2_max, ch1_min, ch1_max, ...]
```

```javascript
// For wavesurfer.js with multiple channels, split manually:
const response = await fetch('/storage/output-peaks.json');
const peaks = await response.json();

const channelPeaks = [
    peaks.filter((_, i) => i % 4 < 2), // Ch 1
    peaks.filter((_, i) => i % 4 >= 2), // Ch 2
];

const wavesurfer = WaveSurfer.create({
    container: '#waveform',
    url: '/storage/output.m4a',
    peaks: channelPeaks,
    splitChannels: true,
});
```

## Troubleshooting

### Large Files Taking Too Long

Increase `samplesPerPixel` to reduce data:

```php
->withPeaks(samplesPerPixel: 2048) // Less detail = faster processing
```

### S3 Streaming Fails

Disable S3 streaming and use local temp files:

```env
FFMPEG_S3_STREAMING=false
```

### Memory Issues

The peaks are processed incrementally, but for very long files you may need more memory:

```php
ini_set('memory_limit', '512M');
```
