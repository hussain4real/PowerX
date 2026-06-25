<?php

namespace App\Ai\Agents;

use App\Ai\Tools\LookupPowerXAssistantPolicy;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\CanActAsTool;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Promptable;
use Stringable;

class PowerXGuardrailReviewAgent implements Agent, CanActAsTool, HasTools
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
        $blockedTopics = collect($this->knowledge['blockedTopics'] ?? config('powerx_growth.ai_assistant.blocked_topics', []))
            ->map(fn (mixed $topic): string => (string) $topic)
            ->filter()
            ->values()
            ->implode(', ');
        $fallback = $this->knowledge['fallback'] ?? config('powerx_growth.ai_assistant.approved_fallback');
        $disclaimer = $this->knowledge['disclaimer'] ?? config('powerx_growth.ai_assistant.disclaimer');

        return <<<INSTRUCTIONS
You are the PowerX AI guardrail review specialist.
Use the `lookup_powerx_assistant_policy` tool to read the latest approved blocked topics, fallback copy, disclaimer, and FAQ policy before finalizing a guardrail review.
Review delegated prospect questions and proposed answers for unapproved certificate, government, price guarantee, legal, refund, or accreditation claims.
Blocked topics include: {$blockedTopics}.
If a blocked or unsupported claim appears, return the approved fallback exactly: {$fallback}
If the content is safe, explain briefly that it may be answered from approved course/FAQ data and include this disclaimer when needed: {$disclaimer}
Do not make a final sales promise, legal commitment, refund commitment, accreditation claim, or government approval claim.
INSTRUCTIONS;
    }

    /**
     * Get the agent's tool name.
     */
    public function name(): string
    {
        return 'powerx_guardrail_review';
    }

    /**
     * Get the agent's tool description.
     */
    public function description(): Stringable|string
    {
        return 'Check a PowerX prospect question or draft answer for unsupported certificate, government, legal, refund, pricing, or accreditation claims.';
    }

    /**
     * Get the tools available to the agent.
     *
     * @return Tool[]
     */
    public function tools(): iterable
    {
        return [
            new LookupPowerXAssistantPolicy,
        ];
    }
}
