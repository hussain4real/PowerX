<?php

use App\Actions\PowerX\DispatchReadyEmailCommunications;
use App\Jobs\DeliverEmailCommunication;
use App\Models\Communication;
use App\Models\StudentProfile;
use App\Notifications\PowerXCommunicationNotification;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

it('queues only ready email communications for database queue delivery', function (): void {
    $now = CarbonImmutable::parse('2026-05-26 10:00:00');
    $this->travelTo($now);
    Queue::fake();

    $ready = Communication::factory()->create([
        'channel' => Communication::CHANNEL_EMAIL,
        'status' => Communication::STATUS_SCHEDULED,
        'scheduled_at' => $now->subMinute(),
    ]);
    $future = Communication::factory()->create([
        'channel' => Communication::CHANNEL_EMAIL,
        'status' => Communication::STATUS_SCHEDULED,
        'scheduled_at' => $now->addHour(),
    ]);
    $whatsapp = Communication::factory()->create([
        'channel' => Communication::CHANNEL_WHATSAPP,
        'status' => Communication::STATUS_SCHEDULED,
        'scheduled_at' => $now->subMinute(),
    ]);

    $queued = app(DispatchReadyEmailCommunications::class)->handle($now, 1);

    expect($queued)->toBe(1)
        ->and($ready->fresh()->status)->toBe(Communication::STATUS_QUEUED)
        ->and($ready->fresh()->queued_at?->toDateTimeString())->toBe('2026-05-26 10:00:00')
        ->and($future->fresh()->status)->toBe(Communication::STATUS_SCHEDULED)
        ->and($whatsapp->fresh()->status)->toBe(Communication::STATUS_SCHEDULED);

    Queue::assertPushed(DeliverEmailCommunication::class, fn (DeliverEmailCommunication $job): bool => $job->communicationId === $ready->id && $job->queue === 'mail');
});

it('skips dispatch when automated email delivery is disabled', function (): void {
    Queue::fake();
    config(['powerx_notifications.delivery.email.enabled' => false]);

    Communication::factory()->create([
        'channel' => Communication::CHANNEL_EMAIL,
        'status' => Communication::STATUS_SCHEDULED,
        'scheduled_at' => now()->subMinute(),
    ]);

    expect(app(DispatchReadyEmailCommunications::class)->handle())->toBe(0);

    Queue::assertNothingPushed();
});

it('dispatches ready emails from the artisan command', function (): void {
    Queue::fake();

    $communication = Communication::factory()->create([
        'channel' => Communication::CHANNEL_EMAIL,
        'status' => Communication::STATUS_SCHEDULED,
        'scheduled_at' => now()->subMinute(),
    ]);

    $this->artisan('powerx:communications:dispatch', ['--limit' => 1])
        ->assertSuccessful();

    expect($communication->fresh()->status)->toBe(Communication::STATUS_QUEUED);

    Queue::assertPushed(DeliverEmailCommunication::class, fn (DeliverEmailCommunication $job): bool => $job->communicationId === $communication->id);
});

it('sends email communications and records delivery metadata', function (): void {
    Notification::fake();
    $studentProfile = StudentProfile::factory()->create([
        'full_name' => 'Aisha Candidate',
        'email' => 'aisha@example.test',
    ]);
    $communication = Communication::factory()
        ->for($studentProfile, 'studentProfile')
        ->create([
            'channel' => Communication::CHANNEL_EMAIL,
            'status' => Communication::STATUS_QUEUED,
            'subject' => 'PowerX class reminder',
            'message' => 'Your practical session starts at 7 PM.',
        ]);

    (new DeliverEmailCommunication($communication->id))->handle();

    Notification::assertSentOnDemand(PowerXCommunicationNotification::class, function (PowerXCommunicationNotification $notification, array $channels, object $notifiable): bool {
        return $channels === ['mail']
            && $notifiable->routes['mail'] === ['aisha@example.test' => 'Aisha Candidate']
            && $notification->toArray($notifiable) === [
                'title' => 'PowerX class reminder',
                'body' => 'Your practical session starts at 7 PM.',
                'type' => 'powerx_communication',
            ];
    });

    $mail = (new PowerXCommunicationNotification('PowerX subject', 'PowerX message'))->toMail(new stdClass);

    expect($communication->fresh()->status)->toBe(Communication::STATUS_DELIVERED)
        ->and($communication->fresh()->sent_at)->not->toBeNull()
        ->and($communication->fresh()->delivered_at)->not->toBeNull()
        ->and($mail->subject)->toBe('PowerX subject')
        ->and($mail->introLines)->toBe(['PowerX message']);
});

it('marks unsupported or incomplete email jobs as failed without sending', function (): void {
    Notification::fake();

    $whatsapp = Communication::factory()->create([
        'channel' => Communication::CHANNEL_WHATSAPP,
        'status' => Communication::STATUS_QUEUED,
    ]);
    $missingRecipient = Communication::factory()->create([
        'lead_id' => null,
        'student_profile_id' => null,
        'company_id' => null,
        'user_id' => null,
        'channel' => Communication::CHANNEL_EMAIL,
        'status' => Communication::STATUS_QUEUED,
        'metadata' => [],
    ]);

    (new DeliverEmailCommunication($whatsapp->id))->handle();
    (new DeliverEmailCommunication($missingRecipient->id))->handle();

    Notification::assertNothingSent();

    expect($whatsapp->fresh()->status)->toBe(Communication::STATUS_FAILED)
        ->and($whatsapp->fresh()->failure_reason)->toBe('Only email communications can be delivered automatically.')
        ->and($missingRecipient->fresh()->status)->toBe(Communication::STATUS_FAILED)
        ->and($missingRecipient->fresh()->failure_reason)->toBe('Email recipient address is missing.');
});

it('records queue job failures on the communication lifecycle', function (): void {
    $communication = Communication::factory()->create([
        'channel' => Communication::CHANNEL_EMAIL,
        'status' => Communication::STATUS_QUEUED,
    ]);
    $job = new DeliverEmailCommunication($communication->id);

    expect($job->backoff())->toBe([60, 300, 900]);

    $job->failed(new RuntimeException(str_repeat('x', 300)));

    expect($communication->fresh()->status)->toBe(Communication::STATUS_FAILED)
        ->and(mb_strlen((string) $communication->fresh()->failure_reason))->toBe(255);
});
