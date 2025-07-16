<?php

declare(strict_types=1);

it('can create basic configuration', function () {
    $config = include __DIR__.'/../../src/Config/statecraft.php';

    expect($config)
        ->toBeArray();
});

it('config path is accessible', function () {
    expect(file_exists(__DIR__.'/../../src/Config/statecraft.php'))
        ->toBeTrue();
});

it('config returns empty array by default', function () {
    $config = include __DIR__.'/../../src/Config/statecraft.php';

    expect($config)
        ->toBeArray()
        ->toBeEmpty();
});
