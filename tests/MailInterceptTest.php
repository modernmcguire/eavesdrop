<?php

namespace ModernMcGuire\Eavesdrop\Tests;

use Illuminate\Support\Facades\Mail;
use ModernMcGuire\Eavesdrop\Eavesdrop;
use ModernMcGuire\Eavesdrop\Tests\Fixtures\TestMailable;

class MailInterceptTest extends TestCase
{
    protected function send(): void
    {
        Mail::to('customer@example.com')->send(new TestMailable);
    }

    protected function delivered(): int
    {
        return Mail::mailer()->getSymfonyTransport()->messages()->count();
    }

    public function test_it_logs_and_intercepts_an_outbound_email(): void
    {
        $this->send();

        $this->assertSame(0, $this->delivered(), 'The email should have been intercepted.');

        $this->assertDatabaseCount('eavesdrops', 1);

        $record = Eavesdrop::first();
        $this->assertSame('email', $record->type);
        $this->assertSame('array', $record->channel);
        $this->assertSame('customer@example.com', $record->to);
        $this->assertSame('Your receipt', $record->subject);
        $this->assertStringContainsString('Thanks for your order.', $record->content);
        $this->assertSame('app@example.com', $record->payload['from']);
    }

    public function test_it_logs_but_delivers_when_intercept_is_off(): void
    {
        config()->set('eavesdrop.email.intercept', false);

        $this->send();

        $this->assertSame(1, $this->delivered(), 'The email should have been delivered.');
        $this->assertDatabaseCount('eavesdrops', 1);
    }

    public function test_it_does_not_log_when_logging_is_off(): void
    {
        config()->set('eavesdrop.email.log', false);
        config()->set('eavesdrop.email.intercept', false);

        $this->send();

        $this->assertSame(1, $this->delivered(), 'The email should have been delivered.');
        $this->assertDatabaseCount('eavesdrops', 0);
    }
}
