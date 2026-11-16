<?php

namespace App\Http\Requests;

use App\Models\ServiceRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class UpdateJobStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $serviceRequest = $this->route('serviceRequest');

        return $serviceRequest instanceof ServiceRequest
            && $this->user()?->can('updateStatus', $serviceRequest);
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:scheduled,on_the_way,in_progress,completed'],
            'scheduled_date' => ['nullable', 'date', 'after_or_equal:today'],
            'scheduled_time' => ['nullable', 'date_format:H:i'],
            'completion_notes' => ['nullable', 'string', 'max:3000'],
            'completion_images' => ['nullable', 'array', 'max:5'],
            'completion_images.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'mimetypes:image/jpeg,image/png,image/webp', 'max:5120', 'dimensions:max_width=6000,max_height=6000'],
        ];
    }

    /**
     * Same authoritative datetime sanity as StoreBookingRequest/
     * StoreRescheduleRequestRequest, evaluated against the server clock: the
     * `after_or_equal:today` date-only rule above lets "today at a time that
     * already passed" through, so it must also be strictly in the future.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->input('status') !== 'scheduled') {
                return;
            }

            if ($validator->errors()->hasAny(['scheduled_date', 'scheduled_time'])) {
                return;
            }

            $date = $this->input('scheduled_date');
            $time = $this->input('scheduled_time');

            if (! $date || ! $time) {
                return;
            }

            try {
                $when = Carbon::parse(trim("$date $time"));
            } catch (\Throwable $e) {
                return;
            }

            if ($when->lessThanOrEqualTo(now())) {
                $validator->errors()->add('scheduled_time', __('bookings.time_in_past'));
            }
        });
    }
}
