<?php

declare(strict_types=1);

describe('LaravelStatecraftServiceProvider', function () {
    it('class exists', function () {
        expect(class_exists('Grazulex\LaravelStatecraft\LaravelStatecraftServiceProvider'))
            ->toBeTrue();
    });

    it('has correct namespace', function () {
        expect((new ReflectionClass(Grazulex\LaravelStatecraft\LaravelStatecraftServiceProvider::class))->getNamespaceName())
            ->toBe('Grazulex\LaravelStatecraft');
    });

    it('extends ServiceProvider', function () {
        expect(is_subclass_of(Grazulex\LaravelStatecraft\LaravelStatecraftServiceProvider::class, Illuminate\Support\ServiceProvider::class))
            ->toBeTrue();
    });

    it('has required methods', function () {
        expect(method_exists(Grazulex\LaravelStatecraft\LaravelStatecraftServiceProvider::class, 'register'))
            ->toBeTrue();
        expect(method_exists(Grazulex\LaravelStatecraft\LaravelStatecraftServiceProvider::class, 'boot'))
            ->toBeTrue();
    });
});
