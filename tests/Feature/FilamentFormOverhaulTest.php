<?php

use App\Enums\PowerXRole;
use App\Filament\Resources\Invoices\Pages\CreateInvoice;
use App\Filament\Resources\Leads\Pages\CreateLead;
use App\Filament\Resources\PaymentTransactions\Pages\CreatePaymentTransaction;
use App\Filament\Resources\Questions\Pages\CreateQuestion;
use App\Filament\Support\PowerXForm;
use App\Models\Company;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\PaymentTransaction;
use App\Models\Question;
use App\Models\StudentProfile;
use App\Models\User;
use Database\Seeders\PowerXAccessSeeder;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(PowerXAccessSeeder::class);
});

test('lead form stores structured metadata and defaults to the current team', function (): void {
    $user = actingAsManagementUser();

    Livewire::test(CreateLead::class)
        ->fillForm([
            'name' => 'PowerX Form Lead',
            'email' => 'form-lead@example.test',
            'phone' => '+97450000000',
            'source' => 'website',
            'status' => 'qualified',
            'metadata' => [
                'utm_campaign' => 'spring-intake',
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $lead = Lead::query()->where('email', 'form-lead@example.test')->firstOrFail();

    expect($lead->team_id)->toBe($user->current_team_id)
        ->and($lead->status)->toBe('qualified')
        ->and($lead->metadata)->toBe(['utm_campaign' => 'spring-intake']);
});

test('invoice form validates controlled money fields', function (): void {
    actingAsManagementUser();

    Livewire::test(CreateInvoice::class)
        ->fillForm([
            'number' => 'INV-FORM-001',
            'type' => 'invoice',
            'status' => 'issued',
            'currency' => 'QAR',
            'subtotal' => -1,
            'discount_total' => 0,
            'tax_total' => 0,
            'total' => -1,
        ])
        ->call('create')
        ->assertHasFormErrors([
            'subtotal' => 'min',
            'total' => 'min',
        ]);

    expect(Invoice::query()->where('number', 'INV-FORM-001')->exists())->toBeFalse();
});

test('question form persists structured options and answer keys', function (): void {
    $user = actingAsManagementUser();
    $course = Course::factory()->create([
        'team_id' => $user->current_team_id,
    ]);

    Livewire::test(CreateQuestion::class)
        ->fillForm([
            'course_id' => $course->id,
            'topic' => 'Safety',
            'difficulty' => 'standard',
            'type' => 'single_choice',
            'question_text' => 'Which answer is safe?',
            'options' => [
                ['key' => 'A', 'label' => 'Use approved PPE'],
                ['key' => 'B', 'label' => 'Ignore lockout tags'],
            ],
            'correct_answer' => ['A'],
            'explanation' => 'Approved PPE is required for practical safety.',
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $question = Question::query()->where('question_text', 'Which answer is safe?')->firstOrFail();

    expect($question->team_id)->toBe($user->current_team_id)
        ->and($question->options)->toBe([
            ['key' => 'A', 'label' => 'Use approved PPE'],
            ['key' => 'B', 'label' => 'Ignore lockout tags'],
        ])
        ->and($question->correct_answer)->toBe(['A']);
});

test('payment form cannot bypass audited approval fields', function (): void {
    $user = actingAsManagementUser();

    Livewire::test(CreatePaymentTransaction::class)
        ->fillForm([
            'method' => 'cash',
            'reference' => 'CASH-FORM-001',
            'status' => 'approved',
            'currency' => 'QAR',
            'amount' => 500,
            'approved_by_id' => $user->id,
            'approved_at' => now()->toDateTimeString(),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $payment = PaymentTransaction::query()->where('reference', 'CASH-FORM-001')->firstOrFail();

    expect($payment->team_id)->toBe($user->current_team_id)
        ->and($payment->status)->toBe('pending')
        ->and($payment->approved_by_id)->toBeNull()
        ->and($payment->approved_at)->toBeNull();
});

test('shared form relationship labels use business context instead of ids', function (): void {
    $studentProfile = StudentProfile::factory()->create([
        'full_name' => 'Fatima Ali',
        'email' => 'fatima@example.test',
        'mobile' => '+97455550000',
    ]);
    $course = Course::factory()->create([
        'title' => 'Kahramaa Preparation',
    ]);
    $company = Company::factory()->create([
        'name' => 'Doha MEP',
    ]);
    $enrollment = Enrollment::factory()
        ->for($studentProfile)
        ->for($course)
        ->create([
            'status' => 'active',
        ]);
    $invoice = Invoice::factory()
        ->for($studentProfile)
        ->for($company)
        ->create([
            'number' => 'INV-LABEL-001',
            'status' => 'paid',
            'total' => 1500,
        ]);

    expect(PowerXForm::studentProfileSelect()->getOptionLabelFromRecord($studentProfile))
        ->toBe('Fatima Ali - fatima@example.test - +97455550000')
        ->and(PowerXForm::enrollmentSelect()->getOptionLabelFromRecord($enrollment))
        ->toBe("#{$enrollment->id} - Fatima Ali - Kahramaa Preparation - active")
        ->and(PowerXForm::invoiceSelect()->getOptionLabelFromRecord($invoice))
        ->toBe('INV-LABEL-001 - Fatima Ali - paid - QAR 1500.00');
});

test('team scoped form queries remain safe without an authenticated current team', function (): void {
    Course::factory()->count(2)->create();

    expect(PowerXForm::teamScoped(Course::query())->count())->toBe(2)
        ->and(PowerXForm::relationshipSelect('course_id', 'course', 'title', teamScoped: false)->isSearchable())
        ->toBeTrue();
});

function actingAsManagementUser(): User
{
    $user = User::factory()->create();
    grantPowerXRole($user, PowerXRole::Management);

    test()->actingAs($user);

    return $user;
}
