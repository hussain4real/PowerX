<?php

use App\Enums\PowerXRole;
use App\Models\AttendanceRecord;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\StudentProfile;
use App\Models\TrainingBatch;
use App\Models\TrainingSession;
use App\Models\User;
use Database\Seeders\PowerXAccessSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(PowerXAccessSeeder::class);
});

test('instructors see assigned batches session rosters practical records and resources', function () {
    $this->withoutVite();

    $instructor = User::factory()->create(['name' => 'Instructor Noor']);
    $instructor->assignRole(PowerXRole::Instructor->value);
    $team = $instructor->currentTeam;
    $course = Course::factory()
        ->for($team)
        ->create([
            'title' => 'Electrical Safety Practical',
            'slug' => 'electrical-safety-practical',
            'category' => 'Electrical safety',
        ]);
    $module = CourseModule::factory()
        ->for($course)
        ->create(['title' => 'Workshop readiness']);
    Lesson::factory()
        ->for($module, 'courseModule')
        ->create(['title' => 'Safe isolation checklist', 'lesson_type' => 'document']);
    $batch = TrainingBatch::factory()
        ->for($team)
        ->for($course)
        ->for($instructor, 'instructor')
        ->create([
            'name' => 'PX-LAB-01',
            'delivery_mode' => 'classroom',
            'capacity' => 12,
            'starts_at' => now()->addDay(),
        ]);
    $futureSession = TrainingSession::factory()
        ->for($batch, 'trainingBatch')
        ->create(['title' => 'Practical lab', 'starts_at' => now()->addDays(2)]);
    $pastSession = TrainingSession::factory()
        ->for($batch, 'trainingBatch')
        ->create(['title' => 'Theory review', 'starts_at' => now()->subDay()]);

    $pendingProfile = StudentProfile::factory()->for($team)->create(['full_name' => 'Aisha Rahman']);
    $passedProfile = StudentProfile::factory()->for($team)->create(['full_name' => 'Bilal Khan']);
    $pendingEnrollment = Enrollment::factory()
        ->for($team)
        ->for($course)
        ->for($pendingProfile, 'studentProfile')
        ->create(['status' => 'active', 'payment_status' => 'paid']);
    $passedEnrollment = Enrollment::factory()
        ->for($team)
        ->for($course)
        ->for($passedProfile, 'studentProfile')
        ->create(['status' => 'active', 'payment_status' => 'paid']);

    AttendanceRecord::factory()
        ->for($team)
        ->for($futureSession, 'trainingSession')
        ->for($pendingEnrollment)
        ->create([
            'status' => 'pending',
            'practical_outcome' => null,
            'practical_comments' => null,
        ]);
    AttendanceRecord::factory()
        ->for($team)
        ->for($futureSession, 'trainingSession')
        ->for($passedEnrollment)
        ->create([
            'status' => 'present',
            'practical_outcome' => 'passed',
            'practical_score' => 95,
            'practical_comments' => 'Excellent safety sequence.',
        ]);
    AttendanceRecord::factory()
        ->for($team)
        ->for($pastSession, 'trainingSession')
        ->for($pendingEnrollment)
        ->create([
            'status' => 'present',
            'practical_outcome' => 'passed',
        ]);

    TrainingBatch::factory()
        ->for($team)
        ->for($course)
        ->for(User::factory(), 'instructor')
        ->create(['name' => 'PX-OTHER-01']);

    $this->actingAs($instructor)
        ->get(route('instructor.portal', ['current_team' => $team]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Instructor/Portal')
            ->where('summary.assignedBatches', 1)
            ->where('summary.scheduledSessions', 2)
            ->where('summary.students', 2)
            ->where('summary.pendingAttendance', 1)
            ->where('summary.pendingPractical', 1)
            ->where('summary.nextSessionLabel', 'Practical lab')
            ->has('batches', 1)
            ->where('batches.0.name', 'PX-LAB-01')
            ->where('batches.0.attendanceRate', 67)
            ->where('batches.0.course.title', 'Electrical Safety Practical')
            ->where('batches.0.sessions.0.title', 'Theory review')
            ->where('batches.0.sessions.1.students.0.studentName', 'Aisha Rahman')
            ->where('batches.0.sessions.1.students.0.attendanceStatus', 'pending')
            ->where('batches.0.sessions.1.students.1.studentName', 'Bilal Khan')
            ->where('batches.0.sessions.1.students.1.practicalOutcome', 'passed')
            ->where('batches.0.resources.0.title', 'Workshop readiness')
            ->where('batches.0.resources.0.lessons.0.title', 'Safe isolation checklist'));
});

test('instructor portal renders an empty assigned batch state', function () {
    $this->withoutVite();

    $instructor = User::factory()->create();
    $instructor->assignRole(PowerXRole::Instructor->value);

    $this->actingAs($instructor)
        ->get(route('instructor.portal', ['current_team' => $instructor->currentTeam]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Instructor/Portal')
            ->where('summary.assignedBatches', 0)
            ->where('summary.nextSessionLabel', 'No upcoming assigned session')
            ->has('batches', 0));
});

test('student users cannot access the instructor portal', function () {
    $student = User::factory()->create();
    $student->assignRole(PowerXRole::Student->value);

    $this->actingAs($student)
        ->get(route('instructor.portal', ['current_team' => $student->currentTeam]))
        ->assertForbidden();
});
