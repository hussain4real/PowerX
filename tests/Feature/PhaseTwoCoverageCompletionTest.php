<?php

use App\Actions\PowerX\BuildStudentExamAttempt;
use App\Actions\PowerX\BuildStudentLessonViewer;
use App\Actions\PowerX\BuildStudentPortal;
use App\Actions\PowerX\RecordAssessmentConfigurationChange;
use App\Actions\PowerX\ResolveStudentLessonAccess;
use App\Actions\PowerX\StartExamAttempt;
use App\Actions\PowerX\SubmitExamAttempt;
use App\Actions\PowerX\UpdateLessonProgress;
use App\Enums\PowerXRole;
use App\Filament\Resources\CoursePackages\Pages\EditCoursePackage;
use App\Filament\Resources\Courses\Pages\EditCourse;
use App\Filament\Resources\Exams\Pages\EditExam;
use App\Http\Requests\StoreStudentExamAttemptRequest;
use App\Http\Requests\SubmitStudentExamAttemptRequest;
use App\Http\Requests\UpdateStudentExamAttemptRequest;
use App\Models\AuditEvent;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\CoursePackage;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Question;
use App\Models\StudentProfile;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\PowerXAccessSeeder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

beforeEach(function (): void {
    $this->seed(PowerXAccessSeeder::class);
});

test('student exam attempt builder falls back to submitted answers when selected question metadata is absent', function (): void {
    [$user, $team, $enrollment, $exam, $question] = phaseTwoExamContext();
    $attempt = ExamAttempt::factory()
        ->for($team)
        ->for($exam)
        ->for($enrollment)
        ->for($enrollment->studentProfile, 'studentProfile')
        ->create([
            'answers' => [
                ['question_id' => $question->id, 'answer' => 'A'],
                'ignored malformed answer',
            ],
            'metadata' => null,
            'started_at' => null,
            'submitted_at' => now(),
            'result' => 'passed',
        ]);

    $payload = app(BuildStudentExamAttempt::class)->handle($attempt);

    expect($payload['attempt']['expiresAt'])->toBeNull()
        ->and($payload['questions'][0]['id'])->toBe($question->id)
        ->and($payload['questions'][0]['answer'])->toBe(['A'])
        ->and($user->is($enrollment->studentProfile->user))->toBeTrue();
});

test('lesson viewer builder rejects malformed portal payloads and exposes adjacent lesson payloads', function (): void {
    [$user, $team, $enrollment, $lesson] = phaseTwoLessonContext();
    $nextLesson = Lesson::factory()
        ->for($lesson->courseModule, 'courseModule')
        ->create(['is_active' => true]);

    expect(fn () => phaseTwoLessonViewer(['enrollments' => 'invalid'])->handle($user, $team, $enrollment, $lesson))
        ->toThrow(NotFoundHttpException::class)
        ->and(fn () => phaseTwoLessonViewer(['enrollments' => [['id' => 999]]])->handle($user, $team, $enrollment, $lesson))
        ->toThrow(NotFoundHttpException::class)
        ->and(fn () => phaseTwoLessonViewer(['enrollments' => [['id' => $enrollment->id, 'modules' => 'invalid']]])->handle($user, $team, $enrollment, $lesson))
        ->toThrow(NotFoundHttpException::class)
        ->and(fn () => phaseTwoLessonViewer(['enrollments' => [['id' => $enrollment->id, 'modules' => [['lessons' => 'invalid']]]]])->handle($user, $team, $enrollment, $lesson))
        ->toThrow(NotFoundHttpException::class);

    $payload = phaseTwoLessonViewer([
        'profile' => ['id' => $enrollment->student_profile_id],
        'summary' => [],
        'courseCatalog' => [],
        'enrollments' => [[
            'id' => $enrollment->id,
            'modules' => [[
                'lessons' => [
                    ['id' => $lesson->id, 'title' => 'Current'],
                    ['id' => $nextLesson->id, 'title' => 'Next'],
                ],
            ]],
        ]],
    ])->handle($user, $team, $enrollment, $lesson);

    expect($payload['lesson']['title'])->toBe('Current')
        ->and($payload['nextLesson']['title'])->toBe('Next');
});

test('student portal handles downloadable lesson files and exam attempt limits without a package', function (): void {
    [$user, $team, $enrollment, $lesson] = phaseTwoLessonContext(enrollmentOverrides: ['course_package_id' => null]);
    $lesson
        ->addMediaFromString('Download-only attachment')
        ->usingFileName('archive.bin')
        ->toMediaCollection('learning-materials');
    Exam::factory()
        ->for($team)
        ->for($enrollment->course)
        ->create(['max_attempts' => 1, 'is_active' => true]);

    $portal = app(BuildStudentPortal::class)->handle($user, $team);

    expect($portal['enrollments'][0]['modules'][0]['lessons'][0]['media'][0]['mediaType'])->toBe('download')
        ->and($portal['enrollments'][0]['exams'][0]['maxAttempts'])->toBe(1)
        ->and(app(ResolveStudentLessonAccess::class)->hasPreviewAccess($enrollment, $lesson))->toBeFalse();
});

test('assessment configuration audit action skips unchanged payloads', function (): void {
    $course = Course::factory()->create();

    app(RecordAssessmentConfigurationChange::class)->handle(
        subject: $course,
        actor: null,
        before: ['requires_exam_pass_for_certificate' => true],
        after: ['requires_exam_pass_for_certificate' => true],
    );

    expect(AuditEvent::query()->where('action', 'assessment.configuration_changed')->exists())->toBeFalse();
});

test('start exam attempt applies filters, package-free limits, and max attempt validation', function (): void {
    [$user, $team, $enrollment, $exam, $question] = phaseTwoExamContext([
        'question_count' => 1,
        'randomize_questions' => false,
        'metadata' => [
            'question_topics' => ['Safety'],
            'question_difficulties' => ['standard'],
        ],
    ], ['course_package_id' => null]);
    Question::factory()
        ->for($team)
        ->for($enrollment->course)
        ->create(['topic' => 'Load', 'difficulty' => 'standard', 'is_active' => true]);

    $attempt = app(StartExamAttempt::class)->handle($exam, $enrollment);

    expect($attempt->metadata['selected_question_ids'])->toBe([$question->id])
        ->and($user->is($enrollment->studentProfile->user))->toBeTrue();

    [$maxUser, $maxTeam, $maxEnrollment, $maxExam] = phaseTwoExamContext(['max_attempts' => 1]);
    $maxEnrollment->coursePackage->update(['max_exam_attempts' => 1]);
    ExamAttempt::factory()
        ->for($maxTeam)
        ->for($maxExam)
        ->for($maxEnrollment)
        ->for($maxEnrollment->studentProfile, 'studentProfile')
        ->create();

    expect(fn () => app(StartExamAttempt::class)->handle($maxExam, $maxEnrollment))
        ->toThrow(ValidationException::class)
        ->and($maxUser->is($maxEnrollment->studentProfile->user))->toBeTrue();
});

test('submit exam attempt rejects invalid access and question payloads while supporting answer fallback selection', function (): void {
    [$user, $team, $enrollment, $exam, $question] = phaseTwoExamContext();
    $attempt = phaseTwoPendingAttempt($team, $enrollment, $exam, null);

    $submitted = app(SubmitExamAttempt::class)->handle($attempt, [
        ['question_id' => $question->id, 'answer' => ['A']],
    ]);

    expect($submitted->result)->toBe('passed');

    [$inactiveUser, $inactiveTeam, $inactiveEnrollment, $inactiveExam, $inactiveQuestion] = phaseTwoExamContext();
    $inactiveEnrollment->update(['status' => Enrollment::STATUS_PENDING, 'payment_status' => 'pending']);
    expect(fn () => app(SubmitExamAttempt::class)->handle(
        phaseTwoPendingAttempt($inactiveTeam, $inactiveEnrollment, $inactiveExam, [$inactiveQuestion->id]),
        [['question_id' => $inactiveQuestion->id, 'answer' => ['A']]],
    ))->toThrow(ValidationException::class);

    [$futureUser, $futureTeam, $futureEnrollment, $futureExam, $futureQuestion] = phaseTwoExamContext(enrollmentOverrides: ['access_starts_at' => now()->addDay()]);
    expect(fn () => app(SubmitExamAttempt::class)->handle(
        phaseTwoPendingAttempt($futureTeam, $futureEnrollment, $futureExam, [$futureQuestion->id]),
        [['question_id' => $futureQuestion->id, 'answer' => ['A']]],
    ))->toThrow(ValidationException::class);

    [$expiredUser, $expiredTeam, $expiredEnrollment, $expiredExam, $expiredQuestion] = phaseTwoExamContext(enrollmentOverrides: ['access_expires_at' => now()->subDay()]);
    expect(fn () => app(SubmitExamAttempt::class)->handle(
        phaseTwoPendingAttempt($expiredTeam, $expiredEnrollment, $expiredExam, [$expiredQuestion->id]),
        [['question_id' => $expiredQuestion->id, 'answer' => ['A']]],
    ))->toThrow(ValidationException::class);

    [$unselectedUser, $unselectedTeam, $unselectedEnrollment, $unselectedExam, $unselectedQuestion] = phaseTwoExamContext();
    $otherQuestion = Question::factory()->for($unselectedTeam)->for($unselectedEnrollment->course)->create();
    expect(fn () => app(SubmitExamAttempt::class)->handle(
        phaseTwoPendingAttempt($unselectedTeam, $unselectedEnrollment, $unselectedExam, [$unselectedQuestion->id]),
        [['question_id' => $otherQuestion->id, 'answer' => ['A']]],
    ))->toThrow(ValidationException::class);

    [$missingUser, $missingTeam, $missingEnrollment, $missingExam, $missingQuestion] = phaseTwoExamContext();
    $missingQuestion->update(['is_active' => false]);
    expect(fn () => app(SubmitExamAttempt::class)->handle(
        phaseTwoPendingAttempt($missingTeam, $missingEnrollment, $missingExam, [$missingQuestion->id]),
        [['question_id' => $missingQuestion->id, 'answer' => ['A']]],
    ))->toThrow(ValidationException::class);

    expect($user->is($enrollment->studentProfile->user))
        ->and($inactiveUser->is($inactiveEnrollment->studentProfile->user))
        ->and($futureUser->is($futureEnrollment->studentProfile->user))
        ->and($expiredUser->is($expiredEnrollment->studentProfile->user))
        ->and($unselectedUser->is($unselectedEnrollment->studentProfile->user))
        ->and($missingUser->is($missingEnrollment->studentProfile->user))
        ->toBeTrue();
});

test('lesson progress defaults missing content revisions while recording media events', function (): void {
    [$user, $team, $enrollment, $lesson] = phaseTwoLessonContext();
    $lesson->content_revision = null;

    $progress = app(UpdateLessonProgress::class)->handle($enrollment, $lesson, [
        'progress_percentage' => 25,
        'event' => 'media_downloaded',
    ]);

    expect($progress->lesson_content_revision)->toBe(1)
        ->and($progress->metadata['events'][0]['event'])->toBe('media_downloaded')
        ->and($user->currentTeam->is($team))->toBeTrue();
});

test('lesson progress preserves an existing completion timestamp', function (): void {
    [$user, $team, $enrollment, $lesson] = phaseTwoLessonContext();
    $completedAt = now()->subDay();
    $progress = LessonProgress::factory()
        ->for($enrollment)
        ->for($lesson, 'lesson')
        ->create([
            'progress_percentage' => 100,
            'completed_at' => $completedAt,
        ]);

    $updated = app(UpdateLessonProgress::class)->handle($enrollment, $lesson, [
        'progress_percentage' => 80,
    ]);

    expect($updated->completed_at->toDateTimeString())->toBe($completedAt->toDateTimeString())
        ->and($progress->fresh()->completed_at->toDateTimeString())->toBe($completedAt->toDateTimeString())
        ->and($user->currentTeam->is($team))->toBeTrue();
});

test('course and package certificate requirement edits are audited from filament save flows', function (): void {
    $manager = grantPowerXRole(User::factory()->create(), PowerXRole::Management);
    $this->actingAs($manager);
    $course = Course::factory()->for($manager->currentTeam)->create([
        'requires_attendance_for_certificate' => false,
    ]);
    $package = CoursePackage::factory()->for($manager->currentTeam)->for($course)->create([
        'requires_practical_pass_for_certificate' => false,
    ]);

    Livewire::test(EditCourse::class, ['record' => $course->getRouteKey()])
        ->fillForm([
            'team_id' => $manager->current_team_id,
            'title' => $course->title,
            'slug' => $course->slug,
            'category' => $course->category,
            'delivery_mode' => $course->delivery_mode,
            'summary' => $course->summary,
            'description' => $course->description,
            'currency' => $course->currency,
            'base_price' => $course->base_price,
            'validity_days' => $course->validity_days,
            'requires_lesson_completion_for_certificate' => true,
            'requires_exam_pass_for_certificate' => true,
            'requires_attendance_for_certificate' => true,
            'requires_practical_pass_for_certificate' => false,
            'status' => $course->status,
            'published_at' => $course->published_at?->toDateTimeString(),
            'is_featured' => $course->is_featured,
            'content_revision' => $course->content_revision,
            'metadata' => $course->metadata,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    Livewire::test(EditCoursePackage::class, ['record' => $package->id])
        ->fillForm([
            'team_id' => $manager->current_team_id,
            'course_id' => $course->id,
            'package_type' => $package->package_type,
            'name' => $package->name,
            'slug' => $package->slug,
            'currency' => $package->currency,
            'price' => $package->price,
            'discount_price' => $package->discount_price,
            'validity_days' => $package->validity_days,
            'max_exam_attempts' => $package->max_exam_attempts,
            'includes_certificate' => true,
            'requires_lesson_completion_for_certificate' => true,
            'requires_exam_pass_for_certificate' => true,
            'requires_attendance_for_certificate' => false,
            'requires_practical_pass_for_certificate' => true,
            'allows_free_preview' => $package->allows_free_preview,
            'is_active' => $package->is_active,
            'metadata' => $package->metadata,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(AuditEvent::query()
        ->where('action', 'assessment.configuration_changed')
        ->whereMorphedTo('subject', $course)
        ->where('metadata->configuration_scope', 'course_certificate_requirements')
        ->exists())->toBeTrue()
        ->and(AuditEvent::query()
            ->where('action', 'assessment.configuration_changed')
            ->whereMorphedTo('subject', $package)
            ->where('metadata->configuration_scope', 'package_certificate_requirements')
            ->exists())->toBeTrue();
});

test('filament assessment edit pages ignore unexpected record types in defensive guards', function (): void {
    $wrongRecord = Exam::factory()->create();

    expect(phaseTwoEditPageSnapshot(EditCourse::class, $wrongRecord))->toBe([])
        ->and(phaseTwoEditPageSnapshot(EditCoursePackage::class, $wrongRecord))->toBe([])
        ->and(phaseTwoEditPageSnapshot(EditExam::class, Course::factory()->create()))->toBe([]);

    phaseTwoInvokeEditPageAfterSave(EditCourse::class, $wrongRecord);
    phaseTwoInvokeEditPageAfterSave(EditCoursePackage::class, $wrongRecord);
    phaseTwoInvokeEditPageAfterSave(EditExam::class, Course::factory()->create());

    expect(AuditEvent::query()->where('action', 'assessment.configuration_changed')->count())->toBe(0);
});

test('student exam attempt form requests deny malformed submitted and expired contexts', function (): void {
    [$user, $team, $enrollment, $exam, $question] = phaseTwoExamContext();

    expect(phaseTwoFormRequest(StoreStudentExamAttemptRequest::class, null)->authorize())->toBeFalse();

    $inactiveExam = Exam::factory()->for($team)->for($enrollment->course)->create(['is_active' => false]);
    expect(phaseTwoFormRequest(StoreStudentExamAttemptRequest::class, $user, [
        'enrollment' => $enrollment,
        'exam' => $inactiveExam,
    ])->authorize())->toBeFalse();

    expect(phaseTwoFormRequest(SubmitStudentExamAttemptRequest::class, null)->authorize())->toBeFalse();

    $submittedAttempt = phaseTwoPendingAttempt($team, $enrollment, $exam, [$question->id]);
    $submittedAttempt->update(['submitted_at' => now()]);
    expect(phaseTwoFormRequest(SubmitStudentExamAttemptRequest::class, $user, [
        'examAttempt' => $submittedAttempt,
    ])->authorize())->toBeFalse();

    expect(phaseTwoFormRequest(UpdateStudentExamAttemptRequest::class, null)->authorize())->toBeFalse();
    expect(phaseTwoFormRequest(UpdateStudentExamAttemptRequest::class, $user, [
        'examAttempt' => $submittedAttempt,
    ])->authorize())->toBeFalse();

    $expiredAttempt = phaseTwoPendingAttempt($team, $enrollment, $exam, [$question->id]);
    $expiredAttempt->update(['started_at' => now()->subHours(2)]);
    expect(phaseTwoFormRequest(UpdateStudentExamAttemptRequest::class, $user, [
        'examAttempt' => $expiredAttempt,
    ])->authorize())->toBeFalse();
});

/**
 * @return array{0: User, 1: Team, 2: Enrollment, 3: Lesson}
 */
function phaseTwoLessonContext(array $enrollmentOverrides = []): array
{
    $user = grantPowerXRole(User::factory()->create(), PowerXRole::Student);
    $team = $user->currentTeam;
    $profile = StudentProfile::factory()->for($team)->for($user)->create();
    $course = Course::factory()->for($team)->create();
    $module = CourseModule::factory()->for($course)->create(['is_active' => true]);
    $lesson = Lesson::factory()->for($module, 'courseModule')->create(['is_active' => true]);
    $package = CoursePackage::factory()->for($team)->for($course)->create();
    $enrollment = Enrollment::factory()
        ->for($team)
        ->for($profile, 'studentProfile')
        ->for($course)
        ->for($package, 'coursePackage')
        ->create(array_merge([
            'status' => Enrollment::STATUS_ACTIVE,
            'payment_status' => 'paid',
            'access_starts_at' => now()->subDay(),
            'access_expires_at' => now()->addMonth(),
        ], $enrollmentOverrides));

    return [$user, $team, $enrollment, $lesson];
}

/**
 * @return array{0: User, 1: Team, 2: Enrollment, 3: Exam, 4: Question}
 */
function phaseTwoExamContext(array $examOverrides = [], array $enrollmentOverrides = []): array
{
    $user = grantPowerXRole(User::factory()->create(), PowerXRole::Student);
    $team = $user->currentTeam;
    $profile = StudentProfile::factory()->for($team)->for($user)->create();
    $course = Course::factory()->for($team)->create();
    $package = CoursePackage::factory()->for($team)->for($course)->create();
    $enrollment = Enrollment::factory()
        ->for($team)
        ->for($profile, 'studentProfile')
        ->for($course)
        ->for($package, 'coursePackage')
        ->create(array_merge([
            'status' => Enrollment::STATUS_ACTIVE,
            'payment_status' => 'paid',
            'access_starts_at' => now()->subDay(),
            'access_expires_at' => now()->addMonth(),
        ], $enrollmentOverrides));
    $exam = Exam::factory()
        ->for($team)
        ->for($course)
        ->create(array_merge([
            'is_active' => true,
            'duration_minutes' => 30,
            'pass_mark' => 70,
        ], $examOverrides));
    $question = Question::factory()
        ->for($team)
        ->for($course)
        ->create([
            'topic' => 'Safety',
            'difficulty' => 'standard',
            'correct_answer' => ['A'],
            'is_active' => true,
        ]);

    return [$user, $team, $enrollment, $exam, $question];
}

function phaseTwoPendingAttempt(Team $team, Enrollment $enrollment, Exam $exam, ?array $selectedQuestionIds): ExamAttempt
{
    return ExamAttempt::factory()
        ->for($team)
        ->for($exam)
        ->for($enrollment)
        ->for($enrollment->studentProfile, 'studentProfile')
        ->create([
            'metadata' => $selectedQuestionIds === null ? null : ['selected_question_ids' => $selectedQuestionIds],
            'started_at' => now(),
            'submitted_at' => null,
            'result' => 'pending',
        ]);
}

function phaseTwoLessonViewer(array $payload): BuildStudentLessonViewer
{
    $portal = new class($payload) extends BuildStudentPortal
    {
        public function __construct(private array $payload) {}

        public function handle(User $user, Team $team, bool $includeCourseCatalog = false): array
        {
            return $this->payload;
        }
    };

    return new BuildStudentLessonViewer($portal);
}

/**
 * @param  class-string  $pageClass
 * @return array<string, mixed>
 */
function phaseTwoEditPageSnapshot(string $pageClass, object $record): array
{
    $page = new $pageClass;
    phaseTwoSetPageRecord($page, $record);
    $method = new ReflectionMethod($pageClass, 'assessmentConfigurationSnapshot');
    $method->setAccessible(true);

    return $method->invoke($page);
}

/**
 * @param  class-string  $pageClass
 */
function phaseTwoInvokeEditPageAfterSave(string $pageClass, object $record): void
{
    $page = new $pageClass;
    phaseTwoSetPageRecord($page, $record);
    $method = new ReflectionMethod($pageClass, 'afterSave');
    $method->setAccessible(true);
    $method->invoke($page);
}

function phaseTwoSetPageRecord(object $page, object $record): void
{
    $property = new ReflectionProperty($page, 'record');
    $property->setAccessible(true);
    $property->setValue($page, $record);
}

/**
 * @param  class-string<FormRequest>  $requestClass
 * @param  array<string, mixed>  $routeParameters
 */
function phaseTwoFormRequest(string $requestClass, ?User $user, array $routeParameters = []): FormRequest
{
    $request = $requestClass::create('/student-portal/exam-attempts', 'POST');
    $route = new RoutingRoute('POST', '/student-portal/exam-attempts', []);
    $route->bind($request);

    foreach ($routeParameters as $key => $value) {
        $route->setParameter($key, $value);
    }

    $request->setUserResolver(fn (): ?User => $user);
    $request->setRouteResolver(fn (): RoutingRoute => $route);

    return $request;
}
