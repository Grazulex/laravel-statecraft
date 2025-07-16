<?php

declare(strict_types=1);

it('can validate package structure', function () {
    expect(file_exists(__DIR__.'/../../composer.json'))
        ->toBeTrue();

    expect(file_exists(__DIR__.'/../../src/LaravelStatecraftServiceProvider.php'))
        ->toBeTrue();

    expect(file_exists(__DIR__.'/../../src/Config/statecraft.php'))
        ->toBeTrue();
});

it('has correct composer package name', function () {
    $composer = json_decode(file_get_contents(__DIR__.'/../../composer.json'), true);

    expect($composer['name'])
        ->toBe('grazulex/laravel-statecraft');
});

it('requires correct PHP version', function () {
    $composer = json_decode(file_get_contents(__DIR__.'/../../composer.json'), true);

    expect($composer['require']['php'])
        ->toBe('^8.3');
});

it('has Laravel dependencies', function () {
    $composer = json_decode(file_get_contents(__DIR__.'/../../composer.json'), true);

    expect($composer['require'])
        ->toHaveKey('illuminate/support')
        ->toHaveKey('illuminate/contracts');
});
