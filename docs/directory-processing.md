# Directory Processing

Process multiple video files from a directory automatically.

## Basic Usage

Process all video files in a directory:

```php
use Ritechoice23\FluentFFmpeg\Facades\FFmpeg;

// Process all videos in a directory
FFmpeg::fromDirectory('/path/to/videos')
    ->resize(1920, 1080)
    ->save('/path/to/output/');
```

## Recursive Processing

Process videos in subdirectories:

```php
FFmpeg::fromDirectory('/path/to/videos', recursive: true)
    ->videoCodec('libx264')
    ->save('/path/to/output/');
```

## Filter by Extensions

Only process specific file types:

```php
FFmpeg::allowExtensions(['mp4', 'mov'])
    ->fromDirectory('/path/to/videos')
    ->save('/path/to/output/');
```

Default extensions: `mp4`, `avi`, `mkv`, `mov`, `flv`, `wmv`, `webm`, `m4v`

You can also process **audio files** (`mp3`, `wav`, `aac`, `flac`, `ogg`, etc.) and **image files** (`jpg`, `png`, `gif`, etc.) for audio-to-video conversion.

## Output Patterns

### Save to Directory

Save all processed videos to an output directory with the same filenames:

```php
FFmpeg::fromDirectory('/path/to/videos')
    ->resize(1920, 1080)
    ->save('/path/to/output/'); // Must be a directory
```

### Custom Output Pattern

Use placeholders for custom output naming:

```php
FFmpeg::fromDirectory('/path/to/videos')
    ->resize(1920, 1080)
    ->save('/path/to/output/processed_{name}.{ext}');
```

Available placeholders:

-   `{n}` or `{index}` - File number (1-based or 0-based)
-   `{name}` - Original filename without extension
-   `{ext}` - Original file extension

Examples:

```php
// Output: video_1.mp4, video_2.mp4, video_3.mp4
->save('/output/video_{n}.mp4');

// Output: processed_holiday.mp4, processed_birthday.mp4
->save('/output/processed_{name}.{ext}');

// Output: 0_holiday.mp4, 1_birthday.mp4
->save('/output/{index}_{name}.{ext}');
```

## Per-File Callbacks

Execute custom logic for each file:

```php
FFmpeg::fromDirectory('/path/to/videos')
    ->eachFile(function ($builder, $filePath) {
        // Access current file
        $filename = basename($filePath);

        // Modify builder for this specific file
        if (str_contains($filename, 'portrait')) {
            $builder->resize(1080, 1920);
        } else {
            $builder->resize(1920, 1080);
        }

        // Add custom watermark per file
        $builder->withWatermark("/watermarks/{$filename}.png");
    })
    ->save('/path/to/output/');
```

The callback receives:

-   `$builder` - Clone of FFmpegBuilder for this file
-   `$filePath` - Full path to the current file

## Return Value

When processing directories, `save()` returns an array of results:

```php
$results = FFmpeg::fromDirectory('/path/to/videos')
    ->resize(1920, 1080)
    ->save('/path/to/output/');

// Results format:
[
    [
        'input' => '/path/to/videos/video1.mp4',
        'output' => '/path/to/output/video1.mp4',
        'success' => true,
    ],
    [
        'input' => '/path/to/videos/video2.mp4',
        'output' => '/path/to/output/video2.mp4',
        'success' => false,
        'error' => 'Error message...',
    ],
]
```

## Complete Examples

### Batch Convert Format

```php
FFmpeg::allowExtensions(['avi', 'mkv'])
    ->fromDirectory('/path/to/mixed/videos')
    ->videoCodec('libx264')
    ->audioCodec('aac')
    ->save('/path/to/output/{name}.mp4');
```

### Add Watermark to All Videos

```php
FFmpeg::fromDirectory('/path/to/videos')
    ->withWatermark('/path/to/logo.png', 'bottom-right')
    ->save('/path/to/output/');
```

### Dynamic Text per Video

```php
FFmpeg::fromDirectory('/path/to/videos')
    ->withText(function ($file) {
        return 'File: ' . basename($file);
    }, [
        'position' => 'top-center',
        'font_size' => 24,
    ])
    ->save('/path/to/output/');
```

### Combine with Other Features

```php
FFmpeg::fromDirectory('/path/to/videos', recursive: true)
    ->eachFile(function ($builder, $file) {
        // Add intro only to videos starting with 'main'
        if (str_starts_with(basename($file), 'main')) {
            $builder->withIntro('/path/to/intro.mp4');
        }
    })
    ->withWatermark('/path/to/logo.png')
    ->resize(1920, 1080)
    ->videoCodec('libx264')
    ->audioBitrate('192k')
    ->save('/path/to/output/final_{name}.mp4');
```

## Error Handling

```php
$results = FFmpeg::fromDirectory('/path/to/videos')
    ->resize(1920, 1080)
    ->save('/path/to/output/');

foreach ($results as $result) {
    if ($result['success']) {
        echo "✓ Processed: {$result['input']}\n";
    } else {
        echo "✗ Failed: {$result['input']}\n";
        echo "  Error: {$result['error']}\n";
    }
}
```

## Audio to Video Conversion

Convert a directory of audio files (MP3, WAV, etc.) to videos with images/thumbnails.

### Basic Audio to Video

Convert all MP3 files to videos with a static image:

```php
FFmpeg::fromDirectory('/path/to/audio')
    ->allowExtensions(['mp3'])
    ->eachFile(function ($builder, $audioFile) {
        $name = pathinfo($audioFile, PATHINFO_FILENAME);

        // Clear default input and rebuild
        $builder->inputs = [];
        $builder->fromPath('/path/to/thumbnail.jpg')
            ->addInput($audioFile)
            ->resolution(1920, 1080)
            ->universalCompatibility()
            ->withText($name);
    })
    ->save('/path/to/output/{name}.mp4');
```

### Audio to Video with Image Overlays

Add multiple images/overlays to audio files:

```php
FFmpeg::fromDirectory('/path/to/podcasts')
    ->allowExtensions(['mp3', 'wav'])
    ->eachFile(function ($builder, $audioFile) {
        $name = basename($audioFile, '.mp3');

        $builder->inputs = [];
        $builder->fromPath('/path/to/background.jpg')
            ->addInput('/path/to/wave-animation.png')
            ->addInput($audioFile)
            ->overlay(['x' => '(W-w)/2', 'y' => 'H-h-50'])
            ->resolution(1920, 1080)
            ->universalCompatibility()
            ->withText($name, [
                'position' => 'bottom-center',
                'font_size' => 32,
            ]);
    })
    ->save('/path/to/output/{name}.mp4');
```

### Audio to Video with Intro/Outro

Add intro and outro videos to audio files:

```php
FFmpeg::fromDirectory('/path/to/sermons')
    ->allowExtensions(['mp3'])
    ->eachFile(function ($builder, $audioFile) {
        $name = pathinfo($audioFile, PATHINFO_FILENAME);

        $builder->inputs = [];
        $builder->fromPath('/path/to/sermon-thumbnail.jpg')
            ->addInput('/path/to/audio-wave.png')
            ->withIntro('/path/to/church-intro.mp4')
            ->withOutro('/path/to/church-outro.mp4')
            ->addInput($audioFile)
            ->overlay(['x' => '(W-w)/2', 'y' => 'H-h-50'])
            ->resolution(1920, 1080)
            ->universalCompatibility()
            ->withText($name);
    })
    ->save('/path/to/output/{name}.mp4');
```

### Per-File Custom Thumbnails

Use different thumbnails for each audio file:

```php
FFmpeg::fromDirectory('/path/to/audio')
    ->allowExtensions(['mp3'])
    ->eachFile(function ($builder, $audioFile) {
        $name = pathinfo($audioFile, PATHINFO_FILENAME);

        // Look for matching thumbnail (same name as audio)
        $thumbnailPath = dirname($audioFile) . '/thumbnails/' . $name . '.jpg';
        if (!file_exists($thumbnailPath)) {
            $thumbnailPath = '/path/to/default-thumbnail.jpg';
        }

        $builder->inputs = [];
        $builder->fromPath($thumbnailPath)
            ->addInput($audioFile)
            ->resolution(1920, 1080)
            ->universalCompatibility()
            ->withText($name, ['position' => 'bottom-center']);
    })
    ->save('/path/to/output/{name}.mp4');
```

## Tips

1. **Memory Management**: Processing many large files sequentially may take time but is memory efficient
2. **Output Directory**: Ensure output directory exists or use a pattern that includes the directory
3. **File Filtering**: Use `allowExtensions()` to avoid processing non-video files
4. **Callbacks**: Use `eachFile()` for per-file customization without breaking the chain
5. **Progress Tracking**: Use `onProgress()` for each file to track processing status
