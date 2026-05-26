<?php

namespace App\Contracts\Payments;

use App\Models\PaymentTransaction;
use App\Payments\PaymentCheckoutPreparation;
use App\Payments\PaymentCheckoutRequest;
use App\Payments\PaymentReconciliationResult;
use App\Payments\PaymentWebhookInspection;
use App\Payments\PaymentWebhookPayload;

interface PaymentGateway
{
    public function prepareCheckout(PaymentCheckoutRequest $request): PaymentCheckoutPreparation;

    public function inspectWebhook(PaymentWebhookPayload $payload): PaymentWebhookInspection;

    public function reconcile(PaymentTransaction $paymentTransaction): PaymentReconciliationResult;
}
