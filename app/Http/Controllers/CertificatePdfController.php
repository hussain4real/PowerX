<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use Illuminate\Support\Str;
use Spatie\LaravelPdf\Enums\Format;
use Spatie\LaravelPdf\PdfBuilder;

use function Spatie\LaravelPdf\Support\pdf;

class CertificatePdfController extends Controller
{
    public function __invoke(Certificate $certificate): PdfBuilder
    {
        $certificate->loadMissing([
            'approvedBy',
            'course',
            'enrollment.coursePackage',
            'studentProfile',
            'team',
        ]);

        return pdf('pdf.powerx.certificate', compact('certificate'))
            ->format(Format::A4)
            ->landscape()
            ->margins(top: 10, right: 10, bottom: 10, left: 10, unit: 'mm')
            ->inline(Str::slug($certificate->certificate_number).'.pdf');
    }
}
