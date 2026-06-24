<?php

namespace App\Actions\PowerX;

use App\Models\CoursePackage;
use App\Models\Enrollment;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConvertLeadToEnrollment
{
    public function __construct(
        private RecordLeadActivity $recordLeadActivity,
        private CreateCommunicationFromTemplate $createCommunicationFromTemplate,
    ) {}

    public function handle(Lead $lead, User $actor, ?CoursePackage $package = null, ?string $notes = null): Enrollment
    {
        return DB::transaction(function () use ($lead, $actor, $package, $notes): Enrollment {
            $lead = Lead::query()
                ->with(['company', 'course'])
                ->whereKey($lead->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lead->course === null) {
                throw ValidationException::withMessages([
                    'course_id' => __('Choose a course before converting this lead.'),
                ]);
            }

            if ($package !== null && (int) $package->course_id !== (int) $lead->course_id) {
                throw ValidationException::withMessages([
                    'course_package_id' => __('The package must belong to the lead course.'),
                ]);
            }

            $profile = $this->studentProfileForLead($lead);
            $enrollment = Enrollment::query()->create([
                'team_id' => $lead->team_id,
                'student_profile_id' => $profile->id,
                'company_id' => $lead->company_id,
                'course_id' => $lead->course_id,
                'course_package_id' => $package?->id,
                'status' => Enrollment::STATUS_PENDING,
                'payment_status' => 'pending',
                'notes' => $notes,
                'metadata' => [
                    'admission_channel' => 'lead_conversion',
                    'lead_id' => $lead->id,
                    'lead_source' => $lead->source,
                    'lead_campaign' => $lead->campaign,
                ],
            ]);

            $lead->forceFill([
                'converted_at' => $lead->converted_at ?? now(),
                'metadata' => [
                    ...($lead->metadata ?? []),
                    'conversion' => [
                        'enrollment_id' => $enrollment->id,
                        'converted_by_id' => $actor->id,
                        'converted_at' => now()->toISOString(),
                    ],
                ],
            ])->save();

            $this->recordLeadActivity->handle(
                lead: $lead,
                actor: $actor,
                type: LeadActivity::TYPE_CONVERTED,
                title: __('Lead converted to enrollment.'),
                notes: $notes,
                nextStatus: Lead::STATUS_ENROLLED,
                outcome: 'registered',
                metadata: [
                    'enrollment_id' => $enrollment->id,
                    'course_id' => $lead->course_id,
                    'course_package_id' => $package?->id,
                ],
            );

            $this->createRegistrationConfirmation($enrollment);

            return $enrollment->refresh();
        });
    }

    private function studentProfileForLead(Lead $lead): StudentProfile
    {
        $query = StudentProfile::query()->where('team_id', $lead->team_id);

        $profile = $query
            ->when($lead->email, fn ($query, string $email) => $query->where('email', $email))
            ->when(! $lead->email && $lead->phone, fn ($query, string $phone) => $query->where('mobile', $phone))
            ->first();

        if ($profile) {
            return $profile;
        }

        return StudentProfile::query()->create([
            'team_id' => $lead->team_id,
            'company_id' => $lead->company_id,
            'full_name' => $lead->name,
            'email' => $lead->email,
            'mobile' => $lead->phone,
            'document_status' => 'pending',
            'metadata' => [
                'source' => 'lead_conversion',
                'lead_id' => $lead->id,
            ],
        ]);
    }

    private function createRegistrationConfirmation(Enrollment $enrollment): void
    {
        $student = $enrollment->studentProfile;
        $course = $enrollment->course;

        $this->createCommunicationFromTemplate->handle('registration_confirmation', [
            'student_name' => $student->full_name,
            'course_title' => $course->title,
            'recipient_phone' => $student->mobile,
        ], [
            'team_id' => $enrollment->team_id,
            'lead_id' => $enrollment->metadata['lead_id'] ?? null,
            'student_profile_id' => $student->id,
            'company_id' => $enrollment->company_id,
            'status' => 'draft',
            'metadata' => [
                'enrollment_id' => $enrollment->id,
                'source' => 'lead_conversion',
            ],
        ]);
    }
}
