<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkerProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'worker' && $this->user()->workerProfile !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'regex:/^\+?[0-9][0-9 .()\-]{6,28}$/'],
            'bio' => ['nullable', 'string', 'max:3000'],
            'bio_ar' => ['nullable', 'string', 'max:3000'],
            'experience_years' => ['required', 'integer', 'min:0', 'max:60'],
            'service_radius_km' => ['required', 'numeric', 'min:1', 'max:200'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'mimetypes:image/jpeg,image/png,image/webp', 'max:5120', 'dimensions:max_width=6000,max_height=6000'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => __('validation.custom.phone.regex'),
        ];
    }
}
