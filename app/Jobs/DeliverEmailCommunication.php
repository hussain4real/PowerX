<?php

namespace App\Jobs;

use App\Models\Communication;
use App\Notifications\PowerXCommunicationNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Throwable;

class DeliverEmailCommunication implements ShouldQueue
{
    use Queueable;

    public int $timeout = 30;

    public int $tries = 3;

    public function __construct(public int $communicationId) {}

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return array_map('intval', config('powerx_notifications.delivery.email.backoff', [60, 300, 900]));
    }

    public function handle(): void
    {
        $communication = Communication::query()
            ->with(['user:id,name,email', 'studentProfile:id,full_name,email', 'lead:id,name,email', 'company:id,name,email'])
            ->findOrFail($this->communicationId);

        if ($communication->channel !== Communication::CHANNEL_EMAIL) {
            $communication->markFailed('Only email communications can be delivered automatically.');

            return;
        }

        $email = $this->recipientEmail($communication);

        if (blank($email)) {
            $communication->markFailed('Email recipient address is missing.');

            return;
        }

        Notification::sendNow(
            Notification::route('mail', [(string) $email => $this->recipientName($communication, (string) $email)]),
            new PowerXCommunicationNotification(
                $communication->subject ?: 'PowerX update',
                $communication->message,
            ),
        );

        $communication->markDelivered();
    }

    public function failed(?Throwable $exception): void
    {
        $communication = Communication::query()->find($this->communicationId);

        if ($communication && $exception) {
            $communication->markFailed(Str::limit($exception->getMessage(), 255, ''));
        }
    }

    private function recipientEmail(Communication $communication): ?string
    {
        $email = collect([
            $communication->user?->email,
            $communication->studentProfile?->email,
            $communication->lead?->email,
            $communication->company?->email,
            data_get($communication->metadata, 'recipient_email'),
        ])->first(fn (mixed $email): bool => filled($email));

        return $email === null ? null : (string) $email;
    }

    private function recipientName(Communication $communication, string $email): string
    {
        $name = collect([
            $communication->user?->name,
            $communication->studentProfile?->full_name,
            $communication->lead?->name,
            $communication->company?->name,
            data_get($communication->metadata, 'recipient_name'),
            $email,
        ])->first(fn (mixed $name): bool => filled($name));

        return (string) $name;
    }
}
