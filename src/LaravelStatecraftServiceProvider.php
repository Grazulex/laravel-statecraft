<?php

declare(strict_types=1);

namespace Grazulex\LaravelStatecraft;

use Illuminate\Support\ServiceProvider;
use Throwable;

final class LaravelStatecraftServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/Config/statecraft.php' => config_path('statecraft.php'),
        ], 'statecraft-config');
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/Config/statecraft.php', 'statecraft');
    }

}
