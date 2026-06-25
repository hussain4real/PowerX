<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCommunicationProviderWebhookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $secret = config('powerx_notifications.provider.webhook_secret');

        if (blank($secret)) {
            return true;
        }

        return hash_equals((string) $secret, (string) $this->header('X-PowerX-Webhook-Secret'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'provider' => ['required', 'string', 'max:64'],
            'channel' => ['nullable', Rule::in(['whatsapp', 'sms'])],
            'communication_id' => ['nullable', 'integer', 'exists:communications,id'],
            'provider_message_id' => ['nullable', 'string', 'max:255'],
            'event_id' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['accepted', 'sent', 'delivered', 'read', 'failed', 'opt_out', 'opted_out', 'unsubscribed'])],
            'failure_reason' => ['nullable', 'string', 'max:255'],
            'occurred_at' => ['nullable', 'date'],
            'retry_at' => ['nullable', 'date'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
