<?php

namespace ModernMcGuire\Eavesdrop\Tests\Fixtures;

use Illuminate\Notifications\Notification;

class TestSmsNotification extends Notification
{
    public function via($notifiable): array
    {
        return ['twilio'];
    }

    public function toTwilio($notifiable): object
    {
        return (object) ['content' => 'Your verification code is 123456'];
    }
}
