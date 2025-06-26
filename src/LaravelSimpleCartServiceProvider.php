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
                __DIR__.'/../config/config.php' => config_path('laravel-simple-cart.php'),
            ], 'config');
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Merge config
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'laravel-simple-cart');

        // Register the main class to use with the facade
        $this->app->bind('cart', function () {
            return new Cart($this->app['session']);
        });
    }
}
