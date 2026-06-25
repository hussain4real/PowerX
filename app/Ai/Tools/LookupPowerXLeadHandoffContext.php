<?php

namespace App\Ai\Tools;

use App\Models\Course;
use App\Models\Lead;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class LookupPowerXLeadHandoffContext implements Tool
{
    /**
     * Get the tool name exposed to AI providers.
     */
    public function name(): string
    {
        return 'lookup_powerx_lead_handoff_context';
    }

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Look up limited read-only PowerX CRM and course context for an AI assistant handoff using supplied contact details.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $email = trim((string) $request->string('email'));
        $phone = trim((string) $request->string('phone'));
        $message = trim((string) $request->string('message'));
        $courseId = $request->integer('course_id');
        $course = $courseId > 0
            ? Course::query()
                ->select(['id', 'title', 'category', 'delivery_mode'])
                ->published()
                ->find($courseId)
            : null;
        $lead = filled($email) || filled($phone)
            ? Lead::query()
                ->select(['id', 'company_id', 'course_id', 'name', 'email', 'phone', 'source', 'campaign', 'status', 'course_interest', 'follow_up_at', 'metadata', 'updated_at'])
                ->with([
                    'company:id,name',
                    'course:id,title,category,delivery_mode',
                ])
                ->where(function (Builder $builder) use ($email, $phone): void {
                    $builder
                        ->when($email !== '', fn (Builder $builder) => $builder->orWhere('email', $email))
                        ->when($phone !== '', fn (Builder $builder) => $builder->orWhere('phone', $phone));
                })
                ->latest('updated_at')
                ->first()
            : null;

        return json_encode([
            'source' => 'powerx_database',
            'scope' => 'limited_crm_handoff_context',
            'contactProvided' => filled($email) || filled($phone),
            'selectedCourse' => $course === null ? null : [
                'id' => $course->id,
                'title' => $course->title,
                'category' => $course->category,
                'deliveryMode' => $course->delivery_mode,
            ],
            'existingLead' => $lead === null ? null : $this->leadPayload($lead),
            'messageSignals' => [
                'registrationIntent' => str($message)->lower()->contains(['register', 'registration', 'enroll', 'quotation', 'quote', 'schedule', 'batch', 'call', 'whatsapp', 'payment', 'course fee']),
                'messageExcerpt' => str($message)->limit(220)->toString(),
            ],
        ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'email' => $schema->string()->description('Optional prospect email to match against existing CRM leads.'),
            'phone' => $schema->string()->description('Optional prospect phone to match against existing CRM leads.'),
            'course_id' => $schema->integer()->description('Optional selected PowerX course ID.'),
            'message' => $schema->string()->description('Optional prospect message used only for factual handoff signals.'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function leadPayload(Lead $lead): array
    {
        return [
            'id' => $lead->id,
            'name' => $lead->name,
            'source' => $lead->source,
            'campaign' => $lead->campaign,
            'status' => $lead->status,
            'courseInterest' => $lead->course_interest,
            'followUpAt' => $lead->follow_up_at?->toDateTimeString(),
            'updatedAt' => $lead->updated_at?->toDateTimeString(),
            'company' => $lead->company === null ? null : [
                'id' => $lead->company->id,
                'name' => $lead->company->name,
            ],
            'course' => $lead->course === null ? null : [
                'id' => $lead->course->id,
                'title' => $lead->course->title,
                'category' => $lead->course->category,
                'deliveryMode' => $lead->course->delivery_mode,
            ],
            'aiAssistant' => [
                'intent' => data_get($lead->metadata, 'ai_assistant.intent'),
                'preferredCourseTitle' => data_get($lead->metadata, 'ai_assistant.preferred_course_title'),
                'guardrailTriggered' => data_get($lead->metadata, 'ai_assistant.guardrail_triggered'),
                'nextAction' => data_get($lead->metadata, 'ai_assistant.next_action'),
            ],
        ];
    }
}
