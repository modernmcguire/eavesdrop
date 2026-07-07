<?php

namespace ModernMcGuire\Eavesdrop\Tests\Fixtures;

use Illuminate\Notifications\Notification;

class FakeTwilioChannel
{
    public static array $sent = [];

    public function send($notifiable, Notification $notification): void
    {
        static::$sent[] = $notification->toTwilio($notifiable);
    }
}
