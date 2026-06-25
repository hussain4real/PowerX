<?php

namespace App\Actions\PowerX;

use App\Models\Invoice;
use Illuminate\Support\Str;
use Spatie\LaravelPdf\Enums\Format;
use Spatie\LaravelPdf\Facades\Pdf;

class GenerateInvoicePdf
{
    public function handle(Invoice $invoice): string
    {
        $invoice->loadMissing([
            'company',
            'enrollment.course',
            'enrollment.coursePackage',
            'paymentTransactions.approvedBy',
            'paymentTransactions.media',
            'studentProfile',
            'team',
        ]);

        $path = $this->path($invoice);

        Pdf::view('pdf.powerx.invoice', [
            'documentTitle' => $this->documentTitle($invoice),
            'invoice' => $invoice,
        ])
            ->format(Format::A4)
            ->margins(top: 12, right: 12, bottom: 14, left: 12, unit: 'mm')
            ->disk('local')
            ->save($path);

        $invoice->forceFill([
            'metadata' => array_replace_recursive($invoice->metadata ?? [], [
                'pdf' => [
                    'path' => $path,
                    'generated_at' => now()->toISOString(),
                ],
            ]),
        ])->save();

        return $path;
    }

    private function documentTitle(Invoice $invoice): string
    {
        return match ($invoice->type) {
            'quotation' => 'Quotation',
            'receipt' => 'Receipt',
            default => 'Invoice',
        };
    }

    private function path(Invoice $invoice): string
    {
        $directory = $invoice->type === 'quotation' ? 'powerx/quotations' : 'powerx/invoices';

        return $directory.'/'.Str::slug($invoice->number).'.pdf';
    }
}
