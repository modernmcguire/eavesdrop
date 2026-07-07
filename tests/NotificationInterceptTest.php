<?php

namespace ModernMcGuire\Eavesdrop\Tests;

use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Notification;
use ModernMcGuire\Eavesdrop\Eavesdrop;
use ModernMcGuire\Eavesdrop\Tests\Fixtures\FakeTwilioChannel;
use ModernMcGuire\Eavesdrop\Tests\Fixtures\TestSmsNotification;

class NotificationInterceptTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        FakeTwilioChannel::$sent = [];

        // Register a fake 'twilio' channel so a non-intercepted send has somewhere to go.
        $this->app->make(ChannelManager::class)->extend('twilio', fn () => new FakeTwilioChannel);
    }

    protected function notify(): void
    {
        Notification::route('twilio', '+15551234567')
            ->notify(new TestSmsNotification);
    }

    public function test_it_logs_and_intercepts_a_matching_sms_notification(): void
    {
        $this->notify();

        $this->assertCount(0, FakeTwilioChannel::$sent, 'The SMS should have been intercepted.');

        $this->assertDatabaseCount('eavesdrops', 1);

        $record = Eavesdrop::first();
        $this->assertSame('sms', $record->type);
        $this->assertSame('twilio', $record->channel);
        $this->assertSame('+15551234567', $record->to);
        $this->assertSame('Your verification code is 123456', $record->content);
        $this->assertSame(TestSmsNotification::class, $record->notification);
    }

    public function test_it_logs_but_delivers_when_intercept_is_off(): void
    {
        config()->set('eavesdrop.sms.intercept', false);

        $this->notify();

        $this->assertCount(1, FakeTwilioChannel::$sent, 'The SMS should have been delivered.');
        $this->assertDatabaseCount('eavesdrops', 1);
    }

    public function test_it_does_not_log_when_logging_is_off(): void
    {
        config()->set('eavesdrop.sms.log', false);
        config()->set('eavesdrop.sms.intercept', false);

        $this->notify();

        $this->assertCount(1, FakeTwilioChannel::$sent, 'The SMS should have been delivered.');
        $this->assertDatabaseCount('eavesdrops', 0);
    }

    public function test_it_ignores_channels_not_in_the_config(): void
    {
        config()->set('eavesdrop.sms.channels', ['vonage']);

        $this->notify();

        $this->assertCount(1, FakeTwilioChannel::$sent, 'The SMS should have been delivered.');
        $this->assertDatabaseCount('eavesdrops', 0);
    }
}
