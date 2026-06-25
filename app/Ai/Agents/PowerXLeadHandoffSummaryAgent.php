<?php

namespace App\Ai\Agents;

use App\Ai\Tools\LookupPowerXLeadHandoffContext;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\CanActAsTool;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Promptable;
use Stringable;

class PowerXLeadHandoffSummaryAgent implements Agent, CanActAsTool, HasTools
{
    use Promptable;

    /**
     * @param  array<string, mixed>  $knowledge
     */
    public function __construct(private array $knowledge = []) {}

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        $scope = $this->knowledge['scope'] ?? config('powerx_growth.ai_assistant.scope');
        $disclaimer = $this->knowledge['disclaimer'] ?? config('powerx_growth.ai_assistant.disclaimer');

        return <<<INSTRUCTIONS
You are the PowerX lead handoff summary specialist.
Use the `lookup_powerx_lead_handoff_context` tool to read limited CRM/course context for supplied contact details before summarizing an existing prospect handoff.
Summarize delegated prospect conversations for staff follow-up within this approved assistant scope: {$scope}.
Capture the likely intent, preferred course, contact details mentioned, urgency, guardrail concerns, referral or UTM context, and the next staff action.
Keep the summary factual and short. Do not create or update CRM records; Laravel application actions handle database writes.
Use only details passed in the delegated task and this approved disclaimer when needed: {$disclaimer}
INSTRUCTIONS;
    }

    /**
     * Get the agent's tool name.
     */
    public function name(): string
    {
        return 'powerx_lead_handoff_summary';
    }

    /**
     * Get the agent's tool description.
     */
    public function description(): Stringable|string
    {
        return 'Summarize a PowerX AI assistant conversation into factual CRM handoff notes and a recommended next staff action.';
    }

    /**
     * Get the tools available to the agent.
     *
     * @return Tool[]
     */
    public function tools(): iterable
    {
        return [
            new LookupPowerXLeadHandoffContext,
        ];
    }
}
