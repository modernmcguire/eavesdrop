<?php

namespace ModernMcGuire\Eavesdrop\Tests;

use Illuminate\Support\Facades\Mail;
use ModernMcGuire\Eavesdrop\Eavesdrop;
use ModernMcGuire\Eavesdrop\Tests\Fixtures\TestMailable;
use ModernMcGuire\Eavesdrop\Transport\EavesdropTransport;
use Orchestra\Testbench\Attributes\DefineEnvironment;

class MailTransportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('mail.default', 'eavesdrop');
    }

    public function test_the_eavesdrop_mailer_resolves_the_eavesdrop_transport(): void
    {
        $this->assertInstanceOf(EavesdropTransport::class, Mail::mailer()->getSymfonyTransport());
    }

    public function test_it_logs_the_email_exactly_once_and_never_delivers(): void
    {
        config()->set('eavesdrop.email.intercept', false);

        Mail::to('customer@example.com')->send(new TestMailable);

        $this->assertDatabaseCount('eavesdrops', 1);
    }

    public function test_it_captures_the_full_email_payload_including_attachments(): void
    {
        config()->set('eavesdrop.email.intercept', false);

        Mail::to('customer@example.com')
            ->cc('manager@example.com')
            ->bcc('audit@example.com')
            ->send((new TestMailable)->attachData('receipt bytes', 'receipt.pdf', ['mime' => 'application/pdf']));

        $payload = Eavesdrop::first()->payload;

        $this->assertSame('customer@example.com', $payload['to']);
        $this->assertSame('manager@example.com', $payload['cc']);
        $this->assertSame('audit@example.com', $payload['bcc']);
        $this->assertSame('Your receipt', $payload['subject']);
        $this->assertStringContainsString('Thanks for your order.', $payload['html']);
        $this->assertSame([
            ['filename' => 'receipt.pdf', 'content_type' => 'application/pdf'],
        ], $payload['attachments']);
    }

    protected function usesAppDefinedEavesdropMailer($app): void
    {
        $app['config']->set('mail.mailers.eavesdrop', ['transport' => 'array']);
    }

    #[DefineEnvironment('usesAppDefinedEavesdropMailer')]
    public function test_it_does_not_override_an_app_defined_eavesdrop_mailer(): void
    {
        $this->assertSame(['transport' => 'array'], config('mail.mailers.eavesdrop'));
    }
}
