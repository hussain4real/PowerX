<?php

use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\StudentProfile;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\PowerXAccessSeeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

beforeEach(function (): void {
    $this->seed(PowerXAccessSeeder::class);
    Storage::fake('local');
});

test('student portal exposes signed lesson media links only for paid open enrollments', function (): void {
    $this->withoutVite();

    [$user, $team] = studentLessonMediaFixture();

    $this
        ->actingAs($user)
        ->get(route('student.portal', ['current_team' => $team]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Student/Portal')
            ->where('enrollments.0.accessStatus', 'open')
            ->where('enrollments.0.modules.0.lessons.0.media.0.collectionLabel', 'Video')
            ->where('enrollments.0.modules.0.lessons.0.media.1.fileName', 'permit-guide.pdf')
            ->where('enrollments.0.modules.0.lessons.0.media.1.collectionName', 'learning-materials')
            ->where('enrollments.0.modules.0.lessons.0.media.1.humanReadableSize', '20 B')
            ->where('enrollments.1.accessStatus', 'admission_pending')
            ->where('enrollments.1.modules.0.lessons.0.media', []));
});

test('student can download owned lesson media from a signed route', function (): void {
    [$user, $team, $lesson, $media] = studentLessonMediaFixture();

    $response = $this
        ->actingAs($user)
        ->get(signedStudentLessonMediaUrl($team, $lesson, $media));

    $response
        ->assertSuccessful()
        ->assertDownload('permit-guide.pdf');

    expect($response->streamedContent())->toBe('Private training PDF');
});

test('unsigned lesson media urls are rejected', function (): void {
    [$user, $team, $lesson, $media] = studentLessonMediaFixture();

    $this
        ->actingAs($user)
        ->get(route('student.lesson-media.show', [
            'current_team' => $team,
            'lesson' => $lesson,
            'media' => $media,
        ]))
        ->assertForbidden();
});

test('students cannot download lesson media outside their paid access', function (): void {
    [$user, $team, $lesson, $media] = studentLessonMediaFixture([
        'status' => 'pending',
        'payment_status' => 'pending',
    ]);

    $this
        ->actingAs($user)
        ->get(signedStudentLessonMediaUrl($team, $lesson, $media))
        ->assertForbidden();
});

test('students cannot download media from another lesson', function (): void {
    [$user, $team, $lesson] = studentLessonMediaFixture();
    $otherLesson = Lesson::factory()
        ->for($lesson->courseModule, 'courseModule')
        ->create(['title' => 'Another private file']);
    $otherMedia = $otherLesson
        ->addMediaFromString('Other private file')
        ->usingFileName('other-file.pdf')
        ->toMediaCollection('learning-materials');

    $this
        ->actingAs($user)
        ->get(signedStudentLessonMediaUrl($team, $lesson, $otherMedia))
        ->assertNotFound();
});

/**
 * @param  array<string, mixed>  $openEnrollmentOverrides
 * @return array{0: User, 1: Team, 2: Lesson, 3: Media}
 */
function studentLessonMediaFixture(array $openEnrollmentOverrides = []): array
{
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $profile = StudentProfile::factory()
        ->for($team)
        ->for($user)
        ->create();
    $course = Course::factory()
        ->for($team)
        ->create(['title' => 'Kahramaa Private Media']);
    $module = CourseModule::factory()
        ->for($course)
        ->create(['title' => 'Private module', 'sort_order' => 1]);
    $lesson = Lesson::factory()
        ->for($module, 'courseModule')
        ->create(['title' => 'Private lesson', 'is_preview' => false, 'sort_order' => 1]);
    $lesson
        ->addMediaFromString('Private training video')
        ->setOrder(1)
        ->usingFileName('permit-video.mp4')
        ->toMediaCollection('video');
    $media = $lesson
        ->addMediaFromString('Private training PDF')
        ->setOrder(2)
        ->usingFileName('permit-guide.pdf')
        ->toMediaCollection('learning-materials');

    $lockedCourse = Course::factory()
        ->for($team)
        ->create(['title' => 'Pending Media Course']);
    $lockedModule = CourseModule::factory()
        ->for($lockedCourse)
        ->create(['title' => 'Locked module', 'sort_order' => 1]);
    $lockedLesson = Lesson::factory()
        ->for($lockedModule, 'courseModule')
        ->create(['title' => 'Locked lesson', 'is_preview' => false, 'sort_order' => 1]);
    $lockedLesson
        ->addMediaFromString('Locked training PDF')
        ->usingFileName('locked-guide.pdf')
        ->toMediaCollection('learning-materials');

    Enrollment::factory()
        ->for($team)
        ->for($profile, 'studentProfile')
        ->for($lockedCourse)
        ->create([
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);

    Enrollment::factory()
        ->for($team)
        ->for($profile, 'studentProfile')
        ->for($course)
        ->create(array_merge([
            'status' => 'active',
            'payment_status' => 'paid',
            'access_starts_at' => now()->subDay(),
            'access_expires_at' => now()->addMonth(),
        ], $openEnrollmentOverrides));

    return [$user, $team, $lesson, $media];
}

function signedStudentLessonMediaUrl(mixed $team, Lesson $lesson, Media $media): string
{
    return URL::temporarySignedRoute('student.lesson-media.show', now()->addMinutes(10), [
        'current_team' => $team,
        'lesson' => $lesson,
        'media' => $media,
    ]);
}
