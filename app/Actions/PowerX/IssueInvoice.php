<?php

namespace App\Actions\PowerX;

use App\Models\Enrollment;
use App\Models\Invoice;
use Illuminate\Support\Str;

class IssueInvoice
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Enrollment $enrollment, array $data): Invoice
    {
        $subtotal = (float) $data['subtotal'];
        $discountTotal = (float) ($data['discount_total'] ?? 0);
        $taxTotal = (float) ($data['tax_total'] ?? 0);

        return Invoice::create([
            'team_id' => $enrollment->team_id,
            'enrollment_id' => $enrollment->id,
            'company_id' => $enrollment->company_id,
            'student_profile_id' => $enrollment->student_profile_id,
            'number' => $data['number'] ?? $this->invoiceNumber(),
            'type' => $data['type'] ?? 'invoice',
            'status' => 'issued',
            'currency' => $data['currency'] ?? $enrollment->coursePackage?->currency ?? $enrollment->course->currency,
            'subtotal' => $subtotal,
            'discount_total' => $discountTotal,
            'tax_total' => $taxTotal,
            'total' => max(0, $subtotal - $discountTotal + $taxTotal),
            'issued_at' => $data['issued_at'] ?? now(),
            'due_at' => $data['due_at'] ?? now()->addDays(7),
            'metadata' => [
                'line_items' => $data['line_items'] ?? [
                    [
                        'description' => $enrollment->course->title,
                        'amount' => $subtotal,
                    ],
                ],
            ],
        ]);
    }

    private function invoiceNumber(): string
    {
        do {
            $number = 'PX-INV-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        } while (Invoice::where('number', $number)->exists());

        return $number;
    }
}
