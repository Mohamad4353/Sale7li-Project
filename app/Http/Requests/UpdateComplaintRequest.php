<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateComplaintRequest extends FormRequest
{
    /** Mirrors the `complaints.status` enum. */
    public const STATUSES = ['open', 'investigating', 'resolved', 'closed'];

    public function authorize(): bool
    {
        // The route sits in the admin group (role:admin); this is defence in depth.
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(self::STATUSES)],
            'internal_notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
