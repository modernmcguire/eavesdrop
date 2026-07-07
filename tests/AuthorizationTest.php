<?php

namespace ModernMcGuire\Eavesdrop\Tests;

use Illuminate\Support\Facades\Gate;
use ModernMcGuire\Eavesdrop\Eavesdrop;

class AuthorizationTest extends TestCase
{
    public function test_the_inbox_is_accessible_in_the_local_environment_by_default(): void
    {
        $this->app['env'] = 'local';

        $this->get('/eavesdrop')->assertOk();
    }

    public function test_the_inbox_is_blocked_outside_the_local_environment_by_default(): void
    {
        $message = Eavesdrop::create([
            'type' => 'sms',
            'channel' => 'twilio',
            'to' => '+15551234567',
            'content' => 'Your code is 123456',
            'notification' => 'App\\Notifications\\VerifyPhone',
            'payload' => ['content' => 'Your code is 123456'],
        ]);

        $this->get('/eavesdrop')->assertForbidden();
        $this->get('/eavesdrop/list')->assertForbidden();
        $this->get("/eavesdrop/{$message->id}")->assertForbidden();
        $this->delete('/eavesdrop')->assertForbidden();

        $this->assertSame(1, Eavesdrop::query()->count());
    }

    public function test_the_gate_can_grant_access_outside_the_local_environment(): void
    {
        Gate::define('viewEavesdrop', fn ($user = null) => true);

        $this->get('/eavesdrop')->assertOk();
    }

    public function test_the_gate_overrides_the_local_default_when_defined(): void
    {
        $this->app['env'] = 'local';

        Gate::define('viewEavesdrop', fn ($user = null) => false);

        $this->get('/eavesdrop')->assertForbidden();
    }
}
