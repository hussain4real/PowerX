<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Support\Str;
use Spatie\LaravelPdf\Enums\Format;
use Spatie\LaravelPdf\PdfBuilder;

use function Spatie\LaravelPdf\Support\pdf;

class InvoicePdfController extends Controller
{
    public function __invoke(Invoice $invoice): PdfBuilder
    {
        $invoice->loadMissing([
            'company',
            'enrollment.course',
            'enrollment.coursePackage',
            'paymentTransactions',
            'studentProfile',
            'team',
        ]);

        $documentTitle = $invoice->type === 'quotation' ? 'Quotation' : 'Invoice';

        return pdf('pdf.powerx.invoice', compact('documentTitle', 'invoice'))
            ->format(Format::A4)
            ->margins(top: 12, right: 12, bottom: 14, left: 12, unit: 'mm')
            ->inline(Str::slug($invoice->number).'.pdf');
    }
}
