<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CoursePackage;
use Inertia\Inertia;
use Inertia\Response;

class WelcomeController extends Controller
{
    public function __invoke(): Response
    {
        $featuredCourses = Course::query()
            ->published()
            ->with([
                'packages' => fn ($query) => $query->active()->orderBy('price'),
            ])
            ->withCount(['modules', 'enrollments'])
            ->orderByDesc('is_featured')
            ->latest('published_at')
            ->latest()
            ->limit(3)
            ->get();

        $leadCourseOptions = Course::query()
            ->published()
            ->select(['id', 'title'])
            ->orderBy('title')
            ->get();

        return Inertia::render('Welcome', [
            'featuredCourses' => $featuredCourses
                ->map(fn (Course $course) => $this->featuredCourse($course))
                ->values(),
            'leadCourseOptions' => $leadCourseOptions
                ->map(fn (Course $course) => [
                    'id' => $course->id,
                    'title' => $course->title,
                ])
                ->values(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function featuredCourse(Course $course): array
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
            'packages' => $course->packages
                ->map(fn (CoursePackage $package) => [
                    'id' => $package->id,
                    'name' => $package->name,
                    'currency' => $package->currency,
                    'price' => $package->price,
                    'discountPrice' => $package->discount_price,
                    'validityDays' => $package->validity_days,
                    'includesCertificate' => $package->includes_certificate,
                ])
                ->values(),
        ];
    }
}
