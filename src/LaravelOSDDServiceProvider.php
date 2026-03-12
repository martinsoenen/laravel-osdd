<?php

namespace Xefi\LaravelOSDD;

use Illuminate\Support\ServiceProvider;
use Xefi\LaravelOSDD\Console\Commands\Make\FactoryMakeCommand;
use Xefi\LaravelOSDD\Console\Commands\LayerCommand;

class LaravelOSDDServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                LayerCommand::class,
                FactoryMakeCommand::class,
            ]);
        }

        $this->publishes([
            __DIR__.'/../config/osdd.php' => config_path('osdd.php'),
        ]);
    }

    /**
     * Register any package services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/osdd.php', 'osdd'
        );
    }
}