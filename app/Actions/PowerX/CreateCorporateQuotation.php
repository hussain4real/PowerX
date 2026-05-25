<?php

namespace App\Actions\PowerX;

use App\Models\Company;
use App\Models\Course;
use App\Models\CoursePackage;
use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\StudentProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateCorporateQuotation
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): Invoice
    {
        return DB::transaction(function () use ($data): Invoice {
            $course = Course::query()->published()->findOrFail($data['course_id']);
            $package = $this->selectedPackage($course, $data['course_package_id'] ?? null);
            $employees = collect($data['employees'])->values();
            $quotationNumber = $this->quotationNumber();
            $unitPrice = $this->unitPrice($course, $package);

            $company = Company::query()->updateOrCreate(
                [
                    'team_id' => $course->team_id,
                    'name' => $data['company_name'],
                ],
                [
                    'contact_name' => $data['contact_name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'],
                    'address' => $data['address'] ?? null,
                    'metadata' => [
                        'source' => 'corporate_quotation_request',
                        'requested_course_id' => $course->id,
                        'requested_package_id' => $package?->id,
                        'employee_count' => $employees->count(),
                    ],
                ],
            );

            $enrollments = $employees->map(function (array $employee) use ($company, $course, $package, $quotationNumber): Enrollment {
                $profile = StudentProfile::query()->create([
                    'team_id' => $course->team_id,
                    'company_id' => $company->id,
                    'full_name' => $employee['full_name'],
                    'email' => $employee['email'] ?? null,
                    'mobile' => $employee['mobile'] ?? null,
                    'profession' => $employee['profession'] ?? null,
                    'preferred_schedule' => $employee['preferred_schedule'] ?? null,
                    'document_status' => 'pending',
                    'metadata' => [
                        'source' => 'corporate_quotation_request',
                        'quotation_number' => $quotationNumber,
                    ],
                ]);

                return Enrollment::query()->create([
                    'team_id' => $course->team_id,
                    'student_profile_id' => $profile->id,
                    'company_id' => $company->id,
                    'course_id' => $course->id,
                    'course_package_id' => $package?->id,
                    'status' => 'pending',
                    'payment_status' => 'pending',
                    'notes' => $employee['notes'] ?? null,
                    'metadata' => [
                        'admission_channel' => 'corporate_quotation',
                        'quotation_number' => $quotationNumber,
                    ],
                ]);
            });

            $subtotal = $unitPrice * $employees->count();
            $discountTotal = (float) ($data['discount_total'] ?? 0);
            $taxTotal = (float) ($data['tax_total'] ?? 0);

            return Invoice::query()->create([
                'team_id' => $course->team_id,
                'company_id' => $company->id,
                'number' => $quotationNumber,
                'type' => 'quotation',
                'status' => 'issued',
                'currency' => $package?->currency ?? $course->currency,
                'subtotal' => $subtotal,
                'discount_total' => $discountTotal,
                'tax_total' => $taxTotal,
                'total' => max(0, $subtotal - $discountTotal + $taxTotal),
                'issued_at' => now(),
                'due_at' => now()->addDays(14),
                'metadata' => [
                    'course_id' => $course->id,
                    'course_package_id' => $package?->id,
                    'employee_count' => $employees->count(),
                    'enrollment_ids' => $enrollments->pluck('id')->all(),
                    'request_notes' => $data['notes'] ?? null,
                    'line_items' => $employees
                        ->map(fn (array $employee): array => [
                            'description' => $course->title.' - '.$employee['full_name'],
                            'amount' => $unitPrice,
                        ])
                        ->all(),
                ],
            ]);
        });
    }

    private function selectedPackage(Course $course, mixed $packageId): ?CoursePackage
    {
        return blank($packageId)
            ? null
            : CoursePackage::query()
                ->whereBelongsTo($course)
                ->active()
                ->findOrFail($packageId);
    }

    private function unitPrice(Course $course, ?CoursePackage $package): float
    {
        return (float) ($package?->discount_price ?? $package?->price ?? $course->base_price);
    }

    private function quotationNumber(): string
    {
        do {
            $number = 'PX-QUO-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        } while (Invoice::query()->where('number', $number)->exists());

        return $number;
    }
}
