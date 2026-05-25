<?php

use App\Actions\PowerX\GenerateCertificatePdf;
use App\Actions\PowerX\GenerateInvoicePdf;
use App\Actions\PowerX\GeneratePaymentReceiptPdf;
use App\Enums\PowerXRole;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\CoursePackage;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Invoice;
use App\Models\LessonProgress;
use App\Models\PaymentTransaction;
use App\Models\Question;
use App\Models\StudentProfile;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\PowerXAccessSeeder;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;

beforeEach(function () {
    Pdf::fake();
    $this->seed(PowerXAccessSeeder::class);
});

test('invoice and quotation pdf documents are saved with private paths', function (string $type, string $expectedDirectory, string $expectedTitle) {
    [$invoice] = powerxPdfFixtures(invoiceType: $type);

    $path = app(GenerateInvoicePdf::class)->handle($invoice);

    expect($path)->toBe($expectedDirectory.'/px-inv-pdf-001.pdf')
        ->and($invoice->fresh()->metadata['pdf']['path'])->toBe($path)
        ->and($invoice->fresh()->metadata['pdf']['generated_at'])->not->toBeEmpty();

    Pdf::assertSaved(function (PdfBuilder $pdf, string $savedPath) use ($path, $expectedTitle, $invoice): bool {
        return $savedPath === $path
            && $pdf->viewName === 'pdf.powerx.invoice'
            && $pdf->viewData['documentTitle'] === $expectedTitle
            && $pdf->viewData['invoice']->is($invoice)
            && str_contains($pdf->html, $invoice->number);
    });
})->with([
    'invoice' => ['invoice', 'powerx/invoices', 'Invoice'],
    'quotation' => ['quotation', 'powerx/quotations', 'Quotation'],
    'receipt invoice record' => ['receipt', 'powerx/invoices', 'Receipt'],
]);

test('payment receipt pdf document is saved and linked to payment metadata', function () {
    [, $payment] = powerxPdfFixtures();

    $path = app(GeneratePaymentReceiptPdf::class)->handle($payment);

    expect($path)->toBe('powerx/receipts/bank-pdf-001.pdf')
        ->and($payment->fresh()->metadata['receipt_pdf']['path'])->toBe($path);

    Pdf::assertSaved(function (PdfBuilder $pdf, string $savedPath) use ($path, $payment): bool {
        return $savedPath === $path
            && $pdf->viewName === 'pdf.powerx.receipt'
            && $pdf->viewData['payment']->is($payment)
            && str_contains($pdf->html, 'Payment Receipt')
            && str_contains($pdf->html, 'BANK-PDF-001');
    });
});

test('certificate pdf document is saved and marks generation time', function () {
    [, , $certificate] = powerxPdfFixtures();

    $path = app(GenerateCertificatePdf::class)->handle($certificate);

    expect($path)->toBe('powerx/certificates/px-cert-pdf-001.pdf')
        ->and($certificate->fresh()->metadata['pdf']['path'])->toBe($path)
        ->and($certificate->fresh()->pdf_generated_at)->not->toBeNull();

    Pdf::assertSaved(function (PdfBuilder $pdf, string $savedPath) use ($path, $certificate): bool {
        return $savedPath === $path
            && $pdf->viewName === 'pdf.powerx.certificate'
            && $pdf->viewData['certificate']->is($certificate)
            && $pdf->orientation === 'Landscape'
            && str_contains($pdf->html, $certificate->certificate_number);
    });
});

test('authorized staff can preview finance and certificate pdf responses', function () {
    [$invoice, $payment, $certificate, $manager] = powerxPdfFixtures();

    $manager->assignRole(PowerXRole::Management->value);
    $this->actingAs($manager);

    $this->get(route('documents.invoices.pdf', $invoice))->assertSuccessful();
    $this->get(route('documents.payments.receipt', $payment))->assertSuccessful();
    $this->get(route('documents.certificates.pdf', $certificate))->assertSuccessful();

    Pdf::assertRespondedWithPdf(fn (PdfBuilder $pdf): bool => $pdf->viewName === 'pdf.powerx.invoice'
        && $pdf->isInline()
        && $pdf->downloadName === 'px-inv-pdf-001.pdf');
    Pdf::assertRespondedWithPdf(fn (PdfBuilder $pdf): bool => $pdf->viewName === 'pdf.powerx.receipt'
        && $pdf->isInline()
        && $pdf->downloadName === 'bank-pdf-001.pdf');
    Pdf::assertRespondedWithPdf(fn (PdfBuilder $pdf): bool => $pdf->viewName === 'pdf.powerx.certificate'
        && $pdf->isInline()
        && $pdf->downloadName === 'px-cert-pdf-001.pdf');
});

test('students cannot preview protected finance pdf responses', function () {
    [$invoice, $payment] = powerxPdfFixtures();
    $student = User::factory()->create();
    $student->assignRole(PowerXRole::Student->value);

    $this->actingAs($student);

    $this->get(route('documents.invoices.pdf', $invoice))->assertForbidden();
    $this->get(route('documents.payments.receipt', $payment))->assertForbidden();
});

/**
 * @return array{0: Invoice, 1: PaymentTransaction, 2: Certificate, 3: User}
 */
function powerxPdfFixtures(string $invoiceType = 'invoice'): array
{
    $team = Team::factory()->create();
    $manager = User::factory()->create();
    $course = Course::factory()->for($team)->create(['title' => 'Kahramaa Exam Preparation', 'currency' => 'QAR']);
    $package = CoursePackage::factory()->for($team)->for($course)->create(['currency' => 'QAR', 'price' => 1500]);
    $profile = StudentProfile::factory()->for($team)->create([
        'full_name' => 'Aisha Candidate',
        'email' => 'aisha@example.com',
    ]);
    $enrollment = Enrollment::factory()
        ->for($team)
        ->for($profile, 'studentProfile')
        ->for($course)
        ->for($package, 'coursePackage')
        ->create([
            'status' => 'active',
            'payment_status' => 'paid',
        ]);
    $invoice = Invoice::factory()
        ->for($team)
        ->for($enrollment)
        ->for($profile, 'studentProfile')
        ->create([
            'number' => 'PX-INV-PDF-001',
            'type' => $invoiceType,
            'status' => 'paid',
            'currency' => 'QAR',
            'subtotal' => 1500,
            'discount_total' => 0,
            'tax_total' => 0,
            'total' => 1500,
            'metadata' => [
                'line_items' => [
                    ['description' => 'Kahramaa Exam Preparation', 'amount' => 1500],
                ],
            ],
        ]);
    $payment = PaymentTransaction::factory()
        ->for($team)
        ->for($enrollment)
        ->for($invoice)
        ->for($profile, 'studentProfile')
        ->for($manager, 'approvedBy')
        ->create([
            'reference' => 'BANK-PDF-001',
            'status' => 'approved',
            'currency' => 'QAR',
            'amount' => 1500,
            'approved_at' => now(),
        ]);

    $exam = Exam::factory()->for($team)->for($course)->create(['is_active' => true]);
    $question = Question::factory()->for($team)->for($course)->create(['is_active' => true]);
    LessonProgress::factory()->for($enrollment)->create(['completed_at' => now()]);
    ExamAttempt::factory()
        ->for($team)
        ->for($exam)
        ->for($enrollment)
        ->for($profile, 'studentProfile')
        ->create([
            'answers' => [['question_id' => $question->id, 'answer' => ['A']]],
            'result' => 'passed',
        ]);
    $certificate = Certificate::factory()
        ->for($team)
        ->for($enrollment)
        ->for($profile, 'studentProfile')
        ->for($course)
        ->for($manager, 'approvedBy')
        ->create([
            'certificate_number' => 'PX-CERT-PDF-001',
            'status' => 'issued',
            'result' => 'passed',
            'issued_at' => now(),
        ]);

    return [$invoice, $payment, $certificate, $manager];
}
