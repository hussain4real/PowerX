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

it('snapshots the lesson content revision when progress starts', function () {
    $team = Team::factory()->create();
    $course = Course::factory()->for($team)->create();
    $profile = StudentProfile::factory()->for($team)->create();
    $enrollment = Enrollment::factory()
        ->for($team)
        ->for($profile, 'studentProfile')
        ->for($course)
        ->create(['status' => 'active', 'payment_status' => 'paid']);
    $module = CourseModule::factory()->for($course)->create();
    $lesson = Lesson::factory()
        ->for($module, 'courseModule')
        ->create(['content_revision' => 3]);

    $progress = app(UpdateLessonProgress::class)->handle($enrollment, $lesson, [
        'progress_percentage' => 25,
        'last_position_seconds' => 300,
    ]);

    $lesson->update([
        'content_revision' => 4,
        'content_retired_at' => now()->subDay(),
    ]);

    $updatedProgress = app(UpdateLessonProgress::class)->handle($enrollment, $lesson->refresh(), [
        'progress_percentage' => 100,
        'last_position_seconds' => 900,
    ]);

    expect($updatedProgress->id)->toBe($progress->id)
        ->and($updatedProgress->lesson_content_revision)->toBe(3)
        ->and($updatedProgress->progress_percentage)->toBe(100)
        ->and(LessonProgress::count())->toBe(1);
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
