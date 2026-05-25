<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use Inertia\Inertia;
use Inertia\Response;

class CertificateVerificationController extends Controller
{
    public function show(string $token): Response
    {
        $certificate = Certificate::query()
            ->with([
                'course:id,title,category',
                'studentProfile:id,full_name',
            ])
            ->where('verification_token', $token)
            ->where('status', 'issued')
            ->whereNotNull('issued_at')
            ->firstOrFail();

        return Inertia::render('Certificates/Verify', [
            'certificate' => [
                'number' => $certificate->certificate_number,
                'status' => $certificate->status,
                'result' => $certificate->result,
                'issuedAt' => $certificate->issued_at?->toFormattedDateString(),
                'expiresAt' => $certificate->expires_at?->toFormattedDateString(),
                'course' => [
                    'title' => $certificate->course->title,
                    'category' => $certificate->course->category,
                ],
                'student' => [
                    'name' => $certificate->studentProfile->full_name,
                ],
            ],
        ]);
    }
}
