<?php

namespace App\Actions\PowerX;

use App\Models\Certificate;
use App\Models\Communication;
use Illuminate\Support\Arr;

class CreateRenewalReminderCommunication
{
    public function __construct(
        private BuildRenewalGrowthOpportunities $buildRenewalGrowthOpportunities,
        private CreateCommunicationFromTemplate $createCommunicationFromTemplate,
    ) {}

    public function handle(Certificate $certificate): Communication
    {
        $opportunity = $this->buildRenewalGrowthOpportunities->forCertificate($certificate);
        $certificate->loadMissing(['course', 'studentProfile.company', 'team']);
        $studentProfile = $certificate->studentProfile;
        $company = $studentProfile?->company;
        $recipientPhone = $studentProfile?->mobile ?: $company?->phone;

        return $this->createCommunicationFromTemplate->handle('renewal_reminder', [
            'student_name' => $studentProfile?->full_name ?? 'PowerX graduate',
            'course_title' => $certificate->course?->title ?? 'PowerX course',
            'expiry_date' => $certificate->expires_at?->format('d M Y') ?? 'Not set',
            'recipient_phone' => $recipientPhone,
        ], [
            'team_id' => $certificate->team_id,
            'student_profile_id' => $studentProfile?->id,
            'company_id' => $company?->id,
            'channel' => blank($recipientPhone) ? Communication::CHANNEL_EMAIL : Communication::CHANNEL_WHATSAPP,
            'status' => Communication::STATUS_SCHEDULED,
            'scheduled_at' => $certificate->expires_at?->copy()->subDays(30),
            'metadata' => [
                'certificate_id' => $certificate->id,
                'certificate_number' => $certificate->certificate_number,
                'recommended_courses' => Arr::pluck($opportunity['recommendedCourses'], 'title'),
                'days_until_expiry' => $opportunity['daysUntilExpiry'],
            ],
        ]);
    }
}
