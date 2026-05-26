<?php

namespace App\Payments;

use App\Contracts\Payments\PaymentGateway;
use App\Enums\PaymentCheckoutStatus;
use App\Enums\PaymentReconciliationStatus;
use App\Enums\PaymentWebhookStatus;
use App\Models\PaymentTransaction;
use Psr\Log\LoggerInterface;

class NullPaymentGateway implements PaymentGateway
{
    /**
     * @param  list<string>  $pendingSignOffItems
     */
    public function __construct(
        private LoggerInterface $logger,
        private array $pendingSignOffItems = [],
    ) {
        $this->pendingSignOffItems = $pendingSignOffItems ?: PaymentGatewayMetadata::pendingSignOffItems();
    }

    public function prepareCheckout(PaymentCheckoutRequest $request): PaymentCheckoutPreparation
    {
        $preparation = new PaymentCheckoutPreparation(
            status: PaymentCheckoutStatus::ProviderSignOffRequired,
            provider: 'null',
            invoiceId: $request->invoiceId,
            amount: $request->amount,
            currency: $request->currency,
            checkoutUrl: null,
            providerReference: null,
            metadata: $this->metadata($request->metadata),
        );

        $this->logger->info(
            'Payment checkout preparation deferred until gateway sign-off.',
            $preparation->toLogContext(),
        );

        return $preparation;
    }

    public function inspectWebhook(PaymentWebhookPayload $payload): PaymentWebhookInspection
    {
        $inspection = new PaymentWebhookInspection(
            status: PaymentWebhookStatus::ProviderSignOffRequired,
            provider: $payload->provider,
            eventId: $payload->eventId,
            transactionReference: null,
            signatureVerified: null,
            metadata: $this->metadata([
                'webhook_summary' => $payload->sanitizedSummary(),
            ]),
        );

        $this->logger->info(
            'Payment webhook inspection deferred until gateway sign-off.',
            $inspection->toLogContext(),
        );

        return $inspection;
    }

    public function reconcile(PaymentTransaction $paymentTransaction): PaymentReconciliationResult
    {
        $result = new PaymentReconciliationResult(
            status: PaymentReconciliationStatus::ProviderSignOffRequired,
            provider: $paymentTransaction->provider ?? 'null',
            paymentTransactionId: (int) $paymentTransaction->getKey(),
            providerReference: $paymentTransaction->reference,
            providerStatus: null,
            metadata: $this->metadata([
                'current_status' => $paymentTransaction->status,
                'method' => $paymentTransaction->method,
            ]),
        );

        $this->logger->info(
            'Payment reconciliation deferred until gateway sign-off.',
            $result->toLogContext(),
        );

        return $result;
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    private function metadata(array $metadata): array
    {
        return PaymentGatewayMetadata::sanitize([
            ...$metadata,
            'pending_sign_off' => $this->pendingSignOffItems,
        ]);
    }
}
