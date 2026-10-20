<?php

namespace App\Http\Requests;

use App\Models\Subscription;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePreferencesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'renewal_reminders'     => ['sometimes', 'boolean'],
            'trial_reminders'       => ['sometimes', 'boolean'],
            'expired_alerts'        => ['sometimes', 'boolean'],
            'reminder_days'         => ['sometimes', 'integer', 'min:1', 'max:30'],
            'default_billing_cycle' => ['sometimes', Rule::in(Subscription::BILLING_CYCLES)],
            'default_currency'      => ['sometimes', Rule::in(['PHP'])],
        ];
    }
}