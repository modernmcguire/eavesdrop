<?php

namespace ModernMcGuire\Eavesdrop\Transport;

use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;

class EavesdropTransport extends AbstractTransport
{
    protected function doSend(SentMessage $message): void
    {
        //
    }

    public function __toString(): string
    {
        return 'eavesdrop';
    }
}
