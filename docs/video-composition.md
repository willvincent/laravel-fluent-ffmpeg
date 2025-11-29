# Video Composition

Add intro videos, outro videos, and watermarks to any video processing. These features work globally across the entire package - single videos, clips, HLS streams, etc.

## Overview

Video composition allows you to:
- **Add intro** - Prepend a video before your content
- **Add outro** - Append a video after your content  
- **Add watermark** - Overlay an image/logo on your video

These work with **any** FFmpeg operation: single videos, batch clips, HLS exports, format conversions, etc.

## Basic Usage

### Add Watermark

```php
use Ritechoice23\FluentFFmpeg\Facades\FFmpeg;

FFmpeg::fromPath('video.mp4')
    ->withWatermark('logo.png', 'top-right')
    ->save('branded.mp4');
```

**Watermark Positions:**
- `top-left` - Top left corner (10px padding)
- `top-right` - Top right corner (default)
- `bottom-left` - Bottom left corner
- `bottom-right` - Bottom right corner
- `center` - Center of video

### Add Intro

```php
FFmpeg::fromPath('video.mp4')
    ->withIntro('intro.mp4')
    ->save('with-intro.mp4');
```

### Add Outro

```php
FFmpeg::fromPath('video.mp4')
    ->withOutro('outro.mp4')
    ->save('with-outro.mp4');
```

### Combine All

```php
FFmpeg::fromPath('video.mp4')
    ->withIntro('brand-intro.mp4')
    ->withOutro('cta-outro.mp4')
    ->withWatermark('logo.png', 'bottom-right')
    ->save('complete.mp4');
```

## Works Everywhere

### With Format Conversion

```php
FFmpeg::fromPath('video.avi')
    ->withIntro('intro.mp4')
    ->withWatermark('logo.png')
    ->videoCodec('libx264')
    ->save('output.mp4');
```

### With HLS Streaming

```php
FFmpeg::fromPath('video.mp4')
    ->withIntro('intro.mp4')
    ->withWatermark('logo.png', 'top-right')
    ->exportForHLS()
    ->addFormat('1080p')
    ->addFormat('720p')
    ->save('stream.m3u8');
```

### With Clips

```php
FFmpeg::fromPath('long-video.mp4')
    ->clips([
        ['start' => '00:00:10', 'end' => '00:00:30'],
        ['start' => '00:01:00', 'end' => '00:01:20'],
    ])
    ->withIntro('intro.mp4')              // Added to EACH clip
    ->withOutro('outro.mp4')              // Added to EACH clip
    ->withWatermark('logo.png')           // Added to EACH clip
    ->save('clip.mp4');
// Creates: clip_1.mp4, clip_2.mp4 (each with intro/outro/watermark)
```

### With Resolution/Quality Changes

```php
FFmpeg::fromPath('4k-video.mp4')
    ->resolution(1920, 1080)
    ->withWatermark('logo.png')
    ->videoBitrate('5000k')
    ->save('1080p-branded.mp4');
```

### With Filters

```php
FFmpeg::fromPath('video.mp4')
    ->addFilter('fade', ['in', 0, 30])
    ->withIntro('intro.mp4')
    ->withWatermark('logo.png')
    ->save('output.mp4');
```

## Watermark Customization

### Position Examples

```php
// Logo in top-left
->withWatermark('logo.png', 'top-left')

// Brand in top-right
->withWatermark('brand.png', 'top-right')

// Copyright in bottom-right
->withWatermark('copyright.png', 'bottom-right')

// Centered badge
->withWatermark('badge.png', 'center')
```

### Best Watermark Formats

- **PNG with transparency** - Recommended (supports alpha channel)
- **PNG with solid background** - Works well
- **JPG** - Works but no transparency

```php
// Transparent logo (best)
->withWatermark('logo-transparent.png', 'top-right')
```

## Intro/Outro Best Practices

### Matching Specs

For best results, intro/outro videos should match your source video:

```php
// Good: Same resolution and framerate
// Source: 1920x1080 @ 30fps
// Intro:  1920x1080 @ 30fps
// Outro:  1920x1080 @ 30fps

FFmpeg::fromPath('video-1080p-30fps.mp4')
    ->withIntro('intro-1080p-30fps.mp4')
    ->withOutro('outro-1080p-30fps.mp4')
    ->save('complete.mp4');
```

### Different Specs (Auto-scaled)

If specs don't match, FFmpeg will handle it, but may require re-encoding:

```php
// Source: 1920x1080 @ 30fps
// Intro:  1280x720 @ 24fps (will be scaled/adjusted)

FFmpeg::fromPath('video.mp4')
    ->withIntro('intro-720p.mp4')  // Auto-scaled to 1080p
    ->save('output.mp4');
```

## Common Use Cases

### Branded Content

```php
// Add brand intro and watermark to all videos
FFmpeg::fromPath($video)
    ->withIntro(public_path('brand/intro.mp4'))
    ->withWatermark(public_path('brand/logo.png'), 'top-right')
    ->save($output);
```

### Social Media Posts

```php
// Instagram-ready with intro, outro, and watermark
FFmpeg::fromPath('content.mp4')
    ->withIntro('instagram-intro.mp4')
    ->withOutro('follow-cta.mp4')
    ->withWatermark('handle.png', 'bottom-left')
    ->resolution(1080, 1080)  // Square for Instagram
    ->save('instagram-post.mp4');
```

### Tutorial Videos

```php
// Educational content with consistent branding
FFmpeg::fromPath('lesson.mp4')
    ->withIntro('course-intro.mp4')
    ->withOutro('next-lesson-cta.mp4')
    ->withWatermark('school-logo.png', 'bottom-right')
    ->save('tutorial.mp4');
```

### Product Demos

```php
// Demo with company branding
FFmpeg::fromPath('product-demo.mp4')
    ->withIntro('company-intro.mp4')
    ->withOutro('contact-cta.mp4')
    ->withWatermark('company-logo.png', 'top-right')
    ->save('branded-demo.mp4');
```

## How It Works

### Intro/Outro Process

1. **Temporary file** - Main video is processed first
2. **Concat demuxer** - FFmpeg concatenates intro + video + outro
3. **Copy codec** - Fast concatenation (no re-encoding when specs match)
4. **Final output** - Complete video with intro/outro

### Watermark Process

1. **Overlay filter** - FFmpeg overlays watermark image
2. **Re-encoding** - Required for overlay (uses libx264)
3. **Position calculation** - Dynamic positioning based on video dimensions
4. **Final output** - Video with watermark

### Combined Process

When using intro/outro AND watermark:

1. **Extract/process** main video
2. **Add intro/outro** via concatenation
3. **Add watermark** via overlay filter
4. **Output** final composed video

## Performance Considerations

### Speed Comparison

```php
// Fastest: No composition
FFmpeg::fromPath('video.mp4')->save('output.mp4');

// Fast: Intro/outro only (copy codec if specs match)
FFmpeg::fromPath('video.mp4')
    ->withIntro('intro.mp4')
    ->withOutro('outro.mp4')
    ->save('output.mp4');

// Slower: Watermark (requires re-encoding)
FFmpeg::fromPath('video.mp4')
    ->withWatermark('logo.png')
    ->save('output.mp4');

// Slowest: All three (concatenation + overlay re-encoding)
FFmpeg::fromPath('video.mp4')
    ->withIntro('intro.mp4')
    ->withOutro('outro.mp4')
    ->withWatermark('logo.png')
    ->save('output.mp4');
```

### Optimization Tips

1. **Match specs** - Use intro/outro with same resolution/framerate for fast copy concat
2. **Prepare assets** - Pre-process intro/outro/watermark to optimal formats
3. **Queue long jobs** - Use Laravel queues for batch operations
4. **Test first** - Try one video before batch processing

## File Requirements

### Intro/Outro Videos

- **Format**: Any FFmpeg-supported format (MP4, AVI, MOV, etc.)
- **Recommended**: MP4 with H.264 codec
- **Resolution**: Ideally matches source video
- **Framerate**: Ideally matches source video

### Watermark Images

- **Format**: PNG (preferred), JPG, or any image format
- **Transparency**: PNG with alpha channel recommended
- **Size**: Should be appropriately sized for video resolution
- **Example**: 200x50px logo for 1080p video

## Error Handling

```php
try {
    FFmpeg::fromPath('video.mp4')
        ->withIntro('intro.mp4')
        ->withWatermark('logo.png')
        ->save('output.mp4');
        
    echo "Video composed successfully!";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

## Tips

1. **Consistent branding** - Reuse same intro/outro/watermark across all content
2. **Version control** - Keep intro/outro/watermark files in version control
3. **Test positions** - Try different watermark positions to avoid covering important content
4. **Compression** - Use optimized/compressed watermark images
5. **Aspect ratios** - Ensure intro/outro match your video's aspect ratio

Perfect for creating professional, branded video content at scale!

## Advanced Overlay API

The `overlay()` method gives you precise control over compositing videos and images. Use it for Picture-in-Picture effects, custom watermarks, and multi-layer compositions.

### Basic Overlay

```php
// Overlay a logo (second input) on main video
FFmpeg::fromPath('video.mp4')
    ->addInput('logo.png')
    ->overlay(['x' => 20, 'y' => 20])
    ->save('output.mp4');
```

### Picture-in-Picture

```php
// Add小 webcam feed in corner of screen share
FFmpeg::fromPath('screenshare.mp4')
    ->addInput('webcam.mp4')
    ->overlay([
        'x' => 'W-w-20',      // 20px from right
        'y' => 'H-h-20',      // 20px from bottom
        'width' => 320,
        'height' => 240
    ])
    ->save('presentation.mp4');
```

### Position Options

- **`x`**: Horizontal position (pixels or FFmpeg expression like `W-w-10` for right edge)
- **`y`**: Vertical position (pixels or FFmpeg expression like `H-h-10` for bottom edge) 
- **`width`**: Scale overlay to this width (optional)
- **`height`**: Scale overlay to this height (optional)

### Advanced Positioning

```php
// Center overlay
->overlay(['x' => '(W-w)/2', 'y' => '(H-h)/2'])

// Top-left with padding
->overlay(['x' => 10, 'y' => 10])

// Bottom-right corner
->overlay(['x' => 'W-w-10', 'y' => 'H-h-10'])
```

### Multiple Overlays

```php
// Overlay multiple elements (requires complex filters)
FFmpeg::fromPath('video.mp4')
    ->addInput('watermark.png')
    ->addInput('logo.png')
    ->overlay(['x' => 10, 'y' => 10])  // First overlay
    ->save('output.mp4');
```

**Note:** `withWatermark()` is a convenience method that uses `overlay()` internally with predefined positions.

## Video Concatenation API

The `concat()` method allows you to join multiple videos into a single output file.

### Basic Concatenation

```php
FFmpeg::fromPath('part1.mp4')
    ->concat(['part2.mp4', 'part3.mp4'])
    ->save('complete.mp4');
```

### Multiple Inputs

```php
// Combine intro, main content, and outro
FFmpeg::fromPath('intro.mp4')
    ->concat([
        'main-content.mp4',
        'outro.mp4'
    ])
    ->save('final-video.mp4');
```

### With Processing

```php
// Concatenate then apply watermark
FFmpeg::fromPath('clip1.mp4')
    ->concat(['clip2.mp4', 'clip3.mp4'])
    ->withWatermark('logo.png', 'top-right')
    ->save('compilation.mp4');
```

### Requirements

- All videos should have the same resolution, codec, and framerate for best results
- If specs differ, FFmpeg will handle conversion but may require re-encoding

**Note:** For intro/outro workflows, use `withIntro()` and `withOutro()` which provide a simpler API.
