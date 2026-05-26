<?php

namespace App\Actions\PowerX;

use App\Contracts\Payments\PaymentGateway;
use App\Models\Invoice;
use App\Payments\PaymentCheckoutPreparation;
use App\Payments\PaymentCheckoutRequest;

class PrepareOnlinePayment
{
    public function __construct(private PaymentGateway $paymentGateway) {}

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function handle(Invoice $invoice, array $metadata = []): PaymentCheckoutPreparation
    {
        return $this->paymentGateway->prepareCheckout(
            PaymentCheckoutRequest::fromInvoice($invoice, $metadata),
        );
    }
}
