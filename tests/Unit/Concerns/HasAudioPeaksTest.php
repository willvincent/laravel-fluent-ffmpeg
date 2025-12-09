<?php

use Ritechoice23\FluentFFmpeg\Builder\FFmpegBuilder;

it('throws exception when no input file is specified', function () {
    $builder = new FFmpegBuilder;

    expect(fn () => $builder->generatePeaks())->toThrow(RuntimeException::class, 'No input file specified');
});

it('has generatePeaks method', function () {
    $builder = new FFmpegBuilder;

    expect(method_exists($builder, 'generatePeaks'))->toBeTrue();
});

it('has generatePeaksToFile method', function () {
    $builder = new FFmpegBuilder;

    expect(method_exists($builder, 'generatePeaksToFile'))->toBeTrue();
});

it('has generatePeaksToDisk method', function () {
    $builder = new FFmpegBuilder;

    expect(method_exists($builder, 'generatePeaksToDisk'))->toBeTrue();
});
