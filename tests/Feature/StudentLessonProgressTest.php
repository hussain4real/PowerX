<?php

use App\Enums\TeamRole;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\StudentProfile;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\PowerXAccessSeeder;

beforeEach(function (): void {
    $this->seed(PowerXAccessSeeder::class);
});

test('students can update lesson progress from the portal', function (): void {
    [$user, $team, $enrollment, $lesson] = studentLessonProgressFixture();

    $this
        ->actingAs($user)
        ->from(route('student.portal', ['current_team' => $team]))
        ->patch(route('student.lesson-progress.update', [
            'current_team' => $team,
            'enrollment' => $enrollment,
            'lesson' => $lesson,
        ]), [
            'progress_percentage' => 45,
            'last_position_seconds' => 420,
            'event' => 'lesson_started',
        ])
        ->assertRedirect(route('student.portal', ['current_team' => $team]))
        ->assertSessionHasNoErrors();

    $progress = LessonProgress::query()->sole();

    expect($progress->enrollment_id)->toBe($enrollment->id)
        ->and($progress->lesson_id)->toBe($lesson->id)
        ->and($progress->progress_percentage)->toBe(45)
        ->and($progress->last_position_seconds)->toBe(420)
        ->and($progress->completed_at)->toBeNull();

    $this
        ->actingAs($user)
        ->patch(route('student.lesson-progress.update', [
            'current_team' => $team,
            'enrollment' => $enrollment,
            'lesson' => $lesson,
        ]), [
            'progress_percentage' => 100,
            'last_position_seconds' => 900,
            'event' => 'lesson_completed',
        ])
        ->assertRedirect(route('student.portal', ['current_team' => $team]))
        ->assertSessionHasNoErrors();

    $progress->refresh();

    expect($progress->progress_percentage)->toBe(100)
        ->and($progress->last_position_seconds)->toBe(900)
        ->and($progress->completed_at)->not->toBeNull();
});

test('student lesson progress updates do not regress existing progress', function (): void {
    [$user, $team, $enrollment, $lesson] = studentLessonProgressFixture();
    $progress = LessonProgress::factory()
        ->for($enrollment)
        ->for($lesson, 'lesson')
        ->create([
            'progress_percentage' => 60,
            'last_position_seconds' => 500,
            'completed_at' => null,
        ]);

    $this
        ->actingAs($user)
        ->patch(route('student.lesson-progress.update', [
            'current_team' => $team,
            'enrollment' => $enrollment,
            'lesson' => $lesson,
        ]), [
            'progress_percentage' => 1,
            'last_position_seconds' => 120,
            'event' => 'lesson_started',
        ])
        ->assertRedirect(route('student.portal', ['current_team' => $team]))
        ->assertSessionHasNoErrors();

    $progress->refresh();

    expect($progress->progress_percentage)->toBe(60)
        ->and($progress->last_position_seconds)->toBe(120);
});

test('students cannot update another students lesson progress', function (): void {
    [$owner, $team, $enrollment, $lesson] = studentLessonProgressFixture();
    $student = User::factory()->create();

    $team->members()->attach($student, ['role' => TeamRole::Member->value]);
    $student->switchTeam($team);

    StudentProfile::factory()
        ->for($team)
        ->for($student)
        ->create();

    $this
        ->actingAs($student)
        ->patch(route('student.lesson-progress.update', [
            'current_team' => $team,
            'enrollment' => $enrollment,
            'lesson' => $lesson,
        ]), [
            'progress_percentage' => 100,
            'last_position_seconds' => 300,
        ])
        ->assertForbidden();

    expect($owner->is($student))->toBeFalse()
        ->and(LessonProgress::query()->count())->toBe(0);
});

test('students cannot update progress without open paid lesson access', function (array $enrollmentOverrides): void {
    [$user, $team, $enrollment, $lesson] = studentLessonProgressFixture($enrollmentOverrides);

    $this
        ->actingAs($user)
        ->patch(route('student.lesson-progress.update', [
            'current_team' => $team,
            'enrollment' => $enrollment,
            'lesson' => $lesson,
        ]), [
            'progress_percentage' => 50,
            'last_position_seconds' => 240,
        ])
        ->assertForbidden();

    expect(LessonProgress::query()->count())->toBe(0);
})->with([
    'unpaid enrollment' => [['payment_status' => 'pending']],
    'pending access window' => [['access_starts_at' => now()->addDay()]],
    'expired access window' => [['access_expires_at' => now()->subDay()]],
]);

test('students cannot update progress for lessons hidden by inactive modules', function (): void {
    [$user, $team, $enrollment, $lesson] = studentLessonProgressFixture(
        moduleOverrides: ['is_active' => false],
    );

    $this
        ->actingAs($user)
        ->patch(route('student.lesson-progress.update', [
            'current_team' => $team,
            'enrollment' => $enrollment,
            'lesson' => $lesson,
        ]), [
            'progress_percentage' => 50,
            'last_position_seconds' => 240,
        ])
        ->assertForbidden();

    expect(LessonProgress::query()->count())->toBe(0);
});

/**
 * @param  array<string, mixed>  $enrollmentOverrides
 * @param  array<string, mixed>  $moduleOverrides
 * @param  array<string, mixed>  $lessonOverrides
 * @return array{0: User, 1: Team, 2: Enrollment, 3: Lesson}
 */
function studentLessonProgressFixture(
    array $enrollmentOverrides = [],
    array $moduleOverrides = [],
    array $lessonOverrides = [],
): array {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $profile = StudentProfile::factory()
        ->for($team)
        ->for($user)
        ->create();
    $course = Course::factory()
        ->for($team)
        ->create(['title' => 'Student LMS Course']);
    $module = CourseModule::factory()
        ->for($course)
        ->create(array_merge([
            'title' => 'Student LMS module',
            'is_active' => true,
        ], $moduleOverrides));
    $lesson = Lesson::factory()
        ->for($module, 'courseModule')
        ->create(array_merge([
            'title' => 'Student LMS lesson',
            'content' => 'Read this dashboard lesson before marking it complete.',
            'is_active' => true,
        ], $lessonOverrides));
    $enrollment = Enrollment::factory()
        ->for($team)
        ->for($profile, 'studentProfile')
        ->for($course)
        ->create(array_merge([
            'status' => 'active',
            'payment_status' => 'paid',
            'access_starts_at' => now()->subDay(),
            'access_expires_at' => now()->addMonth(),
        ], $enrollmentOverrides));

    return [$user, $team, $enrollment, $lesson];
}
