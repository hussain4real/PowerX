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
            'queued_at' => $attributes['queued_at'] ?? null,
            'sent_at' => $attributes['sent_at'] ?? null,
            'delivered_at' => $attributes['delivered_at'] ?? null,
            'failed_at' => $attributes['failed_at'] ?? null,
            'retry_at' => $attributes['retry_at'] ?? null,
            'retry_count' => $attributes['retry_count'] ?? 0,
            'failure_reason' => $attributes['failure_reason'] ?? null,
            'opted_out_at' => $attributes['opted_out_at'] ?? null,
            'opt_out_reason' => $attributes['opt_out_reason'] ?? null,
            'metadata' => [
                'template_context' => $context,
                'whatsapp_text' => $rendered['whatsappText'],
                'whatsapp_url' => $rendered['whatsappUrl'],
                ...($attributes['metadata'] ?? []),
            ],
        ]);
    }
}
