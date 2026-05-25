<?php

use App\Actions\PowerX\UpdateLessonProgress;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\StudentProfile;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('creates and updates lesson progress for an enrolled course', function () {
    $team = Team::factory()->create();
    $course = Course::factory()->for($team)->create();
    $profile = StudentProfile::factory()->for($team)->create();
    $enrollment = Enrollment::factory()
        ->for($team)
        ->for($profile, 'studentProfile')
        ->for($course)
        ->create(['status' => 'active', 'payment_status' => 'paid']);
    $module = CourseModule::factory()->for($course)->create();
    $lesson = Lesson::factory()->for($module, 'courseModule')->create();

    $progress = app(UpdateLessonProgress::class)->handle($enrollment, $lesson, [
        'progress_percentage' => 35,
        'last_position_seconds' => 420,
        'event' => 'video_tick',
    ]);
    $updatedProgress = app(UpdateLessonProgress::class)->handle($enrollment, $lesson, [
        'progress_percentage' => 100,
        'last_position_seconds' => 1200,
        'event' => 'lesson_completed',
    ]);

    expect($progress->id)->toBe($updatedProgress->id)
        ->and(LessonProgress::count())->toBe(1)
        ->and($updatedProgress->progress_percentage)->toBe(100)
        ->and($updatedProgress->last_position_seconds)->toBe(1200)
        ->and($updatedProgress->completed_at)->not->toBeNull()
        ->and($enrollment->lessonProgress()->completed()->count())->toBe(1);
});

it('rejects lesson progress for a lesson outside the enrollment course', function () {
    $team = Team::factory()->create();
    $course = Course::factory()->for($team)->create();
    $otherCourse = Course::factory()->for($team)->create();
    $enrollment = Enrollment::factory()
        ->for($team)
        ->for($course)
        ->create();
    $otherModule = CourseModule::factory()->for($otherCourse)->create();
    $lesson = Lesson::factory()->for($otherModule, 'courseModule')->create();

    app(UpdateLessonProgress::class)->handle($enrollment, $lesson, [
        'progress_percentage' => 20,
    ]);
})->throws(ValidationException::class);
