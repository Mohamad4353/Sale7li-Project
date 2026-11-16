<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates a professional's bulk service-catalog update. Category ownership and
 * the "real, active category" allow-list are enforced in the controller, which
 * iterates the active categories rather than trusting the posted keys.
 */
class UpdateWorkerServicesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'worker' && $this->user()->workerProfile !== null;
    }

    public function rules(): array
    {
        return [
            'services' => ['nullable', 'array'],
            'services.*.enabled' => ['nullable', 'boolean'],
            'services.*.price_from' => ['nullable', 'numeric', 'min:0', 'max:100000'],
        ];
    }
}
