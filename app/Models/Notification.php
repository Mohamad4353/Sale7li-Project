<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Lang;

class Notification extends Model
{
    protected $fillable = ['user_id', 'type', 'title', 'message', 'data', 'service_request_id', 'read_at'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime', 'data' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class, 'service_request_id');
    }

    /**
     * Create a structured notification. `data` carries the parameters; `title`
     * and `message` are also stored, rendered in English, so any legacy or
     * non-localised reader still works. Localised text is produced at display
     * time by renderedTitle() / renderedBody().
     *
     * @param  array<string, mixed>  $data
     */
    public static function record(int $userId, string $type, ?int $serviceRequestId = null, array $data = []): self
    {
        return static::create([
            'user_id' => $userId,
            'type' => $type,
            'data' => $data ?: null,
            'service_request_id' => $serviceRequestId,
            'title' => Lang::get('notifications.types.'.$type, [], 'en'),
            'message' => Lang::get('notifications.messages.'.$type, static::renderParams($data), 'en'),
        ]);
    }

    /** Localised heading, falling back to the stored title then a humanised type. */
    public function renderedTitle(): string
    {
        $key = 'notifications.types.'.$this->type;
        $translated = __($key);

        if ($translated !== $key && ! str_starts_with($translated, 'notifications.')) {
            return $translated;
        }

        return $this->title ?: ucfirst(str_replace(['_', '.'], ' ', (string) $this->type));
    }

    /** Localised body from `data`, falling back to the stored message prose. */
    public function renderedBody(): string
    {
        if (! empty($this->data)) {
            $key = 'notifications.messages.'.$this->type;
            $translated = __($key, static::renderParams($this->data));

            if ($translated !== $key && ! str_starts_with($translated, 'notifications.')) {
                return $translated;
            }
        }

        return (string) $this->message;
    }

    /**
     * Enrich raw parameters with locale-aware derivatives so `data` itself stays
     * locale-free (e.g. a `status` code becomes a translated `status_label`).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private static function renderParams(array $data): array
    {
        if (isset($data['status'])) {
            $data['status_label'] = __('status.request.'.$data['status']);
        }

        if (isset($data['complaint_status'])) {
            $data['complaint_status_label'] = __('status.complaint.'.$data['complaint_status']);
        }

        return $data;
    }
}
