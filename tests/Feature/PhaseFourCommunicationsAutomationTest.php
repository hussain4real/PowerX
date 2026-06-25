<?php

use App\Actions\PowerX\ApplyCommunicationProviderWebhook;
use App\Actions\PowerX\CreateCommunicationFromTemplate;
use App\Actions\PowerX\DispatchReadyProviderCommunications;
use App\Actions\PowerX\OptOutCommunicationRecipient;
use App\Actions\PowerX\RetryCommunicationDelivery;
use App\Actions\PowerX\ScheduleLifecycleCommunications;
use App\Communications\HttpCommunicationProvider;
use App\Models\AttendanceRecord;
use App\Models\Certificate;
use App\Models\Communication;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\StudentProfile;
use App\Models\Team;
use App\Models\TrainingBatch;
use App\Models\TrainingSession;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Psr\Log\NullLogger;

it('dispatches whatsapp through a configured provider and applies webhook lifecycle updates', function (): void {
    $now = CarbonImmutable::parse('2026-06-24 09:00:00');
    $this->travelTo($now);

    config([
        'powerx_notifications.delivery.whatsapp.enabled' => true,
        'powerx_notifications.delivery.whatsapp.driver' => 'http',
        'powerx_notifications.delivery.whatsapp.endpoint' => 'https://provider.example/messages',
        'powerx_notifications.delivery.whatsapp.token' => 'secret-token',
        'powerx_notifications.provider.webhook_secret' => 'webhook-secret',
    ]);

    Http::fake([
        'provider.example/messages' => Http::response([
            'message_id' => 'wa-message-123',
            'status' => 'accepted',
        ], 202),
    ]);

    $communication = app(CreateCommunicationFromTemplate::class)->handle('lead_follow_up', [
        'lead_name' => 'Fatima Lead',
        'course_title' => 'Kahramaa Exam Preparation',
        'recipient_phone' => '+974 5011 2233',
    ], [
        'channel' => Communication::CHANNEL_WHATSAPP,
        'status' => Communication::STATUS_SCHEDULED,
        'scheduled_at' => $now->subMinute(),
    ]);

    $counts = app(DispatchReadyProviderCommunications::class)->handle($now);

    Http::assertSent(fn ($request): bool => $request->hasHeader('Authorization', 'Bearer secret-token')
        && $request['id'] === $communication->id
        && $request['recipient']['phone_normalized'] === '97450112233');

    expect($counts)->toBe([
        'accepted' => 1,
        'fallback' => 0,
        'failed' => 0,
        'opted_out' => 0,
    ])
        ->and($communication->fresh()->status)->toBe(Communication::STATUS_ACCEPTED)
        ->and(data_get($communication->fresh()->metadata, 'provider.message_id'))->toBe('wa-message-123');

    $this->postJson(route('communications.provider-webhooks.store'), [
        'provider' => 'whatsapp',
        'provider_message_id' => 'wa-message-123',
        'status' => 'delivered',
        'event_id' => 'event-delivered',
        'occurred_at' => '2026-06-24T09:03:00+00:00',
    ], [
        'X-PowerX-Webhook-Secret' => 'bad-secret',
    ])->assertForbidden();

    $this->postJson(route('communications.provider-webhooks.store'), [
        'provider' => 'whatsapp',
        'provider_message_id' => 'wa-message-123',
        'status' => 'delivered',
        'event_id' => 'event-delivered',
        'occurred_at' => '2026-06-24T09:03:00+00:00',
        'metadata' => ['provider_status' => 'delivered'],
    ], [
        'X-PowerX-Webhook-Secret' => 'webhook-secret',
    ])->assertOk()
        ->assertJsonPath('handled', true)
        ->assertJsonPath('status', Communication::STATUS_DELIVERED);

    $this->postJson(route('communications.provider-webhooks.store'), [
        'provider' => 'whatsapp',
        'communication_id' => $communication->id,
        'status' => 'read',
        'occurred_at' => '2026-06-24T09:04:00+00:00',
    ], [
        'X-PowerX-Webhook-Secret' => 'webhook-secret',
    ])->assertOk()
        ->assertJsonPath('status', Communication::STATUS_READ);

    expect($communication->fresh()->status)->toBe(Communication::STATUS_READ)
        ->and(data_get($communication->fresh()->metadata, 'provider.last_webhook.status'))->toBe('read');
});

it('keeps whatsapp click to chat fallback and blocks sms until enabled', function (): void {
    $now = CarbonImmutable::parse('2026-06-24 10:00:00');
    $this->travelTo($now);

    config([
        'powerx_notifications.delivery.whatsapp.enabled' => false,
        'powerx_notifications.delivery.sms.enabled' => false,
    ]);

    $whatsapp = app(CreateCommunicationFromTemplate::class)->handle('lead_follow_up', [
        'lead_name' => 'Aisha Lead',
        'course_title' => 'Electrical Safety',
        'recipient_phone' => '+974 5511 2233',
    ], [
        'channel' => Communication::CHANNEL_WHATSAPP,
        'status' => Communication::STATUS_SCHEDULED,
        'scheduled_at' => $now->subMinute(),
    ]);

    $sms = Communication::factory()->create([
        'channel' => Communication::CHANNEL_SMS,
        'status' => Communication::STATUS_SCHEDULED,
        'scheduled_at' => $now->subMinute(),
        'metadata' => ['recipient_phone_normalized' => '97455112233'],
    ]);

    $counts = app(DispatchReadyProviderCommunications::class)->handle($now);

    expect($counts)->toBe([
        'accepted' => 0,
        'fallback' => 1,
        'failed' => 1,
        'opted_out' => 0,
    ])
        ->and($whatsapp->fresh()->status)->toBe(Communication::STATUS_FALLBACK)
        ->and(data_get($whatsapp->fresh()->metadata, 'provider.metadata.fallback_url'))->toStartWith('https://wa.me/97455112233?text=')
        ->and($sms->fresh()->status)->toBe(Communication::STATUS_FAILED)
        ->and($sms->fresh()->failure_reason)->toContain('SMS provider is disabled');
});

it('retries transient provider failures and moves exhausted attempts to dead letter', function (): void {
    $now = CarbonImmutable::parse('2026-06-24 11:00:00');
    $this->travelTo($now);

    config([
        'powerx_notifications.delivery.whatsapp.enabled' => true,
        'powerx_notifications.delivery.whatsapp.driver' => 'http',
        'powerx_notifications.delivery.whatsapp.endpoint' => 'https://provider.example/messages',
        'powerx_notifications.provider.max_retries' => 3,
        'powerx_notifications.provider.retry_delay_minutes' => 20,
    ]);

    Http::fakeSequence()
        ->push(['message' => 'Provider unavailable'], 500)
        ->push(['message' => 'Invalid template'], 422)
        ->push(['message' => 'Provider unavailable'], 500);

    $retryable = Communication::factory()->create([
        'channel' => Communication::CHANNEL_WHATSAPP,
        'status' => Communication::STATUS_SCHEDULED,
        'scheduled_at' => $now->subMinute(),
        'retry_count' => 0,
        'metadata' => ['recipient_phone_normalized' => '97450000001'],
    ]);
    $rejected = Communication::factory()->create([
        'channel' => Communication::CHANNEL_WHATSAPP,
        'status' => Communication::STATUS_SCHEDULED,
        'scheduled_at' => $now->subMinute(),
        'retry_count' => 0,
        'metadata' => ['recipient_phone_normalized' => '97450000002'],
    ]);
    $exhausted = Communication::factory()->create([
        'channel' => Communication::CHANNEL_WHATSAPP,
        'status' => Communication::STATUS_SCHEDULED,
        'scheduled_at' => $now->subMinute(),
        'retry_count' => 3,
        'metadata' => ['recipient_phone_normalized' => '97450000003'],
    ]);

    $counts = app(DispatchReadyProviderCommunications::class)->handle($now);

    expect($counts['failed'])->toBe(3)
        ->and($retryable->fresh()->status)->toBe(Communication::STATUS_RETRY)
        ->and($retryable->fresh()->retry_count)->toBe(1)
        ->and($retryable->fresh()->retry_at?->toDateTimeString())->toBe('2026-06-24 11:20:00')
        ->and($rejected->fresh()->status)->toBe(Communication::STATUS_FAILED)
        ->and($rejected->fresh()->failure_reason)->toBe('Invalid template')
        ->and($exhausted->fresh()->status)->toBe(Communication::STATUS_DEAD_LETTER);
});

it('handles connection exceptions, opt outs, and manual retry actions', function (): void {
    $now = CarbonImmutable::parse('2026-06-24 12:00:00');
    $this->travelTo($now);

    Http::fake(fn () => throw new ConnectionException('Connection timed out.'));

    $connectionFailure = Communication::factory()->create([
        'channel' => Communication::CHANNEL_WHATSAPP,
        'metadata' => ['recipient_phone_normalized' => '97451110000'],
    ]);

    $provider = new HttpCommunicationProvider(
        provider: 'whatsapp',
        endpoint: 'https://provider.example/messages',
        token: null,
        timeout: 5,
        logger: new NullLogger,
    );

    $result = $provider->deliver($connectionFailure);

    expect($result->failureReason)->toBe('Connection timed out.')
        ->and($result->retryAt?->toDateTimeString())->toBe('2026-06-24 12:15:00');

    $team = Team::factory()->create();
    $source = Communication::factory()->for($team)->create([
        'channel' => Communication::CHANNEL_WHATSAPP,
        'status' => Communication::STATUS_ACCEPTED,
        'metadata' => ['recipient_phone_normalized' => '97455550000'],
    ]);
    $future = Communication::factory()->for($team)->create([
        'channel' => Communication::CHANNEL_WHATSAPP,
        'status' => Communication::STATUS_SCHEDULED,
        'metadata' => ['recipient_phone' => '+974 5555 0000'],
    ]);
    $differentRecipient = Communication::factory()->for($team)->create([
        'channel' => Communication::CHANNEL_WHATSAPP,
        'status' => Communication::STATUS_SCHEDULED,
        'metadata' => ['recipient_phone_normalized' => '97455559999'],
    ]);
    $missingRecipient = Communication::factory()->create([
        'channel' => Communication::CHANNEL_WHATSAPP,
        'status' => Communication::STATUS_SCHEDULED,
        'lead_id' => null,
        'student_profile_id' => null,
        'company_id' => null,
        'user_id' => null,
        'metadata' => [],
    ]);

    expect(app(OptOutCommunicationRecipient::class)->handle($source, $now, 'STOP'))->toBe(2)
        ->and($source->fresh()->status)->toBe(Communication::STATUS_OPTED_OUT)
        ->and($future->fresh()->status)->toBe(Communication::STATUS_OPTED_OUT)
        ->and($differentRecipient->fresh()->status)->toBe(Communication::STATUS_SCHEDULED)
        ->and(app(OptOutCommunicationRecipient::class)->handle($missingRecipient, $now, 'No phone'))->toBe(1)
        ->and($missingRecipient->fresh()->status)->toBe(Communication::STATUS_OPTED_OUT);

    $emailSource = Communication::factory()->for($team)->create([
        'channel' => Communication::CHANNEL_EMAIL,
        'status' => Communication::STATUS_ACCEPTED,
        'metadata' => ['recipient_email' => 'STUDENT@example.test'],
    ]);
    $emailFuture = Communication::factory()->for($team)->create([
        'channel' => Communication::CHANNEL_EMAIL,
        'status' => Communication::STATUS_SCHEDULED,
        'metadata' => ['template_context' => ['recipient_email' => 'student@example.test']],
    ]);

    expect(app(OptOutCommunicationRecipient::class)->handle($emailSource, $now, 'Email stop'))->toBe(2)
        ->and($emailFuture->fresh()->status)->toBe(Communication::STATUS_OPTED_OUT);

    $deadLetter = Communication::factory()->create([
        'channel' => Communication::CHANNEL_WHATSAPP,
        'status' => Communication::STATUS_DEAD_LETTER,
        'retry_count' => 3,
        'failed_at' => $now,
    ]);
    $optedOut = Communication::factory()->create([
        'channel' => Communication::CHANNEL_WHATSAPP,
        'status' => Communication::STATUS_OPTED_OUT,
        'opted_out_at' => $now,
    ]);

    expect(app(RetryCommunicationDelivery::class)->handle($deadLetter, $now->addMinute(), 'Retry from review'))->toBeTrue()
        ->and($deadLetter->fresh()->status)->toBe(Communication::STATUS_RETRY)
        ->and($deadLetter->fresh()->retry_count)->toBe(1)
        ->and(app(RetryCommunicationDelivery::class)->handle($optedOut))->toBeFalse();
});

it('enforces previous opt outs during provider dispatch and accepts unsigned local webhooks when no secret is configured', function (): void {
    $now = CarbonImmutable::parse('2026-06-24 13:00:00');
    $this->travelTo($now);

    config([
        'powerx_notifications.provider.webhook_secret' => null,
        'powerx_notifications.delivery.whatsapp.enabled' => true,
        'powerx_notifications.delivery.whatsapp.driver' => 'http',
        'powerx_notifications.delivery.whatsapp.endpoint' => 'https://provider.example/messages',
    ]);
    Http::fake([
        'provider.example/messages' => Http::response(['message' => 'No recipient'], 422),
    ]);

    $team = Team::factory()->create();
    Communication::factory()->for($team)->create([
        'channel' => Communication::CHANNEL_WHATSAPP,
        'status' => Communication::STATUS_OPTED_OUT,
        'opted_out_at' => $now->subDay(),
        'metadata' => ['recipient_phone_normalized' => '97456660000'],
    ]);
    $scheduled = Communication::factory()->for($team)->create([
        'channel' => Communication::CHANNEL_WHATSAPP,
        'status' => Communication::STATUS_SCHEDULED,
        'scheduled_at' => $now->subMinute(),
        'metadata' => ['recipient_phone' => '+974 5666 0000'],
    ]);
    $missingRecipient = Communication::factory()->for($team)->create([
        'channel' => Communication::CHANNEL_WHATSAPP,
        'status' => Communication::STATUS_SCHEDULED,
        'scheduled_at' => $now->subMinute(),
        'lead_id' => null,
        'student_profile_id' => null,
        'company_id' => null,
        'user_id' => null,
        'metadata' => [],
    ]);

    $counts = app(DispatchReadyProviderCommunications::class)->handle($now);

    expect($counts['opted_out'])->toBe(1)
        ->and($scheduled->fresh()->status)->toBe(Communication::STATUS_OPTED_OUT)
        ->and($missingRecipient->fresh()->status)->toBe(Communication::STATUS_FAILED);

    $this->postJson(route('communications.provider-webhooks.store'), [
        'provider' => 'whatsapp',
        'provider_message_id' => 'missing-provider-id',
        'status' => 'sent',
    ])->assertAccepted()
        ->assertJsonPath('handled', false);
    $this->postJson(route('communications.provider-webhooks.store'), [
        'provider' => 'whatsapp',
        'status' => 'sent',
    ])->assertAccepted()
        ->assertJsonPath('handled', false);
});

it('applies provider failure webhooks to retry, failed, and dead letter states', function (): void {
    $now = CarbonImmutable::parse('2026-06-24 14:00:00');
    $this->travelTo($now);

    $retryable = Communication::factory()->create([
        'channel' => Communication::CHANNEL_WHATSAPP,
        'status' => Communication::STATUS_ACCEPTED,
        'retry_count' => 0,
    ]);
    $failed = Communication::factory()->create([
        'channel' => Communication::CHANNEL_WHATSAPP,
        'status' => Communication::STATUS_ACCEPTED,
        'retry_count' => 0,
    ]);
    $deadLetter = Communication::factory()->create([
        'channel' => Communication::CHANNEL_WHATSAPP,
        'status' => Communication::STATUS_ACCEPTED,
        'retry_count' => 3,
    ]);
    $optOut = Communication::factory()->create([
        'channel' => Communication::CHANNEL_WHATSAPP,
        'status' => Communication::STATUS_ACCEPTED,
        'metadata' => ['recipient_phone_normalized' => '97458880000'],
    ]);
    $sent = Communication::factory()->create([
        'channel' => Communication::CHANNEL_WHATSAPP,
        'status' => Communication::STATUS_QUEUED,
    ]);

    $this->postJson(route('communications.provider-webhooks.store'), [
        'provider' => 'whatsapp',
        'communication_id' => $retryable->id,
        'status' => 'failed',
        'failure_reason' => 'Temporary provider issue',
        'retry_at' => '2026-06-24T14:30:00+00:00',
    ])->assertOk();
    $this->postJson(route('communications.provider-webhooks.store'), [
        'provider' => 'whatsapp',
        'communication_id' => $failed->id,
        'status' => 'failed',
        'failure_reason' => 'Recipient blocked delivery',
    ])->assertOk();
    $this->postJson(route('communications.provider-webhooks.store'), [
        'provider' => 'whatsapp',
        'communication_id' => $deadLetter->id,
        'status' => 'failed',
        'failure_reason' => 'Retries exhausted',
    ])->assertOk();
    $this->postJson(route('communications.provider-webhooks.store'), [
        'provider' => 'whatsapp',
        'communication_id' => $optOut->id,
        'status' => 'opt_out',
        'failure_reason' => 'STOP',
    ])->assertOk();
    $this->postJson(route('communications.provider-webhooks.store'), [
        'provider' => 'whatsapp',
        'communication_id' => $sent->id,
        'status' => 'sent',
    ])->assertOk();

    expect($retryable->fresh()->status)->toBe(Communication::STATUS_RETRY)
        ->and($retryable->fresh()->retry_at?->toDateTimeString())->toBe('2026-06-24 14:30:00')
        ->and($failed->fresh()->status)->toBe(Communication::STATUS_FAILED)
        ->and($deadLetter->fresh()->status)->toBe(Communication::STATUS_DEAD_LETTER)
        ->and($optOut->fresh()->status)->toBe(Communication::STATUS_OPTED_OUT)
        ->and($optOut->fresh()->opt_out_reason)->toBe('STOP')
        ->and($sent->fresh()->status)->toBe(Communication::STATUS_ACCEPTED);

    $ignored = app(ApplyCommunicationProviderWebhook::class)->handle([
        'provider' => 'whatsapp',
        'communication_id' => $failed->id,
        'status' => 'ignored_status',
        'occurred_at' => $now,
    ]);

    expect($ignored?->id)->toBe($failed->id);
});

it('schedules lifecycle reminders once and exposes the scheduler command', function (): void {
    $now = CarbonImmutable::parse('2026-06-24 08:00:00');
    $this->travelTo($now);

    $team = Team::factory()->create();
    $course = Course::factory()->for($team)->create(['title' => 'Kahramaa Exam Preparation']);
    $studentProfile = StudentProfile::factory()->for($team)->create([
        'full_name' => 'Fatima Student',
        'email' => 'fatima@example.test',
        'mobile' => '+974 5000 1111',
    ]);
    $enrollment = Enrollment::factory()->for($team)->for($course)->for($studentProfile, 'studentProfile')->create([
        'status' => Enrollment::STATUS_APPROVED,
        'payment_status' => 'pending',
    ]);
    $invoice = Invoice::factory()->for($team)->for($enrollment)->for($studentProfile, 'studentProfile')->create([
        'status' => 'issued',
        'total' => 1200,
        'due_at' => $now->addDay(),
        'paid_at' => null,
    ]);
    $batch = TrainingBatch::factory()->for($team)->for($course)->create(['venue' => 'Doha Training Lab']);
    $session = TrainingSession::factory()->for($batch, 'trainingBatch')->create([
        'status' => 'scheduled',
        'starts_at' => $now->addHours(12),
        'venue' => 'Doha Training Lab',
    ]);
    $attendanceRecord = AttendanceRecord::factory()->for($team)->for($session, 'trainingSession')->for($enrollment)->create([
        'status' => 'pending',
    ]);
    $readyCertificate = Certificate::factory()->for($team)->for($course)->for($studentProfile, 'studentProfile')->for($enrollment)->create([
        'status' => 'issued',
        'issued_at' => $now->subDay(),
        'expires_at' => $now->addYear(),
        'certificate_number' => 'PX-CERT-READY',
    ]);
    $renewalCertificate = Certificate::factory()->for($team)->for($course)->for($studentProfile, 'studentProfile')->for($enrollment)->create([
        'status' => 'issued',
        'issued_at' => null,
        'expires_at' => $now->addDays(20),
        'certificate_number' => 'PX-CERT-RENEW',
    ]);
    $pendingEnrollment = Enrollment::factory()->for($team)->for($course)->for($studentProfile, 'studentProfile')->create([
        'status' => Enrollment::STATUS_PENDING,
    ]);
    $lead = Lead::factory()->for($team)->for($course)->create([
        'name' => 'Aisha Lead',
        'phone' => '+974 5000 2222',
        'status' => Lead::STATUS_QUALIFIED,
        'follow_up_at' => $now->subHour(),
    ]);

    $counts = app(ScheduleLifecycleCommunications::class)->handle($now);

    expect($counts)->toBe([
        'payment_reminders' => 1,
        'class_reminders' => 1,
        'certificate_ready' => 1,
        'renewal_reminders' => 1,
        'registration_confirmations' => 1,
        'lead_follow_ups' => 1,
    ])
        ->and(Communication::query()->where('template_key', 'payment_reminder')->first()?->metadata['invoice_id'])->toBe($invoice->id)
        ->and(Communication::query()->where('template_key', 'class_reminder')->first()?->metadata['attendance_record_id'])->toBe($attendanceRecord->id)
        ->and(Communication::query()->where('template_key', 'certificate_issued')->first()?->metadata['certificate_id'])->toBe($readyCertificate->id)
        ->and(Communication::query()->where('template_key', 'renewal_reminder')->first()?->metadata['certificate_id'])->toBe($renewalCertificate->id)
        ->and(Communication::query()->where('template_key', 'registration_confirmation')->first()?->metadata['enrollment_id'])->toBe($pendingEnrollment->id)
        ->and(Communication::query()->where('template_key', 'lead_follow_up')->first()?->metadata['lead_id'])->toBe($lead->id);

    expect(app(ScheduleLifecycleCommunications::class)->handle($now))->toBe([
        'payment_reminders' => 0,
        'class_reminders' => 0,
        'certificate_ready' => 0,
        'renewal_reminders' => 0,
        'registration_confirmations' => 0,
        'lead_follow_ups' => 0,
    ]);

    $emailDraft = app(CreateCommunicationFromTemplate::class)->handle('lead_follow_up', [
        'lead_name' => 'No Phone Lead',
        'course_title' => 'PowerX course',
        'recipient_email' => 'lead@example.test',
    ]);

    expect($emailDraft->metadata['recipient_phone_normalized'])->toBeNull();

    config(['powerx_notifications.lifecycle.enabled' => false]);

    $this->artisan('powerx:communications:schedule-lifecycle')
        ->assertSuccessful();
});
