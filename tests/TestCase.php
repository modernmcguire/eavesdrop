<?php

namespace ModernMcGuire\Eavesdrop\Tests;

use ModernMcGuire\Eavesdrop\EavesdropServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            EavesdropServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('database.default', 'testing');
        $app['config']->set('mail.default', 'array');
        $app['config']->set('mail.from', ['address' => 'app@example.com', 'name' => 'App']);

        $app['config']->set('eavesdrop.sms.log', true);
        $app['config']->set('eavesdrop.sms.intercept', true);
        $app['config']->set('eavesdrop.sms.channels', ['twilio']);
        $app['config']->set('eavesdrop.email.log', true);
        $app['config']->set('eavesdrop.email.intercept', true);
    }

    protected function defineDatabaseMigrations()
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
