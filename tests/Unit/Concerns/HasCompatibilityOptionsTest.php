<?php

use Ritechoice23\FluentFFmpeg\Builder\FFmpegBuilder;

it('can set web optimized settings', function () {
    $builder = new FFmpegBuilder;
    $builder->webOptimized();

    expect($builder->getOutputOptions())->toHaveKey('profile:v', 'baseline')
        ->and($builder->getOutputOptions())->toHaveKey('level', '3.0')
        ->and($builder->getOutputOptions())->toHaveKey('pix_fmt', 'yuv420p')
        ->and($builder->getOutputOptions())->toHaveKey('movflags', '+faststart');
});

it('can set mobile optimized settings', function () {
    $builder = new FFmpegBuilder;
    $builder->mobileOptimized();

    expect($builder->getOutputOptions())->toHaveKey('profile:v', 'main')
        ->and($builder->getOutputOptions())->toHaveKey('level', '3.1')
        ->and($builder->getOutputOptions())->toHaveKey('movflags', '+faststart');
});

it('can set universal compatibility settings', function () {
    $builder = new FFmpegBuilder;
    $builder->universalCompatibility();

    expect($builder->getOutputOptions())->toHaveKey('profile:v', 'baseline')
        ->and($builder->getOutputOptions())->toHaveKey('level', '3.0')
        ->and($builder->getOutputOptions())->toHaveKey('f', 'mp4')
        ->and($builder->getOutputOptions())->toHaveKey('movflags', '+faststart');
});

it('can set ios optimized settings', function () {
    $builder = new FFmpegBuilder;
    $builder->iosOptimized();

    expect($builder->getOutputOptions())->toHaveKey('profile:v', 'high')
        ->and($builder->getOutputOptions())->toHaveKey('level', '4.0')
        ->and($builder->getOutputOptions())->toHaveKey('movflags', '+faststart');
});

it('can set android optimized settings', function () {
    $builder = new FFmpegBuilder;
    $builder->androidOptimized();

    expect($builder->getOutputOptions())->toHaveKey('profile:v', 'main')
        ->and($builder->getOutputOptions())->toHaveKey('level', '3.1')
        ->and($builder->getOutputOptions())->toHaveKey('movflags', '+faststart');
});

it('can set fast start', function () {
    $builder = new FFmpegBuilder;
    $builder->fastStart();

    expect($builder->getOutputOptions())->toHaveKey('movflags', '+faststart');
});

it('can set h264 profile and level', function () {
    $builder = new FFmpegBuilder;
    $builder->h264Profile('high', '4.2');

    expect($builder->getOutputOptions())->toHaveKey('profile:v', 'high')
        ->and($builder->getOutputOptions())->toHaveKey('level', '4.2');
});

it('can set gop size', function () {
    $builder = new FFmpegBuilder;
    $builder->gopSize(120);

    expect($builder->getOutputOptions())->toHaveKey('g', 120);
});

it('can set keyframe interval', function () {
    $builder = new FFmpegBuilder;
    $builder->keyframeInterval(60);

    expect($builder->getOutputOptions())->toHaveKey('keyint_min', 60);
});

it('can set scene change threshold', function () {
    $builder = new FFmpegBuilder;
    $builder->sceneChangeThreshold(0);

    expect($builder->getOutputOptions())->toHaveKey('sc_threshold', 0);
});

it('can set reliable keyframes', function () {
    $builder = new FFmpegBuilder;
    $builder->reliableKeyframes(90, true);

    expect($builder->getOutputOptions())->toHaveKey('g', 90)
        ->and($builder->getOutputOptions())->toHaveKey('keyint_min', 90)
        ->and($builder->getOutputOptions())->toHaveKey('sc_threshold', 0);
});

it('can set reliable keyframes without disabling scene detection', function () {
    $builder = new FFmpegBuilder;
    $builder->reliableKeyframes(60, false);

    expect($builder->getOutputOptions())->toHaveKey('g', 60)
        ->and($builder->getOutputOptions())->toHaveKey('keyint_min', 60)
        ->and($builder->getOutputOptions())->not->toHaveKey('sc_threshold');
});

it('universal compatibility includes gop settings', function () {
    $builder = new FFmpegBuilder;
    $builder->universalCompatibility();

    expect($builder->getOutputOptions())->toHaveKey('g', 60)
        ->and($builder->getOutputOptions())->toHaveKey('keyint_min', 60)
        ->and($builder->getOutputOptions())->toHaveKey('sc_threshold', 0);
});
