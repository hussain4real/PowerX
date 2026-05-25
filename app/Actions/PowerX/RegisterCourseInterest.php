<?php

namespace App\Actions\PowerX;

use App\Models\Company;
use App\Models\Course;
use App\Models\CoursePackage;
use App\Models\Enrollment;
use App\Models\StudentProfile;
use Illuminate\Support\Facades\DB;

class RegisterCourseInterest
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Course $course, array $data): Enrollment
    {
        return DB::transaction(function () use ($course, $data) {
            $package = $this->selectedPackage($course, $data['course_package_id'] ?? null);
            $company = $this->companyFromRegistration($data, $course->team_id);

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
                    'source' => 'public_registration',
                ],
            ]);

            return Enrollment::create([
                'team_id' => $course->team_id,
                'student_profile_id' => $profile->id,
                'company_id' => $company?->id,
                'course_id' => $course->id,
                'course_package_id' => $package?->id,
                'status' => 'pending',
                'payment_status' => 'pending',
                'notes' => $data['message'] ?? null,
                'metadata' => [
                    'admission_channel' => 'public_website',
                    'requested_package' => $package?->name,
                ],
            ]);
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
}
