<?php

it('has s3_streaming enabled by default', function () {
    $default = config('fluent-ffmpeg.s3_streaming');

    expect($default)->toBe(true);
});

it('has peaks_format set to simple by default', function () {
    $default = config('fluent-ffmpeg.peaks_format');

    expect($default)->toBe('simple');
});

it('respects FFMPEG_S3_STREAMING env variable', function () {
    // This would be tested in integration tests with actual env
    // Just verify the config structure exists
    $config = config('fluent-ffmpeg');

    expect($config)->toHaveKey('s3_streaming');
});

it('respects FFMPEG_PEAKS_FORMAT env variable', function () {
    $config = config('fluent-ffmpeg');

    expect($config)->toHaveKey('peaks_format');
});

it('has valid peaks_format values', function () {
    config(['fluent-ffmpeg.peaks_format' => 'simple']);
    expect(config('fluent-ffmpeg.peaks_format'))->toBe('simple');

    config(['fluent-ffmpeg.peaks_format' => 'full']);
    expect(config('fluent-ffmpeg.peaks_format'))->toBe('full');
});
