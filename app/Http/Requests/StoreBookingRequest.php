<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class StoreBookingRequest extends FormRequest
{
    /** How far ahead a service can be scheduled. */
    private const MAX_HORIZON_DAYS = 90;

    public function authorize(): bool
    {
        return $this->user()?->role === 'customer';
    }

    public function rules(): array
    {
        return [
            'service_category_id' => [
                'required',
                Rule::exists('service_categories', 'id')->where('is_active', true),
            ],
            'problem_description' => ['required', 'string', 'max:5000'],
            'address_id' => [
                'required',
                Rule::exists('addresses', 'id')->where('user_id', $this->user()->id),
            ],
            'preferred_date' => ['required', 'date', 'after_or_equal:today'],
            'preferred_time' => ['required', 'date_format:H:i'],
            'is_emergency' => ['nullable', 'boolean'],
            'photos' => ['nullable', 'array', 'max:5'],
            'photos.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'mimetypes:image/jpeg,image/png,image/webp', 'max:5120', 'dimensions:max_width=6000,max_height=6000'],
        ];
    }

    /**
     * Authoritative datetime sanity, evaluated against the server clock: the
     * requested slot must be strictly in the future and no more than
     * MAX_HORIZON_DAYS ahead. The per-field format rules run first.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->hasAny(['preferred_date', 'preferred_time'])) {
                return;
            }

            try {
                $when = Carbon::parse(trim($this->input('preferred_date').' '.$this->input('preferred_time')));
            } catch (\Throwable $e) {
                return;
            }

            if ($when->lessThanOrEqualTo(now())) {
                $validator->errors()->add('preferred_time', __('bookings.time_in_past'));
            }

            if ($when->greaterThan(now()->addDays(self::MAX_HORIZON_DAYS))) {
                $validator->errors()->add('preferred_date', __('bookings.date_too_far'));
            }
        });
    }
}
