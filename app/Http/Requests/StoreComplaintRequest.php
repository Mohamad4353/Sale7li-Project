<?php

namespace App\Http\Requests;

use App\Models\ServiceRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreComplaintRequest extends FormRequest
{
    /** The customer-facing complaint categories. Priority and status are server-set. */
    public const CATEGORIES = ['work_quality', 'no_show', 'damage', 'billing', 'behavior', 'other'];

    public function authorize(): bool
    {
        $serviceRequest = $this->route('serviceRequest');

        return $serviceRequest instanceof ServiceRequest
            && $this->user()?->can('complain', $serviceRequest);
    }

    public function rules(): array
    {
        return [
            'category' => ['required', Rule::in(self::CATEGORIES)],
            'description' => ['required', 'string', 'max:3000'],
        ];
    }
}
