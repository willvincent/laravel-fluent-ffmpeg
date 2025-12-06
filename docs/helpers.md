# Helper Methods

Convenient utility methods for common FFmpeg tasks.

## Extract Audio

Extract audio track from video:

```php
FFmpeg::fromPath('video.mp4')
    ->extractAudio()
    ->audioCodec('mp3')
    ->audioBitrate('320k')
    ->save('audio.mp3');
```

## Replace Audio

Replace video's audio track:

```php
FFmpeg::fromPath('video.mp4')
    ->addInput('new-audio.mp3')
    ->replaceAudio()
    ->save('video-new-audio.mp4');
```

## Waveform Visualization

### Generate Waveform Image

```php
FFmpeg::fromPath('audio.mp3')
    ->waveform()
    ->save('waveform.png');
```

### Customize Waveform

```php
FFmpeg::fromPath('audio.mp3')
    ->waveform([
        'width' => 1920,     // Width in pixels (default: 1920)
        'height' => 1080,    // Height in pixels (default: 1080)
        'color' => 'cyan'    // Color name or hex code (default: 'white')
    ])
    ->save('waveform.png');
```

### Color Options

```php
// Named colors: 'cyan', 'red', 'green', 'white', etc.
->waveform(['color' => 'cyan'])

// Hex codes
->waveform(['color' => '#ff6b6b'])  // Coral red
->waveform(['color' => '#4a90e2'])  // Blue
```

### Create Audio Video

**Simple audio + image:**

```php
FFmpeg::fromPath('audio.mp3')
    ->addInput('cover-art.jpg')
    ->videoCodec('libx264')
    ->audioCodec('copy')
    ->resolution(1920, 1080)
    ->save('audio-video.mp4');
```

**Audio + waveform:**

```php
// Generate waveform
FFmpeg::fromPath('audio.mp3')
    ->waveform(['width' => 1920, 'height' => 1080, 'color' => 'white'])
    ->save('wave.png');

// Create video
FFmpeg::fromPath('wave.png')
    ->addInput('audio.mp3')
    ->videoCodec('libx264')
    ->audioCodec('copy')
    ->save('waveform-video.mp4');
```

**Cover art + waveform overlay:**

```php
// Generate waveform
FFmpeg::fromPath('podcast.mp3')
    ->waveform(['width' => 800, 'height' => 200, 'color' => 'cyan'])
    ->save('wave.png');

// Overlay on cover art
FFmpeg::fromPath('cover.jpg')
    ->addInput('wave.png')
    ->addInput('podcast.mp3')
    ->overlay([
        'x' => '(W-w)/2',   // Center horizontally
        'y' => 'H-h-50',    // 50px from bottom
        'width' => 800,
        'height' => 200
    ])
    ->resolution(1920, 1080)
    ->save('podcast-video.mp4');
```

## Create GIF

```php
FFmpeg::fromPath('video.mp4')
    ->clip('00:00:05', '00:00:10')
    ->toGif(['fps' => 15, 'width' => 480])
    ->save('animation.gif');
```

## Video from Images

```php
FFmpeg::fromImages('images/%03d.png', ['framerate' => 24])
    ->duration(10)
    ->save('slideshow.mp4');
```

Image patterns:

-   `images/%03d.png` - images/001.png, images/002.png, etc.
-   `frame_%04d.jpg` - frame_0001.jpg, frame_0002.jpg, etc.

## Presets

```php
// Built-in preset
FFmpeg::fromPath('video.mp4')
    ->preset('1080p')
    ->save('output.mp4');

// Custom preset
FFmpeg::fromPath('video.mp4')
    ->preset([
        'resolution' => [1920, 1080],
        'video_bitrate' => '5000k',
        'audio_bitrate' => '192k'
    ])
    ->save('output.mp4');
```

## HLS Streaming

```php
FFmpeg::fromPath('video.mp4')
    ->preset('1080p')
    ->hls([
        'segment_time' => 10,
        'playlist_type' => 'vod'
    ])
    ->save('stream.m3u8');
```

## DASH Streaming

```php
FFmpeg::fromPath('video.mp4')
    ->dash(['segment_duration' => 10])
    ->save('manifest.mpd');
```

## Debugging

### Get Command Without Executing

Use `getCommand()` or `dryRun()` to see the generated FFmpeg command without executing it:

```php
$command = FFmpeg::fromPath('video.mp4')
    ->videoCodec('libx264')
    ->resolution(1920, 1080)
    ->audioBitrate('192k')
    ->getCommand();

echo $command;
// Output: ffmpeg -i "video.mp4" -c:v libx264 -s 1920x1080 -b:a 192k -y "output.mp4"
```

### Dump and Die (dd)

Use `ddCommand()` to dump the command and stop execution (useful during development):

```php
FFmpeg::fromPath('video.mp4')
    ->videoCodec('libx264')
    ->resolution(1920, 1080)
    ->clips([
        ['start' => '00:00:10', 'end' => '00:00:20'],
        ['start' => '00:01:00', 'end' => '00:01:30'],
    ])
    ->withWatermark('logo.png', 'bottom-right')
    ->ddCommand(); // Dies and dumps the command with Laravel dd()
```

**Use cases:**

-   Verify complex filter chains are correct
-   Debug command generation issues
-   Share exact commands with team members
-   Test command structure before execution

---

Perfect for quick media transformations and creative workflows!
