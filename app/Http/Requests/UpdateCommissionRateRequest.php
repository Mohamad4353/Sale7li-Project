<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Server-side validation for the admin commission-rate setting. The rate is a
 * decimal fraction with at most 4 decimal places, 0 – 0.9000 inclusive.
 * Client-side checks are never trusted.
 */
class UpdateCommissionRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'rate' => ['required', 'numeric', 'min:0', 'max:0.9', 'regex:/^\d(\.\d{1,4})?$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'rate.max' => __('payments.rate_error_range'),
            'rate.min' => __('payments.rate_error_range'),
            'rate.regex' => __('payments.rate_error_format'),
        ];
    }
}
