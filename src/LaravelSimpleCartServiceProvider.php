<?php

namespace Ashraam\LaravelSimpleCart;

use Illuminate\Support\ServiceProvider;
use Ashraam\LaravelSimpleCart\Cart;

class LaravelSimpleCartServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('laravelsimplecart.php'),
            ], 'config');
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Merge config
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'laravelsimplecart');

        // Register the main class to use with the facade
        $this->app->singleton('cart', function () {
            return new Cart();
        });
    }
}
