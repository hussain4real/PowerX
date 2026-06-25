<?php

namespace App\Actions\PowerX;

use App\Models\PaymentTransaction;
use Illuminate\Support\Str;
use Spatie\LaravelPdf\Enums\Format;
use Spatie\LaravelPdf\Facades\Pdf;

class GeneratePaymentReceiptPdf
{
    public function handle(PaymentTransaction $paymentTransaction): string
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

        $path = 'powerx/receipts/'.$this->receiptNumber($paymentTransaction).'.pdf';

        Pdf::view('pdf.powerx.receipt', [
            'payment' => $paymentTransaction,
            'receiptNumber' => $this->receiptNumber($paymentTransaction),
        ])
            ->format(Format::A4)
            ->margins(top: 12, right: 12, bottom: 14, left: 12, unit: 'mm')
            ->disk('local')
            ->save($path);

        $paymentTransaction->forceFill([
            'metadata' => array_replace_recursive($paymentTransaction->metadata ?? [], [
                'receipt_pdf' => [
                    'path' => $path,
                    'generated_at' => now()->toISOString(),
                ],
            ]),
        ])->save();

        return $path;
    }

    private function receiptNumber(PaymentTransaction $paymentTransaction): string
    {
        return Str::slug($paymentTransaction->reference ?: 'PX-RCPT-'.$paymentTransaction->id);
    }
}
