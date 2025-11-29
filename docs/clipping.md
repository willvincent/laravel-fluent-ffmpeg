# Clipping

Extract single or multiple clips from videos. Works seamlessly with [video composition](video-composition.md) for adding intro/outro/watermarks.

## API Methods

### `clip(string $start, string $end)`

Extract a single clip from start time to end time.

```php
use Ritechoice23\FluentFFmpeg\Facades\FFmpeg;

FFmpeg::fromPath('video.mp4')
    ->clip('00:00:10', '00:00:20')
    ->save('clip.mp4');
```

### `clips(array $clips)`

Define multiple clips for batch extraction. Each clip is an array with `start` and `end` keys.

```php
FFmpeg::fromPath('video.mp4')
    ->clips([
        ['start' => '00:00:10', 'end' => '00:00:20'],
        ['start' => '00:00:30', 'end' => '00:00:50'],
        ['start' => '00:01:00', 'end' => '00:01:15'],
    ])
    ->save('clip.mp4');
// Creates: clip_1.mp4, clip_2.mp4, clip_3.mp4
```

### `batchClips(array $clips, string $outputPattern)`

Extract multiple clips with custom output naming pattern using `{n}` for the clip number.

```php
FFmpeg::fromPath('video.mp4')
    ->batchClips([
        ['start' => '00:00:10', 'end' => '00:00:20'],
        ['start' => '00:00:30', 'end' => '00:00:50'],
    ], 'highlight_{n}.mp4');
// Creates: highlight_1.mp4, highlight_2.mp4
```

## Time Format

All time values use FFmpeg time format:

```php
'10'                // 10 seconds
'90'                // 90 seconds (1:30)
'00:00:10'          // 10 seconds  
'00:01:30'          // 1 minute 30 seconds
'01:05:30'          // 1 hour 5 minutes 30 seconds
'00:00:10.500'      // 10.5 seconds (with milliseconds)
```

## Usage Examples

### Single Clip

```php
// Extract 10-second clip
FFmpeg::fromPath('video.mp4')
    ->clip('00:05:00', '00:05:10')
    ->save('snippet.mp4');
```

### Multiple Clips

```php
// Extract 3 clips with auto-numbering
FFmpeg::fromPath('webinar.mp4')
    ->clips([
        ['start' => '00:05:10', 'end' => '00:05:40'],
        ['start' => '00:12:20', 'end' => '00:12:50'],
        ['start' => '00:18:00', 'end' => '00:18:30'],
    ])
    ->save('highlight.mp4');
// Output: highlight_1.mp4, highlight_2.mp4, highlight_3.mp4
```

### Custom Output Pattern

```php
// Custom naming with batchClips()
FFmpeg::fromPath('tutorial.mp4')
    ->batchClips([
        ['start' => '00:00:00', 'end' => '00:05:30'],
        ['start' => '00:05:30', 'end' => '00:15:00'],
        ['start' => '00:15:00', 'end' => '00:20:00'],
    ], 'chapter_{n}.mp4');
// Output: chapter_1.mp4, chapter_2.mp4, chapter_3.mp4
```

## With Video Composition

Apply intro, outro, or watermark to clips. See [Video Composition](video-composition.md) for details.

### Single Clip

```php
FFmpeg::fromPath('video.mp4')
    ->clip('00:00:10', '00:00:30')
    ->withWatermark('logo.png', 'top-right')
    ->save('branded-clip.mp4');
```

### Multiple Clips

Each clip gets the same composition elements (intro/outro/watermark):

```php
FFmpeg::fromPath('video.mp4')
    ->clips([
        ['start' => '00:00:10', 'end' => '00:00:30'],
        ['start' => '00:01:00', 'end' => '00:01:20'],
    ])
    ->withIntro('intro.mp4')
    ->withOutro('outro.mp4')
    ->withWatermark('logo.png', 'bottom-right')
    ->save('branded.mp4');
// Output: branded_1.mp4, branded_2.mp4
// (each with intro, outro, and watermark)
```

## With Other Methods

Combine clipping with other FFmpeg operations:

```php
// Clip + resize + codec
FFmpeg::fromPath('video.mp4')
    ->clip('00:05:00', '00:06:00')
    ->resolution(1280, 720)
    ->videoCodec('libx264')
    ->audioCodec('aac')
    ->save('clip-hd.mp4');
```

```php
// Multiple clips + processing
FFmpeg::fromPath('video.mkv')
    ->clips([
        ['start' => '00:00:10', 'end' => '00:00:30'],
        ['start' => '00:01:00', 'end' => '00:01:20'],
    ])
    ->videoCodec('libx264')
    ->audioBitrate('192k')
    ->save('clip.mp4');
```

## Return Values

**Single clip** - Returns boolean:
```php
$success = FFmpeg::fromPath('video.mp4')
    ->clip('00:00:10', '00:00:20')
    ->save('output.mp4');  // true or false
```

**Multiple clips** - Returns array of file paths:
```php
$files = FFmpeg::fromPath('video.mp4')
    ->clips([...])
    ->save('clip.mp4');
// Returns: ['clip_1.mp4', 'clip_2.mp4', 'clip_3.mp4']
```

## Auto-Numbering

When using `clips()`, the `save()` method automatically inserts numbers before the file extension:

```php
->save('clip.mp4')              // → clip_1.mp4, clip_2.mp4, clip_3.mp4
->save('highlight.mp4')         // → highlight_1.mp4, highlight_2.mp4, ...
->save('path/to/video.mp4')     // → path/to/video_1.mp4, path/to/video_2.mp4, ...
```

For custom numbering patterns, use `batchClips()` with `{n}` placeholder.

## Real-World Examples

### Social Media Highlights

```php
FFmpeg::fromPath('webinar.mp4')
    ->clips([
        ['start' => '00:05:10', 'end' => '00:05:40'],  // 30s clip
        ['start' => '00:12:20', 'end' => '00:12:50'],  // 30s clip
        ['start' => '00:18:00', 'end' => '00:18:30'],  // 30s clip
    ])
    ->withIntro('brand-intro.mp4')
    ->withOutro('cta-outro.mp4')
    ->withWatermark('logo.png', 'top-right')
    ->save('instagram.mp4');
```

### Tutorial Chapters

```php
FFmpeg::fromPath('full-tutorial.mp4')
    ->batchClips([
        ['start' => '00:00:00', 'end' => '00:05:30'],  // Chapter 1
        ['start' => '00:05:30', 'end' => '00:15:00'],  // Chapter 2
        ['start' => '00:15:00', 'end' => '00:20:00'],  // Chapter 3
    ], 'chapter_{n}.mp4')
    ->withWatermark('course-logo.png')
    ->save();  // Not needed when using batchClips()
```

### Product Features

```php
FFmpeg::fromPath('product-demo.mp4')
    ->clips([
        ['start' => '00:02:10', 'end' => '00:02:45'],
        ['start' => '00:05:30', 'end' => '00:06:00'],
        ['start' => '00:08:15', 'end' => '00:08:45'],
    ])
    ->withWatermark('brand.png', 'bottom-right')
    ->save('feature.mp4');
```

## Error Handling

```php
try {
    $clips = FFmpeg::fromPath('video.mp4')
        ->clips([
            ['start' => '00:00:10', 'end' => '00:00:20'],
            ['start' => '00:00:30', 'end' => '00:00:50'],
        ])
        ->save('clip.mp4');
        
    foreach ($clips as $file) {
        echo "Created: {$file}\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

## Performance Notes

- Clips without composition use copy codec (very fast, no re-encoding)
- Clips are processed sequentially (one at a time)
- Adding intro/outro/watermark requires re-encoding (slower)

---

**See Also:**
- [Video Composition](video-composition.md) - Add intro/outro/watermarks
- [API Reference](api-reference.md) - Complete method list
