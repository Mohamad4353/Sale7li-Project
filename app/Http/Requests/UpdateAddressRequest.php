<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('address')) ?? false;
    }

    /** Same shape as StoreAddressRequest — user_id / id are never accepted here. */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:50'],
            'street' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'area' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:30'],
            'instructions' => ['nullable', 'string', 'max:1000'],
            'is_default' => ['nullable', 'boolean'],
        ];
    }
}
