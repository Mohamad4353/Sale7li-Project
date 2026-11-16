<?php

namespace App\Http\Requests;

use App\Models\ServiceRequest;
use Illuminate\Foundation\Http\FormRequest;

class StoreQuotationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $serviceRequest = $this->route('serviceRequest');

        return $serviceRequest instanceof ServiceRequest
            && $this->user()?->can('quote', $serviceRequest);
    }

    public function rules(): array
    {
        // `decimal:0,2` keeps these to a plain money format (no scientific
        // notation, at most two decimal places) so the integer-cent parser in
        // the controller never receives a value it must reject at runtime.
        $money = ['numeric', 'decimal:0,2', 'min:0', 'max:1000000'];

        return [
            'base_price' => ['required', ...$money],
            'additional_charges' => ['nullable', ...$money],
            'discount' => ['nullable', ...$money],
            'tax' => ['nullable', ...$money],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
