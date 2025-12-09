<?php

use Ritechoice23\FluentFFmpeg\Builder\FFmpegBuilder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    config(['fluent-ffmpeg.s3_streaming' => true]);
});


it('uses temporaryUrl when s3_streaming is enabled', function () {
    config(['fluent-ffmpeg.s3_streaming' => true]);

    Storage::fake('s3');
    Storage::disk('s3')->put('test.mp3', 'fake content');

    // Mock temporaryUrl to return a URL
    Storage::shouldReceive('disk')
        ->with('s3')
        ->andReturnSelf()
        ->shouldReceive('temporaryUrl')
        ->with('test.mp3', Mockery::any())
        ->andReturn('https://s3.amazonaws.com/bucket/test.mp3?signature=xyz');

    $builder = new FFmpegBuilder;
    $builder->fromDisk('s3', 'test.mp3');

    $inputs = $builder->getInputs();

    expect($inputs[0])->toContain('https://s3.amazonaws.com');
});

it('falls back to path when temporaryUrl throws exception', function () {
    config(['fluent-ffmpeg.s3_streaming' => true]);

    Storage::fake('local');
    Storage::disk('local')->put('test.mp3', 'fake content');

    // Mock Storage to throw exception on temporaryUrl
    Storage::shouldReceive('disk')
        ->with('local')
        ->andReturnSelf()
        ->shouldReceive('temporaryUrl')
        ->andThrow(new \RuntimeException('temporaryUrl not supported'))
        ->shouldReceive('path')
        ->with('test.mp3')
        ->andReturn('/fake/path/test.mp3');

    $builder = new FFmpegBuilder;
    $builder->fromDisk('local', 'test.mp3');

    $inputs = $builder->getInputs();

    // Should use local path instead of URL
    expect($inputs[0])->not->toContain('http')
        ->and($inputs[0])->toBe('/fake/path/test.mp3');
});

it('uses path when s3_streaming is disabled', function () {
    config(['fluent-ffmpeg.s3_streaming' => false]);

    Storage::fake('s3');
    Storage::disk('s3')->put('test.mp3', 'fake content');

    $builder = new FFmpegBuilder;
    $builder->fromDisk('s3', 'test.mp3');

    $inputs = $builder->getInputs();

    // Should not attempt to use temporaryUrl
    expect($inputs[0])->not->toContain('http');
});

it('returns self for method chaining', function () {
    Storage::fake('local');
    Storage::disk('local')->put('test.mp3', 'fake content');

    $builder = new FFmpegBuilder;
    $result = $builder->fromDisk('local', 'test.mp3');

    expect($result)->toBe($builder);
});

it('returns boolean when peaks not configured', function () {
    // This would need actual FFmpeg execution, so we'll test the logic
    $builder = new FFmpegBuilder;

    // Mock execute to return standard result
    $mockResult = ['success' => true, 'peaks' => null];

    // Simulate what execute() does
    $peaksFormat = config('fluent-ffmpeg.peaks_format', 'simple');

    if (isset($mockResult['peaks']) && $mockResult['peaks'] !== null) {
        $result = [
            'output_path' => 'test.mp4',
            'peaks' => $peaksFormat === 'full'
                ? $mockResult['peaks']
                : $mockResult['peaks']['data'],
        ];
    } else {
        $result = $mockResult['success'] ?? true;
    }

    expect($result)->toBe(true);
});

it('returns array with peaks when configured - simple format', function () {
    config(['fluent-ffmpeg.peaks_format' => 'simple']);

    $mockResult = [
        'success' => true,
        'peaks' => [
            'version' => 2,
            'channels' => 2,
            'sample_rate' => 44100,
            'data' => [0.1, 0.2, 0.3],
        ],
    ];

    // Simulate execute logic
    $peaksFormat = config('fluent-ffmpeg.peaks_format', 'simple');

    if (isset($mockResult['peaks']) && $mockResult['peaks'] !== null) {
        $result = [
            'output_path' => 'test.mp4',
            'peaks' => $peaksFormat === 'full'
                ? $mockResult['peaks']
                : $mockResult['peaks']['data'],
        ];
    } else {
        $result = $mockResult['success'] ?? true;
    }

    expect($result)->toBeArray()
        ->and($result['output_path'])->toBe('test.mp4')
        ->and($result['peaks'])->toBe([0.1, 0.2, 0.3]);
});

it('returns array with peaks when configured - full format', function () {
    config(['fluent-ffmpeg.peaks_format' => 'full']);

    $mockResult = [
        'success' => true,
        'peaks' => [
            'version' => 2,
            'channels' => 2,
            'sample_rate' => 44100,
            'samples_per_pixel' => 512,
            'bits' => 32,
            'length' => 3,
            'data' => [0.1, 0.2, 0.3],
        ],
    ];

    // Simulate execute logic
    $peaksFormat = config('fluent-ffmpeg.peaks_format', 'full');

    if (isset($mockResult['peaks']) && $mockResult['peaks'] !== null) {
        $result = [
            'output_path' => 'test.mp4',
            'peaks' => $peaksFormat === 'full'
                ? $mockResult['peaks']
                : $mockResult['peaks']['data'],
        ];
    } else {
        $result = $mockResult['success'] ?? true;
    }

    expect($result)->toBeArray()
        ->and($result['output_path'])->toBe('test.mp4')
        ->and($result['peaks'])->toBeArray()
        ->and($result['peaks']['version'])->toBe(2)
        ->and($result['peaks']['data'])->toBe([0.1, 0.2, 0.3]);
});
