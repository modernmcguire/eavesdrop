<?php

namespace ModernMcGuire\Eavesdrop\Listeners;

use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Support\Str;
use ModernMcGuire\Eavesdrop\Eavesdrop;

class EavesdropNotification
{
    /**
     * Handle the notification sending event.
     *
     * Watched SMS channels are logged, then optionally intercepted. Returning
     * false halts the send so the notification is never delivered; returning
     * null lets it proceed.
     */
    public function handle(NotificationSending $event): ?bool
    {
        if (! $this->watches($event->channel)) {
            return null;
        }

        if (config('eavesdrop.sms.log')) {
            $this->store($event);
        }

        return config('eavesdrop.sms.intercept') ? false : null;
    }

    protected function watches(string $channel): bool
    {
        $target = $this->normalize($channel);

        return collect(config('eavesdrop.sms.channels', []))
            ->contains(fn ($configured) => $this->normalize($configured) === $target);
    }

    protected function store(NotificationSending $event): void
    {
        $message = $this->resolveMessage($event);

        Eavesdrop::create([
            'type' => 'sms',
            'channel' => $this->normalize($event->channel),
            'to' => $this->resolveRecipient($event),
            'content' => $this->resolveContent($message),
            'notifiable_type' => is_object($event->notifiable) ? get_class($event->notifiable) : null,
            'notifiable_id' => is_object($event->notifiable) && method_exists($event->notifiable, 'getKey')
                ? $event->notifiable->getKey()
                : null,
            'notification' => get_class($event->notification),
            'payload' => $this->toArray($message),
        ]);
    }

    /**
     * Call the notification's to{Channel}() method to build the message.
     */
    protected function resolveMessage(NotificationSending $event): mixed
    {
        $method = 'to' . Str::studly($this->normalize($event->channel));

        return method_exists($event->notification, $method)
            ? $event->notification->{$method}($event->notifiable)
            : null;
    }

    protected function resolveRecipient(NotificationSending $event): ?string
    {
        $driver = $this->normalize($event->channel);

        if (! method_exists($event->notifiable, 'routeNotificationFor')) {
            return null;
        }

        $route = $event->notifiable->routeNotificationFor($driver, $event->notification);

        return is_scalar($route) ? (string) $route : null;
    }

    protected function resolveContent(mixed $message): ?string
    {
        return match (true) {
            is_string($message) => $message,
            is_object($message) && isset($message->content) => $message->content,
            is_object($message) && isset($message->text) => $message->text,
            default => null,
        };
    }

    protected function toArray(mixed $message): ?array
    {
        if (is_object($message) && method_exists($message, 'toArray')) {
            return $message->toArray();
        }

        return is_object($message) ? get_object_vars($message) : null;
    }

    /**
     * Reduce a channel name or class to a comparable driver token.
     * e.g. NotificationChannels\Twilio\TwilioChannel => "twilio"
     */
    protected function normalize(string $channel): string
    {
        return Str::of($channel)
            ->classBasename()
            ->replaceLast('Channel', '')
            ->lower()
            ->toString();
    }
}
