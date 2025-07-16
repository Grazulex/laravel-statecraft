<?php

declare(strict_types=1);

namespace Grazulex\LaravelStatecraft;

use Illuminate\Support\ServiceProvider;

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

        $this->publishes([
            __DIR__.'/../database/migrations/' => database_path('migrations'),
        ], 'statecraft-migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\Commands\MakeStateMachineCommand::class,
                Console\Commands\GenerateCommand::class,
            ]);
        }
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/Config/statecraft.php', 'statecraft');
    }
}
