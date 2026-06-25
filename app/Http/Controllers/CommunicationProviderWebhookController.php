<?php

namespace App\Http\Controllers;

use App\Actions\PowerX\ApplyCommunicationProviderWebhook;
use App\Http\Requests\StoreCommunicationProviderWebhookRequest;
use Illuminate\Http\JsonResponse;

class CommunicationProviderWebhookController extends Controller
{
    public function __invoke(
        StoreCommunicationProviderWebhookRequest $request,
        ApplyCommunicationProviderWebhook $applyCommunicationProviderWebhook,
    ): JsonResponse {
        $communication = $applyCommunicationProviderWebhook->handle($request->validated());

        return response()->json([
            'handled' => $communication !== null,
            'communication_id' => $communication?->id,
            'status' => $communication?->status,
        ], $communication ? 200 : 202);
    }
}
