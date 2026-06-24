<?php

use App\Models\Course;
use App\Models\CourseModule;
use App\Models\CoursePackage;
use App\Models\Enrollment;
use App\Models\FreePreviewEvent;
use App\Models\Lesson;
use App\Models\StudentProfile;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\PowerXAccessSeeder;
use Illuminate\Support\Facades\Route;
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
            ->where('enrollments.0.modules.0.lessons.0.media.0.mediaType', 'video')
            ->where('enrollments.0.modules.0.lessons.0.media.0.inlineUrl', fn (string $url): bool => str_contains($url, 'disposition=inline'))
            ->where('enrollments.0.modules.0.lessons.0.media.1.fileName', 'permit-guide.pdf')
            ->where('enrollments.0.modules.0.lessons.0.media.1.collectionName', 'learning-materials')
            ->where('enrollments.0.modules.0.lessons.0.media.1.mediaType', 'pdf')
            ->where('enrollments.0.modules.0.lessons.0.media.1.humanReadableSize', '20 B')
            ->where('enrollments.0.modules.0.lessons.0.viewerUrl', fn (?string $url): bool => $url !== null && str_contains($url, '/student-portal/enrollments/'))
            ->where('enrollments.1.accessStatus', 'admission_pending')
            ->where('enrollments.1.modules.0.lessons.0.media', []));
});

test('student lesson media embeds stay signed without cache backed throttling', function (): void {
    $middleware = Route::getRoutes()
        ->getByName('student.lesson-media.show')
        ?->gatherMiddleware() ?? [];

    expect($middleware)->toContain('signed')
        ->and($middleware)->not->toContain('throttle:60,1');
});

test('student can open a dedicated lesson viewer with embedded media urls', function (): void {
    $this->withoutVite();

    [$user, $team, $lesson,, $enrollment] = studentLessonMediaFixture();

    $this
        ->actingAs($user)
        ->get(route('student.lessons.show', [
            'current_team' => $team,
            'enrollment' => $enrollment,
            'lesson' => $lesson,
        ]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Student/LessonViewer')
            ->where('enrollment.course.title', 'Kahramaa Private Media')
            ->where('lesson.title', 'Private lesson')
            ->where('lesson.media.0.mediaType', 'video')
            ->where('lesson.media.1.mediaType', 'pdf')
            ->where('lesson.media.1.downloadUrl', fn (string $url): bool => ! str_contains($url, 'storage/app')));
});

test('student can download owned lesson media from a signed route', function (): void {
    [$user, $team, $lesson, $media, $enrollment] = studentLessonMediaFixture();

    $response = $this
        ->actingAs($user)
        ->get(signedStudentLessonMediaUrl($team, $enrollment, $lesson, $media, 'download'));

    $response
        ->assertSuccessful()
        ->assertDownload('permit-guide.pdf');

    expect($response->streamedContent())->toBe('Private training PDF');
});

test('student can stream owned video or pdf media inline from a signed route', function (): void {
    [$user, $team, $lesson,, $enrollment] = studentLessonMediaFixture();
    $video = $lesson->getFirstMedia('video');

    $response = $this
        ->actingAs($user)
        ->get(signedStudentLessonMediaUrl($team, $enrollment, $lesson, $video, 'inline'));

    $response->assertSuccessful();

    expect($response->headers->get('content-disposition'))->toContain('inline')
        ->and($response->streamedContent())->toBe('Private training video');
});

test('unsigned lesson media urls are rejected', function (): void {
    [$user, $team, $lesson, $media, $enrollment] = studentLessonMediaFixture();

    $this
        ->actingAs($user)
        ->get(route('student.lesson-media.show', [
            'current_team' => $team,
            'enrollment' => $enrollment,
            'lesson' => $lesson,
            'media' => $media,
            'disposition' => 'download',
        ]))
        ->assertForbidden();
});

test('expired signed lesson media urls are rejected', function (): void {
    [$user, $team, $lesson, $media, $enrollment] = studentLessonMediaFixture();

    $url = URL::temporarySignedRoute('student.lesson-media.show', now()->subMinute(), [
        'current_team' => $team,
        'enrollment' => $enrollment,
        'lesson' => $lesson,
        'media' => $media,
        'disposition' => 'inline',
    ]);

    $this
        ->actingAs($user)
        ->get($url)
        ->assertForbidden();
});

test('students cannot download lesson media outside their paid access', function (): void {
    [$user, $team, $lesson, $media, $enrollment] = studentLessonMediaFixture([
        'status' => 'pending',
        'payment_status' => 'pending',
    ]);

    $this
        ->actingAs($user)
        ->get(signedStudentLessonMediaUrl($team, $enrollment, $lesson, $media))
        ->assertForbidden();
});

test('students cannot download media from another lesson', function (): void {
    [$user, $team, $lesson,, $enrollment] = studentLessonMediaFixture();
    $otherLesson = Lesson::factory()
        ->for($lesson->courseModule, 'courseModule')
        ->create(['title' => 'Another private file']);
    $otherMedia = $otherLesson
        ->addMediaFromString('Other private file')
        ->usingFileName('other-file.pdf')
        ->toMediaCollection('learning-materials');

    $this
        ->actingAs($user)
        ->get(signedStudentLessonMediaUrl($team, $enrollment, $lesson, $otherMedia))
        ->assertNotFound();
});

test('unpaid students can stream approved preview media but not paid lesson media', function (): void {
    $this->withoutVite();

    $user = User::factory()->create();
    $team = $user->currentTeam;
    $profile = StudentProfile::factory()->for($team)->for($user)->create();
    $course = Course::factory()->for($team)->create(['title' => 'Preview LMS Course']);
    $package = CoursePackage::factory()
        ->for($team)
        ->for($course)
        ->create(['allows_free_preview' => true]);
    $module = CourseModule::factory()->for($course)->create(['is_active' => true]);
    $previewLesson = Lesson::factory()
        ->for($module, 'courseModule')
        ->create(['is_preview' => true, 'is_active' => true]);
    $paidLesson = Lesson::factory()
        ->for($module, 'courseModule')
        ->create(['is_preview' => false, 'is_active' => true]);
    $previewMedia = $previewLesson
        ->addMediaFromString('Preview video')
        ->usingFileName('preview.mp4')
        ->toMediaCollection('video');
    $paidMedia = $paidLesson
        ->addMediaFromString('Paid video')
        ->usingFileName('paid.mp4')
        ->toMediaCollection('video');
    $enrollment = Enrollment::factory()
        ->for($team)
        ->for($profile, 'studentProfile')
        ->for($course)
        ->for($package, 'coursePackage')
        ->create([
            'status' => Enrollment::STATUS_PENDING,
            'payment_status' => 'pending',
        ]);

    $this
        ->actingAs($user)
        ->get(route('student.lessons.show', [
            'current_team' => $team,
            'enrollment' => $enrollment,
            'lesson' => $previewLesson,
        ]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Student/LessonViewer')
            ->where('lesson.isPreview', true)
            ->where('lesson.media.0.fileName', 'preview.mp4'));

    $this
        ->actingAs($user)
        ->get(signedStudentLessonMediaUrl($team, $enrollment, $previewLesson, $previewMedia, 'inline', 'student_preview_media'))
        ->assertSuccessful();

    expect(FreePreviewEvent::query()->whereBelongsTo($previewLesson, 'lesson')->where('source', 'student_preview_media')->exists())->toBeTrue();

    $this
        ->actingAs($user)
        ->get(route('student.lessons.show', [
            'current_team' => $team,
            'enrollment' => $enrollment,
            'lesson' => $paidLesson,
        ]))
        ->assertForbidden();

    $this
        ->actingAs($user)
        ->get(signedStudentLessonMediaUrl($team, $enrollment, $paidLesson, $paidMedia, 'inline'))
        ->assertForbidden();
});

/**
 * @param  array<string, mixed>  $openEnrollmentOverrides
 * @return array{0: User, 1: Team, 2: Lesson, 3: Media, 4: Enrollment}
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

    $enrollment = Enrollment::factory()
        ->for($team)
        ->for($profile, 'studentProfile')
        ->for($course)
        ->create(array_merge([
            'status' => 'active',
            'payment_status' => 'paid',
            'access_starts_at' => now()->subDay(),
            'access_expires_at' => now()->addMonth(),
        ], $openEnrollmentOverrides));

    return [$user, $team, $lesson, $media, $enrollment];
}

function signedStudentLessonMediaUrl(
    mixed $team,
    Enrollment $enrollment,
    Lesson $lesson,
    Media $media,
    string $disposition = 'download',
    string $source = 'paid_lesson_media',
): string {
    return URL::temporarySignedRoute('student.lesson-media.show', now()->addMinutes(10), [
        'current_team' => $team,
        'enrollment' => $enrollment,
        'lesson' => $lesson,
        'media' => $media,
        'disposition' => $disposition,
        'source' => $source,
    ]);
}
