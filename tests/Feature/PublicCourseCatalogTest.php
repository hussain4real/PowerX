<?php

use App\Models\Company;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\CoursePackage;
use App\Models\Enrollment;
use App\Models\Lead;
use App\Models\Lesson;
use App\Models\StudentProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

it('renders the public course catalog with published courses only', function () {
    $this->withoutVite();

    $publishedCourse = Course::factory()->create([
        'title' => 'Kahramaa Electrical Safety Preparation',
        'slug' => 'kahramaa-electrical-safety-preparation',
        'status' => 'published',
        'published_at' => now(),
    ]);
    CoursePackage::factory()->for($publishedCourse)->create(['price' => 1200, 'is_active' => true]);
    Course::factory()->create(['status' => 'draft']);

    $this->get(route('courses.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Courses/Index')
            ->has('courses', 1)
            ->where('courses.0.slug', $publishedCourse->slug)
            ->where('leadCourseOptions.0.id', $publishedCourse->id));

    $source = file_get_contents(resource_path('js/pages/Courses/Index.vue'));
    $assistantSource = file_get_contents(resource_path('js/components/powerx/PowerXAssistantPanel.vue'));

    expect($source)
        ->toContain('PublicThemeSwitcher')
        ->toContain('MotionReveal')
        ->toContain('motion-safe:animate-powerx-scan')
        ->toContain('motion-safe:transition-all')
        ->toContain('motion-reduce')
        ->toContain('bg-background text-foreground dark:bg-powerx-ink dark:text-white')
        ->toContain('dark:bg-white/[0.04]')
        ->not->toContain('min-h-screen bg-powerx-ink text-white')
        ->not->toContain('tone="dark"')
        ->and($assistantSource)
        ->toContain("tone?: 'dark' | 'light' | 'adaptive'")
        ->toContain('dark:border-white/10');
});

it('renders a course detail page with packages and modules', function () {
    $this->withoutVite();

    $course = Course::factory()->create([
        'title' => 'Industrial Control Panel Practical',
        'slug' => 'industrial-control-panel-practical',
        'status' => 'published',
        'published_at' => now(),
    ]);
    $package = CoursePackage::factory()->for($course)->create(['name' => 'Professional', 'is_active' => true]);
    $module = CourseModule::factory()->for($course)->create(['title' => 'Panel safety', 'is_active' => true]);
    Lesson::factory()->for($module, 'courseModule')->create(['title' => 'Lockout basics', 'is_active' => true]);

    $this->get(route('courses.show', ['course' => $course]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Courses/Show')
            ->where('course.slug', $course->slug)
            ->where('course.packages.0.name', $package->name)
            ->where('course.modules.0.title', $module->title)
            ->where('course.modules.0.lessons.0.title', 'Lockout basics'));

    $source = file_get_contents(resource_path('js/pages/Courses/Show.vue'));

    expect($source)
        ->toContain('PublicThemeSwitcher')
        ->toContain('MotionReveal')
        ->toContain('motion-safe:animate-powerx-scan')
        ->toContain('motion-safe:transition-all')
        ->toContain('motion-reduce')
        ->toContain('bg-background text-foreground dark:bg-powerx-ink dark:text-white')
        ->toContain('dark:bg-white/[0.04]')
        ->not->toContain('min-h-screen bg-powerx-ink text-white')
        ->not->toContain('tone="dark"');
});

it('keeps public motion enhancements reduced-motion aware', function (): void {
    $revealSource = file_get_contents(resource_path('js/components/powerx/MotionReveal.vue'));
    $scrollRevealSource = file_get_contents(resource_path('js/composables/useScrollReveal.ts'));

    expect($revealSource)
        ->toContain('useScrollReveal')
        ->toContain('motion-safe:transition-[opacity,transform,filter]')
        ->toContain('motion-reduce:transition-none')
        ->toContain('transitionDelay')
        ->and($scrollRevealSource)
        ->toContain('IntersectionObserver')
        ->toContain('prefers-reduced-motion')
        ->toContain('requestAnimationFrame')
        ->toContain('showImmediately');
});

it('captures public lead inquiries', function () {
    $course = Course::factory()->create(['status' => 'published', 'published_at' => now()]);

    $this->post(route('leads.store'), [
        'name' => 'Aamir Khan',
        'email' => 'aamir@example.com',
        'phone' => '+97450000000',
        'company_name' => 'Doha Electrical Co',
        'course_id' => $course->id,
        'message' => 'Need weekend timing.',
        'source' => 'catalog',
    ])->assertRedirect()->assertSessionHasNoErrors();

    $lead = Lead::firstOrFail();

    $this->assertModelExists($lead);
    expect($lead->course->is($course))->toBeTrue()
        ->and($lead->company)->toBeInstanceOf(Company::class)
        ->and($lead->status)->toBe('new')
        ->and($lead->source)->toBe('catalog');
});

it('creates a pending enrollment from a public registration request', function () {
    $course = Course::factory()->create(['status' => 'published', 'published_at' => now()]);
    $package = CoursePackage::factory()->for($course)->create(['name' => 'Exam Ready', 'is_active' => true]);

    $this->post(route('courses.registrations.store', ['course' => $course]), [
        'full_name' => 'Fatima Ali',
        'email' => 'fatima@example.com',
        'mobile' => '+97451111111',
        'profession' => 'Electrical Engineer',
        'company_name' => 'Qatar Facilities',
        'qatar_location' => 'Doha',
        'preferred_schedule' => 'Weekend',
        'course_package_id' => $package->id,
        'message' => 'Please send bank transfer details.',
    ])->assertRedirect(route('courses.show', ['course' => $course]))->assertSessionHasNoErrors();

    $profile = StudentProfile::firstOrFail();
    $enrollment = Enrollment::firstOrFail();

    $this->assertModelExists($profile);
    $this->assertModelExists($enrollment);

    expect($profile->full_name)->toBe('Fatima Ali')
        ->and($profile->company)->toBeInstanceOf(Company::class)
        ->and($enrollment->course->is($course))->toBeTrue()
        ->and($enrollment->coursePackage->is($package))->toBeTrue()
        ->and($enrollment->status)->toBe('pending')
        ->and($enrollment->payment_status)->toBe('pending');
});
