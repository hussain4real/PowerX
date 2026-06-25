<?php

use App\Actions\PowerX\BuildInstructorPortal;
use App\Actions\PowerX\BuildOperationsDashboard;
use App\Actions\PowerX\BuildStudentPortal;
use App\Enums\PowerXRole;
use App\Models\AuditEvent;
use App\Models\Certificate;
use App\Models\Communication;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\CoursePackage;
use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Lesson;
use App\Models\PaymentTransaction;
use App\Models\StudentProfile;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\PowerXDemoSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('demo seeder creates a playable local PowerX workspace idempotently', function (): void {
    $this->seed(PowerXDemoSeeder::class);
    $this->seed(PowerXDemoSeeder::class);

    $team = Team::query()->where('slug', 'powerx-training-center')->firstOrFail();
    $testUser = User::query()->where('email', 'test@example.com')->firstOrFail();
    $student = User::query()->where('email', 'student@powerx.test')->firstOrFail();
    $instructor = User::query()->where('email', 'instructor@powerx.test')->firstOrFail();

    expect(Course::query()->whereBelongsTo($team)->published()->count())->toBe(6)
        ->and(CoursePackage::query()->whereBelongsTo($team)->count())->toBe(7)
        ->and(CourseModule::query()->whereHas('course', fn ($query) => $query->whereBelongsTo($team))->count())->toBe(8)
        ->and(Lesson::query()->whereHas('courseModule.course', fn ($query) => $query->whereBelongsTo($team))->count())->toBe(16)
        ->and(StudentProfile::query()->whereBelongsTo($team)->count())->toBe(3)
        ->and(Enrollment::query()->whereBelongsTo($team)->count())->toBe(4)
        ->and(Lead::query()->whereBelongsTo($team)->count())->toBe(3)
        ->and(Invoice::query()->whereBelongsTo($team)->count())->toBe(3)
        ->and(PaymentTransaction::query()->whereBelongsTo($team)->count())->toBe(3)
        ->and(Certificate::query()->whereBelongsTo($team)->count())->toBe(3)
        ->and(Communication::query()->whereBelongsTo($team)->count())->toBe(2)
        ->and(AuditEvent::query()->whereBelongsTo($team)->count())->toBe(2)
        ->and($testUser->currentTeam->is($team))->toBeTrue()
        ->and($testUser->hasRole(PowerXRole::Management->value))->toBeTrue()
        ->and($student->hasRole(PowerXRole::Student->value))->toBeTrue()
        ->and($instructor->hasRole(PowerXRole::Instructor->value))->toBeTrue();

    $dashboard = app(BuildOperationsDashboard::class)->handle($team);
    $studentPortal = app(BuildStudentPortal::class)->handle($student, $team);
    $instructorPortal = app(BuildInstructorPortal::class)->handle($instructor, $team);

    expect($dashboard['summaryCards'][0]['value'])->toBe('1')
        ->and($dashboard['summaryCards'][1]['value'])->toBe('QAR 7,950')
        ->and($dashboard['finance']['pending_payments'])->toBe(1)
        ->and($dashboard['growth']['renewal_opportunities'])->toBe(2)
        ->and($dashboard['growth']['campaigns_tracked'])->toBe(2)
        ->and($dashboard['growth']['campaign_revenue_label'])->toBe('QAR 6,600.00')
        ->and($dashboard['growth']['campaign_cost_label'])->toBe('QAR 1,350.00')
        ->and($dashboard['growth']['campaign_roi_label'])->toBe('633.3%')
        ->and($studentPortal['profile']['fullName'])->toBe('Fatima Ali')
        ->and($studentPortal['summary']['enrolledCourses'])->toBe(2)
        ->and($studentPortal['summary']['pendingPayments'])->toBe(1)
        ->and($studentPortal['summary']['issuedCertificates'])->toBe(1)
        ->and($studentPortal['summary']['nextSessionLabel'])->toBe('Weekend practical lab')
        ->and($instructorPortal['summary']['assignedBatches'])->toBe(1)
        ->and($instructorPortal['summary']['students'])->toBe(2)
        ->and($instructorPortal['summary']['pendingAttendance'])->toBe(2)
        ->and(Certificate::query()->where('verification_token', 'demo-fatima-kahramaa-certificate')->exists())->toBeTrue();
});

test('homepage presents enriched public safe PowerX marketing copy', function (): void {
    $this->withoutVite();

    $featuredCourse = Course::factory()->create([
        'title' => 'Kahramaa Exam Preparation',
        'slug' => 'kahramaa-exam-preparation',
        'status' => 'published',
        'is_featured' => true,
        'published_at' => now(),
    ]);
    CoursePackage::factory()->for($featuredCourse)->create([
        'price' => 1200,
        'is_active' => true,
    ]);
    CourseModule::factory()->for($featuredCourse)->create(['is_active' => true]);

    $safetyCourse = Course::factory()->create([
        'title' => 'Electrical Safety and Compliance',
        'slug' => 'electrical-safety-and-compliance',
        'status' => 'published',
        'published_at' => now()->subDay(),
    ]);
    CoursePackage::factory()->for($safetyCourse)->create([
        'price' => 900,
        'is_active' => true,
    ]);
    Course::factory()->create(['status' => 'draft']);

    $this->get(route('home'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Welcome')
            ->has('featuredCourses', 2)
            ->where('featuredCourses.0.slug', $featuredCourse->slug)
            ->where('featuredCourses.0.lowestPackagePrice', '1200.00')
            ->has('leadCourseOptions', 2)
            ->where('leadCourseOptions.0.title', 'Electrical Safety and Compliance'));

    $source = file_get_contents(resource_path('js/pages/Welcome.vue'));

    expect($source)
        ->toContain('Practical electrical training for Qatar\'s site-ready')
        ->toContain('professionals.')
        ->toContain('Corporate workforce training')
        ->toContain('Clear guidance from first enquiry to completion.')
        ->toContain('PowerX keeps every step easy to understand')
        ->toContain(':delay="index * 90"')
        ->toContain('MotionReveal')
        ->toContain('motion-safe:animate-powerx-scan')
        ->toContain('motion-safe:animate-powerx-float')
        ->toContain('motion-reduce')
        ->toContain('source')
        ->toContain("source: params.get('source') ?? 'homepage'")
        ->toContain('trackingEntries')
        ->toContain('PowerX completion records')
        ->not->toContain('The public site should sell')
        ->not->toContain('operational depth behind the scenes')
        ->not->toContain('99% success')
        ->not->toContain('99% Exam Success')
        ->not->toContain('Pass in 7 days')
        ->not->toContain('100% complete')
        ->not->toContain('government-issued certificate');
});
