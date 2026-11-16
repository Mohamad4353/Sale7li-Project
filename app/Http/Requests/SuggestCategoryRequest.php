<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SuggestCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        // The route sits behind role:customer + verified; this is defence in
        // depth, mirroring StoreBookingRequest.
        return $this->user()?->role === 'customer';
    }

    public function rules(): array
    {
        return [
            'description' => ['required', 'string', 'max:2000'],
        ];
    }
}
