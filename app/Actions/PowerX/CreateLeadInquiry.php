<?php

namespace App\Actions\PowerX;

use App\Models\Company;
use App\Models\Course;
use App\Models\Lead;
use Illuminate\Support\Arr;

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

        $attribution = $this->attributionMetadata($data);

        return Lead::create([
            'team_id' => $course?->team_id,
            'company_id' => $company?->id,
            'course_id' => $course?->id,
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'source' => $data['source'] ?? $attribution['utm_source'] ?? 'website',
            'campaign' => $data['campaign'] ?? $attribution['utm_campaign'] ?? null,
            'status' => Lead::STATUS_NEW,
            'course_interest' => $course?->title ?? ($data['course_interest'] ?? null),
            'notes' => $data['message'] ?? null,
            'follow_up_at' => now()->addDay(),
            'metadata' => [
                'company_name' => $data['company_name'] ?? null,
                'channel' => 'public_website',
                'attribution' => $attribution,
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
