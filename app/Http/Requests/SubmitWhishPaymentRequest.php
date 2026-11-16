<?php

namespace App\Http\Requests;

use App\Models\ServiceRequest;
use Illuminate\Foundation\Http\FormRequest;

class SubmitWhishPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $serviceRequest = $this->route('serviceRequest');

        return $serviceRequest instanceof ServiceRequest
            && $this->user()?->can('pay', $serviceRequest);
    }

    public function rules(): array
    {
        return [
            'whish_reference' => ['required', 'string', 'min:4', 'max:64', 'regex:/^[A-Za-z0-9\- ]+$/'],
            // Optional proof. MIME is re-checked from file content, not the
            // client header; executables and scripts cannot pass mimes/mimetypes.
            'proof' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,webp,pdf',
                'mimetypes:image/jpeg,image/png,image/webp,application/pdf',
                'max:5120',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'whish_reference.regex' => __('payments.whish_reference_invalid'),
        ];
    }
}
