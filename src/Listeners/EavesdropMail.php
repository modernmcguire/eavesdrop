<?php

namespace ModernMcGuire\Eavesdrop\Listeners;

use Illuminate\Mail\Events\MessageSending;
use ModernMcGuire\Eavesdrop\Eavesdrop;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;

class EavesdropMail
{
    /**
     * Handle the message sending event.
     *
     * Outbound email is logged, then optionally intercepted. Returning false
     * halts the send so the message is never delivered; returning null lets it
     * proceed.
     */
    public function handle(MessageSending $event): ?bool
    {
        if (config('eavesdrop.email.log')) {
            $this->store($event->message, $event->data['mailer'] ?? 'mail');
        }

        return config('eavesdrop.email.intercept') ? false : null;
    }

    protected function store(Email $message, string $mailer): void
    {
        $html = $this->body($message->getHtmlBody());
        $text = $this->body($message->getTextBody());

        Eavesdrop::create([
            'type' => 'email',
            'channel' => $mailer,
            'to' => $this->addresses($message->getTo()) ?: null,
            'subject' => $message->getSubject(),
            'content' => $text ?: $html,
            'payload' => [
                'from' => $this->addresses($message->getFrom()),
                'to' => $this->addresses($message->getTo()),
                'cc' => $this->addresses($message->getCc()),
                'bcc' => $this->addresses($message->getBcc()),
                'subject' => $message->getSubject(),
                'html' => $html,
                'text' => $text,
                'attachments' => $this->attachments($message),
            ],
        ]);
    }

    /**
     * @param  array<int, Address>  $addresses
     */
    protected function addresses(array $addresses): string
    {
        return collect($addresses)
            ->map(fn (Address $address) => $address->getAddress())
            ->implode(', ');
    }

    /**
     * Capture attachment metadata (filename + content type), not the raw bytes.
     *
     * @return array<int, array{filename: ?string, content_type: string}>
     */
    protected function attachments(Email $message): array
    {
        return collect($message->getAttachments())
            ->map(fn (DataPart $part) => [
                'filename' => $part->getFilename(),
                'content_type' => $part->getMediaType() . '/' . $part->getMediaSubtype(),
            ])
            ->all();
    }

    /**
     * A message body may be a string or a stream resource.
     */
    protected function body(mixed $body): ?string
    {
        if (is_resource($body)) {
            return stream_get_contents($body) ?: null;
        }

        return is_string($body) ? $body : null;
    }
}
