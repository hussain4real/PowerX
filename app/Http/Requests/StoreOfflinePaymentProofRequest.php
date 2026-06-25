<?php

namespace App\Http\Requests;

use App\Actions\PowerX\ResolvePortalFinanceAccess;
use App\Models\Invoice;
use App\Models\PaymentTransaction;
use App\Models\Team;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class StoreOfflinePaymentProofRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $invoice = $this->route('invoice');
        $team = $this->route('current_team');

        if (! $user || ! $invoice instanceof Invoice || ! $team instanceof Team) {
            return false;
        }

        return app(ResolvePortalFinanceAccess::class)->canUseInvoice($user, $team, $invoice);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'method' => ['required', 'string', Rule::in(array_keys(PaymentTransaction::manualMethodOptions()))],
            'amount' => ['required', 'numeric', 'min:1', 'max:999999'],
            'reference' => ['nullable', 'string', 'max:120'],
            'paid_at' => ['nullable', 'date'],
            'payer_name' => ['nullable', 'string', 'max:160'],
            'payer_email' => ['nullable', 'email', 'max:160'],
            'bank_name' => ['nullable', 'string', 'max:160'],
            'deposit_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'proof' => [
                'required',
                File::types(['pdf', 'jpg', 'jpeg', 'png', 'webp'])->max(10 * 1024),
            ],
        ];
    }
}
