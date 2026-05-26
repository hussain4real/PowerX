<?php

namespace App\Payments;

use Illuminate\Support\Str;

class PaymentGatewayMetadata
{
    public const REDACTED = '[redacted]';

    /**
     * @var list<string>
     */
    private const SENSITIVE_KEY_FRAGMENTS = [
        'authorization',
        'api_key',
        'card',
        'cvc',
        'cvv',
        'iban',
        'pan',
        'password',
        'private_key',
        'secret',
        'secret_key',
        'signature',
        'token',
    ];

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    public static function sanitize(array $metadata): array
    {
        $sanitized = [];

        foreach ($metadata as $key => $value) {
            $key = (string) $key;
            $sanitized[$key] = self::isSensitiveKey($key)
                ? self::REDACTED
                : self::sanitizeValue($value);
        }

        return $sanitized;
    }

    /**
     * @return list<string>
     */
    public static function pendingSignOffItems(): array
    {
        return [
            'Payment provider selection and commercial approval',
            'Checkout success, cancellation, and webhook callback URLs',
            'Refund and void policy plus provider capability',
            'Legal, receipt, invoice, and tax/VAT wording',
            'Settlement, reconciliation, payout, and dispute details',
        ];
    }

    private static function sanitizeValue(mixed $value): mixed
    {
        if (is_array($value)) {
            return self::sanitize($value);
        }

        return $value;
    }

    private static function isSensitiveKey(string $key): bool
    {
        return Str::of($key)
            ->lower()
            ->contains(self::SENSITIVE_KEY_FRAGMENTS);
    }
}
