<?php

namespace App\Actions\PowerX;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\Lead;
use App\Models\Team;
use Illuminate\Support\Collection;

class BuildRenewalGrowthOpportunities
{
    public const RENEWAL_WINDOW_DAYS = 60;

    /**
     * @return array{summary: array{renewal_count: int, overdue_count: int, campaign_count: int}, renewals: array<int, array<string, mixed>>, campaigns: array<int, array<string, string>>}
     */
    public function handle(Team $team): array
    {
        $courseCatalog = $this->courseCatalog($team);
        $renewals = Certificate::query()
            ->whereBelongsTo($team)
            ->where('status', 'issued')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now()->addDays(self::RENEWAL_WINDOW_DAYS))
            ->with(['course:id,title,category', 'studentProfile:id,company_id,full_name,email,mobile', 'studentProfile.company:id,name,contact_name,email,phone'])
            ->select(['id', 'team_id', 'student_profile_id', 'course_id', 'certificate_number', 'expires_at'])
            ->orderBy('expires_at')
            ->get()
            ->map(fn (Certificate $certificate): array => $this->formatRenewal($certificate, $courseCatalog))
            ->values();
        $campaigns = $this->campaignPerformance($team);

        return [
            'summary' => [
                'renewal_count' => $renewals->count(),
                'overdue_count' => $renewals->filter(fn (array $renewal): bool => $renewal['daysUntilExpiry'] < 0)->count(),
                'campaign_count' => $campaigns->count(),
            ],
            'renewals' => $renewals->all(),
            'campaigns' => $campaigns->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function forCertificate(Certificate $certificate): array
    {
        $certificate->loadMissing([
            'course:id,title,category',
            'studentProfile:id,company_id,full_name,email,mobile',
            'studentProfile.company:id,name,contact_name,email,phone',
            'team:id,name',
        ]);

        return $this->formatRenewal($certificate, $this->courseCatalog($certificate->team));
    }

    /**
     * @return Collection<int, Course>
     */
    private function courseCatalog(Team $team): Collection
    {
        return Course::query()
            ->whereBelongsTo($team)
            ->published()
            ->select(['id', 'title', 'category', 'summary'])
            ->orderBy('title')
            ->get();
    }

    /**
     * @param  Collection<int, Course>  $courseCatalog
     * @return array<string, mixed>
     */
    private function formatRenewal(Certificate $certificate, Collection $courseCatalog): array
    {
        $studentProfile = $certificate->studentProfile;
        $company = $studentProfile?->company;

        return [
            'certificateId' => $certificate->id,
            'certificateNumber' => $certificate->certificate_number,
            'studentName' => $studentProfile?->full_name ?? 'Not linked',
            'companyName' => $company?->name,
            'coordinatorName' => $company?->contact_name,
            'courseTitle' => $certificate->course?->title ?? 'Not linked',
            'expiresAt' => $certificate->expires_at?->format('Y-m-d'),
            'daysUntilExpiry' => (int) now()->startOfDay()->diffInDays($certificate->expires_at?->copy()->startOfDay(), false),
            'recommendedCourses' => $this->recommendCourses($courseCatalog, $certificate->course),
        ];
    }

    /**
     * @param  Collection<int, Course>  $courseCatalog
     * @return array<int, array{id: int, title: string, category: string|null, summary: string|null}>
     */
    private function recommendCourses(Collection $courseCatalog, ?Course $currentCourse): array
    {
        return $courseCatalog
            ->reject(fn (Course $course): bool => $course->is($currentCourse))
            ->sortByDesc(fn (Course $course): int => $course->category === $currentCourse?->category ? 1 : 0)
            ->take(3)
            ->map(fn (Course $course): array => [
                'id' => $course->id,
                'title' => $course->title,
                'category' => $course->category,
                'summary' => $course->summary,
            ])
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, array<string, string>>
     */
    private function campaignPerformance(Team $team): Collection
    {
        return Lead::query()
            ->whereBelongsTo($team)
            ->select('source', 'campaign')
            ->selectRaw('COUNT(*) as lead_count')
            ->selectRaw("SUM(CASE WHEN status = 'qualified' THEN 1 ELSE 0 END) as qualified_count")
            ->selectRaw("SUM(CASE WHEN status = 'converted' THEN 1 ELSE 0 END) as converted_count")
            ->groupBy('source', 'campaign')
            ->orderByDesc('converted_count')
            ->orderByDesc('qualified_count')
            ->get()
            ->map(fn (Lead $lead): array => [
                'source' => $lead->source ?: 'Unattributed',
                'campaign' => $lead->campaign ?: 'Not set',
                'leadCount' => (string) (int) $lead->lead_count,
                'qualifiedCount' => (string) (int) $lead->qualified_count,
                'convertedCount' => (string) (int) $lead->converted_count,
                'conversionRate' => $this->percentage((int) $lead->converted_count, (int) $lead->lead_count),
            ]);
    }

    private function percentage(int $value, int $total): string
    {
        return $total > 0 ? number_format(($value / $total) * 100, 1).'%' : '0.0%';
    }
}
