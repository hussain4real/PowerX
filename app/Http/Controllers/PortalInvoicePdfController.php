<?php

namespace App\Http\Controllers;

use App\Actions\PowerX\ResolvePortalFinanceAccess;
use App\Models\Invoice;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\LaravelPdf\Enums\Format;
use Spatie\LaravelPdf\PdfBuilder;

use function Spatie\LaravelPdf\Support\pdf;

class PortalInvoicePdfController extends Controller
{
    public function __invoke(
        Request $request,
        Team $currentTeam,
        Invoice $invoice,
        ResolvePortalFinanceAccess $resolvePortalFinanceAccess,
    ): PdfBuilder {
        abort_unless($resolvePortalFinanceAccess->canUseInvoice($request->user(), $currentTeam, $invoice), 403);

        $invoice->loadMissing([
            'company',
            'enrollment.course',
            'enrollment.coursePackage',
            'paymentTransactions.approvedBy',
            'paymentTransactions.media',
            'studentProfile',
            'team',
        ]);

        $documentTitle = match ($invoice->type) {
            'quotation' => 'Quotation',
            'receipt' => 'Receipt',
            default => 'Invoice',
        };

        return pdf('pdf.powerx.invoice', compact('documentTitle', 'invoice'))
            ->format(Format::A4)
            ->margins(top: 12, right: 12, bottom: 14, left: 12, unit: 'mm')
            ->inline(Str::slug($invoice->number).'.pdf');
    }
}
