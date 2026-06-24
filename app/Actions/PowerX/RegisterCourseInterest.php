<?php

namespace App\Actions\PowerX;

use App\Models\Company;
use App\Models\Course;
use App\Models\CoursePackage;
use App\Models\Enrollment;
use App\Models\Lead;
use App\Models\StudentProfile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class RegisterCourseInterest
{
    public function __construct(private CreateCommunicationFromTemplate $createCommunicationFromTemplate) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Course $course, array $data): Enrollment
    {
        return DB::transaction(function () use ($course, $data) {
            $package = $this->selectedPackage($course, $data['course_package_id'] ?? null);
            $company = $this->companyFromRegistration($data, $course->team_id);
            $attribution = $this->attributionMetadata($data);
            $source = $data['source'] ?? $attribution['utm_source'] ?? 'public_registration';
            $campaign = $data['campaign'] ?? $attribution['utm_campaign'] ?? null;

            $profile = StudentProfile::create([
                'team_id' => $course->team_id,
                'company_id' => $company?->id,
                'full_name' => $data['full_name'],
                'email' => $data['email'],
                'mobile' => $data['mobile'],
                'profession' => $data['profession'] ?? null,
                'qatar_location' => $data['qatar_location'] ?? null,
                'preferred_schedule' => $data['preferred_schedule'] ?? null,
                'document_status' => 'pending',
                'metadata' => [
                    'source' => $source,
                    'campaign' => $campaign,
                    'attribution' => $attribution,
                ],
            ]);

            $lead = Lead::query()->create([
                'team_id' => $course->team_id,
                'company_id' => $company?->id,
                'course_id' => $course->id,
                'name' => $data['full_name'],
                'email' => $data['email'],
                'phone' => $data['mobile'],
                'source' => $source,
                'campaign' => $campaign,
                'status' => Lead::STATUS_PAYMENT_PENDING,
                'course_interest' => $course->title,
                'notes' => $data['message'] ?? null,
                'follow_up_at' => now()->addDay(),
                'outcome' => 'registered',
                'metadata' => [
                    'channel' => 'public_registration',
                    'student_profile_id' => $profile->id,
                    'requested_package' => $package?->name,
                    'attribution' => $attribution,
                ],
            ]);

            $enrollment = Enrollment::create([
                'team_id' => $course->team_id,
                'student_profile_id' => $profile->id,
                'company_id' => $company?->id,
                'course_id' => $course->id,
                'course_package_id' => $package?->id,
                'status' => Enrollment::STATUS_PENDING,
                'payment_status' => 'pending',
                'notes' => $data['message'] ?? null,
                'metadata' => [
                    'admission_channel' => 'public_website',
                    'requested_package' => $package?->name,
                    'lead_id' => $lead->id,
                    'source' => $source,
                    'campaign' => $campaign,
                    'attribution' => $attribution,
                ],
            ]);

            $lead->forceFill([
                'metadata' => [
                    ...($lead->metadata ?? []),
                    'enrollment_id' => $enrollment->id,
                ],
            ])->save();

            $this->createCommunicationFromTemplate->handle('registration_confirmation', [
                'student_name' => $profile->full_name,
                'course_title' => $course->title,
                'recipient_phone' => $profile->mobile,
            ], [
                'team_id' => $course->team_id,
                'lead_id' => $lead->id,
                'student_profile_id' => $profile->id,
                'company_id' => $company?->id,
                'status' => 'draft',
                'metadata' => [
                    'enrollment_id' => $enrollment->id,
                    'source' => $source,
                    'campaign' => $campaign,
                ],
            ]);

            return $enrollment;
        });
    }

    private function selectedPackage(Course $course, mixed $packageId): ?CoursePackage
    {
        if (blank($packageId)) {
            return null;
        }

        return CoursePackage::query()
            ->whereBelongsTo($course)
            ->active()
            ->findOrFail($packageId);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function companyFromRegistration(array $data, ?int $teamId): ?Company
    {
        if (blank($data['company_name'] ?? null)) {
            return null;
        }

        return Company::firstOrCreate(
            [
                'team_id' => $teamId,
                'name' => $data['company_name'],
            ],
            [
                'contact_name' => $data['full_name'],
                'email' => $data['email'],
                'phone' => $data['mobile'],
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributionMetadata(array $data): array
    {
        return array_filter(Arr::only($data, [
            'utm_source',
            'utm_medium',
            'utm_campaign',
            'utm_content',
            'utm_term',
        ]), fn (mixed $value): bool => filled($value));
    }
}
