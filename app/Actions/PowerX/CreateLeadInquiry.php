<?php

namespace App\Actions\PowerX;

use App\Models\Company;
use App\Models\Course;
use App\Models\Lead;

class CreateLeadInquiry
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): Lead
    {
        $course = null;

        if (! empty($data['course_id'])) {
            $course = Course::query()->published()->findOrFail($data['course_id']);
        }

        $company = $this->companyFromInquiry($data, $course?->team_id);

        return Lead::create([
            'team_id' => $course?->team_id,
            'company_id' => $company?->id,
            'course_id' => $course?->id,
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'source' => $data['source'] ?? 'website',
            'status' => 'new',
            'course_interest' => $course?->title ?? ($data['course_interest'] ?? null),
            'notes' => $data['message'] ?? null,
            'follow_up_at' => now()->addDay(),
            'metadata' => [
                'company_name' => $data['company_name'] ?? null,
                'channel' => 'public_website',
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function companyFromInquiry(array $data, ?int $teamId): ?Company
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
                'contact_name' => $data['name'],
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
            ],
        );
    }
}
