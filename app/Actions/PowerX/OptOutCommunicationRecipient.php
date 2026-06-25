<?php

namespace App\Actions\PowerX;

use App\Models\Communication;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class OptOutCommunicationRecipient
{
    public function handle(Communication $communication, ?CarbonInterface $optedOutAt = null, ?string $reason = null): int
    {
        $optedOutAt ??= now();
        $reason ??= 'Recipient opted out of automated '.$communication->channel.' communications.';
        $recipientKey = $this->recipientKey($communication);

        if ($recipientKey === null) {
            return $communication->markOptedOut($optedOutAt, $reason) ? 1 : 0;
        }

        $count = 0;

        foreach ($this->candidateCommunications($communication) as $candidate) {
            if ($this->recipientKey($candidate) !== $recipientKey) {
                continue;
            }

            if ($candidate->markOptedOut($optedOutAt, $reason)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @return Collection<int, Communication>
     */
    private function candidateCommunications(Communication $communication): Collection
    {
        return Communication::query()
            ->where('channel', $communication->channel)
            ->whereIn('status', [
                Communication::STATUS_DRAFT,
                Communication::STATUS_SCHEDULED,
                Communication::STATUS_QUEUED,
                Communication::STATUS_ACCEPTED,
                Communication::STATUS_RETRY,
                Communication::STATUS_FAILED,
            ])
            ->when(
                $communication->team_id !== null,
                fn ($query) => $query->where('team_id', $communication->team_id),
            )
            ->get();
    }

    private function recipientKey(Communication $communication): ?string
    {
        if ($communication->channel === Communication::CHANNEL_EMAIL) {
            $email = data_get($communication->metadata, 'recipient_email')
                ?? data_get($communication->metadata, 'template_context.recipient_email')
                ?? $communication->user?->email
                ?? $communication->studentProfile?->email
                ?? $communication->lead?->email
                ?? $communication->company?->email;

            return filled($email) ? str((string) $email)->lower()->toString() : null;
        }

        $phone = data_get($communication->metadata, 'recipient_phone_normalized')
            ?? data_get($communication->metadata, 'recipient_phone')
            ?? data_get($communication->metadata, 'template_context.recipient_phone')
            ?? $communication->studentProfile?->mobile
            ?? $communication->lead?->phone
            ?? $communication->company?->phone;

        $digits = str((string) $phone)->replaceMatches('/\D+/', '')->toString();

        return $digits === '' ? null : $digits;
    }
}
