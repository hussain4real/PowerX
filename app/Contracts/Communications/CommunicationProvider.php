<?php

namespace App\Contracts\Communications;

use App\Communications\CommunicationDeliveryResult;
use App\Models\Communication;

interface CommunicationProvider
{
    public function deliver(Communication $communication): CommunicationDeliveryResult;
}
