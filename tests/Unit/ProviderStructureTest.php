<?php

declare(strict_types=1);

it('can load service provider file', function () {
    $providerPath = __DIR__.'/../../src/LaravelStatecraftServiceProvider.php';

    expect(file_exists($providerPath))
        ->toBeTrue();

    $content = file_get_contents($providerPath);

    expect($content)
        ->toContain('LaravelStatecraftServiceProvider')
        ->toContain('ServiceProvider')
        ->toContain('boot')
        ->toContain('register');
});

it('validates service provider structure', function () {
    $reflection = new ReflectionClass(Grazulex\LaravelStatecraft\LaravelStatecraftServiceProvider::class);

    expect($reflection->isFinal())
        ->toBeTrue();

    expect($reflection->hasMethod('boot'))
        ->toBeTrue();

    expect($reflection->hasMethod('register'))
        ->toBeTrue();
});

it('has correct file permissions', function () {
    $providerPath = __DIR__.'/../../src/LaravelStatecraftServiceProvider.php';

    expect(is_readable($providerPath))
        ->toBeTrue();
});
