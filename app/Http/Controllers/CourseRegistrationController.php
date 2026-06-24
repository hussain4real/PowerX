<?php

namespace App\Http\Controllers;

use App\Actions\PowerX\RegisterCourseInterest;
use App\Http\Requests\StoreCourseRegistrationRequest;
use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class CourseRegistrationController extends Controller
{
    public function store(Course $course, StoreCourseRegistrationRequest $request, RegisterCourseInterest $registerCourseInterest): RedirectResponse
    {
        abort_unless($course->isPublished(), 404);

        $enrollment = $registerCourseInterest->handle($course, $request->validated());

        $request->session()->put('powerx.lead_id', $enrollment->metadata['lead_id'] ?? null);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Registration request received. Our admissions team will confirm payment and schedule details.')]);

        return to_route('courses.show', ['course' => $course]);
    }
}
