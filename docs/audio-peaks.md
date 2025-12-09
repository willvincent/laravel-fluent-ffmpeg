# Audio Peaks Generation

Generate audio peaks data for waveform visualization, for example with [wavesurfer.js](https://wavesurfer.xyz/).

## Basic Usage

```php
use Ritechoice23\FluentFFmpeg\Facades\FFmpeg;

// Generate peaks with raw values (no normalization)
$peaks = FFmpeg::fromPath('audio.mp3')->generatePeaks();

// Generate peaks normalized for wavesurfer.js (0 to 1 range)
$peaks = FFmpeg::fromPath('audio.mp3')->generatePeaks(
    samplesPerPixel: 512,
    normalizeRange: [0, 1]
);

// Generate peaks with signed normalization (-1 to 1 range)
$peaks = FFmpeg::fromPath('audio.mp3')->generatePeaks(
    samplesPerPixel: 512,
    normalizeRange: [-1, 1]
);

// Custom normalization range
$peaks = FFmpeg::fromPath('audio.mp3')->generatePeaks(
    samplesPerPixel: 512,
    normalizeRange: [0, 100]
);
```
Note: Normalization in this case is _only_ of audio peaks data, not the actual audio itself.

## Save to File

```php
// Save to JSON file
FFmpeg::fromPath('audio.mp3')
    ->generatePeaksToFile(
        'public/peaks/audio.json',
        samplesPerPixel: 512,
        normalizeRange: [0, 1]
    );

// Save to Laravel disk (S3, etc.)
FFmpeg::fromPath('audio.mp3')
    ->generatePeaksToDisk(
        disk: 's3',
        path: 'peaks/audio.json',
        samplesPerPixel: 512,
        normalizeRange: [0, 1]
    );
```

## Resolution Control

The `samplesPerPixel` parameter controls detail vs. file size:

```php
// High detail (more data, larger file)
$peaks = FFmpeg::fromPath('audio.mp3')->generatePeaks(samplesPerPixel: 256);

// Medium detail (balanced)
$peaks = FFmpeg::fromPath('audio.mp3')->generatePeaks(samplesPerPixel: 512);

// Low detail (less data, smaller file - good for long files)
$peaks = FFmpeg::fromPath('audio.mp3')->generatePeaks(samplesPerPixel: 2048);
```

## Output Format

```json
{
    "version": 2,
    "channels": 2,
    "sample_rate": 44100,
    "samples_per_pixel": 512,
    "bits": 32,
    "length": 1000,
    "data": [0.234, 0.456, 0.123, 0.789, ...]
}
```

- **version**: Format version (2 for multi-channel)
- **channels**: Number of audio channels (1=mono, 2=stereo)
- **sample_rate**: Original sample rate in Hz
- **samples_per_pixel**: Samples per peak point
- **bits**: 32 for normalized, 16 for raw
- **length**: Number of min/max pairs per channel
- **data**: Interleaved channel min/max values

## wavesurfer.js Integration

```javascript
// Fetch pre-generated peaks
const response = await fetch('/peaks/audio.json');
const peaksData = await response.json();

// Initialize wavesurfer
const wavesurfer = WaveSurfer.create({
    container: '#waveform',
    waveColor: 'violet',
    progressColor: 'purple',
    peaks: [peaksData.data],
    duration: audioDuration // Required when using peaks
});

wavesurfer.load('/audio.mp3');
```

## Laravel Example

```php
class AudioController extends Controller
{
    public function upload(Request $request)
    {
        $file = $request->file('audio');
        $path = $file->store('audio', 'public');
        $fullPath = storage_path('app/public/' . $path);

        // Generate peaks for wavesurfer.js
        $peaksPath = str_replace(
            pathinfo($path, PATHINFO_EXTENSION),
            'json',
            $path
        );

        FFmpeg::fromPath($fullPath)->generatePeaksToFile(
            storage_path('app/public/' . $peaksPath),
            samplesPerPixel: 512,
            normalizeRange: [0, 1] // For wavesurfer.js
        );

        return response()->json([
            'audio_url' => Storage::url($path),
            'peaks_url' => Storage::url($peaksPath),
        ]);
    }
}
```

## Normalization Options

### No Normalization (Raw Values)
```php
// Returns raw PCM values: -32768 to 32767
$peaks = FFmpeg::fromPath('audio.mp3')->generatePeaks(
    normalizeRange: null
);
```

### Absolute Normalization (0 to 1)
```php
// Best for wavesurfer.js - values from 0 (silence) to 1 (max amplitude)
$peaks = FFmpeg::fromPath('audio.mp3')->generatePeaks(
    normalizeRange: [0, 1]
);
```

### Signed Normalization (-1 to 1)
```php
// Preserves positive/negative waveform: -1 to 1
$peaks = FFmpeg::fromPath('audio.mp3')->generatePeaks(
    normalizeRange: [-1, 1]
);
```

### Custom Range
```php
// Any custom range you need
$peaks = FFmpeg::fromPath('audio.mp3')->generatePeaks(
    normalizeRange: [0, 255]  // 8-bit range
);
```

## Performance

- **Memory efficient**: Streams audio data incrementally, doesn't load entire file into memory
- **Pre-generate**: Generate peaks when audio is uploaded, not on-demand
- **Background jobs**: Use Laravel queues for large files
- **Adjust resolution**: Higher `samplesPerPixel` for long audio files

```php
// Queue for background processing
dispatch(function() use ($audioPath) {
    FFmpeg::fromPath($audioPath)->generatePeaksToFile(
        'public/peaks/audio.json',
        samplesPerPixel: 1024,
        normalizeRange: [0, 1]
    );
});
```
