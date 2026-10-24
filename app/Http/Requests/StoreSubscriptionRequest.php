<?php

namespace App\Http\Requests;

use App\Models\Subscription;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'            => ['required', 'string', 'max:100'],
            'cost'            => ['required', 'numeric', 'min:0', 'max:9999999'],
            'billing_cycle'   => ['required', Rule::in(Subscription::BILLING_CYCLES)],
            'renewal_date'    => ['required', 'date'],
            'category'        => ['required', Rule::in(Subscription::CATEGORIES)],
            'payment_method'  => ['nullable', 'string', 'max:100'],
            'status'          => ['required', Rule::in(Subscription::STATUSES)],
            'is_trial'        => ['nullable', 'boolean'],
            'trial_end_date'  => [
                Rule::requiredIf(fn () => $this->boolean('is_trial')),
                'nullable',
                'date',
                'after_or_equal:renewal_date',
            ],
            'last_used_date'  => ['nullable', 'date', 'before_or_equal:today'],
            'notes'           => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'cost.min'                 => 'The cost cannot be negative.',
            'trial_end_date.required_if' => 'A trial end date is required when this is a free trial.',
            'trial_end_date.after_or_equal' => 'The trial end date cannot be before the renewal date.',
            'last_used_date.before_or_equal' => 'The last used date cannot be in the future.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_trial' => $this->boolean('is_trial'),
        ]);
    }
}