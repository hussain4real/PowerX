<?php

use App\Models\Communication;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\CoursePackage;
use App\Models\Enrollment;
use App\Models\FreePreviewEvent;
use App\Models\Lead;
use App\Models\Lesson;
use App\Models\StudentProfile;

test('public lead inquiries and registrations preserve source campaign and utm attribution', function (): void {
    $course = Course::factory()->create(['status' => 'published', 'published_at' => now()]);
    $package = CoursePackage::factory()->for($course)->create(['name' => 'Exam Ready', 'is_active' => true]);

    $this
        ->post(route('leads.store'), [
            'name' => 'Aamir Khan',
            'email' => 'aamir@example.test',
            'phone' => '+97450000000',
            'course_id' => $course->id,
            'source' => 'google',
            'campaign' => 'kahramaa-search',
            'utm_source' => 'google',
            'utm_medium' => 'cpc',
            'utm_campaign' => 'kahramaa-search',
            'utm_content' => 'sitelink',
            'utm_term' => 'kahramaa course',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $lead = Lead::query()->where('email', 'aamir@example.test')->firstOrFail();

    expect($lead->source)->toBe('google')
        ->and($lead->campaign)->toBe('kahramaa-search')
        ->and($lead->metadata['attribution'])->toMatchArray([
            'utm_source' => 'google',
            'utm_medium' => 'cpc',
            'utm_campaign' => 'kahramaa-search',
            'utm_content' => 'sitelink',
            'utm_term' => 'kahramaa course',
        ]);

    $this
        ->post(route('courses.registrations.store', ['course' => $course]), [
            'full_name' => 'Fatima Ali',
            'email' => 'fatima@example.test',
            'mobile' => '+97451111111',
            'course_package_id' => $package->id,
            'source' => 'linkedin',
            'campaign' => 'engineers-june',
            'utm_source' => 'linkedin',
            'utm_medium' => 'paid-social',
            'utm_campaign' => 'engineers-june',
        ])
        ->assertRedirect(route('courses.show', ['course' => $course]))
        ->assertSessionHasNoErrors();

    $registrationLead = Lead::query()->where('email', 'fatima@example.test')->firstOrFail();
    $profile = StudentProfile::query()->where('email', 'fatima@example.test')->firstOrFail();
    $enrollment = Enrollment::query()->whereBelongsTo($profile, 'studentProfile')->firstOrFail();

    expect($registrationLead->status)->toBe(Lead::STATUS_PAYMENT_PENDING)
        ->and($registrationLead->source)->toBe('linkedin')
        ->and($registrationLead->campaign)->toBe('engineers-june')
        ->and($profile->metadata['attribution']['utm_medium'])->toBe('paid-social')
        ->and($enrollment->metadata['lead_id'])->toBe($registrationLead->id)
        ->and($enrollment->metadata['campaign'])->toBe('engineers-june')
        ->and(Communication::query()->where('template_key', 'registration_confirmation')->where('lead_id', $registrationLead->id)->exists())->toBeTrue();
});

test('preview start and completion events are tracked with lead source campaign course and lesson', function (): void {
    $course = Course::factory()->create(['status' => 'published', 'published_at' => now()]);
    $module = CourseModule::factory()->for($course)->create(['is_active' => true]);
    $lesson = Lesson::factory()
        ->for($module, 'courseModule')
        ->create(['is_active' => true, 'is_preview' => true]);
    $lead = Lead::factory()
        ->for($course)
        ->create([
            'email' => 'preview@example.test',
            'source' => 'newsletter',
            'campaign' => 'preview-push',
        ]);

    $this
        ->withSession(['powerx.lead_id' => $lead->id])
        ->post(route('courses.preview-events.store', ['course' => $course]), [
            'event_type' => FreePreviewEvent::EVENT_STARTED,
            'lesson_id' => $lesson->id,
            'email' => 'preview@example.test',
            'source' => 'newsletter',
            'campaign' => 'preview-push',
            'utm_source' => 'newsletter',
            'utm_campaign' => 'preview-push',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $this
        ->withSession(['powerx.lead_id' => $lead->id])
        ->post(route('courses.preview-events.store', ['course' => $course]), [
            'event_type' => FreePreviewEvent::EVENT_COMPLETED,
            'lesson_id' => $lesson->id,
            'source' => 'newsletter',
            'campaign' => 'preview-push',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $events = FreePreviewEvent::query()
        ->whereBelongsTo($course)
        ->whereBelongsTo($lesson)
        ->orderBy('id')
        ->get();

    expect($events)->toHaveCount(2)
        ->and($events[0]->event_type)->toBe(FreePreviewEvent::EVENT_STARTED)
        ->and($events[0]->lead_id)->toBe($lead->id)
        ->and($events[0]->source)->toBe('newsletter')
        ->and($events[0]->campaign)->toBe('preview-push')
        ->and($events[0]->metadata['attribution']['utm_campaign'])->toBe('preview-push')
        ->and($events[1]->event_type)->toBe(FreePreviewEvent::EVENT_COMPLETED);
});

test('preview tracking allows lessonless events and matches leads by contact details', function (): void {
    $course = Course::factory()->create(['status' => 'published', 'published_at' => now()]);
    $lead = Lead::factory()
        ->for($course)
        ->create([
            'email' => 'contact-preview@example.test',
            'phone' => '+97455550000',
        ]);

    $this
        ->post(route('courses.preview-events.store', ['course' => $course]), [
            'event_type' => FreePreviewEvent::EVENT_STARTED,
            'email' => 'contact-preview@example.test',
            'phone' => '+97455550000',
            'utm_source' => 'paid-search',
            'utm_campaign' => 'lesson-preview',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $this
        ->post(route('courses.preview-events.store', ['course' => $course]), [
            'event_type' => FreePreviewEvent::EVENT_COMPLETED,
            'utm_source' => 'direct',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $events = FreePreviewEvent::query()
        ->whereBelongsTo($course)
        ->orderBy('id')
        ->get();

    expect($events)->toHaveCount(2)
        ->and($events[0]->lead_id)->toBe($lead->id)
        ->and($events[0]->lesson_id)->toBeNull()
        ->and($events[0]->source)->toBe('paid-search')
        ->and($events[0]->campaign)->toBe('lesson-preview')
        ->and($events[0]->metadata['matched_by'])->toBe('session_or_contact')
        ->and($events[0]->metadata['contact']['email'])->toBe('contact-preview@example.test')
        ->and($events[1]->lead_id)->toBeNull()
        ->and($events[1]->lesson_id)->toBeNull()
        ->and($events[1]->source)->toBe('direct')
        ->and($events[1]->metadata['matched_by'])->toBeNull();
});

test('preview tracking rejects non preview lessons for the course', function (): void {
    $course = Course::factory()->create(['status' => 'published', 'published_at' => now()]);
    $module = CourseModule::factory()->for($course)->create(['is_active' => true]);
    $lesson = Lesson::factory()
        ->for($module, 'courseModule')
        ->create(['is_active' => true, 'is_preview' => false]);

    $this
        ->post(route('courses.preview-events.store', ['course' => $course]), [
            'event_type' => FreePreviewEvent::EVENT_STARTED,
            'lesson_id' => $lesson->id,
        ])
        ->assertSessionHasErrors('lesson_id');
});
