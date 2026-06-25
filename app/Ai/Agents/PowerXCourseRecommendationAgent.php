<?php

namespace App\Ai\Agents;

use App\Ai\Tools\LookupPowerXAssistantPolicy;
use App\Ai\Tools\SearchPowerXCourseCatalog;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\CanActAsTool;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Promptable;
use Stringable;

class PowerXCourseRecommendationAgent implements Agent, CanActAsTool, HasTools
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
        $fallback = $this->knowledge['fallback'] ?? config('powerx_growth.ai_assistant.approved_fallback');
        $disclaimer = $this->knowledge['disclaimer'] ?? config('powerx_growth.ai_assistant.disclaimer');

        return <<<INSTRUCTIONS
You are the PowerX course recommendation specialist.
Use the `search_powerx_course_catalog` tool to read approved published PowerX courses, active packages, and scheduled batches from the application database before recommending a course.
Use the `lookup_powerx_assistant_policy` tool when a question overlaps FAQ, pricing, certificate, refund, accreditation, or government-approval policy.
Operate within this approved assistant scope: {$scope}
If the database and approved policy data do not answer the task, say PowerX staff must confirm the detail and use this fallback when needed: {$fallback}
Do not invent course names, prices, batches, certificate outcomes, accreditation, or government approval claims.
Return concise guidance the parent PowerX course guide can use in a prospect-facing response.
Use this disclaimer when needed: {$disclaimer}
INSTRUCTIONS;
    }

    /**
     * Get the agent's tool name.
     */
    public function name(): string
    {
        return 'powerx_course_recommendation';
    }

    /**
     * Get the agent's tool description.
     */
    public function description(): Stringable|string
    {
        return 'Recommend PowerX courses, packages, pricing, and scheduled batches using only approved catalog and FAQ data.';
    }

    /**
     * Get the tools available to the agent.
     *
     * @return Tool[]
     */
    public function tools(): iterable
    {
        return [
            new SearchPowerXCourseCatalog,
            new LookupPowerXAssistantPolicy,
        ];
    }
}
