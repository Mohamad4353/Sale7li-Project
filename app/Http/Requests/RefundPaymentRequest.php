<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Admin full-refund request. The ONLY accepted input is an optional free-text
 * reason — it is recorded for audit and never influences any financial figure.
 * The refund amount is derived entirely server-side from the payment.
 */
class RefundPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
