<?php

namespace App\Actions\PowerX;

use App\Models\Course;
use App\Models\CoursePackage;
use App\Models\TrainingBatch;

class BuildAssistantKnowledgeSource
{
    /**
     * @return array<string, mixed>
     */
    public function handle(?Course $focusCourse = null): array
    {
        $courses = Course::query()
            ->published()
            ->with([
                'packages' => fn ($query) => $query->active()->orderBy('price'),
                'trainingBatches' => fn ($query) => $query
                    ->where('status', 'scheduled')
                    ->where('starts_at', '>=', now())
                    ->orderBy('starts_at')
                    ->limit(3),
            ])
            ->orderBy('title')
            ->get();

        return [
            'scope' => config('powerx_growth.ai_assistant.scope'),
            'disclaimer' => config('powerx_growth.ai_assistant.disclaimer'),
            'fallback' => config('powerx_growth.ai_assistant.approved_fallback'),
            'blockedTopics' => config('powerx_growth.ai_assistant.blocked_topics', []),
            'faq' => config('powerx_growth.ai_assistant.faq', []),
            'focusCourseId' => $focusCourse?->id,
            'courses' => $courses
                ->map(fn (Course $course): array => $this->coursePayload($course))
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function coursePayload(Course $course): array
    {
        return [
            'id' => $course->id,
            'teamId' => $course->team_id,
            'title' => $course->title,
            'slug' => $course->slug,
            'category' => $course->category,
            'summary' => $course->summary,
            'description' => $course->description,
            'deliveryMode' => $course->delivery_mode,
            'currency' => $course->currency,
            'basePrice' => (float) $course->base_price,
            'validityDays' => $course->validity_days,
            'scheduleAvailability' => $course->trainingBatches
                ->map(fn (TrainingBatch $batch): array => [
                    'id' => $batch->id,
                    'name' => $batch->name,
                    'deliveryMode' => $batch->delivery_mode,
                    'venue' => $batch->venue,
                    'capacity' => $batch->capacity,
                    'startsAt' => $batch->starts_at?->format('Y-m-d H:i'),
                    'endsAt' => $batch->ends_at?->format('Y-m-d H:i'),
                ])
                ->values()
                ->all(),
            'packages' => $course->packages
                ->map(fn (CoursePackage $package): array => [
                    'id' => $package->id,
                    'name' => $package->name,
                    'currency' => $package->currency,
                    'price' => (float) $package->price,
                    'discountPrice' => $package->discount_price === null ? null : (float) $package->discount_price,
                    'validityDays' => $package->validity_days,
                    'includesCertificate' => $package->includes_certificate,
                    'allowsFreePreview' => $package->allows_free_preview,
                ])
                ->values()
                ->all(),
        ];
    }
}
