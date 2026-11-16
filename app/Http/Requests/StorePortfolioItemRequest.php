<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates a new portfolio item. `worker_id` is never accepted here — the
 * controller sets it from the authenticated worker's profile relationship.
 * Image rules mirror the profile-photo upload (raster only, no SVG, ≤ 5 MB).
 */
class StorePortfolioItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'worker' && $this->user()->workerProfile !== null;
    }

    public function rules(): array
    {
        return [
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'mimetypes:image/jpeg,image/png,image/webp', 'max:5120', 'dimensions:max_width=6000,max_height=6000'],
            'title' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_visible' => ['required', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }
}
