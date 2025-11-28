# Media Information (Probe)

Get detailed information about media files using FFprobe.

## Basic Usage

```php
use Ritechoice23\FluentFFmpeg\Facades\FFmpeg;

$info = FFmpeg::probe('video.mp4');
```

## Available Information

### Duration

```php
$duration = $info->duration(); // in seconds (e.g., 125.5)
```

### Video Information

```php
// Video codec
$videoCodec = $info->videoCodec(); // e.g., 'h264', 'vp9'

// Resolution
$width = $info->width();   // e.g., 1920
$height = $info->height(); // e.g., 1080

// Frame rate
$fps = $info->frameRate(); // e.g., 29.97, 30, 60

// Bit rate
$videoBitrate = $info->videoBitrate(); // in bits per second
```

### Audio Information

```php
// Audio codec
$audioCodec = $info->audioCodec(); // e.g., 'aac', 'mp3'

// Sample rate
$sampleRate = $info->sampleRate(); // e.g., 44100, 48000

// Channels
$channels = $info->audioChannels(); // e.g., 2 (stereo), 6 (5.1)

// Bit rate
$audioBitrate = $info->audioBitrate(); // in bits per second
```

### Format Information

```php
// Container format
$format = $info->format(); // e.g., 'mp4', 'mov', 'mkv'

// File size
$size = $info->size(); // in bytes
```

## Practical Examples

### Check Video Duration Before Clipping

```php
$info = FFmpeg::probe('long-video.mp4');
$duration = $info->duration();

if ($duration > 3600) {
    // Video is longer than 1 hour
    echo "Video duration: " . gmdate("H:i:s", $duration);
}
```

### Validate Resolution

```php
$info = FFmpeg::probe('video.mp4');

if ($info->width() >= 1920 && $info->height() >= 1080) {
    echo "Video is Full HD or higher";
} else {
    echo "Video resolution: {$info->width()}x{$info->height()}";
}
```

### Check if Re-encoding is Needed

```php
$info = FFmpeg::probe('input.mp4');

if ($info->videoCodec() !== 'h264') {
    // Need to re-encode
    FFmpeg::fromPath('input.mp4')
        ->videoCodec('libx264')
        ->save('output.mp4');
} else {
    echo "Already encoded with H.264";
}
```

### Get File Information Summary

```php
$info = FFmpeg::probe('video.mp4');

echo "Format: " . $info->format() . "\n";
echo "Duration: " . gmdate("H:i:s", $info->duration()) . "\n";
echo "Resolution: {$info->width()}x{$info->height()}\n";
echo "Video Codec: " . $info->videoCodec() . "\n";
echo "Audio Codec: " . $info->audioCodec() . "\n";
echo "Frame Rate: " . $info->frameRate() . " fps\n";
echo "File Size: " . round($info->size() / 1024 / 1024, 2) . " MB\n";
```

### Determine Optimal Encoding Settings

```php
$info = FFmpeg::probe('source.mp4');

// Match source frame rate
$fps = $info->frameRate();

// Calculate bitrate based on resolution
$pixels = $info->width() * $info->height();
$targetBitrate = match(true) {
    $pixels >= 3840 * 2160 => '20000k', // 4K
    $pixels >= 1920 * 1080 => '5000k',  // 1080p
    $pixels >= 1280 * 720 => '2500k',   // 720p
    default => '1000k'
};

FFmpeg::fromPath('source.mp4')
    ->resolution($info->width(), $info->height())
    ->frameRate($fps)
    ->videoBitrate($targetBitrate)
    ->save('optimized.mp4');
```

## Use Cases

### Pre-flight Validation

```php
function validateVideo(string $path): array
{
    $info = FFmpeg::probe($path);
    $errors = [];
    
    // Check minimum resolution
    if ($info->width() < 1280 ||$info->height() < 720) {
        $errors[] = "Resolution must be at least 720p";
    }
    
    // Check codec
    if (!in_array($info->videoCodec(), ['h264', 'h265'])) {
        $errors[] = "Unsupported video codec: " . $info->videoCodec();
    }
    
    // Check duration
    if ($info->duration() > 7200) {
        $errors[] = "Video exceeds maximum duration of 2 hours";
    }
    
    return $errors;
}

$errors = validateVideo('upload.mp4');
if (empty($errors)) {
    // Process video
} else {
    // Show errors to user
}
```

### Smart Thumbnail Generation

```php
$info = FFmpeg::probe('video.mp4');
$duration = $info->duration();

// Extract thumbnail at 25% mark
$time = gmdate("H:i:s", $duration * 0.25);

FFmpeg::fromPath('video.mp4')
    ->seek($time)
    ->frames(1)
    ->save('thumbnail.jpg');
```

### Conditional Processing

```php
$info = FFmpeg::probe('input.mp4');

// Only re-encode if needed
if ($info->videoCodec() !== 'h264' || $info->audioCodec() !== 'aac') {
    FFmpeg::fromPath('input.mp4')
        ->videoCodec('libx264')
        ->audioCodec('aac')
        ->save('output.mp4');
} else {
    // Just copy
    copy('input.mp4', 'output.mp4');
}
```

## Error Handling

```php
try {
    $info = FFmpeg::probe('video.mp4');
    echo "Duration: " . $info->duration() . " seconds";
} catch (\Exception $e) {
    echo "Error probing file: " . $e->getMessage();
}
```

## MediaInfo Methods Reference

| Method | Returns | Description |
|--------|---------|-------------|
| `duration()` | float | Duration in seconds |
| `width()` | int | Video width in pixels |
| `height()` | int | Video height in pixels |
| `videoCodec()` | string | Video codec name |
| `audioCodec()` | string | Audio codec name |
| `frameRate()` | float | Frames per second |
| `videoBitrate()` | int | Video bitrate (bits/sec) |
| `audioBitrate()` | int | Audio bitrate (bits/sec) |
| `sampleRate()` | int | Audio sample rate (Hz) |
| `audioChannels()` | int | Number of audio channels |
| `format()` | string | Container format |
| `size()` | int | File size in bytes |

## Tips

- **Cache results**: Probing is fast but cache for frequently accessed files
- **Validate first**: Use probe before processing to catch issues early
- **Format times**: Use `gmdate("H:i:s", $duration)` for human-readable duration
- **File size**: Convert bytes to MB with `$info->size() / 1024 / 1024`
- **Check exists**: Ensure file exists before probing to avoid errors

## See Also

- [Basic Usage](basic-usage.md) - General package usage
- [Clipping](clipping.md) - Extract clips based on duration
- [API Reference](api-reference.md) - All available methods
