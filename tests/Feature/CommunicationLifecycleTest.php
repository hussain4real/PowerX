<?php

use App\Actions\PowerX\CreateCommunicationFromTemplate;
use App\Models\Communication;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Schema;

it('stores scheduler lifecycle metadata on communications', function (): void {
    $queuedAt = CarbonImmutable::parse('2026-05-25 10:00:00');
    $deliveredAt = $queuedAt->addMinutes(5);
    $failedAt = $queuedAt->addMinutes(2);
    $retryAt = $queuedAt->addHour();
    $optedOutAt = $queuedAt->addDay();

    $communication = Communication::factory()->create([
        'status' => Communication::STATUS_FAILED,
        'queued_at' => $queuedAt,
        'sent_at' => $queuedAt->addMinute(),
        'delivered_at' => $deliveredAt,
        'failed_at' => $failedAt,
        'retry_at' => $retryAt,
        'retry_count' => 2,
        'failure_reason' => 'Mailbox unavailable',
        'opted_out_at' => $optedOutAt,
        'opt_out_reason' => 'Recipient requested no automated follow-up',
    ])->refresh();

    expect(Schema::hasColumns('communications', [
        'queued_at',
        'delivered_at',
        'failed_at',
        'retry_at',
        'retry_count',
        'failure_reason',
        'opted_out_at',
        'opt_out_reason',
    ]))->toBeTrue()
        ->and($communication->queued_at?->toDateTimeString())->toBe('2026-05-25 10:00:00')
        ->and($communication->delivered_at?->toDateTimeString())->toBe('2026-05-25 10:05:00')
        ->and($communication->failed_at?->toDateTimeString())->toBe('2026-05-25 10:02:00')
        ->and($communication->retry_at?->toDateTimeString())->toBe('2026-05-25 11:00:00')
        ->and($communication->retry_count)->toBe(2)
        ->and($communication->failure_reason)->toBe('Mailbox unavailable')
        ->and($communication->opted_out_at?->toDateTimeString())->toBe('2026-05-26 10:00:00')
        ->and($communication->opt_out_reason)->toBe('Recipient requested no automated follow-up')
        ->and(Communication::channelOptions())->toMatchArray([
            Communication::CHANNEL_EMAIL => 'Email',
            Communication::CHANNEL_WHATSAPP => 'WhatsApp',
        ])
        ->and(Communication::statusOptions())->toMatchArray([
            Communication::STATUS_QUEUED => 'Queued',
            Communication::STATUS_ACCEPTED => 'Provider accepted',
            Communication::STATUS_DELIVERED => 'Delivered',
            Communication::STATUS_READ => 'Read',
            Communication::STATUS_FALLBACK => 'Manual fallback',
            Communication::STATUS_FAILED => 'Failed',
            Communication::STATUS_RETRY => 'Retry',
            Communication::STATUS_DEAD_LETTER => 'Dead letter',
            Communication::STATUS_OPTED_OUT => 'Opted out',
        ]);
});

it('preserves template assisted drafts and click to chat metadata with lifecycle fields', function (): void {
    $retryAt = CarbonImmutable::parse('2026-05-25 11:00:00');

    $communication = app(CreateCommunicationFromTemplate::class)->handle('lead_follow_up', [
        'lead_name' => 'Aisha Khan',
        'course_title' => 'Kahramaa Exam Preparation',
        'recipient_phone' => '+974 5011 2233',
    ], [
        'channel' => Communication::CHANNEL_WHATSAPP,
        'status' => Communication::STATUS_RETRY,
        'retry_at' => $retryAt,
        'retry_count' => 3,
        'failure_reason' => 'Carrier throttled during dry run',
        'metadata' => ['source' => 'scheduler-preview'],
    ])->refresh();

    expect($communication->channel)->toBe(Communication::CHANNEL_WHATSAPP)
        ->and($communication->message)->toContain('PowerX is following up')
        ->and($communication->status)->toBe(Communication::STATUS_RETRY)
        ->and($communication->retry_at?->toDateTimeString())->toBe('2026-05-25 11:00:00')
        ->and($communication->retry_count)->toBe(3)
        ->and($communication->failure_reason)->toBe('Carrier throttled during dry run')
        ->and($communication->metadata['source'])->toBe('scheduler-preview')
        ->and($communication->metadata['template_context']['lead_name'])->toBe('Aisha Khan')
        ->and($communication->metadata['whatsapp_url'])->toStartWith('https://wa.me/97450112233?text=');
});

it('records lifecycle transitions without sending through a provider', function (): void {
    $now = CarbonImmutable::parse('2026-05-25 10:00:00');
    $this->travelTo($now);

    $communication = Communication::factory()->create([
        'channel' => Communication::CHANNEL_WHATSAPP,
        'status' => Communication::STATUS_SCHEDULED,
        'scheduled_at' => $now->subMinute(),
        'sent_at' => null,
        'metadata' => ['whatsapp_url' => 'https://wa.me/97450112233?text=Hello'],
    ]);

    expect($communication->markQueued())->toBeTrue();

    $communication->refresh();
    expect($communication->status)->toBe(Communication::STATUS_QUEUED)
        ->and($communication->queued_at?->toDateTimeString())->toBe('2026-05-25 10:00:00')
        ->and($communication->metadata['whatsapp_url'])->toBe('https://wa.me/97450112233?text=Hello');

    expect($communication->markFailed('Mailbox throttled', $now->addMinutes(2)))->toBeTrue();

    $communication->refresh();
    expect($communication->status)->toBe(Communication::STATUS_FAILED)
        ->and($communication->failed_at?->toDateTimeString())->toBe('2026-05-25 10:02:00')
        ->and($communication->failure_reason)->toBe('Mailbox throttled');

    expect($communication->scheduleRetry($now->addHour(), 'Retry after throttling'))->toBeTrue();

    $communication->refresh();
    expect($communication->status)->toBe(Communication::STATUS_RETRY)
        ->and($communication->retry_at?->toDateTimeString())->toBe('2026-05-25 11:00:00')
        ->and($communication->retry_count)->toBe(1)
        ->and($communication->failure_reason)->toBe('Retry after throttling');

    expect($communication->markDelivered($now->addHours(2)))->toBeTrue();

    $communication->refresh();
    expect($communication->status)->toBe(Communication::STATUS_DELIVERED)
        ->and($communication->sent_at?->toDateTimeString())->toBe('2026-05-25 12:00:00')
        ->and($communication->delivered_at?->toDateTimeString())->toBe('2026-05-25 12:00:00')
        ->and($communication->metadata['whatsapp_url'])->toBe('https://wa.me/97450112233?text=Hello');

    expect($communication->markRead($now->addHours(3)))->toBeTrue();
    expect($communication->refresh()->status)->toBe(Communication::STATUS_READ);

    expect($communication->markDeadLetter('Retries exhausted', $now->addHours(4)))->toBeTrue();
    expect($communication->refresh()->status)->toBe(Communication::STATUS_DEAD_LETTER);
});

it('finds due scheduled and retry communications for scheduler pickup', function (): void {
    $now = CarbonImmutable::parse('2026-05-25 10:00:00');

    $dueScheduled = Communication::factory()->create([
        'status' => Communication::STATUS_SCHEDULED,
        'scheduled_at' => $now->subMinute(),
    ]);
    $immediateScheduled = Communication::factory()->create([
        'status' => Communication::STATUS_SCHEDULED,
        'scheduled_at' => null,
    ]);
    $futureScheduled = Communication::factory()->create([
        'status' => Communication::STATUS_SCHEDULED,
        'scheduled_at' => $now->addHour(),
    ]);
    $dueRetry = Communication::factory()->create([
        'status' => Communication::STATUS_RETRY,
        'retry_at' => $now->subMinute(),
    ]);
    $futureRetry = Communication::factory()->create([
        'status' => Communication::STATUS_RETRY,
        'retry_at' => $now->addHour(),
    ]);
    $queued = Communication::factory()->create([
        'status' => Communication::STATUS_QUEUED,
        'queued_at' => $now,
    ]);
    $optedOut = Communication::factory()->create([
        'status' => Communication::STATUS_SCHEDULED,
        'scheduled_at' => $now->subMinute(),
    ]);
    $optedOut->markOptedOut($now, 'WhatsApp opt-out');

    $readyCommunicationIds = Communication::query()
        ->readyForDelivery($now)
        ->pluck('id')
        ->all();

    expect($readyCommunicationIds)->toEqualCanonicalizing([
        $dueScheduled->id,
        $immediateScheduled->id,
        $dueRetry->id,
    ])
        ->and($futureScheduled->status)->toBe(Communication::STATUS_SCHEDULED)
        ->and($futureRetry->status)->toBe(Communication::STATUS_RETRY)
        ->and($queued->status)->toBe(Communication::STATUS_QUEUED)
        ->and($optedOut->refresh()->status)->toBe(Communication::STATUS_OPTED_OUT)
        ->and($optedOut->opt_out_reason)->toBe('WhatsApp opt-out');
});
