<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates a professional's weekly availability.
 *
 * Day keys are strictly allow-listed to 0–6 (0 = Monday … 6 = Sunday, matching
 * the order rendered on the public profile). Times are plain H:i strings in the
 * application's local time — the `time` columns carry no timezone, and the
 * platform operates in a single timezone.
 */
class UpdateAvailabilityRequest extends FormRequest
{
    private const DAYS = ['0', '1', '2', '3', '4', '5', '6'];

    public function authorize(): bool
    {
        return $this->user()?->role === 'worker' && $this->user()->workerProfile !== null;
    }

    public function rules(): array
    {
        return [
            'days' => ['required', 'array'],
            'days.*.is_available' => ['required', 'boolean'],
            'days.*.start' => ['nullable', 'date_format:H:i'],
            'days.*.end' => ['nullable', 'date_format:H:i'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $days = (array) $this->input('days', []);

            foreach (array_keys($days) as $key) {
                if (! in_array((string) $key, self::DAYS, true)) {
                    $validator->errors()->add('days', __('workers.av_invalid_day'));

                    return;
                }
            }

            foreach ($days as $key => $day) {
                if (! filter_var($day['is_available'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                    continue;
                }

                $start = $day['start'] ?? null;
                $end = $day['end'] ?? null;

                if (! $start || ! $end) {
                    $validator->errors()->add("days.$key.end", __('workers.av_times_required'));
                } elseif ($end <= $start) {
                    $validator->errors()->add("days.$key.end", __('workers.av_end_after_start'));
                }
            }
        });
    }
}
