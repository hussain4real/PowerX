<?php

namespace App\Enums;

enum PaymentCheckoutStatus: string
{
    case Prepared = 'prepared';
    case ProviderSignOffRequired = 'provider_sign_off_required';
    case Failed = 'failed';
}
