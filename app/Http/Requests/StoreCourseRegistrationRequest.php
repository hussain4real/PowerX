<?php

namespace App\Http\Requests;

use App\Models\Course;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCourseRegistrationRequest extends FormRequest
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
        $course = $this->route('course');
        $courseId = $course instanceof Course ? $course->getKey() : null;

        return [
            'full_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'mobile' => ['required', 'string', 'max:40'],
            'profession' => ['nullable', 'string', 'max:120'],
            'company_name' => ['nullable', 'string', 'max:160'],
            'qatar_location' => ['nullable', 'string', 'max:120'],
            'preferred_schedule' => ['nullable', 'string', 'max:120'],
            'course_package_id' => [
                'nullable',
                'integer',
                Rule::exists('course_packages', 'id')
                    ->where(fn ($query) => $query->where('course_id', $courseId)->where('is_active', true)),
            ],
            'message' => ['nullable', 'string', 'max:1000'],
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
