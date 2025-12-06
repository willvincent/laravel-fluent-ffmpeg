# Cross-Platform Compatibility

## Overview

The package includes built-in optimizations to ensure videos play on all devices and platforms. These optimizations handle codec profiles, pixel formats, and container settings for maximum compatibility.

## Quick Start

### Universal Compatibility (Recommended)

```php
FFmpeg::fromPath('video.mp4')
    ->universalCompatibility()
    ->save('output.mp4');
```

This ensures the video plays on:

-   All web browsers (Chrome, Firefox, Safari, Edge)
-   iOS devices (iPhone, iPad)
-   Android devices
-   Smart TVs
-   Desktop players (VLC, QuickTime, Windows Media Player)

## Compatibility Methods

### Web Optimized

```php
FFmpeg::fromPath('video.mp4')
    ->webOptimized()
    ->save('output.mp4');
```

**Settings:**

-   H.264 Baseline Profile (Level 3.0)
-   YUV420P pixel format
-   AAC audio (stereo)
-   Fast start enabled (progressive download)

**Best for:** HTML5 video players, web streaming

### Mobile Optimized

```php
FFmpeg::fromPath('video.mp4')
    ->mobileOptimized()
    ->save('output.mp4');
```

**Settings:**

-   H.264 Main Profile (Level 3.1)
-   Optimized for mobile bandwidth
-   AAC-LC audio
-   Fast start enabled

**Best for:** Mobile apps, responsive websites

### iOS Optimized

```php
FFmpeg::fromPath('video.mp4')
    ->iosOptimized()
    ->save('output.mp4');
```

**Settings:**

-   H.264 High Profile (Level 4.0)
-   QuickTime-compatible
-   AAC audio

**Best for:** iPhone, iPad, Safari

### Android Optimized

```php
FFmpeg::fromPath('video.mp4')
    ->androidOptimized()
    ->save('output.mp4');
```

**Settings:**

-   H.264 Main Profile (Level 3.1)
-   Wide device compatibility
-   AAC audio

**Best for:** Android devices, Chrome

## Manual Settings

### H.264 Profile and Level

```php
FFmpeg::fromPath('video.mp4')
    ->h264Profile('high', '4.1')
    ->save('output.mp4');
```

**Profiles:**

-   `baseline` - Maximum compatibility (older devices)
-   `main` - Good balance (most devices)
-   `high` - Best quality (modern devices)

**Levels:**

-   `3.0` - SD quality, universal
-   `3.1` - Mobile devices
-   `4.0` - HD quality
-   `4.1` - Full HD quality

### Fast Start

```php
FFmpeg::fromPath('video.mp4')
    ->fastStart()
    ->save('output.mp4');
```

Enables progressive download for web streaming. The video starts playing before fully downloaded.

## Compatibility Matrix

| Method                     | Web | iOS     | Android | Smart TV | Desktop |
| -------------------------- | --- | ------- | ------- | -------- | ------- |
| `universalCompatibility()` | Yes | Yes     | Yes     | Yes      | Yes     |
| `webOptimized()`           | Yes | Yes     | Yes     | Limited  | Yes     |
| `mobileOptimized()`        | Yes | Yes     | Yes     | Limited  | Yes     |
| `iosOptimized()`           | Yes | Yes     | Limited | Limited  | Yes     |
| `androidOptimized()`       | Yes | Limited | Yes     | Limited  | Yes     |

Yes = Fully compatible | Limited = May have limitations

## Best Practices

### For Web Applications

```php
FFmpeg::fromPath('video.mp4')
    ->webOptimized()
    ->resolution(1920, 1080)
    ->videoBitrate('5000k')
    ->save('output.mp4');
```

### For Mobile Apps

```php
// Generate multiple qualities
foreach (['1080p', '720p', '480p'] as $quality) {
    FFmpeg::fromPath('video.mp4')
        ->mobileOptimized()
        ->preset($quality)
        ->save("output_{$quality}.mp4");
}
```

### For Maximum Reach

```php
FFmpeg::fromPath('video.mp4')
    ->universalCompatibility()
    ->resolution(1280, 720)  // Safe resolution
    ->videoBitrate('2500k')  // Moderate bitrate
    ->save('output.mp4');
```

## Technical Details

### Why YUV420P?

The `yuv420p` pixel format is used because:

-   Supported by all H.264 decoders
-   Required for QuickTime/iOS compatibility
-   Smaller file sizes than yuv444p
-   Standard for web video

### Why Baseline Profile?

Baseline profile ensures:

-   Works on older devices
-   No B-frames (simpler decoding)
-   Lower CPU requirements
-   Maximum compatibility

### Why Fast Start?

Fast start (`movflags +faststart`) moves metadata to the beginning of the file, enabling:

-   Progressive download
-   Faster playback start
-   Better user experience
-   Required for most web players

## GOP (Group of Pictures) and Keyframe Settings

### What are GOP and Keyframes?

**GOP (Group of Pictures)** is a sequence of video frames that starts with a keyframe (I-frame) and includes predictive frames (P-frames and B-frames).

**Keyframes** are full frames that don't depend on other frames for decoding. They are essential for:

-   **Seeking** - Jumping to specific points in the video
-   **Video editing** - Cutting and splicing requires keyframes
-   **Index tables** - Proper file indexing for editing software
-   **Streaming** - Adaptive bitrate switching

### Why GOP Settings Matter

Without proper GOP settings, you may encounter:

-   ❌ Corrupt index table errors in editing software
-   ❌ "No keyframe or index table" warnings
-   ❌ Unable to seek/scrub through video
-   ❌ Video won't import into editing software (Premiere, Final Cut, etc.)
-   ❌ Irregular keyframes causing quality issues

### Automatic GOP Settings

**All video encoding operations now include GOP settings by default:**

```php
// All compatibility presets include GOP settings
FFmpeg::fromPath('input.mp4')
    ->universalCompatibility()  // Includes GOP: 60 frames
    ->save('output.mp4');
```

Default GOP settings:

-   **GOP Size (`-g`)**: 60 frames (2 seconds at 30fps)
-   **Minimum Keyframe Interval (`-keyint_min`)**: 60 frames
-   **Scene Change Threshold (`-sc_threshold`)**: 0 (disabled)

### Manual GOP Control

For advanced use cases, you can customize GOP settings:

```php
// Set custom GOP size (e.g., 1 second at 30fps)
FFmpeg::fromPath('input.mp4')
    ->gopSize(30)
    ->keyframeInterval(30)
    ->save('output.mp4');

// Disable scene change detection
FFmpeg::fromPath('input.mp4')
    ->sceneChangeThreshold(0)
    ->save('output.mp4');

// Complete reliable keyframe setup
FFmpeg::fromPath('input.mp4')
    ->reliableKeyframes(60, true)  // 60 frames, disable scene detection
    ->save('output.mp4');
```

### GOP Settings for Different Use Cases

**For Video Editing (Premiere, Final Cut, DaVinci Resolve):**

```php
FFmpeg::fromPath('input.mp4')
    ->gopSize(30)              // 1 second at 30fps
    ->keyframeInterval(30)
    ->sceneChangeThreshold(0)  // Prevent irregular keyframes
    ->save('output.mp4');
```

**For Web Streaming:**

```php
FFmpeg::fromPath('input.mp4')
    ->gopSize(60)              // 2 seconds at 30fps
    ->keyframeInterval(60)
    ->sceneChangeThreshold(0)
    ->save('output.mp4');
```

**For Low-Latency Streaming:**

```php
FFmpeg::fromPath('input.mp4')
    ->gopSize(15)              // 0.5 seconds at 30fps
    ->keyframeInterval(15)
    ->sceneChangeThreshold(0)
    ->save('output.mp4');
```

### Disabling GOP Settings (Advanced)

In rare cases where you need to disable automatic GOP settings:

```php
// For format conversions
FFmpeg::fromPath('input.mp4')
    ->toMp4(['skip_gop_settings' => true])
    ->save('output.mp4');

// Or manually override
FFmpeg::fromPath('input.mp4')
    ->videoCodec('libx264')
    // Don't call GOP methods
    ->save('output.mp4');
```

## Troubleshooting

### Video doesn't play on iPhone

```php
// Ensure YUV420P and fast start
FFmpeg::fromPath('video.mp4')
    ->pixelFormat('yuv420p')
    ->fastStart()
    ->save('output.mp4');
```

### Video doesn't play in browser

```php
// Use web-optimized settings
FFmpeg::fromPath('video.mp4')
    ->webOptimized()
    ->save('output.mp4');
```

### File size too large

```php
// Use lower profile and bitrate
FFmpeg::fromPath('video.mp4')
    ->h264Profile('baseline', '3.0')
    ->videoBitrate('1500k')
    ->save('output.mp4');
```
