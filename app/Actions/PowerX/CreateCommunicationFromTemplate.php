<?php

namespace App\Actions\PowerX;

use App\Models\Communication;

class CreateCommunicationFromTemplate
{
    public function __construct(private RenderCommunicationTemplate $renderCommunicationTemplate) {}

    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $attributes
     */
    public function handle(string $templateKey, array $context, array $attributes = []): Communication
    {
        $rendered = $this->renderCommunicationTemplate->handle($templateKey, $context);
        $channel = $attributes['channel'] ?? Communication::CHANNEL_EMAIL;

        return Communication::query()->create([
            'team_id' => $attributes['team_id'] ?? null,
            'lead_id' => $attributes['lead_id'] ?? null,
            'student_profile_id' => $attributes['student_profile_id'] ?? null,
            'company_id' => $attributes['company_id'] ?? null,
            'user_id' => $attributes['user_id'] ?? null,
            'channel' => $channel,
            'template_key' => $templateKey,
            'subject' => $rendered['subject'],
            'message' => $channel === Communication::CHANNEL_WHATSAPP ? $rendered['whatsappText'] : $rendered['message'],
            'status' => $attributes['status'] ?? Communication::STATUS_DRAFT,
            'scheduled_at' => $attributes['scheduled_at'] ?? null,
            'metadata' => [
                'template_context' => $context,
                'whatsapp_text' => $rendered['whatsappText'],
                'whatsapp_url' => $rendered['whatsappUrl'],
                ...($attributes['metadata'] ?? []),
            ],
        ]);
    }
}
