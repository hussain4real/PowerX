<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCorporateQuotationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $employees = collect($this->input('employees', []))
            ->filter(fn (array $employee): bool => filled($employee['full_name'] ?? null))
            ->values()
            ->all();

        $this->merge(['employees' => $employees]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:160'],
            'contact_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:255'],
            'course_id' => ['required', 'integer', Rule::exists('courses', 'id')->where('status', 'published')],
            'course_package_id' => [
                'nullable',
                'integer',
                Rule::exists('course_packages', 'id')
                    ->where(fn ($query) => $query->where('course_id', $this->integer('course_id'))->where('is_active', true)),
            ],
            'discount_total' => ['nullable', 'numeric', 'min:0'],
            'tax_total' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'employees' => ['required', 'array', 'min:1', 'max:50'],
            'employees.*.full_name' => ['required', 'string', 'max:120'],
            'employees.*.email' => ['nullable', 'email', 'max:255'],
            'employees.*.mobile' => ['nullable', 'string', 'max:40'],
            'employees.*.profession' => ['nullable', 'string', 'max:120'],
            'employees.*.preferred_schedule' => ['nullable', 'string', 'max:120'],
            'employees.*.notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
