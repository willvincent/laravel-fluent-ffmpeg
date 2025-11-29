<?php

use Ritechoice23\FluentFFmpeg\Builder\FFmpegBuilder;

it('can extract clip using clip method', function () {
    $builder = new FFmpegBuilder;
    $builder->clip('00:00:05', '00:00:15');

    expect($builder->getInputOptions()['ss'])->toBe('00:00:05')
        ->and($builder->getOutputOptions()['to'])->toBe('00:00:15');
});

it('clips method returns builder for chaining', function () {
    $builder = new FFmpegBuilder;
    $result = $builder->clips([
        ['start' => '00:00:00', 'end' => '00:00:10'],
    ]);

    expect($result)->toBe($builder);
});

it('clip method chains with seek and stopAt', function () {
    $builder = new FFmpegBuilder;
    $result = $builder->clip('00:01:00', '00:02:00');

    expect($result)->toBe($builder);
    expect($builder->getInputOptions()['ss'])->toBe('00:01:00');
    expect($builder->getOutputOptions()['to'])->toBe('00:02:00');
});
