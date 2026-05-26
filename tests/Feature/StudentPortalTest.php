<?php

use App\Enums\PowerXRole;
use App\Models\AttendanceRecord;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\CoursePackage;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Invoice;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\PaymentTransaction;
use App\Models\StudentProfile;
use App\Models\TrainingBatch;
use App\Models\TrainingSession;
use App\Models\User;
use Database\Seeders\PowerXAccessSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(PowerXAccessSeeder::class);
});

test('students see their course access progress schedule exams finance and certificates', function () {
    $this->withoutVite();

    $user = User::factory()->create();
    $team = $user->currentTeam;
    $profile = StudentProfile::factory()
        ->for($team)
        ->for($user)
        ->create([
            'full_name' => 'Fatima Ali',
            'document_status' => 'verified',
        ]);
    $course = Course::factory()
        ->for($team)
        ->create([
            'title' => 'Kahramaa Electrical Exam Prep',
            'slug' => 'kahramaa-electrical-exam-prep',
            'category' => 'Kahramaa exam preparation',
            'delivery_mode' => 'blended',
        ]);
    $package = CoursePackage::factory()
        ->for($team)
        ->for($course)
        ->create([
            'name' => 'Exam Ready',
            'includes_certificate' => true,
            'validity_days' => 90,
        ]);
    $module = CourseModule::factory()
        ->for($course)
        ->create(['title' => 'Permit and safety rules', 'sort_order' => 1]);
    $completedLesson = Lesson::factory()
        ->for($module, 'courseModule')
        ->create([
            'title' => 'Kahramaa permit overview',
            'is_preview' => true,
            'sort_order' => 1,
            'content_revision' => 2,
            'content_retired_at' => now()->subDay(),
        ]);
    $lockedLesson = Lesson::factory()
        ->for($module, 'courseModule')
        ->create(['title' => 'Paid mock exam walkthrough', 'is_preview' => false, 'sort_order' => 2]);

    $openEnrollment = Enrollment::factory()
        ->for($team)
        ->for($profile, 'studentProfile')
        ->for($course)
        ->for($package, 'coursePackage')
        ->create([
            'status' => 'active',
            'payment_status' => 'paid',
            'access_starts_at' => now()->subDay(),
            'access_expires_at' => now()->addDays(30),
        ]);
    LessonProgress::factory()
        ->for($openEnrollment)
        ->for($completedLesson, 'lesson')
        ->create([
            'lesson_content_revision' => 2,
            'progress_percentage' => 100,
            'completed_at' => now(),
        ]);
    LessonProgress::factory()
        ->for($openEnrollment)
        ->for($lockedLesson, 'lesson')
        ->create([
            'progress_percentage' => 20,
            'completed_at' => null,
        ]);

    $instructor = User::factory()->create(['name' => 'Instructor Noor']);
    $batch = TrainingBatch::factory()
        ->for($team)
        ->for($course)
        ->for($instructor, 'instructor')
        ->create(['name' => 'PX-WKND-01', 'delivery_mode' => 'classroom']);
    $session = TrainingSession::factory()
        ->for($batch, 'trainingBatch')
        ->create(['title' => 'Weekend practical lab', 'starts_at' => now()->addDays(2)]);
    AttendanceRecord::factory()
        ->for($team)
        ->for($session, 'trainingSession')
        ->for($openEnrollment)
        ->create([
            'status' => 'present',
            'practical_outcome' => 'passed',
            'practical_comments' => 'Safe testing sequence.',
        ]);

    $exam = Exam::factory()
        ->for($team)
        ->for($course)
        ->create(['title' => 'Permit mock exam', 'max_attempts' => 3]);
    ExamAttempt::factory()
        ->for($team)
        ->for($exam)
        ->for($openEnrollment)
        ->for($profile, 'studentProfile')
        ->create(['result' => 'passed', 'score' => 86, 'attempt_number' => 1]);
    $invoice = Invoice::factory()
        ->for($team)
        ->for($openEnrollment)
        ->for($profile, 'studentProfile')
        ->create(['number' => 'PX-INV-STU-001', 'status' => 'paid', 'total' => 1500]);
    PaymentTransaction::factory()
        ->for($team)
        ->for($openEnrollment)
        ->for($invoice)
        ->for($profile, 'studentProfile')
        ->create(['status' => 'approved', 'amount' => 1500, 'method' => 'bank_transfer']);
    Certificate::factory()
        ->for($team)
        ->for($openEnrollment)
        ->for($profile, 'studentProfile')
        ->for($course)
        ->create(['certificate_number' => 'PX-CERT-STU-001', 'status' => 'issued']);

    $paymentPendingCourse = Course::factory()->for($team)->create(['title' => 'Payment Pending Course']);
    Enrollment::factory()
        ->for($team)
        ->for($profile, 'studentProfile')
        ->for($paymentPendingCourse)
        ->create(['status' => 'pending', 'payment_status' => 'pending']);

    $enrollmentPendingCourse = Course::factory()->for($team)->create(['title' => 'Enrollment Pending Course']);
    Enrollment::factory()
        ->for($team)
        ->for($profile, 'studentProfile')
        ->for($enrollmentPendingCourse)
        ->create(['status' => 'approved', 'payment_status' => 'paid']);

    $expiredCourse = Course::factory()->for($team)->create(['title' => 'Expired Course']);
    Enrollment::factory()
        ->for($team)
        ->for($profile, 'studentProfile')
        ->for($expiredCourse)
        ->create([
            'status' => 'active',
            'payment_status' => 'paid',
            'access_expires_at' => now()->subDay(),
        ]);

    $this->actingAs($user)
        ->get(route('student.portal', ['current_team' => $team]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Student/Portal')
            ->where('profile.fullName', 'Fatima Ali')
            ->where('summary.enrolledCourses', 4)
            ->where('summary.activeEnrollments', 2)
            ->where('summary.pendingPayments', 1)
            ->where('summary.issuedCertificates', 1)
            ->where('summary.averageProgress', 13)
            ->where('summary.nextSessionLabel', 'Weekend practical lab')
            ->has('enrollments', 4)
            ->where('enrollments.0.accessStatus', 'expired')
            ->where('enrollments.1.accessStatus', 'enrollment_pending')
            ->where('enrollments.2.accessStatus', 'payment_pending')
            ->where('enrollments.3.accessStatus', 'open')
            ->where('enrollments.3.hasPaidAccess', true)
            ->where('enrollments.3.course.title', 'Kahramaa Electrical Exam Prep')
            ->where('enrollments.3.package.name', 'Exam Ready')
            ->where('enrollments.3.progress.completedLessons', 1)
            ->where('enrollments.3.progress.totalLessons', 2)
            ->where('enrollments.3.modules.0.lessons.0.title', 'Kahramaa permit overview')
            ->where('enrollments.3.modules.0.lessons.0.isCompleted', true)
            ->where('enrollments.3.modules.0.lessons.1.isLocked', false)
            ->where('enrollments.3.schedule.0.batch.name', 'PX-WKND-01')
            ->where('enrollments.3.exams.0.title', 'Permit mock exam')
            ->where('enrollments.3.exams.0.canStart', true)
            ->where('enrollments.3.finance.invoices.0.number', 'PX-INV-STU-001')
            ->where('enrollments.3.finance.payments.0.method', 'bank_transfer')
            ->where('enrollments.3.certificates.0.certificateNumber', 'PX-CERT-STU-001'));
});

test('student portal renders an onboarding state when no profile is linked', function () {
    $this->withoutVite();

    $user = User::factory()->create();
    $user->assignRole(PowerXRole::Student->value);

    $this->actingAs($user)
        ->get(route('student.portal', ['current_team' => $user->currentTeam]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Student/Portal')
            ->where('profile', null)
            ->where('summary.enrolledCourses', 0)
            ->where('summary.nextSessionLabel', 'Create a student profile to begin.')
            ->has('enrollments', 0));
});
