<?php

namespace App\Actions\PowerX;

use App\Models\Certificate;
use App\Models\Communication;
use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\TrainingSession;
use Carbon\CarbonInterface;

class ScheduleLifecycleCommunications
{
    public function __construct(
        private readonly CreateCommunicationFromTemplate $createCommunicationFromTemplate,
        private readonly CreateRenewalReminderCommunication $createRenewalReminderCommunication,
    ) {}

    /**
     * @return array{payment_reminders: int, class_reminders: int, certificate_ready: int, renewal_reminders: int, registration_confirmations: int, lead_follow_ups: int}
     */
    public function handle(?CarbonInterface $asOf = null): array
    {
        if (! config('powerx_notifications.lifecycle.enabled', true)) {
            return $this->emptyCounts();
        }

        $asOf ??= now();

        return [
            'payment_reminders' => $this->schedulePaymentReminders($asOf),
            'class_reminders' => $this->scheduleClassReminders($asOf),
            'certificate_ready' => $this->scheduleCertificateReadyMessages($asOf),
            'renewal_reminders' => $this->scheduleRenewalReminders($asOf),
            'registration_confirmations' => $this->scheduleRegistrationConfirmations($asOf),
            'lead_follow_ups' => $this->scheduleLeadFollowUps($asOf),
        ];
    }

    /**
     * @return array{payment_reminders: int, class_reminders: int, certificate_ready: int, renewal_reminders: int, registration_confirmations: int, lead_follow_ups: int}
     */
    private function emptyCounts(): array
    {
        return [
            'payment_reminders' => 0,
            'class_reminders' => 0,
            'certificate_ready' => 0,
            'renewal_reminders' => 0,
            'registration_confirmations' => 0,
            'lead_follow_ups' => 0,
        ];
    }

    private function schedulePaymentReminders(CarbonInterface $asOf): int
    {
        $cutoff = $asOf->copy()->addDays((int) config('powerx_notifications.lifecycle.payment_reminder_days_before_due', 3));
        $scheduled = 0;

        Invoice::query()
            ->whereIn('status', ['issued', 'partial'])
            ->whereNull('paid_at')
            ->whereNotNull('due_at')
            ->where('due_at', '<=', $cutoff)
            ->with(['enrollment.course', 'studentProfile.company', 'company'])
            ->each(function (Invoice $invoice) use ($asOf, &$scheduled): void {
                if ($this->communicationExists('payment_reminder', 'invoice_id', $invoice->id)) {
                    return;
                }

                $studentProfile = $invoice->studentProfile;
                $recipientPhone = $studentProfile?->mobile ?: $invoice->company?->phone ?: $studentProfile?->company?->phone;

                $this->createCommunicationFromTemplate->handle('payment_reminder', [
                    'student_name' => $studentProfile?->full_name ?? $invoice->company?->name ?? 'PowerX learner',
                    'course_title' => $invoice->enrollment?->course?->title ?? 'PowerX course',
                    'amount_due' => $invoice->currency.' '.number_format((float) $invoice->total, 2),
                    'recipient_phone' => $recipientPhone,
                    'recipient_email' => $studentProfile?->email ?: $invoice->company?->email,
                ], [
                    'team_id' => $invoice->team_id,
                    'student_profile_id' => $invoice->student_profile_id,
                    'company_id' => $invoice->company_id,
                    'channel' => $this->channelFor($recipientPhone),
                    'status' => Communication::STATUS_SCHEDULED,
                    'scheduled_at' => $asOf,
                    'metadata' => [
                        'automation' => 'lifecycle',
                        'invoice_id' => $invoice->id,
                        'enrollment_id' => $invoice->enrollment_id,
                    ],
                ]);

                $scheduled++;
            });

        return $scheduled;
    }

    private function scheduleClassReminders(CarbonInterface $asOf): int
    {
        $hours = (int) config('powerx_notifications.lifecycle.class_reminder_hours_before_start', 24);
        $windowEnd = $asOf->copy()->addHours($hours);
        $scheduled = 0;

        TrainingSession::query()
            ->where('status', 'scheduled')
            ->whereBetween('starts_at', [$asOf, $windowEnd])
            ->with(['trainingBatch.course', 'attendanceRecords.enrollment.studentProfile.company'])
            ->each(function (TrainingSession $trainingSession) use ($hours, &$scheduled): void {
                foreach ($trainingSession->attendanceRecords as $attendanceRecord) {
                    if ($this->communicationExists('class_reminder', 'attendance_record_id', $attendanceRecord->id)) {
                        continue;
                    }

                    $enrollment = $attendanceRecord->enrollment;
                    $studentProfile = $enrollment->studentProfile;
                    $recipientPhone = $studentProfile?->mobile ?: $studentProfile?->company?->phone;

                    $this->createCommunicationFromTemplate->handle('class_reminder', [
                        'student_name' => $studentProfile?->full_name ?? 'PowerX learner',
                        'session_title' => $trainingSession->title,
                        'session_time' => $trainingSession->starts_at?->format('d M Y H:i') ?? 'Not set',
                        'venue' => $trainingSession->venue ?? $trainingSession->trainingBatch?->venue ?? 'PowerX training venue',
                        'recipient_phone' => $recipientPhone,
                        'recipient_email' => $studentProfile?->email,
                    ], [
                        'team_id' => $attendanceRecord->team_id,
                        'student_profile_id' => $enrollment->student_profile_id,
                        'company_id' => $enrollment->company_id,
                        'channel' => $this->channelFor($recipientPhone),
                        'status' => Communication::STATUS_SCHEDULED,
                        'scheduled_at' => $trainingSession->starts_at?->copy()->subHours($hours),
                        'metadata' => [
                            'automation' => 'lifecycle',
                            'training_session_id' => $trainingSession->id,
                            'attendance_record_id' => $attendanceRecord->id,
                            'enrollment_id' => $enrollment->id,
                            'course_id' => $trainingSession->trainingBatch?->course_id,
                        ],
                    ]);

                    $scheduled++;
                }
            });

        return $scheduled;
    }

    private function scheduleCertificateReadyMessages(CarbonInterface $asOf): int
    {
        $scheduled = 0;

        Certificate::query()
            ->where('status', 'issued')
            ->whereNotNull('issued_at')
            ->where('issued_at', '<=', $asOf)
            ->with(['course', 'studentProfile.company'])
            ->each(function (Certificate $certificate) use ($asOf, &$scheduled): void {
                if ($this->communicationExists('certificate_issued', 'certificate_id', $certificate->id)) {
                    return;
                }

                $studentProfile = $certificate->studentProfile;
                $recipientPhone = $studentProfile?->mobile ?: $studentProfile?->company?->phone;

                $this->createCommunicationFromTemplate->handle('certificate_issued', [
                    'student_name' => $studentProfile?->full_name ?? 'PowerX graduate',
                    'certificate_number' => $certificate->certificate_number,
                    'course_title' => $certificate->course?->title ?? 'PowerX course',
                    'recipient_phone' => $recipientPhone,
                    'recipient_email' => $studentProfile?->email,
                ], [
                    'team_id' => $certificate->team_id,
                    'student_profile_id' => $certificate->student_profile_id,
                    'channel' => $this->channelFor($recipientPhone),
                    'status' => Communication::STATUS_SCHEDULED,
                    'scheduled_at' => $asOf,
                    'metadata' => [
                        'automation' => 'lifecycle',
                        'certificate_id' => $certificate->id,
                        'enrollment_id' => $certificate->enrollment_id,
                    ],
                ]);

                $scheduled++;
            });

        return $scheduled;
    }

    private function scheduleRenewalReminders(CarbonInterface $asOf): int
    {
        $days = (int) config('powerx_notifications.lifecycle.renewal_reminder_days_before_expiry', 30);
        $windowEnd = $asOf->copy()->addDays($days);
        $scheduled = 0;

        Certificate::query()
            ->where('status', 'issued')
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [$asOf, $windowEnd])
            ->with(['course', 'studentProfile.company', 'team'])
            ->each(function (Certificate $certificate) use (&$scheduled): void {
                if ($this->communicationExists('renewal_reminder', 'certificate_id', $certificate->id)) {
                    return;
                }

                $this->createRenewalReminderCommunication->handle($certificate);
                $scheduled++;
            });

        return $scheduled;
    }

    private function scheduleRegistrationConfirmations(CarbonInterface $asOf): int
    {
        $scheduled = 0;

        Enrollment::query()
            ->where('status', Enrollment::STATUS_PENDING)
            ->with(['course', 'studentProfile.company'])
            ->each(function (Enrollment $enrollment) use ($asOf, &$scheduled): void {
                if ($this->communicationExists('registration_confirmation', 'enrollment_id', $enrollment->id)) {
                    return;
                }

                $studentProfile = $enrollment->studentProfile;
                $recipientPhone = $studentProfile?->mobile ?: $studentProfile?->company?->phone;

                $this->createCommunicationFromTemplate->handle('registration_confirmation', [
                    'student_name' => $studentProfile?->full_name ?? 'PowerX learner',
                    'course_title' => $enrollment->course?->title ?? 'PowerX course',
                    'recipient_phone' => $recipientPhone,
                    'recipient_email' => $studentProfile?->email,
                ], [
                    'team_id' => $enrollment->team_id,
                    'student_profile_id' => $enrollment->student_profile_id,
                    'company_id' => $enrollment->company_id,
                    'channel' => $this->channelFor($recipientPhone),
                    'status' => Communication::STATUS_SCHEDULED,
                    'scheduled_at' => $asOf,
                    'metadata' => [
                        'automation' => 'lifecycle',
                        'enrollment_id' => $enrollment->id,
                    ],
                ]);

                $scheduled++;
            });

        return $scheduled;
    }

    private function scheduleLeadFollowUps(CarbonInterface $asOf): int
    {
        $scheduled = 0;

        Lead::query()
            ->whereNotNull('follow_up_at')
            ->where('follow_up_at', '<=', $asOf)
            ->whereNotIn('status', [Lead::STATUS_ENROLLED, Lead::STATUS_WON, Lead::STATUS_LOST, Lead::STATUS_NOT_RESPONSIVE])
            ->with(['course', 'company'])
            ->each(function (Lead $lead) use ($asOf, &$scheduled): void {
                if ($this->communicationExists('lead_follow_up', 'lead_id', $lead->id)) {
                    return;
                }

                $this->createCommunicationFromTemplate->handle('lead_follow_up', [
                    'lead_name' => $lead->name,
                    'course_title' => $lead->course?->title ?? $lead->course_interest ?? 'PowerX course',
                    'recipient_phone' => $lead->phone ?: $lead->company?->phone,
                    'recipient_email' => $lead->email ?: $lead->company?->email,
                ], [
                    'team_id' => $lead->team_id,
                    'lead_id' => $lead->id,
                    'company_id' => $lead->company_id,
                    'channel' => $this->channelFor($lead->phone ?: $lead->company?->phone),
                    'status' => Communication::STATUS_SCHEDULED,
                    'scheduled_at' => $asOf,
                    'metadata' => [
                        'automation' => 'lifecycle',
                        'lead_id' => $lead->id,
                    ],
                ]);

                $scheduled++;
            });

        return $scheduled;
    }

    private function communicationExists(string $templateKey, string $metadataKey, int $metadataValue): bool
    {
        return Communication::query()
            ->where('template_key', $templateKey)
            ->where("metadata->{$metadataKey}", $metadataValue)
            ->exists();
    }

    private function channelFor(mixed $recipientPhone): string
    {
        return blank($recipientPhone) ? Communication::CHANNEL_EMAIL : Communication::CHANNEL_WHATSAPP;
    }
}
