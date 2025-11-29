<?php

use Ritechoice23\FluentFFmpeg\Builder\FFmpegBuilder;

it('can set format', function () {
    $builder = new FFmpegBuilder;
    $builder->outputFormat('mp4');

    expect($builder->getOutputOptions()['f'])->toBe('mp4');
});

it('can configure HLS output', function () {
    $builder = new FFmpegBuilder;
    $builder->hls(['segment_time' => 5]);

    expect($builder->getOutputOptions()['f'])->toBe('hls')
        ->and($builder->getOutputOptions()['hls_time'])->toBe(5);
});

it('can configure DASH output', function () {
    $builder = new FFmpegBuilder;
    $builder->dash(['segment_duration' => 8]);

    expect($builder->getOutputOptions()['f'])->toBe('dash')
        ->and($builder->getOutputOptions()['seg_duration'])->toBe(8);
});

it('can configure GIF output', function () {
    $builder = new FFmpegBuilder;
    $builder->gif(['fps' => 15, 'width' => 480]);

    expect($builder->getOutputOptions()['f'])->toBe('gif')
        ->and($builder->getOutputOptions()['vf'])->toContain('fps=15');
});

it('can use toGif helper', function () {
    $builder = new FFmpegBuilder;
    $builder->toGif();

    expect($builder->getOutputOptions()['f'])->toBe('gif');
});

// Audio Format Conversions
it('can convert to MP3', function () {
    $builder = new FFmpegBuilder;
    $result = $builder->toMp3();

    expect($result)->toBe($builder);
    expect($builder->getOutputOptions()['f'])->toBe('mp3');
});

it('can convert to MP3 with custom bitrate', function () {
    $builder = new FFmpegBuilder;
    $builder->toMp3(320);

    expect($builder->getOutputOptions())->toHaveKey('f');
});

it('can convert to WAV', function () {
    $builder = new FFmpegBuilder;
    $builder->toWav();

    expect($builder->getOutputOptions()['f'])->toBe('wav');
});

it('can convert to WAV with custom sample rate', function () {
    $builder = new FFmpegBuilder;
    $result = $builder->toWav(48000);

    expect($result)->toBe($builder);
});

it('can convert to AAC', function () {
    $builder = new FFmpegBuilder;
    $builder->toAac();

    expect($builder->getOutputOptions()['f'])->toBe('adts');
});

it('can convert to OGG', function () {
    $builder = new FFmpegBuilder;
    $builder->toOgg();

    expect($builder->getOutputOptions()['f'])->toBe('ogg');
});

it('can convert to M4A', function () {
    $builder = new FFmpegBuilder;
    $builder->toM4a();

    expect($builder->getOutputOptions()['f'])->toBe('mp4');
});

// Video Format Conversions
it('can convert to MP4', function () {
    $builder = new FFmpegBuilder;
    $builder->toMp4();

    expect($builder->getOutputOptions()['f'])->toBe('mp4');
});

it('can convert to MP4 with custom codecs', function () {
    $builder = new FFmpegBuilder;
    $result = $builder->toMp4(['video_codec' => 'libx265', 'audio_codec' => 'aac']);

    expect($result)->toBe($builder);
});

it('can convert to WebM', function () {
    $builder = new FFmpegBuilder;
    $builder->toWebm();

    expect($builder->getOutputOptions()['f'])->toBe('webm');
});

it('can convert to AVI', function () {
    $builder = new FFmpegBuilder;
    $builder->toAvi();

    expect($builder->getOutputOptions()['f'])->toBe('avi');
});

it('can convert to MOV', function () {
    $builder = new FFmpegBuilder;
    $builder->toMov();

    expect($builder->getOutputOptions()['f'])->toBe('mov');
});

it('can convert to FLV', function () {
    $builder = new FFmpegBuilder;
    $builder->toFlv();

    expect($builder->getOutputOptions()['f'])->toBe('flv');
});

it('can convert to MKV', function () {
    $builder = new FFmpegBuilder;
    $builder->toMkv();

    expect($builder->getOutputOptions()['f'])->toBe('matroska');
});

it('format conversions are chainable', function () {
    $builder = new FFmpegBuilder;
    $result = $builder->toMp4();

    expect($result)->toBe($builder);
});
