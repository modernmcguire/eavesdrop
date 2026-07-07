<?php

namespace ModernMcGuire\Eavesdrop;

use Illuminate\Mail\Events\MessageSending;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use ModernMcGuire\Eavesdrop\Http\Middleware\Authorize;
use ModernMcGuire\Eavesdrop\Listeners\EavesdropMail;
use ModernMcGuire\Eavesdrop\Listeners\EavesdropNotification;
use ModernMcGuire\Eavesdrop\Transport\EavesdropTransport;

class EavesdropServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        Event::listen(NotificationSending::class, EavesdropNotification::class);
        Event::listen(MessageSending::class, EavesdropMail::class);

        $this->registerMailTransport();

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'eavesdrop');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->registerRoutes();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('eavesdrop.php'),
            ], 'config');
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'eavesdrop');
    }

    protected function registerMailTransport(): void
    {
        Mail::extend('eavesdrop', fn () => new EavesdropTransport);

        if (! config('mail.mailers.eavesdrop')) {
            config(['mail.mailers.eavesdrop' => ['transport' => 'eavesdrop']]);
        }
    }

    protected function registerRoutes()
    {
        $this->app['router']->group([
            'namespace' => 'ModernMcGuire\Eavesdrop\Http\Controllers',
            'middleware' => [...config('eavesdrop.middleware'), Authorize::class],
        ], function ($router) {
            require __DIR__.'/routes/eavesdrop.php';
        });
    }
}
