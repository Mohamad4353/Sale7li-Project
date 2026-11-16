<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates an edit to an existing portfolio item. The image is optional here
 * (omit it to keep the current one, supply one to replace it). Ownership of the
 * item itself is enforced in the controller, not from any request field.
 */
class UpdatePortfolioItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'worker' && $this->user()->workerProfile !== null;
    }

    public function rules(): array
    {
        return [
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'mimetypes:image/jpeg,image/png,image/webp', 'max:5120', 'dimensions:max_width=6000,max_height=6000'],
            'title' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_visible' => ['required', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }
}
