<?php

namespace App\Payments;

use App\Models\Invoice;

readonly class PaymentCheckoutRequest
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public int $invoiceId,
        public string $amount,
        public string $currency,
        public array $metadata = [],
    ) {}

    /**
     * @param  array<string, mixed>  $metadata
     */
    public static function fromInvoice(Invoice $invoice, array $metadata = []): self
    {
        return new self(
            invoiceId: (int) $invoice->getKey(),
            amount: (string) $invoice->total,
            currency: $invoice->currency,
            metadata: $metadata,
        );
    }
}
