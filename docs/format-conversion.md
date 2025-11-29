# Format Conversion

Convert media files between different formats with ease using convenient helper methods.

## Audio Conversion

### MP3

Convert to MP3 format with customizable bitrate:

```php
use Ritechoice23\FluentFFmpeg\Facades\FFmpeg;

// Default 192k bitrate
FFmpeg::fromPath('audio.wav')
    ->toMp3()
    ->save('audio.mp3');

// High quality 320k
FFmpeg::fromPath('video.mp4')
    ->toMp3(320)
    ->save('audio-hq.mp3');

// Extract audio from video
FFmpeg::fromPath('video.mp4')
    ->toMp3()
    ->save('extracted-audio.mp3');
```

### WAV

Convert to WAV format (uncompressed):

```php
// CD quality (44100Hz)
FFmpeg::fromPath('audio.mp3')
    ->toWav()
    ->save('audio.wav');

// Studio quality (48000Hz)
FFmpeg::fromPath('audio.mp3')
    ->toWav(48000)
    ->save('audio-studio.wav');
```

### AAC

Convert to AAC audio format:

```php
// Default 192k bitrate
FFmpeg::fromPath('audio.mp3')
    ->toAac()
    ->save('audio.aac');

// Custom bitrate
FFmpeg::fromPath('audio.wav')
    ->toAac(256)
    ->save('audio-hq.aac');
```

### M4A

Convert to M4A (AAC in MP4 container):

```php
FFmpeg::fromPath('audio.mp3')
    ->toM4a()
    ->save('audio.m4a');

// High quality
FFmpeg::fromPath('audio.flac')
    ->toM4a(320)
    ->save('audio-hq.m4a');
```

### OGG

Convert to OGG Vorbis:

```php
// Quality 5 (0-10 scale, default)
FFmpeg::fromPath('audio.mp3')
    ->toOgg()
    ->save('audio.ogg');

// High quality (8)
FFmpeg::fromPath('audio.wav')
    ->toOgg(8)
    ->save('audio-hq.ogg');
```

## Video Conversion

### MP4

Convert to MP4 format (H.264 + AAC):

```php
// Basic conversion
FFmpeg::fromPath('video.avi')
    ->toMp4()
    ->save('video.mp4');

// With quality control
FFmpeg::fromPath('video.mov')
    ->toMp4(['quality' => 23])
    ->save('video-hq.mp4');

// Custom codecs
FFmpeg::fromPath('video.webm')
    ->toMp4([
        'video_codec' => 'libx265',  // HEVC/H.265
        'audio_codec' => 'aac'
    ])
    ->save('video-hevc.mp4');
```

### WebM

Convert to WebM format (VP9 + Opus):

```php
// Basic conversion
FFmpeg::fromPath('video.mp4')
    ->toWebm()
    ->save('video.webm');

// With quality
FFmpeg::fromPath('video.avi')
    ->toWebm(['quality' => 31])
    ->save('video.webm');

// VP8 instead of VP9
FFmpeg::fromPath('video.mp4')
    ->toWebm([
        'video_codec' => 'libvpx',
        'audio_codec' => 'libvorbis'
    ])
    ->save('video-vp8.webm');
```

### MOV

Convert to MOV/QuickTime format:

```php
// Basic conversion
FFmpeg::fromPath('video.mp4')
    ->toMov()
    ->save('video.mov');

// High quality
FFmpeg::fromPath('video.avi')
    ->toMov(['quality' => 18])
    ->save('video-hq.mov');
```

### AVI

Convert to AVI format:

```php
FFmpeg::fromPath('video.mp4')
    ->toAvi()
    ->save('video.avi');

// Custom codecs
FFmpeg::fromPath('video.webm')
    ->toAvi([
        'video_codec' => 'mjpeg',
        'audio_codec' => 'pcm_s16le'
    ])
    ->save('video.avi');
```

### MKV

Convert to MKV/Matroska format:

```php
FFmpeg::fromPath('video.mp4')
    ->toMkv()
    ->save('video.mkv');

// With quality
FFmpeg::fromPath('video.avi')
    ->toMkv(['quality' => 20])
    ->save('video-hq.mkv');
```

### FLV

Convert to FLV/Flash Video:

```php
FFmpeg::fromPath('video.mp4')
    ->toFlv()
    ->save('video.flv');
```

## Combined Examples

### Video to Audio Extraction

```php
// Extract audio from video as MP3
FFmpeg::fromPath('video.mp4')
    ->toMp3(320)
    ->save('audio.mp3');

// Extract as WAV
FFmpeg::fromPath('video.mkv')
    ->toWav()
    ->save('audio.wav');
```

### Multi-format Export

```php
$input = 'source-video.avi';

// Export to multiple formats
FFmpeg::fromPath($input)->toMp4()->save('output.mp4');
FFmpeg::fromPath($input)->toWebm()->save('output.webm');
FFmpeg::fromPath($input)->toMov()->save('output.mov');
```

### With Additional Processing

```php
// Convert + resize + watermark
FFmpeg::fromPath('video.avi')
    ->toMp4(['quality' => 23])
    ->resolution(1920, 1080)
    ->withWatermark('logo.png', 'top-right')
    ->save('video-processed.mp4');

// Convert audio + adjust bitrate
FFmpeg::fromPath('audio.flac')
    ->toMp3(320)
    ->audioSampleRate(48000)
    ->save('audio-hq.mp3');
```

## Format Comparison

### Audio Formats

| Format | Quality       | File Size | Use Case                          |
| ------ | ------------- | --------- | --------------------------------- |
| MP3    | Good          | Small     | Universal compatibility           |
| AAC    | Better        | Small     | Modern devices, streaming         |
| OGG    | Better        | Small     | Open source, web                  |
| WAV    | Lossless      | Large     | Editing, archival                 |
| M4A    | Better        | Small     | Apple devices                     |

### Video Formats

| Format | Compatibility | Quality       | Use Case                          |
| ------ | ------------- | ------------- | --------------------------------- |
| MP4    | Excellent     | Good          | Universal, web, mobile            |
| WebM   | Good          | Better        | Web, modern browsers              |
| MOV    | Good          | Excellent     | Apple ecosystem, editing          |
| AVI    | Excellent     | Varies        | Legacy support                    |
| MKV    | Good          | Excellent     | High quality, multiple tracks     |
| FLV    | Legacy        | Fair          | Old web content                   |

## Best Practices

### Quality Settings

For MP4/MOV/MKV/WebM formats, use the `quality` option:
- **18-23**: High quality (larger files)
- **23-28**: Balanced quality (recommended)
- **28-35**: Lower quality (smaller files)

```php
// High quality
->toMp4(['quality' => 20])

// Balanced
->toMp4(['quality' => 25])

// Smaller file
->toMp4(['quality' => 30])
```

### Audio Bitrate Guidelines

**MP3/AAC/M4A:**
- **128k**: Acceptable quality
- **192k**: Good quality (default)
- **256k**: Very good quality
- **320k**: Maximum MP3 quality

**OGG Vorbis Quality:**
- **0-3**: Low quality
- **4-6**: Good quality (5 is default)
- **7-10**: High quality

### Performance Tips

1. **Keep original codec when possible** - Use `copy` codec if no conversion needed
2. **Match source resolution** - Don't upscale unnecessarily
3. **Use appropriate quality** - Higher quality = longer processing time
4. **Queue large jobs** - Use Laravel queues for batch conversions

```php
// Fast copy (no re-encoding)
FFmpeg::fromPath('video.mp4')
    ->outputFormat('mp4')
    ->videoCodec('copy')
    ->audioCodec('copy')
    ->save('copy.mp4');
```

## Error Handling

```php
try {
    FFmpeg::fromPath('input.avi')
        ->toMp4()
        ->save('output.mp4');
        
    echo "Conversion successful!";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

## Codec Reference

Common codecs used by format converters:

**Video:**
- `libx264` - H.264 (widely compatible)
- `libx265` - H.265/HEVC (better compression)
- `libvpx-vp9` - VP9 for WebM
- `mpeg4` - MPEG-4 for AVI

**Audio:**
- `aac` - AAC (modern, efficient)
- `libmp3lame` - MP3 (universal)
- `libopus` - Opus (best for web)
- `libvorbis` - Vorbis for OGG
