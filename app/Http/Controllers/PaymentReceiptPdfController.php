<?php

namespace App\Http\Controllers;

use App\Models\PaymentTransaction;
use Illuminate\Support\Str;
use Spatie\LaravelPdf\Enums\Format;
use Spatie\LaravelPdf\PdfBuilder;

use function Spatie\LaravelPdf\Support\pdf;

class PaymentReceiptPdfController extends Controller
{
    public function __invoke(PaymentTransaction $paymentTransaction): PdfBuilder
    {
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
