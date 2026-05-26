<?php

namespace App\Enums;

enum PaymentWebhookStatus: string
{
    case Verified = 'verified';
    case Rejected = 'rejected';
    case ProviderSignOffRequired = 'provider_sign_off_required';
}
