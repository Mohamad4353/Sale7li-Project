<?php

namespace App\Support;

/**
 * The outcome of AiCategorySuggestionService::suggest() — advisory only.
 *
 * `available === false` covers every failure path (no key configured, empty
 * catalogue, HTTP/timeout error, malformed or structurally-invalid provider
 * response): callers must treat it exactly like "no suggestion" and fall back
 * to the ordinary manual booking form.
 *
 * `categorySlug` is set ONLY when it matched one of the caller-supplied active
 * categories — it is never the raw, unvalidated value the model returned. A
 * `null` slug with `available === true` means the model itself found no fit
 * (or proposed something outside the active catalogue); `structuredDescription`
 * may still be useful in that case.
 */
final readonly class AiCategorySuggestion
{
    private function __construct(
        public bool $available,
        public ?string $categorySlug,
        public ?string $structuredDescription,
    ) {}

    public static function unavailable(): self
    {
        return new self(available: false, categorySlug: null, structuredDescription: null);
    }

    public static function resolved(?string $categorySlug, string $structuredDescription): self
    {
        return new self(available: true, categorySlug: $categorySlug, structuredDescription: $structuredDescription);
    }
}
