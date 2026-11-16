<?php

namespace App\Http\Requests;

use App\Models\RescheduleRequest;
use Illuminate\Foundation\Http\FormRequest;

class DeclineRescheduleRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        $rescheduleRequest = $this->route('rescheduleRequest');

        return $rescheduleRequest instanceof RescheduleRequest
            && $this->user()?->can('respond', $rescheduleRequest);
    }

    public function rules(): array
    {
        return [
            'decline_reason' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
