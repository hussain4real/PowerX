<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseModule;
use App\Models\CoursePackage;
use App\Models\Lesson;
use App\Support\PowerXFeatureFlags;
use Inertia\Inertia;
use Inertia\Response;

class CourseCatalogController extends Controller
{
    public function index(): Response
    {
        $courses = Course::query()
            ->published()
            ->with([
                'packages' => fn ($query) => $query->active()->orderBy('price'),
            ])
            ->withCount(['modules', 'enrollments'])
            ->latest('published_at')
            ->latest()
            ->get();

        return Inertia::render('Courses/Index', [
            'courses' => $courses->map(fn (Course $course) => $this->courseCard($course))->values(),
            'leadCourseOptions' => $courses->map(fn (Course $course) => [
                'id' => $course->id,
                'title' => $course->title,
            ])->values(),
            'aiAssistant' => $this->assistantPayload('ai_chat'),
        ]);
    }

    public function show(Course $course): Response
    {
        abort_unless($course->isPublished(), 404);

        $course->load([
            'packages' => fn ($query) => $query->active()->orderBy('price'),
            'modules' => fn ($query) => $query->active()->orderBy('sort_order'),
            'modules.lessons' => fn ($query) => $query->active()->orderBy('sort_order'),
        ])->loadCount(['modules', 'enrollments', 'questions']);

        return Inertia::render('Courses/Show', [
            'course' => $this->courseDetail($course),
            'aiAssistant' => $this->assistantPayload('ai_chat', $course),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function assistantPayload(string $source, ?Course $course = null): array
    {
        $enabled = PowerXFeatureFlags::aiAssistantIsActive();

        return [
            'enabled' => $enabled,
            'endpoint' => $enabled ? route('powerx-assistant.store') : null,
            'source' => $source,
            'courseId' => $course?->id,
            'courseTitle' => $course?->title,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function courseCard(Course $course): array
    {
        return [
            'id' => $course->id,
            'title' => $course->title,
            'slug' => $course->slug,
            'category' => $course->category,
            'summary' => $course->summary,
            'deliveryMode' => $course->delivery_mode,
            'currency' => $course->currency,
            'basePrice' => $course->base_price,
            'validityDays' => $course->validity_days,
            'isFeatured' => $course->is_featured,
            'modulesCount' => $course->modules_count,
            'enrollmentsCount' => $course->enrollments_count,
            'lowestPackagePrice' => $course->packages->min('price') ?? $course->base_price,
            'packages' => $course->packages->map(fn (CoursePackage $package) => $this->coursePackage($package))->values(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function courseDetail(Course $course): array
    {
        return [
            ...$this->courseCard($course),
            'description' => $course->description,
            'questionsCount' => $course->questions_count,
            'modules' => $course->modules->map(fn (CourseModule $module) => [
                'id' => $module->id,
                'title' => $module->title,
                'summary' => $module->summary,
                'sortOrder' => $module->sort_order,
                'lessons' => $module->lessons->map(fn (Lesson $lesson) => [
                    'id' => $lesson->id,
                    'title' => $lesson->title,
                    'lessonType' => $lesson->lesson_type,
                    'durationMinutes' => $lesson->duration_minutes,
                    'isPreview' => $lesson->is_preview,
                ])->values(),
            ])->values(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function coursePackage(CoursePackage $package): array
    {
        return [
            'id' => $package->id,
            'name' => $package->name,
            'packageType' => $package->package_type,
            'currency' => $package->currency,
            'price' => $package->price,
            'discountPrice' => $package->discount_price,
            'validityDays' => $package->validity_days,
            'maxExamAttempts' => $package->max_exam_attempts,
            'includesCertificate' => $package->includes_certificate,
            'allowsFreePreview' => $package->allows_free_preview,
        ];
    }
}
