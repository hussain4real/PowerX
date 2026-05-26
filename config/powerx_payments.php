<?php

use App\Payments\PaymentGatewayMetadata;

return [
    'gateway' => [
        'driver' => 'null',
        'pending_sign_off' => PaymentGatewayMetadata::pendingSignOffItems(),
    ],
];
