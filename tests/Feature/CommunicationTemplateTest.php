<?php

use App\Actions\PowerX\CreateCommunicationFromTemplate;
use App\Actions\PowerX\RenderCommunicationTemplate;
use App\Models\Communication;

it('renders every configured communication template with email and whatsapp copy', function (string $templateKey, array $context, string $expectedSubject): void {
    $rendered = app(RenderCommunicationTemplate::class)->handle($templateKey, $context);

    expect($rendered['key'])->toBe($templateKey)
        ->and($rendered['subject'])->toContain($expectedSubject)
        ->and($rendered['message'])->not->toContain('{{')
        ->and($rendered['whatsappText'])->not->toContain('{{')
        ->and($rendered['whatsappUrl'])->toStartWith('https://wa.me/97450112233?text=');
})->with([
    'registration confirmation' => ['registration_confirmation', [
        'student_name' => 'Aisha Khan',
        'course_title' => 'Kahramaa Exam Preparation',
        'recipient_phone' => '+974 5011 2233',
    ], 'registration received'],
    'payment reminder' => ['payment_reminder', [
        'student_name' => 'Aisha Khan',
        'course_title' => 'Kahramaa Exam Preparation',
        'amount_due' => 'QAR 2,500',
        'recipient_phone' => '+974 5011 2233',
    ], 'Payment reminder'],
    'class reminder' => ['class_reminder', [
        'student_name' => 'Aisha Khan',
        'session_title' => 'Practical wiring lab',
        'session_time' => 'Monday 7 PM',
        'venue' => 'PowerX Training Center',
        'recipient_phone' => '+974 5011 2233',
    ], 'Class reminder'],
    'class schedule changed' => ['class_schedule_changed', [
        'student_name' => 'Aisha Khan',
        'session_title' => 'Practical wiring lab',
        'previous_session_time' => 'Monday 7 PM',
        'new_session_time' => 'Tuesday 7 PM',
        'venue' => 'PowerX Training Center',
        'reason' => 'Instructor availability',
        'recipient_phone' => '+974 5011 2233',
    ], 'schedule update'],
    'class cancelled' => ['class_cancelled', [
        'student_name' => 'Aisha Khan',
        'session_title' => 'Practical wiring lab',
        'session_time' => 'Monday 7 PM',
        'reason' => 'Venue maintenance',
        'recipient_phone' => '+974 5011 2233',
    ], 'class cancelled'],
    'batch transfer' => ['batch_transfer', [
        'student_name' => 'Aisha Khan',
        'course_title' => 'Kahramaa Exam Preparation',
        'batch_name' => 'PX-WKND-02',
        'first_session_time' => 'Saturday 9 AM',
        'venue' => 'PowerX Training Center',
        'reason' => 'Student requested weekend batch',
        'recipient_phone' => '+974 5011 2233',
    ], 'batch transfer'],
    'make up class' => ['make_up_class', [
        'student_name' => 'Aisha Khan',
        'session_title' => 'Practical wiring lab',
        'session_time' => 'Wednesday 7 PM',
        'venue' => 'PowerX Training Center',
        'reason' => 'Excused absence',
        'recipient_phone' => '+974 5011 2233',
    ], 'make-up class'],
    'certificate issued' => ['certificate_issued', [
        'student_name' => 'Aisha Khan',
        'certificate_number' => 'PX-CERT-2026-001',
        'course_title' => 'Kahramaa Exam Preparation',
        'recipient_phone' => '+974 5011 2233',
    ], 'certificate is ready'],
    'renewal reminder' => ['renewal_reminder', [
        'student_name' => 'Aisha Khan',
        'course_title' => 'Kahramaa Exam Preparation',
        'expiry_date' => '31 May 2027',
        'recipient_phone' => '+974 5011 2233',
    ], 'renewal reminder'],
    'lead follow up' => ['lead_follow_up', [
        'lead_name' => 'Aisha Khan',
        'course_title' => 'Kahramaa Exam Preparation',
        'recipient_phone' => '+974 5011 2233',
    ], 'course follow-up'],
]);

it('renders templates without a whatsapp link when no recipient phone is supplied', function (): void {
    $rendered = app(RenderCommunicationTemplate::class)->handle('lead_follow_up', [
        'lead_name' => 'Aisha Khan',
        'course_title' => 'Kahramaa Exam Preparation',
    ]);

    expect($rendered['whatsappUrl'])->toBeNull()
        ->and($rendered['message'])->toContain('Aisha Khan');
});

it('rejects unknown templates and missing placeholders', function (): void {
    app(RenderCommunicationTemplate::class)->handle('unknown_template');
})->throws(InvalidArgumentException::class, 'Unknown communication template');

it('requires every placeholder value before rendering', function (): void {
    app(RenderCommunicationTemplate::class)->handle('payment_reminder', [
        'student_name' => 'Aisha Khan',
        'course_title' => 'Kahramaa Exam Preparation',
    ]);
})->throws(InvalidArgumentException::class, 'Missing communication template value [amount_due]');

it('creates draft communication records from templates', function (): void {
    $communication = app(CreateCommunicationFromTemplate::class)->handle('lead_follow_up', [
        'lead_name' => 'Aisha Khan',
        'course_title' => 'Kahramaa Exam Preparation',
        'recipient_phone' => '+974 5011 2233',
    ], [
        'channel' => Communication::CHANNEL_WHATSAPP,
        'status' => Communication::STATUS_SCHEDULED,
        'metadata' => ['source' => 'filament-preview'],
    ]);

    expect($communication)->toBeInstanceOf(Communication::class)
        ->and($communication->channel)->toBe(Communication::CHANNEL_WHATSAPP)
        ->and($communication->template_key)->toBe('lead_follow_up')
        ->and($communication->status)->toBe(Communication::STATUS_SCHEDULED)
        ->and($communication->message)->toContain('PowerX is following up')
        ->and($communication->metadata['source'])->toBe('filament-preview')
        ->and($communication->metadata['template_context']['lead_name'])->toBe('Aisha Khan')
        ->and($communication->metadata['whatsapp_url'])->toStartWith('https://wa.me/97450112233?text=');
});
