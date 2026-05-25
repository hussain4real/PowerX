<?php

use App\Http\Controllers\CertificatePdfController;
use App\Http\Controllers\CertificateVerificationController;
use App\Http\Controllers\CorporateQuotationController;
use App\Http\Controllers\CourseCatalogController;
use App\Http\Controllers\CourseRegistrationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InstructorPortalController;
use App\Http\Controllers\InvoicePdfController;
use App\Http\Controllers\LeadInquiryController;
use App\Http\Controllers\PaymentReceiptPdfController;
use App\Http\Controllers\ReportExportController;
use App\Http\Controllers\StudentPortalController;
use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');
Route::inertia('style-guide', 'StyleGuide')->name('style-guide');
Route::get('courses', [CourseCatalogController::class, 'index'])->name('courses.index');
Route::get('courses/{course:slug}', [CourseCatalogController::class, 'show'])->name('courses.show');
Route::get('corporate/quotation', [CorporateQuotationController::class, 'create'])->name('corporate.quotations.create');
Route::post('corporate/quotation', [CorporateQuotationController::class, 'store'])->middleware('throttle:10,1')->name('corporate.quotations.store');
Route::post('leads', [LeadInquiryController::class, 'store'])->middleware('throttle:10,1')->name('leads.store');
Route::post('courses/{course:slug}/registrations', [CourseRegistrationController::class, 'store'])->middleware('throttle:10,1')->name('courses.registrations.store');
Route::get('certificates/verify/{token}', [CertificateVerificationController::class, 'show'])->name('certificates.verify');

Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');
        Route::get('student-portal', StudentPortalController::class)->name('student.portal');
        Route::get('instructor-portal', InstructorPortalController::class)
            ->middleware('can:attendance.manage')
            ->name('instructor.portal');
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('operational.csv', [ReportExportController::class, 'csv'])->name('operational.csv');
            Route::get('operational.pdf', [ReportExportController::class, 'pdf'])->name('operational.pdf');
        });
    });

Route::middleware(['auth'])->group(function () {
    Route::get('invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])->name('invitations.accept');

    Route::prefix('documents')->name('documents.')->group(function () {
        Route::get('invoices/{invoice}/pdf', InvoicePdfController::class)
            ->middleware('can:payments.manage')
            ->name('invoices.pdf');
        Route::get('payments/{paymentTransaction}/receipt', PaymentReceiptPdfController::class)
            ->middleware('can:payments.manage')
            ->name('payments.receipt');
        Route::get('certificates/{certificate}/pdf', CertificatePdfController::class)
            ->middleware('can:certificates.manage')
            ->name('certificates.pdf');
    });
});

require __DIR__.'/settings.php';
