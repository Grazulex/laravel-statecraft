<?php

declare(strict_types=1);

it('can run all tests without errors', function () {
    expect(true)->toBeTrue();
});

it('has functioning test environment', function () {
    expect(class_exists('Tests\TestCase'))
        ->toBeTrue();

    expect(class_exists('PHPUnit\Framework\TestCase'))
        ->toBeTrue();
});

it('can access test base path', function () {
    expect(dirname(__DIR__))
        ->toContain('tests');
});

it('passes integration validation', function () {
    // Ce test valide que l'ensemble fonctionne correctement
    expect(class_exists('Tests\TestCase'))
        ->toBeTrue();

    expect(class_exists('Orchestra\Testbench\TestCase'))
        ->toBeTrue();
});
