<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePowerXAssistantRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:1500'],
            'name' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'company_name' => ['nullable', 'string', 'max:160'],
            'course_id' => [
                'nullable',
                'integer',
                Rule::exists('courses', 'id')->where(fn ($query) => $query->where('status', 'published')),
            ],
            'course_interest' => ['nullable', 'string', 'max:160'],
            'source' => ['nullable', 'string', 'max:80'],
            'campaign' => ['nullable', 'string', 'max:160'],
            'utm_source' => ['nullable', 'string', 'max:160'],
            'utm_medium' => ['nullable', 'string', 'max:160'],
            'utm_campaign' => ['nullable', 'string', 'max:160'],
            'utm_content' => ['nullable', 'string', 'max:160'],
            'utm_term' => ['nullable', 'string', 'max:160'],
            'referral_name' => ['nullable', 'string', 'max:120'],
            'referral_phone' => ['nullable', 'string', 'max:40'],
            'referral_email' => ['nullable', 'email', 'max:255'],
            'referral_relationship' => ['nullable', 'string', 'max:120'],
        ];
    }
}
