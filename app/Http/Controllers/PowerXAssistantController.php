<?php

namespace App\Http\Controllers;

use App\Actions\PowerX\AnswerProspectAssistantPrompt;
use App\Http\Requests\StorePowerXAssistantRequest;
use App\Support\PowerXFeatureFlags;
use Illuminate\Http\JsonResponse;

class PowerXAssistantController extends Controller
{
    public function __invoke(StorePowerXAssistantRequest $request, AnswerProspectAssistantPrompt $answerProspectAssistantPrompt): JsonResponse
    {
        abort_unless(PowerXFeatureFlags::aiAssistantIsActive(), 404);

        return response()->json($answerProspectAssistantPrompt->handle($request->validated()));
    }
}
