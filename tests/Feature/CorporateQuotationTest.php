<?php

use App\Models\Company;
use App\Models\Course;
use App\Models\CoursePackage;
use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\StudentProfile;
use Inertia\Testing\AssertableInertia as Assert;

test('corporate quotation request page lists published course packages', function () {
    $this->withoutVite();

    $publishedCourse = Course::factory()->create([
        'title' => 'Corporate Electrical Safety',
        'status' => 'published',
        'published_at' => now(),
    ]);
    $package = CoursePackage::factory()
        ->for($publishedCourse)
        ->create(['name' => 'Corporate Team', 'price' => 2500]);
    Course::factory()->create(['status' => 'draft']);

    $this->get(route('corporate.quotations.create'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Corporate/QuotationRequest')
            ->has('courseOptions', 1)
            ->where('courseOptions.0.title', 'Corporate Electrical Safety')
            ->where('courseOptions.0.packages.0.name', $package->name));
});

test('corporate quotation requests create company employees enrollments and quotation invoices', function () {
    $course = Course::factory()->create([
        'title' => 'Cable Jointing Corporate Workshop',
        'status' => 'published',
        'published_at' => now(),
    ]);
    $package = CoursePackage::factory()
        ->for($course)
        ->create([
            'name' => 'Corporate Practical',
            'price' => 2000,
            'discount_price' => 1800,
            'currency' => 'QAR',
        ]);

    $this->post(route('corporate.quotations.store'), [
        'company_name' => 'Doha Facilities LLC',
        'contact_name' => 'Mariam Coordinator',
        'email' => 'training@doha.test',
        'phone' => '+97455500000',
        'address' => 'Industrial Area',
        'course_id' => $course->id,
        'course_package_id' => $package->id,
        'discount_total' => 100,
        'tax_total' => 0,
        'notes' => 'Need weekend delivery.',
        'employees' => [
            [
                'full_name' => 'Aamir Technician',
                'email' => 'aamir@example.test',
                'mobile' => '+97455511111',
                'profession' => 'Technician',
                'preferred_schedule' => 'Weekend',
            ],
            [
                'full_name' => 'Fatima Engineer',
                'email' => 'fatima@example.test',
                'mobile' => '+97455522222',
                'profession' => 'Engineer',
                'notes' => 'Needs certificate copy.',
            ],
            [
                'full_name' => '',
            ],
        ],
    ])->assertRedirect(route('corporate.quotations.create'))->assertSessionHasNoErrors();

    $company = Company::firstOrFail();
    $quotation = Invoice::firstOrFail();

    expect($company->name)->toBe('Doha Facilities LLC')
        ->and($company->metadata['employee_count'])->toBe(2)
        ->and(StudentProfile::count())->toBe(2)
        ->and(Enrollment::count())->toBe(2)
        ->and($quotation->type)->toBe('quotation')
        ->and($quotation->status)->toBe('issued')
        ->and($quotation->company->is($company))->toBeTrue()
        ->and($quotation->number)->toStartWith('PX-QUO-')
        ->and($quotation->subtotal)->toBe('3600.00')
        ->and($quotation->discount_total)->toBe('100.00')
        ->and($quotation->total)->toBe('3500.00')
        ->and($quotation->metadata['employee_count'])->toBe(2)
        ->and($quotation->metadata['request_notes'])->toBe('Need weekend delivery.')
        ->and($quotation->metadata['line_items'][0]['description'])->toBe('Cable Jointing Corporate Workshop - Aamir Technician');

    Enrollment::query()->each(function (Enrollment $enrollment) use ($quotation, $company, $course, $package) {
        expect($enrollment->company->is($company))->toBeTrue()
            ->and($enrollment->course->is($course))->toBeTrue()
            ->and($enrollment->coursePackage->is($package))->toBeTrue()
            ->and($enrollment->status)->toBe('pending')
            ->and($enrollment->payment_status)->toBe('pending')
            ->and($enrollment->metadata['quotation_number'])->toBe($quotation->number);
    });
});

test('corporate quotations can fall back to base course pricing', function () {
    $course = Course::factory()->create([
        'base_price' => 950,
        'status' => 'published',
        'published_at' => now(),
    ]);

    $this->post(route('corporate.quotations.store'), [
        'company_name' => 'Base Price Co',
        'contact_name' => 'Base Contact',
        'email' => 'base@example.test',
        'phone' => '+97455533333',
        'course_id' => $course->id,
        'employees' => [
            ['full_name' => 'Base Employee'],
        ],
    ])->assertRedirect(route('corporate.quotations.create'))->assertSessionHasNoErrors();

    $quotation = Invoice::firstOrFail();

    expect($quotation->subtotal)->toBe('950.00')
        ->and($quotation->metadata['course_package_id'])->toBeNull()
        ->and((float) $quotation->metadata['line_items'][0]['amount'])->toBe(950.0);
});

test('corporate quotation requests require at least one employee', function () {
    $course = Course::factory()->create(['status' => 'published', 'published_at' => now()]);

    $this->post(route('corporate.quotations.store'), [
        'company_name' => 'No Employees Co',
        'contact_name' => 'No Employee',
        'email' => 'none@example.test',
        'phone' => '+97455544444',
        'course_id' => $course->id,
        'employees' => [
            ['full_name' => ''],
        ],
    ])->assertSessionHasErrors('employees');
});
