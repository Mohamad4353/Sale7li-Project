<?php

namespace App\Http\Requests;

use App\Models\ServiceRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class StoreRescheduleRequestRequest extends FormRequest
{
    /** Mirrors StoreBookingRequest — how far ahead a proposed slot can be. */
    private const MAX_HORIZON_DAYS = 90;

    public function authorize(): bool
    {
        $serviceRequest = $this->route('serviceRequest');

        return $serviceRequest instanceof ServiceRequest
            && $this->user()?->can('requestReschedule', $serviceRequest);
    }

    public function rules(): array
    {
        return [
            'proposed_date' => ['required', 'date', 'after_or_equal:today'],
            'proposed_time' => ['required', 'date_format:H:i'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Same authoritative datetime sanity as StoreBookingRequest, evaluated
     * against the server clock: strictly in the future, no more than
     * MAX_HORIZON_DAYS ahead. Reuses the identical bookings.* messages — the
     * rule is conceptually the same one, just applied to a proposed slot.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->hasAny(['proposed_date', 'proposed_time'])) {
                return;
            }

            try {
                $when = Carbon::parse(trim($this->input('proposed_date').' '.$this->input('proposed_time')));
            } catch (\Throwable $e) {
                return;
            }

            if ($when->lessThanOrEqualTo(now())) {
                $validator->errors()->add('proposed_time', __('bookings.time_in_past'));
            }

            if ($when->greaterThan(now()->addDays(self::MAX_HORIZON_DAYS))) {
                $validator->errors()->add('proposed_date', __('bookings.date_too_far'));
            }
        });
    }
}
