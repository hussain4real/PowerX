<?php

namespace App\Http\Controllers;

use App\Actions\PowerX\ResolvePortalFinanceAccess;
use App\Models\PaymentTransaction;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\LaravelPdf\Enums\Format;
use Spatie\LaravelPdf\PdfBuilder;

use function Spatie\LaravelPdf\Support\pdf;

class PortalPaymentReceiptPdfController extends Controller
{
    public function __invoke(
        Request $request,
        Team $currentTeam,
        PaymentTransaction $paymentTransaction,
        ResolvePortalFinanceAccess $resolvePortalFinanceAccess,
    ): PdfBuilder {
        abort_unless($resolvePortalFinanceAccess->canUsePayment($request->user(), $currentTeam, $paymentTransaction), 403);
        abort_unless($paymentTransaction->status === PaymentTransaction::STATUS_APPROVED, 404);

        $paymentTransaction->loadMissing([
            'approvedBy',
            'company',
            'enrollment.course',
            'enrollment.coursePackage',
            'invoice',
            'media',
            'studentProfile',
            'team',
        ]);

        $receiptNumber = Str::slug($paymentTransaction->reference ?: 'PX-RCPT-'.$paymentTransaction->id);

        return pdf('pdf.powerx.receipt', [
            'payment' => $paymentTransaction,
            'receiptNumber' => $receiptNumber,
        ])
            ->format(Format::A4)
            ->margins(top: 12, right: 12, bottom: 14, left: 12, unit: 'mm')
            ->inline($receiptNumber.'.pdf');
    }
}
