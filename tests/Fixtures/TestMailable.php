<?php

namespace ModernMcGuire\Eavesdrop\Tests\Fixtures;

use Illuminate\Mail\Mailable;

class TestMailable extends Mailable
{
    public function build(): static
    {
        return $this->subject('Your receipt')
            ->html('<p>Thanks for your order.</p>');
    }
}
