<?php

use App\Actions\PowerX\RecordManualPayment;
use App\Models\Invoice;
use App\Models\PaymentTransaction;

it('keeps online payments deferred and accepts only signed-off manual methods', function (): void {
    $invoice = Invoice::factory()->create([
        'currency' => 'QAR',
        'total' => 500,
    ]);

    expect(config('powerx_payments.online.enabled'))->toBeFalse()
        ->and(PaymentTransaction::manualMethodOptions())->toBe([
            PaymentTransaction::METHOD_BANK_TRANSFER => 'Bank transfer',
            PaymentTransaction::METHOD_CASH => 'Cash',
            PaymentTransaction::METHOD_CHEQUE => 'Cheque',
        ]);

    $payment = app(RecordManualPayment::class)->handle($invoice, [
        'amount' => 500,
        'method' => PaymentTransaction::METHOD_CHEQUE,
        'reference' => 'CHQ-POWERX-001',
    ]);

    expect($payment->method)->toBe(PaymentTransaction::METHOD_CHEQUE)
        ->and($payment->status)->toBe(PaymentTransaction::STATUS_PENDING);

    app(RecordManualPayment::class)->handle($invoice, [
        'amount' => 500,
        'method' => 'card',
    ]);
})->throws(InvalidArgumentException::class, 'Unsupported manual payment method [card].');

it('renders analytics tags only when environment-backed ids are configured', function (): void {
    $this->withoutVite();

    config([
        'services.analytics.google_measurement_id' => 'G-POWERX123',
        'services.analytics.meta_pixel_id' => '1234567890',
    ]);

    $enabledHtml = $this->get(route('home'))
        ->assertSuccessful()
        ->getContent();

    expect($enabledHtml)
        ->toContain('https://www.googletagmanager.com/gtag/js?id=G-POWERX123')
        ->toContain("gtag('config', 'G-POWERX123')")
        ->toContain('https://connect.facebook.net/en_US/fbevents.js')
        ->toContain("fbq('init', '1234567890')");

    config([
        'services.analytics.google_measurement_id' => null,
        'services.analytics.meta_pixel_id' => null,
    ]);

    $disabledHtml = $this->get(route('home'))
        ->assertSuccessful()
        ->getContent();

    expect($disabledHtml)
        ->not->toContain('googletagmanager.com')
        ->not->toContain('connect.facebook.net')
        ->not->toContain("fbq('init'");
});
