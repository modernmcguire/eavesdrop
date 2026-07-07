<?php

namespace ModernMcGuire\Eavesdrop\Tests;

use Illuminate\Support\Facades\Gate;
use ModernMcGuire\Eavesdrop\Eavesdrop;

class InboxEndpointsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Gate::define('viewEavesdrop', fn ($user = null) => true);
    }

    protected function makeMessage(array $attributes = []): Eavesdrop
    {
        return Eavesdrop::create(array_merge([
            'type' => 'sms',
            'channel' => 'twilio',
            'to' => '+15551234567',
            'content' => 'Your code is 123456',
            'notification' => 'App\\Notifications\\VerifyPhone',
            'payload' => ['content' => 'Your code is 123456'],
        ], $attributes));
    }

    public function test_the_inbox_page_renders(): void
    {
        $this->get('/eavesdrop')
            ->assertOk()
            ->assertSee('Eavesdrop')
            ->assertSee('outbound message inspector');
    }

    public function test_the_list_endpoint_returns_paginated_json(): void
    {
        foreach (range(1, 12) as $i) {
            $this->makeMessage(['content' => "Message {$i}"]);
        }

        $this->get('/eavesdrop/list')
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('last_page', 2)
            ->assertJsonPath('current_page', 1);
    }

    public function test_the_list_endpoint_filters_by_search(): void
    {
        $this->makeMessage(['content' => 'apple pie']);
        $this->makeMessage(['content' => 'banana split']);

        $this->get('/eavesdrop/list?search=banana')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.content', 'banana split');
    }

    public function test_the_list_endpoint_filters_by_channel(): void
    {
        $this->makeMessage(['channel' => 'twilio', 'content' => 'from twilio']);
        $this->makeMessage(['channel' => 'vonage', 'content' => 'from vonage']);

        $this->get('/eavesdrop/list?channel=vonage')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.content', 'from vonage');
    }

    public function test_the_channel_filter_is_ignored_when_set_to_all(): void
    {
        $this->makeMessage(['channel' => 'twilio']);
        $this->makeMessage(['channel' => 'vonage']);

        $this->get('/eavesdrop/list?channel=all')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_the_show_endpoint_returns_a_single_message(): void
    {
        $message = $this->makeMessage();

        $this->get("/eavesdrop/{$message->id}")
            ->assertOk()
            ->assertJsonPath('id', $message->id)
            ->assertJsonPath('to', '+15551234567')
            ->assertJsonPath('content', 'Your code is 123456')
            ->assertJsonPath('payload.content', 'Your code is 123456');
    }

    public function test_the_destroy_endpoint_clears_all_messages(): void
    {
        $this->makeMessage();
        $this->makeMessage();

        $this->assertSame(2, Eavesdrop::query()->count());

        $this->delete('/eavesdrop')
            ->assertOk()
            ->assertJsonPath('cleared', true);

        $this->assertSame(0, Eavesdrop::query()->count());
    }
}
