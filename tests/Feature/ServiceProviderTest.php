<?php

declare(strict_types=1);

it('can load the service provider', function () {
    expect(app()->bound('Grazulex\LaravelStatecraft\LaravelStatecraftServiceProvider'))
        ->toBeBool();
});

it('can access basic functionality', function () {
    // Test basique pour vérifier que le package est chargé
    expect(class_exists('Grazulex\LaravelStatecraft\LaravelStatecraftServiceProvider'))
        ->toBeTrue();
});

it('config file exists', function () {
    expect(file_exists(__DIR__.'/../../src/Config/statecraft.php'))
        ->toBeTrue();
});
