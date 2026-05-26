<?php

use App\Actions\PowerX\PrepareOnlinePayment;
use App\Actions\PowerX\RecordManualPayment;
use App\Contracts\Payments\PaymentGateway;
use App\Enums\PaymentCheckoutStatus;
use App\Enums\PaymentReconciliationStatus;
use App\Enums\PaymentWebhookStatus;
use App\Models\Invoice;
use App\Models\PaymentTransaction;
use App\Payments\NullPaymentGateway;
use App\Payments\PaymentCheckoutRequest;
use App\Payments\PaymentGatewayMetadata;
use App\Payments\PaymentWebhookPayload;
use Psr\Log\AbstractLogger;

it('prepares online checkout through the null gateway without creating payments', function () {
    $gateway = app(PaymentGateway::class);
    $invoice = Invoice::factory()->create([
        'currency' => 'QAR',
        'total' => 950,
    ]);

    $preparation = app(PrepareOnlinePayment::class)->handle($invoice, [
        'student_email' => 'candidate@example.com',
        'provider_token' => 'tok_should_not_log',
    ]);

    expect($gateway)->toBeInstanceOf(NullPaymentGateway::class)
        ->and($preparation->status)->toBe(PaymentCheckoutStatus::ProviderSignOffRequired)
        ->and($preparation->provider)->toBe('null')
        ->and($preparation->invoiceId)->toBe($invoice->id)
        ->and($preparation->amount)->toBe('950.00')
        ->and($preparation->currency)->toBe('QAR')
        ->and($preparation->checkoutUrl)->toBeNull()
        ->and($preparation->providerReference)->toBeNull()
        ->and($preparation->metadata['provider_token'])->toBe(PaymentGatewayMetadata::REDACTED)
        ->and($preparation->metadata['pending_sign_off'])->toContain('Checkout success, cancellation, and webhook callback URLs')
        ->and($preparation->metadata['pending_sign_off'])->toContain('Refund and void policy plus provider capability')
        ->and($preparation->metadata['pending_sign_off'])->toContain('Legal, receipt, invoice, and tax/VAT wording')
        ->and($preparation->metadata['pending_sign_off'])->toContain('Settlement, reconciliation, payout, and dispute details')
        ->and(PaymentTransaction::query()->count())->toBe(0);
});

it('redacts checkout metadata before storing log context', function () {
    $logger = paymentSeamLogger();
    $gateway = new NullPaymentGateway($logger);

    $preparation = $gateway->prepareCheckout(new PaymentCheckoutRequest(
        invoiceId: 123,
        amount: '100.00',
        currency: 'QAR',
        metadata: [
            'student_email' => 'candidate@example.com',
            'provider_token' => 'tok_secret_value',
            'nested' => [
                'authorization' => 'Bearer secret',
                'card_number' => '4242424242424242',
                'safe_note' => 'finance approved for future online checkout',
            ],
        ],
    ));

    $context = $logger->records[0]['context'];
    $encodedContext = json_encode($context, JSON_THROW_ON_ERROR);

    expect($logger->records)->toHaveCount(1)
        ->and($logger->records[0]['level'])->toBe('info')
        ->and($preparation->metadata['pending_sign_off'])->toBe(PaymentGatewayMetadata::pendingSignOffItems())
        ->and(data_get($context, 'metadata.provider_token'))->toBe(PaymentGatewayMetadata::REDACTED)
        ->and(data_get($context, 'metadata.nested.authorization'))->toBe(PaymentGatewayMetadata::REDACTED)
        ->and(data_get($context, 'metadata.nested.card_number'))->toBe(PaymentGatewayMetadata::REDACTED)
        ->and(data_get($context, 'metadata.nested.safe_note'))->toBe('finance approved for future online checkout');

    expect($encodedContext)->not->toContain('tok_secret_value')
        ->and($encodedContext)->not->toContain('Bearer secret')
        ->and($encodedContext)->not->toContain('4242424242424242');
});

it('keeps webhook inspection and reconciliation inert until provider sign off', function () {
    $logger = paymentSeamLogger();
    $gateway = new NullPaymentGateway($logger, ['Provider sign-off']);
    $payload = new PaymentWebhookPayload(
        provider: 'future_gateway',
        eventId: 'evt_123',
        headers: [
            'X-Signature' => 'secret-signature',
            'X-Request-Id' => 'req_123',
        ],
        payload: [
            'provider_token' => 'payload-secret',
            'amount' => 100,
        ],
    );
    $payment = PaymentTransaction::factory()->create([
        'method' => 'card',
        'provider' => 'future_gateway',
        'reference' => 'PG-123',
        'status' => 'pending',
    ]);

    $inspection = $gateway->inspectWebhook($payload);
    $reconciliation = $gateway->reconcile($payment);
    $encodedLogs = json_encode($logger->records, JSON_THROW_ON_ERROR);

    expect($inspection->status)->toBe(PaymentWebhookStatus::ProviderSignOffRequired)
        ->and($inspection->provider)->toBe('future_gateway')
        ->and($inspection->eventId)->toBe('evt_123')
        ->and($inspection->transactionReference)->toBeNull()
        ->and($inspection->signatureVerified)->toBeNull()
        ->and(data_get($inspection->metadata, 'webhook_summary.headers.X-Signature'))->toBe(PaymentGatewayMetadata::REDACTED)
        ->and(data_get($inspection->metadata, 'webhook_summary.headers.X-Request-Id'))->toBe('req_123')
        ->and(data_get($inspection->metadata, 'webhook_summary.payload_keys'))->toBe(['provider_token', 'amount'])
        ->and($reconciliation->status)->toBe(PaymentReconciliationStatus::ProviderSignOffRequired)
        ->and($reconciliation->provider)->toBe('future_gateway')
        ->and($reconciliation->paymentTransactionId)->toBe($payment->id)
        ->and($reconciliation->providerReference)->toBe('PG-123')
        ->and($reconciliation->providerStatus)->toBeNull()
        ->and($payment->fresh()->status)->toBe('pending')
        ->and($logger->records)->toHaveCount(2);

    expect($encodedLogs)->not->toContain('payload-secret')
        ->and($encodedLogs)->not->toContain('secret-signature');
});

it('keeps manual payment recording unchanged', function () {
    $invoice = Invoice::factory()->create([
        'currency' => 'QAR',
        'total' => 500,
    ]);

    $payment = app(RecordManualPayment::class)->handle($invoice, [
        'amount' => 500,
        'method' => 'cash',
        'provider' => 'front_desk',
        'reference' => 'CASH-UNCHANGED-001',
    ]);

    expect($payment)->toBeInstanceOf(PaymentTransaction::class)
        ->and($payment->method)->toBe('cash')
        ->and($payment->provider)->toBe('front_desk')
        ->and($payment->reference)->toBe('CASH-UNCHANGED-001')
        ->and($payment->status)->toBe('pending')
        ->and($payment->metadata)->toMatchArray([
            'received_by' => 'manual_entry',
            'notes' => null,
        ]);
});

function paymentSeamLogger(): object
{
    return new class extends AbstractLogger
    {
        /**
         * @var list<array{level: mixed, message: string, context: array<string, mixed>}>
         */
        public array $records = [];

        /**
         * @param  array<string, mixed>  $context
         */
        public function log($level, string|Stringable $message, array $context = []): void
        {
            $this->records[] = [
                'level' => $level,
                'message' => (string) $message,
                'context' => $context,
            ];
        }
    };
}
