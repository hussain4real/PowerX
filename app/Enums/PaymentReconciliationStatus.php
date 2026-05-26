<?php

namespace App\Enums;

enum PaymentReconciliationStatus: string
{
    case Matched = 'matched';
    case Mismatched = 'mismatched';
    case Pending = 'pending';
    case ProviderSignOffRequired = 'provider_sign_off_required';
}
