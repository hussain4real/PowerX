<?php

namespace App\Ai\Tools;

use App\Models\Course;
use App\Models\CoursePackage;
use App\Models\TrainingBatch;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class SearchPowerXCourseCatalog implements Tool
{
    /**
     * Get the tool name exposed to AI providers.
     */
    public function name(): string
    {
        return 'search_powerx_course_catalog';
    }

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Search approved published PowerX courses, active packages, and scheduled batches from the application database.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $query = trim((string) $request->string('query'));
        $courseId = $request->integer('course_id');
        $limit = max(1, min($request->integer('limit', 5), 10));

        $courses = Course::query()
            ->select(['id', 'team_id', 'title', 'slug', 'category', 'summary', 'delivery_mode', 'currency', 'base_price', 'validity_days'])
            ->published()
            ->when($courseId > 0, fn (Builder $builder) => $builder->whereKey($courseId))
            ->when($query !== '', function (Builder $builder) use ($query): void {
                $builder->where(function (Builder $builder) use ($query): void {
                    $builder
                        ->where('title', 'like', "%{$query}%")
                        ->orWhere('category', 'like', "%{$query}%")
                        ->orWhere('summary', 'like', "%{$query}%");
                });
            })
            ->with([
                'packages' => fn ($builder) => $builder
                    ->select(['id', 'course_id', 'name', 'currency', 'price', 'discount_price', 'validity_days', 'includes_certificate', 'allows_free_preview'])
                    ->active()
                    ->orderBy('price')
                    ->limit(3),
                'trainingBatches' => fn ($builder) => $builder
                    ->select(['id', 'course_id', 'name', 'delivery_mode', 'venue', 'capacity', 'status', 'starts_at', 'ends_at'])
                    ->where('status', 'scheduled')
                    ->where('starts_at', '>=', now())
                    ->orderBy('starts_at')
                    ->limit(3),
            ])
            ->orderBy('title')
            ->limit($limit)
            ->get();

        return json_encode([
            'source' => 'powerx_database',
            'scope' => 'published_courses_active_packages_scheduled_batches',
            'query' => $query,
            'courseId' => $courseId > 0 ? $courseId : null,
            'message' => $courses->isEmpty() ? 'No approved published courses matched the lookup.' : 'Approved published course matches are available.',
            'courses' => $courses->map(fn (Course $course): array => $this->coursePayload($course))->values()->all(),
        ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->description('Optional course title, category, or summary search text.'),
            'course_id' => $schema->integer()->description('Optional exact PowerX course ID to inspect.'),
            'limit' => $schema->integer()->min(1)->max(10)->description('Maximum number of approved courses to return.'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function coursePayload(Course $course): array
    {
        return [
            'id' => $course->id,
            'title' => $course->title,
            'slug' => $course->slug,
            'category' => $course->category,
            'summary' => $course->summary,
            'deliveryMode' => $course->delivery_mode,
            'currency' => $course->currency,
            'basePrice' => (float) $course->base_price,
            'validityDays' => $course->validity_days,
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
            'scheduledBatches' => $course->trainingBatches
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
        ];
    }
}
