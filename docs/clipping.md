# Clipping

Extract clips from videos - single or multiple at once. Combine with [video composition](video-composition.md) to add intro/outro/watermarks.

## Single Clip

```php
use Ritechoice23\FluentFFmpeg\Facades\FFmpeg;

// Extract a single clip
FFmpeg::fromPath('video.mp4')
    ->clip('00:00:10', '00:00:20')  // Start and end times
    ->save('clip.mp4');
```

## Multiple Clips (Batch)

```php
// Extract multiple clips - automatically numbered!
FFmpeg::fromPath('video.mp4')
    ->clips([
        ['start' => '00:00:10', 'end' => '00:00:20'],
        ['start' => '00:00:30', 'end' => '00:00:50'],
        ['start' => '00:01:00', 'end' => '00:01:15'],
    ])
    ->save('clip.mp4');
// Auto-creates: clip_1.mp4, clip_2.mp4, clip_3.mp4
```

### How Numbering Works

`save()` automatically detects batch clips and inserts numbers before the file extension:

```php
->save('clip.mp4')              // → clip_1.mp4, clip_2.mp4, ...
->save('highlight.mp4')         // → highlight_1.mp4, highlight_2.mp4, ...
->save('path/to/video.mp4')     // → path/to/video_1.mp4, path/to/video_2.mp4, ...
```

## With Video Composition

Combine clipping with intro/outro/watermark from [video composition](video-composition.md):

```php
// Single clip with watermark
FFmpeg::fromPath('video.mp4')
    ->clip('00:00:10', '00:00:30')
    ->withWatermark('logo.png', 'top-right')
    ->save('branded-clip.mp4');
```

```php
// Multiple clips with intro/outro/watermark
FFmpeg::fromPath('video.mp4')
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

> See [Video Composition](video-composition.md) for details on `withIntro()`, `withOutro()`, and `withWatermark()`.

## Time Format

Use FFmpeg time format for start/end times:

```php
// Seconds
'10'           // 10 seconds
'90'           // 1 minute 30 seconds

// HH:MM:SS
'00:00:10'     // 10 seconds
'00:01:30'     // 1 minute 30 seconds
'01:05:30'     // 1 hour 5 minutes 30 seconds

// HH:MM:SS.ms
'00:00:10.500' // 10.5 seconds
'00:01:30.250' // 1 minute 30.25 seconds
```

## Complete Examples

### Social Media Highlights

```php
// Extract Instagram-ready clips
FFmpeg::fromPath(storage_path('videos/webinar.mp4'))
    ->clips([
        ['start' => '00:05:10', 'end' => '00:05:40'],  // 30s clip
        ['start' => '00:12:20', 'end' => '00:12:50'],  // 30s clip
        ['start' => '00:18:00', 'end' => '00:18:30'],  // 30s clip
    ])
    ->withIntro(public_path('brand-intro.mp4'))
    ->withOutro(public_path('cta-outro.mp4'))
    ->withWatermark(public_path('logo.png'), 'top-right')
    ->save(storage_path('social/instagram.mp4'));

// Creates:
// storage/social/instagram_1.mp4
// storage/social/instagram_2.mp4
// storage/social/instagram_3.mp4
```

### Tutorial Segments

```php
// Split tutorial into chapters
FFmpeg::fromPath('full-tutorial.mp4')
    ->clips([
        ['start' => '00:00:00', 'end' => '00:05:30'],  // Chapter 1
        ['start' => '00:05:30', 'end' => '00:15:00'],  // Chapter 2
        ['start' => '00:15:00', 'end' => '00:20:00'],  // Chapter 3
    ])
    ->withIntro('chapter-intro.mp4')
    ->withWatermark('course-logo.png')
    ->save('chapter.mp4');
```

### Product Feature Highlights

```php
// Create feature highlight reels
FFmpeg::fromPath('product-demo.mp4')
    ->clips([
        ['start' => '00:02:10', 'end' => '00:02:45'],  // Feature 1
        ['start' => '00:05:30', 'end' => '00:06:00'],  // Feature 2
        ['start' => '00:08:15', 'end' => '00:08:45'],  // Feature 3
    ])
    ->withWatermark('brand.png', 'bottom-right')
    ->save('feature-demo.mp4');
```

## Works With Other Methods

```php
// Add resolution, codec, etc.
FFmpeg::fromPath('video.mp4')
    ->clips([...])
    ->resolution(1280, 720)      // Applied to clips
    ->videoCodec('libx264')      // Applied to clips
    ->withWatermark('logo.png')
    ->save('clip.mp4');
```

```php
// Extract and convert format
FFmpeg::fromPath('video.mkv')
    ->clip('00:05:00', '00:06:00')
    ->videoCodec('libx264')
    ->audioCodec('aac')
    ->save('clip.mp4');
```

## Return Values

```php
// Single clip: returns bool
$success = FFmpeg::fromPath('video.mp4')
    ->clip('00:00:10', '00:00:20')
    ->save('output.mp4');  // true or false

// Multiple clips: returns array of file paths
$files = FFmpeg::fromPath('video.mp4')
    ->clips([...])
    ->save('clip.mp4');  // ['clip_1.mp4', 'clip_2.mp4', ...]
```

## Performance Notes

- **Fast**: Clips without composition use copy codec (no re-encoding)
- **Sequential**: Clips are processed one at a time (not parallel)
- **With composition**: Slower due to concatenation/overlay (see [video composition](video-composition.md))

```php
// Fastest (copy codec, no re-encoding)
->clip('00:00:10', '00:00:20')->save('clip.mp4');
->clips([...])->save('clip.mp4');

// Slower (with intro/outro/watermark)
->clips([...])->withIntro('intro.mp4')->save('clip.mp4');
```

## Error Handling

```php
try {
    $clips = FFmpeg::fromPath('video.mp4')
        ->clips([
            ['start' => '00:00:10', 'end' => '00:00:20'],
            ['start' => '00:00:30', 'end' => '00:00:50'],
        ])
        ->withWatermark('logo.png')
        ->save('clip.mp4');
    
    foreach ($clips as $file) {
        echo "Created: {$file}\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

## Best Practices

1. **Test clips first**: Preview times with a single clip before batch processing
   ```php
   ->clip('00:05:10', '00:05:40')->save('test.mp4')
   ```

2. **Check video duration**: Ensure end times don't exceed video length
   ```php
   $info = FFmpeg::probe('video.mp4');
   $duration = $info->duration(); // in seconds
   ```

3. **Use descriptive names**: Make output files easy to identify
   ```php
   ->save('webinar-highlight.mp4')  // Good
   ->save('clip.mp4')               // Less descriptive
   ```

4. **Queue long jobs**: For many clips, use Laravel queues
   ```php
   FFmpeg::fromPath('video.mp4')
       ->clips([...])
       ->queue('output.mp4');
   ```

5. **Verify output**: Always check the first few clips before processing hundreds

## Tips

- **Preview**: Use `->clip()` to test exact timing before batch processing
- **Naming**: Use descriptive patterns like `highlight.mp4` → `highlight_1.mp4`
- **Disk space**: Ensure enough space - clips take up storage
- **Time format**: Use `HH:MM:SS` format for clarity
- **Composition**: See [video composition guide](video-composition.md) for intro/outro/watermark details

## See Also

- [Video Composition](video-composition.md) - Add intro/outro/watermarks
- [Time Options](docs/time-options.md) - All time-based methods
- [Queue Processing](queue.md) - Process videos in background

Perfect for creating highlight reels, social media clips, and tutorial segments!
