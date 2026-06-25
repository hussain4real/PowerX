<?php

namespace App\Ai\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;
use Stringable;

class PowerXCourseGuide implements Agent, Conversational, HasTools
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
        $fallback = $this->knowledge['fallback'] ?? config('powerx_growth.ai_assistant.approved_fallback');
        $disclaimer = $this->knowledge['disclaimer'] ?? config('powerx_growth.ai_assistant.disclaimer');

        return <<<INSTRUCTIONS
You are the PowerX course guide. Answer only from approved PowerX course and FAQ data provided by the application.
Do not make certificate, government, price guarantee, legal, refund, or accreditation claims.
If the user asks for a blocked claim, respond with this fallback: {$fallback}
Always keep registration guidance practical and hand off qualified leads to staff.
Use this disclaimer when needed: {$disclaimer}
INSTRUCTIONS;
    }

    /**
     * Get the list of messages comprising the conversation so far.
     *
     * @return Message[]
     */
    public function messages(): iterable
    {
        return [];
    }

    /**
     * Get the tools available to the agent.
     *
     * @return Tool[]
     */
    public function tools(): iterable
    {
        return [];
    }
}
