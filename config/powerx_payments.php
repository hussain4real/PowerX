<?php

use App\Payments\PaymentGatewayMetadata;

return [
    'online' => [
        'enabled' => env('POWERX_ONLINE_PAYMENTS_ENABLED', false),
    ],

    'manual' => [
        'default_method' => 'bank_transfer',
        'methods' => [
            'bank_transfer' => 'Bank transfer',
            'cash' => 'Cash',
            'cheque' => 'Cheque',
        ],
        'bank_transfer' => [
            'bank_name' => env('POWERX_BANK_NAME'),
            'account_name' => env('POWERX_BANK_ACCOUNT_NAME'),
            'account_number' => env('POWERX_BANK_ACCOUNT_NUMBER'),
            'iban' => env('POWERX_BANK_IBAN'),
            'swift' => env('POWERX_BANK_SWIFT'),
            'currency' => env('POWERX_BANK_CURRENCY', 'QAR'),
        ],
    ],

    'gateway' => [
        'driver' => 'null',
        'pending_sign_off' => PaymentGatewayMetadata::pendingSignOffItems(),
    ],
];
