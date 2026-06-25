<?php

use App\Actions\PowerX\ApproveManualPayment;
use App\Actions\PowerX\GenerateInvoicePdf;
use App\Actions\PowerX\GeneratePaymentReceiptPdf;
use App\Actions\PowerX\ResolvePortalFinanceAccess;
use App\Actions\PowerX\ReviewOfflinePayment;
use App\Actions\PowerX\SubmitOfflinePaymentProof;
use App\Enums\PowerXRole;
use App\Enums\TeamRole;
use App\Filament\Resources\PaymentTransactions\Pages\ListPaymentTransactions;
use App\Http\Requests\StoreOfflinePaymentProofRequest;
use App\Models\AuditEvent;
use App\Models\Company;
use App\Models\Course;
use App\Models\CoursePackage;
use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\PaymentTransaction;
use App\Models\StudentProfile;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\PowerXAccessSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Livewire\Livewire;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;

beforeEach(function (): void {
    $this->seed(PowerXAccessSeeder::class);
    Storage::fake('local');
});

test('students submit offline payment proof and see review status without paid access', function (): void {
    $this->withoutVite();

    [$student, $team, $enrollment, $invoice] = phaseThreeStudentInvoice();

    $this
        ->actingAs($student)
        ->post(route('student.payments.offline-proof.store', [
            'current_team' => $team,
            'invoice' => $invoice,
        ]), [
            'method' => PaymentTransaction::METHOD_BANK_TRANSFER,
            'amount' => 1500,
            'reference' => 'BANK-STUDENT-001',
            'paid_at' => now()->toDateString(),
            'payer_name' => 'Fatima Student',
            'payer_email' => 'fatima@example.test',
            'bank_name' => 'QNB transfer',
            'deposit_date' => now()->toDateString(),
            'notes' => 'Transferred from student account.',
            'proof' => UploadedFile::fake()->create('bank-proof.pdf', 128, 'application/pdf'),
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $payment = PaymentTransaction::query()->firstOrFail();

    expect($payment->status)->toBe(PaymentTransaction::STATUS_PENDING)
        ->and($payment->reference)->toBe('BANK-STUDENT-001')
        ->and((float) $payment->amount)->toBe(1500.0)
        ->and($payment->metadata['offline_payment'])->toMatchArray([
            'payer_name' => 'Fatima Student',
            'payer_email' => 'fatima@example.test',
            'bank_name' => 'QNB transfer',
            'submitted_by_id' => $student->id,
            'submitted_via' => 'student_portal',
        ])
        ->and($payment->getMedia('payment-proofs'))->toHaveCount(1)
        ->and($enrollment->fresh()->payment_status)->toBe('pending')
        ->and($enrollment->fresh()->access_starts_at)->toBeNull();

    $this
        ->actingAs($student)
        ->get(route('student.payments.index', ['current_team' => $team]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Student/Payments')
            ->where('enrollments.0.finance.invoices.0.number', 'PX-OFF-STU-001')
            ->where('enrollments.0.finance.invoices.0.outstandingAmount', 1500)
            ->where('enrollments.0.finance.invoices.0.offlinePaymentProofUrl', route('student.payments.offline-proof.store', [
                'current_team' => $team,
                'invoice' => $invoice,
            ]))
            ->where('enrollments.0.finance.payments.0.reference', 'BANK-STUDENT-001')
            ->where('enrollments.0.finance.payments.0.proofStatus', 'proof_uploaded')
            ->where('enrollments.0.finance.payments.0.receiptUrl', null));
});

test('corporate coordinators submit scoped proof while unmatched coordinators are blocked', function (): void {
    $corporate = grantPowerXRole(User::factory()->create(['name' => 'Karim Coordinator']), PowerXRole::Corporate);
    $team = $corporate->currentTeam;
    [$invoice] = phaseThreeCorporateInvoice($corporate, $team);

    $this
        ->actingAs($corporate)
        ->post(route('corporate.payments.offline-proof.store', [
            'current_team' => $team,
            'invoice' => $invoice,
        ]), [
            'method' => PaymentTransaction::METHOD_CHEQUE,
            'amount' => 3000,
            'reference' => 'CHQ-CORP-001',
            'payer_name' => 'Visible Facilities LLC',
            'proof' => UploadedFile::fake()->image('cheque-proof.jpg'),
        ])
        ->assertRedirect();

    expect(PaymentTransaction::query()->where('reference', 'CHQ-CORP-001')->exists())->toBeTrue();

    $unmatched = grantPowerXRole(User::factory()->create(), PowerXRole::Corporate);
    $team->members()->attach($unmatched, ['role' => TeamRole::Member->value]);
    $unmatched->switchTeam($team);

    $this
        ->actingAs($unmatched)
        ->post(route('corporate.payments.offline-proof.store', [
            'current_team' => $team,
            'invoice' => $invoice,
        ]), [
            'method' => PaymentTransaction::METHOD_CASH,
            'amount' => 3000,
            'proof' => UploadedFile::fake()->create('cash.pdf', 64, 'application/pdf'),
        ])
        ->assertForbidden();
});

test('finance approval unlocks full offline payment exactly once', function (): void {
    [$finance, $team, $enrollment, $invoice] = phaseThreeFinanceInvoice();
    $payment = phaseThreePayment($team, $enrollment, $invoice, 1500);

    $approved = app(ApproveManualPayment::class)->handle($payment, $finance);
    $secondPass = app(ApproveManualPayment::class)->handle($approved, $finance);

    expect($approved->status)->toBe(PaymentTransaction::STATUS_APPROVED)
        ->and($secondPass->status)->toBe(PaymentTransaction::STATUS_APPROVED)
        ->and($invoice->fresh()->status)->toBe('paid')
        ->and($invoice->fresh()->paid_at)->not->toBeNull()
        ->and($enrollment->fresh()->status)->toBe(Enrollment::STATUS_ACTIVE)
        ->and($enrollment->fresh()->payment_status)->toBe('paid')
        ->and($enrollment->fresh()->access_starts_at)->not->toBeNull()
        ->and(AuditEvent::query()->where('action', 'payment.approved')->count())->toBe(1);
});

test('finance rejection request info duplicate and partial statuses do not unlock access', function (string $outcome, string $expectedStatus): void {
    [$finance, $team, $enrollment, $invoice] = phaseThreeFinanceInvoice();
    $payment = phaseThreePayment($team, $enrollment, $invoice, 1500);

    $reviewed = app(ReviewOfflinePayment::class)->handle($payment, $finance, $outcome, [
        'notes' => "Finance marked {$outcome}.",
    ]);

    expect($reviewed->status)->toBe($expectedStatus)
        ->and(data_get($reviewed->metadata, 'finance_review.notes'))->toBe("Finance marked {$outcome}.")
        ->and($invoice->fresh()->status)->toBe('issued')
        ->and($enrollment->fresh()->payment_status)->toBe('pending')
        ->and($enrollment->fresh()->access_starts_at)->toBeNull()
        ->and(AuditEvent::query()->where('action', "payment.{$outcome}")->exists())->toBeTrue();
})->with([
    'reject' => ['reject', PaymentTransaction::STATUS_REJECTED],
    'request information' => ['request_information', PaymentTransaction::STATUS_INFORMATION_REQUESTED],
    'duplicate' => ['duplicate', PaymentTransaction::STATUS_DUPLICATE],
    'partial' => ['partial', PaymentTransaction::STATUS_PARTIAL],
]);

test('refund void and adjust revoke previously approved access', function (string $outcome, string $expectedStatus, array $data): void {
    [$finance, $team, $enrollment, $invoice] = phaseThreeFinanceInvoice();
    $payment = app(ApproveManualPayment::class)->handle(
        phaseThreePayment($team, $enrollment, $invoice, 1500),
        $finance,
    );

    $reviewed = app(ReviewOfflinePayment::class)->handle($payment, $finance, $outcome, [
        'notes' => "Finance marked {$outcome}.",
        ...$data,
    ]);

    expect($reviewed->status)->toBe($expectedStatus)
        ->and($invoice->fresh()->status)->toBe('issued')
        ->and($invoice->fresh()->paid_at)->toBeNull()
        ->and($enrollment->fresh()->payment_status)->toBe('pending')
        ->and($enrollment->fresh()->access_starts_at)->toBeNull()
        ->and($enrollment->fresh()->access_expires_at)->toBeNull();
})->with([
    'refund' => ['refund', PaymentTransaction::STATUS_REFUNDED, []],
    'void' => ['void', PaymentTransaction::STATUS_VOIDED, []],
    'adjust' => ['adjust', PaymentTransaction::STATUS_ADJUSTED, ['amount' => 750]],
]);

test('partial approved offline payments keep invoice partial and student access locked', function (): void {
    [$finance, $team, $enrollment, $invoice] = phaseThreeFinanceInvoice();

    app(ApproveManualPayment::class)->handle(phaseThreePayment($team, $enrollment, $invoice, 500), $finance);

    expect($invoice->fresh()->status)->toBe('partial')
        ->and($enrollment->fresh()->payment_status)->toBe('partial')
        ->and($enrollment->fresh()->access_starts_at)->toBeNull();
});

test('portal invoice and receipt PDFs are scoped to owners and approved receipts', function (): void {
    Pdf::fake();

    [$student, $team, $enrollment, $invoice] = phaseThreeStudentInvoice();
    $finance = grantPowerXRole(User::factory()->create(), PowerXRole::Finance);
    $team->members()->attach($finance, ['role' => TeamRole::Member->value]);
    $finance->switchTeam($team);
    $approvedPayment = app(ApproveManualPayment::class)->handle(
        phaseThreePayment($team, $enrollment, $invoice, 1500),
        $finance,
    );
    $pendingPayment = phaseThreePayment($team, $enrollment, $invoice, 100);
    $quotation = Invoice::factory()
        ->for($team)
        ->for($enrollment)
        ->for($enrollment->studentProfile, 'studentProfile')
        ->create([
            'number' => 'PX-OFF-QUO-001',
            'type' => 'quotation',
            'status' => 'issued',
            'total' => 1500,
        ]);
    $receiptInvoice = Invoice::factory()
        ->for($team)
        ->for($enrollment)
        ->for($enrollment->studentProfile, 'studentProfile')
        ->create([
            'number' => 'PX-OFF-REC-001',
            'type' => 'receipt',
            'status' => 'issued',
            'total' => 1500,
        ]);

    $this
        ->actingAs($student)
        ->get(route('student.payments.invoices.pdf', [
            'current_team' => $team,
            'invoice' => $invoice,
        ]))
        ->assertSuccessful();
    $this
        ->actingAs($student)
        ->get(route('student.payments.receipts.pdf', [
            'current_team' => $team,
            'paymentTransaction' => $approvedPayment,
        ]))
        ->assertSuccessful();
    $this
        ->actingAs($student)
        ->get(route('student.payments.receipts.pdf', [
            'current_team' => $team,
            'paymentTransaction' => $pendingPayment,
        ]))
        ->assertNotFound();
    $this
        ->actingAs($student)
        ->get(route('student.payments.invoices.pdf', [
            'current_team' => $team,
            'invoice' => $quotation,
        ]))
        ->assertSuccessful();
    $this
        ->actingAs($student)
        ->get(route('student.payments.invoices.pdf', [
            'current_team' => $team,
            'invoice' => $receiptInvoice,
        ]))
        ->assertSuccessful();

    Pdf::assertRespondedWithPdf(fn (PdfBuilder $pdf): bool => $pdf->viewName === 'pdf.powerx.invoice'
        && $pdf->isInline());
    Pdf::assertRespondedWithPdf(fn (PdfBuilder $pdf): bool => $pdf->viewName === 'pdf.powerx.receipt'
        && $pdf->isInline());
    Pdf::assertRespondedWithPdf(fn (PdfBuilder $pdf): bool => data_get($pdf->viewData, 'documentTitle') === 'Quotation');
    Pdf::assertRespondedWithPdf(fn (PdfBuilder $pdf): bool => data_get($pdf->viewData, 'documentTitle') === 'Receipt');
});

test('offline payment PDFs include payer proof and approval references', function (): void {
    Pdf::fake();

    [$finance, $team, $enrollment, $invoice] = phaseThreeFinanceInvoice();
    $payment = phaseThreePayment($team, $enrollment, $invoice, 1500, [
        'reference' => 'BANK-PDF-OFFLINE',
        'metadata' => [
            'offline_payment' => [
                'payer_name' => 'Fatima Student',
                'payer_email' => 'fatima@example.test',
                'bank_name' => 'QNB transfer',
                'deposit_date' => now()->toDateString(),
            ],
        ],
    ]);
    $payment
        ->addMedia(UploadedFile::fake()->create('offline-proof.pdf', 64, 'application/pdf'))
        ->toMediaCollection('payment-proofs');
    app(ApproveManualPayment::class)->handle($payment, $finance);

    app(GenerateInvoicePdf::class)->handle($invoice);
    app(GeneratePaymentReceiptPdf::class)->handle($payment->fresh());

    Pdf::assertSaved(fn (PdfBuilder $pdf, string $path): bool => $pdf->viewName === 'pdf.powerx.invoice'
        && $path === 'powerx/invoices/px-off-fin-001.pdf'
        && str_contains($pdf->html, 'BANK-PDF-OFFLINE')
        && str_contains($pdf->html, 'Offline payment activity'));
    Pdf::assertSaved(fn (PdfBuilder $pdf, string $path): bool => $pdf->viewName === 'pdf.powerx.receipt'
        && $path === 'powerx/receipts/bank-pdf-offline.pdf'
        && str_contains($pdf->html, 'Fatima Student')
        && str_contains($pdf->html, 'Offline proof reference'));
});

test('portal finance access supports invoice payment company and mismatch checks', function (): void {
    $corporate = grantPowerXRole(User::factory()->create(), PowerXRole::Corporate);
    $team = $corporate->currentTeam;
    [$invoice, $company] = phaseThreeCorporateInvoice($corporate, $team);
    $student = grantPowerXRole(User::factory()->create(), PowerXRole::Student);
    $team->members()->attach($student, ['role' => TeamRole::Member->value]);
    $student->switchTeam($team);
    $profile = StudentProfile::factory()
        ->for($team)
        ->for($student)
        ->create();
    [$course, $package] = phaseThreeCoursePackage($team);
    $studentEnrollment = Enrollment::factory()
        ->for($team)
        ->for($profile, 'studentProfile')
        ->for($course)
        ->for($package, 'coursePackage')
        ->create();
    $enrollmentOnlyInvoice = Invoice::factory()
        ->for($team)
        ->for($studentEnrollment)
        ->create([
            'company_id' => null,
            'student_profile_id' => null,
        ]);
    $companyOnlyPayment = PaymentTransaction::factory()
        ->for($team)
        ->for($company)
        ->create([
            'invoice_id' => null,
            'enrollment_id' => null,
            'student_profile_id' => null,
            'status' => PaymentTransaction::STATUS_PENDING,
        ]);
    $otherTeam = Team::factory()->create();
    $nonCorporate = User::factory()->create();
    $team->members()->attach($nonCorporate, ['role' => TeamRole::Member->value]);
    $nonCorporate->switchTeam($team);

    $resolver = app(ResolvePortalFinanceAccess::class);

    expect($resolver->canUseInvoice($corporate, $team, $invoice))->toBeTrue()
        ->and($resolver->canUseInvoice($student, $team, $enrollmentOnlyInvoice))->toBeTrue()
        ->and($resolver->canUseInvoice($corporate, $otherTeam, $invoice))->toBeFalse()
        ->and($resolver->canUsePayment($corporate, $team, $companyOnlyPayment))->toBeTrue()
        ->and($resolver->canUsePayment($nonCorporate, $team, $companyOnlyPayment))->toBeFalse()
        ->and($resolver->canUsePayment($corporate, $otherTeam, $companyOnlyPayment))->toBeFalse();
});

test('offline proof action supports finance-created pending records without a proof file and validates review outcomes', function (): void {
    [$student, , , $invoice] = phaseThreeStudentInvoice();
    $payment = app(SubmitOfflinePaymentProof::class)->handle($invoice, $student, [
        'method' => PaymentTransaction::METHOD_CASH,
        'amount' => 1500,
        'reference' => 'CASH-NO-PROOF',
    ], null);
    $reviewer = grantPowerXRole(User::factory()->create(), PowerXRole::Finance);

    expect($payment->getMedia('payment-proofs'))->toHaveCount(0)
        ->and(fn () => app(ReviewOfflinePayment::class)->handle($payment, $reviewer, 'unsupported'))
        ->toThrow(InvalidArgumentException::class);
});

test('offline payment defensive helpers and filament review action remain wired', function (): void {
    [$finance, $team, $enrollment, $invoice] = phaseThreeFinanceInvoice();
    $payment = phaseThreePayment($team, $enrollment, $invoice, 1500);
    $pendingProof = phaseThreePayment($team, $enrollment, $invoice, 1500, ['reference' => 'PENDING-PROOF']);
    $proofedPending = phaseThreePayment($team, $enrollment, $invoice, 1500, ['reference' => 'PROOFED-PENDING']);
    $proofedPending
        ->addMedia(UploadedFile::fake()->create('proofed.pdf', 16, 'application/pdf'))
        ->toMediaCollection('payment-proofs');
    $overdueInvoice = Invoice::factory()
        ->for($team)
        ->for($enrollment)
        ->for($enrollment->studentProfile, 'studentProfile')
        ->create([
            'number' => 'PX-OFF-OVERDUE',
            'status' => 'issued',
            'due_at' => now()->subDay(),
            'total' => 1500,
        ]);
    $overduePayment = phaseThreePayment($team, $enrollment, $overdueInvoice, 1500, ['reference' => 'OVERDUE-PAYMENT']);
    $unmatchedPayment = PaymentTransaction::factory()
        ->for($team)
        ->create([
            'invoice_id' => null,
            'enrollment_id' => null,
            'student_profile_id' => null,
            'status' => PaymentTransaction::STATUS_PENDING,
            'reference' => 'UNMATCHED-PAYMENT',
        ]);

    Livewire::actingAs($finance)
        ->test(ListPaymentTransactions::class)
        ->callTableAction('requestInformation', $payment, [
            'notes' => 'Upload a clearer bank transfer slip.',
        ])
        ->assertHasNoTableActionErrors();
    Livewire::actingAs($finance)
        ->test(ListPaymentTransactions::class)
        ->filterTable('pending_proof')
        ->assertCanSeeTableRecords([$pendingProof])
        ->assertCanNotSeeTableRecords([$proofedPending]);
    Livewire::actingAs($finance)
        ->test(ListPaymentTransactions::class)
        ->filterTable('overdue_invoice')
        ->assertCanSeeTableRecords([$overduePayment])
        ->assertCanNotSeeTableRecords([$proofedPending]);
    Livewire::actingAs($finance)
        ->test(ListPaymentTransactions::class)
        ->filterTable('unmatched')
        ->assertCanSeeTableRecords([$unmatchedPayment])
        ->assertCanNotSeeTableRecords([$proofedPending]);

    $request = StoreOfflinePaymentProofRequest::create('/offline-proof', 'POST');

    expect(ReviewOfflinePayment::outcomeOptions())->toHaveKey('request_information')
        ->and(PaymentTransaction::reviewableStatuses())->toContain(PaymentTransaction::STATUS_INFORMATION_REQUESTED)
        ->and($payment->fresh()->status)->toBe(PaymentTransaction::STATUS_INFORMATION_REQUESTED)
        ->and($request->authorize())->toBeFalse();
});

/**
 * @return array{0: User, 1: Team, 2: Enrollment, 3: Invoice}
 */
function phaseThreeStudentInvoice(): array
{
    $student = grantPowerXRole(User::factory()->create([
        'name' => 'Fatima Student',
        'email' => 'fatima@example.test',
    ]), PowerXRole::Student);
    $team = $student->currentTeam;
    $profile = StudentProfile::factory()
        ->for($team)
        ->for($student)
        ->create([
            'full_name' => 'Fatima Student',
            'email' => $student->email,
        ]);
    [$course, $package] = phaseThreeCoursePackage($team);
    $enrollment = Enrollment::factory()
        ->for($team)
        ->for($profile, 'studentProfile')
        ->for($course)
        ->for($package, 'coursePackage')
        ->create([
            'status' => Enrollment::STATUS_APPROVED,
            'payment_status' => 'pending',
            'access_starts_at' => null,
            'access_expires_at' => null,
        ]);
    $invoice = Invoice::factory()
        ->for($team)
        ->for($enrollment)
        ->for($profile, 'studentProfile')
        ->create([
            'number' => 'PX-OFF-STU-001',
            'type' => 'invoice',
            'status' => 'issued',
            'currency' => 'QAR',
            'subtotal' => 1500,
            'total' => 1500,
        ]);

    return [$student, $team, $enrollment, $invoice];
}

/**
 * @return array{0: Invoice, 1: Company}
 */
function phaseThreeCorporateInvoice(User $corporate, Team $team): array
{
    $company = Company::factory()
        ->for($team)
        ->create([
            'name' => 'Visible Facilities LLC',
            'email' => 'training@visible.test',
            'metadata' => ['coordinator_email' => $corporate->email],
        ]);
    $profile = StudentProfile::factory()
        ->for($team)
        ->for($company)
        ->create(['full_name' => 'Visible Employee']);
    [$course, $package] = phaseThreeCoursePackage($team);
    $enrollment = Enrollment::factory()
        ->for($team)
        ->for($company)
        ->for($profile, 'studentProfile')
        ->for($course)
        ->for($package, 'coursePackage')
        ->create(['status' => Enrollment::STATUS_APPROVED, 'payment_status' => 'pending']);
    $invoice = Invoice::factory()
        ->for($team)
        ->for($company)
        ->for($profile, 'studentProfile')
        ->for($enrollment)
        ->create([
            'number' => 'PX-OFF-CORP-001',
            'type' => 'invoice',
            'status' => 'issued',
            'currency' => 'QAR',
            'subtotal' => 3000,
            'total' => 3000,
        ]);

    return [$invoice, $company];
}

/**
 * @return array{0: User, 1: Team, 2: Enrollment, 3: Invoice}
 */
function phaseThreeFinanceInvoice(): array
{
    $finance = grantPowerXRole(User::factory()->create(), PowerXRole::Finance);
    $team = $finance->currentTeam;
    $profile = StudentProfile::factory()
        ->for($team)
        ->create(['full_name' => 'Finance Student']);
    [$course, $package] = phaseThreeCoursePackage($team);
    $enrollment = Enrollment::factory()
        ->for($team)
        ->for($profile, 'studentProfile')
        ->for($course)
        ->for($package, 'coursePackage')
        ->create([
            'status' => Enrollment::STATUS_APPROVED,
            'payment_status' => 'pending',
            'access_starts_at' => null,
            'access_expires_at' => null,
        ]);
    $invoice = Invoice::factory()
        ->for($team)
        ->for($enrollment)
        ->for($profile, 'studentProfile')
        ->create([
            'number' => 'PX-OFF-FIN-001',
            'type' => 'invoice',
            'status' => 'issued',
            'currency' => 'QAR',
            'subtotal' => 1500,
            'total' => 1500,
            'metadata' => [
                'line_items' => [
                    ['description' => 'Offline payment course', 'amount' => 1500],
                ],
            ],
        ]);

    return [$finance, $team, $enrollment, $invoice];
}

/**
 * @param  array<string, mixed>  $overrides
 */
function phaseThreePayment(Team $team, Enrollment $enrollment, Invoice $invoice, int $amount, array $overrides = []): PaymentTransaction
{
    return PaymentTransaction::factory()
        ->for($team)
        ->for($enrollment)
        ->for($invoice)
        ->for($enrollment->studentProfile, 'studentProfile')
        ->create([
            'status' => PaymentTransaction::STATUS_PENDING,
            'method' => PaymentTransaction::METHOD_BANK_TRANSFER,
            'reference' => 'BANK-OFFLINE-001',
            'currency' => 'QAR',
            'amount' => $amount,
            'approved_by_id' => null,
            'approved_at' => null,
            'metadata' => [
                'offline_payment' => [
                    'payer_name' => $enrollment->studentProfile->full_name,
                ],
            ],
            ...$overrides,
        ]);
}

/**
 * @return array{0: Course, 1: CoursePackage}
 */
function phaseThreeCoursePackage(Team $team): array
{
    $course = Course::factory()
        ->for($team)
        ->create([
            'title' => 'Offline Payment Course',
            'validity_days' => 90,
        ]);
    $package = CoursePackage::factory()
        ->for($team)
        ->for($course)
        ->create([
            'name' => 'Offline Package',
            'price' => 1500,
            'validity_days' => 90,
        ]);

    return [$course, $package];
}
