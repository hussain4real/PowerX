<?php

namespace App\Http\Controllers;

use App\Actions\PowerX\AnswerProspectAssistantPrompt;
use App\Http\Requests\StorePowerXAssistantRequest;
use Illuminate\Http\JsonResponse;
use Laravel\Pennant\Feature;

class PowerXAssistantController extends Controller
{
    public function __invoke(StorePowerXAssistantRequest $request, AnswerProspectAssistantPrompt $answerProspectAssistantPrompt): JsonResponse
    {
        abort_unless(Feature::active(config('powerx_growth.ai_assistant.feature', 'powerx-ai-assistant')), 404);

        return response()->json($answerProspectAssistantPrompt->handle($request->validated()));
    }
}
