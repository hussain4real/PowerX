<?php

namespace App\Actions\PowerX;

use App\Models\Certificate;
use Illuminate\Support\Str;
use Spatie\LaravelPdf\Enums\Format;
use Spatie\LaravelPdf\Facades\Pdf;

class GenerateCertificatePdf
{
    public function handle(Certificate $certificate): string
    {
        $certificate->loadMissing([
            'approvedBy',
            'course',
            'enrollment.coursePackage',
            'studentProfile',
            'team',
        ]);

        $path = 'powerx/certificates/'.Str::slug($certificate->certificate_number).'.pdf';

        Pdf::view('pdf.powerx.certificate', [
            'certificate' => $certificate,
        ])
            ->format(Format::A4)
            ->landscape()
            ->margins(top: 10, right: 10, bottom: 10, left: 10, unit: 'mm')
            ->disk('local')
            ->save($path);

        $certificate->forceFill([
            'pdf_generated_at' => now(),
            'metadata' => array_replace_recursive($certificate->metadata ?? [], [
                'pdf' => [
                    'path' => $path,
                    'generated_at' => now()->toISOString(),
                ],
            ]),
        ])->save();

        return $path;
    }
}
