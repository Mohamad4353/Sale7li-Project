<?php

namespace Tests\Feature\Ui;

use Tests\TestCase;

/**
 * Phase 21 fix, locked by Phase 22: resources/js/app.js must call Alpine.start()
 * exactly once, AFTER every window.* factory it registers. If start() runs
 * first, Alpine's synchronous DOM walk initialises x-data="confirmDialog(...)" /
 * chatPoll(...) / favoriteToggle(...) against undefined globals, leaving those
 * components with an empty scope (native confirm() fallback, stuck modal, dead
 * Cancel button, inert favourite toggle, no chat polling).
 */
class AlpineBootstrapTest extends TestCase
{
    private function appJs(): string
    {
        return file_get_contents(resource_path('js/app.js'));
    }

    public function test_alpine_start_is_called_exactly_once(): void
    {
        $this->assertSame(1, substr_count($this->appJs(), 'Alpine.start()'));
    }

    public function test_every_alpine_factory_is_defined_before_alpine_start(): void
    {
        $js = $this->appJs();
        $start = strpos($js, 'Alpine.start()');
        $this->assertNotFalse($start);

        foreach (['window.confirmDialog', 'window.chatPoll', 'window.favoriteToggle', 'window.aiSuggestion'] as $factory) {
            $pos = strpos($js, $factory.' =');
            $this->assertNotFalse($pos, "{$factory} is not defined in app.js");
            $this->assertLessThan($start, $pos, "{$factory} must be defined before Alpine.start()");
        }

        // The focus plugin is still registered before start().
        $this->assertLessThan($start, strpos($js, 'Alpine.plugin('));
    }

    public function test_chat_poll_uses_the_after_delta_endpoint_on_a_single_interval(): void
    {
        $js = $this->appJs();

        $this->assertStringContainsString('?after=${this.lastId}', $js);      // delta poll, not full reload
        $this->assertStringContainsString('if (!this.timer) this.timer = setInterval', $js); // no duplicate loops
        $this->assertStringContainsString('12000', $js);                       // existing cadence intact
    }

    /**
     * AI Service Assistant frontend logic. Structural/string assertions only —
     * this project's Vitest is deliberately scoped to the React island, so
     * Alpine factories are locked down as PHP feature tests, same as
     * favoriteToggle above.
     */
    public function test_ai_suggestion_never_contacts_the_provider_directly(): void
    {
        $js = $this->appJs();

        $this->assertStringNotContainsString('generativelanguage.googleapis.com', $js);
        $this->assertStringNotContainsString('GEMINI', $js);
        $this->assertStringNotContainsString('x-goog-api-key', $js);
    }

    public function test_ai_suggestion_guards_against_duplicate_in_flight_requests(): void
    {
        $this->assertStringContainsString(
            "if (this.state === 'loading') return;",
            $this->appJs(),
        );
    }

    public function test_ai_suggestion_sends_the_csrf_token_like_the_other_fetch_based_components(): void
    {
        $js = $this->appJs();
        $aiSuggestion = substr($js, strpos($js, 'window.aiSuggestion ='));

        $this->assertStringContainsString(
            "document.querySelector('meta[name=\"csrf-token\"]')?.getAttribute('content')",
            $aiSuggestion,
        );
    }

    public function test_ai_suggestion_only_applies_a_category_slug_that_exists_in_the_dom(): void
    {
        $js = $this->appJs();
        $aiSuggestion = substr($js, strpos($js, 'window.aiSuggestion ='));

        // A slug is only ever kept when a matching [data-category-option] exists.
        $this->assertStringContainsString("querySelectorAll('[data-category-option]')", $aiSuggestion);
        $this->assertStringContainsString('matched ? slug : null', $aiSuggestion);
        // Applying it never builds a new option — only checks an existing one.
        $this->assertStringNotContainsString('createElement', $aiSuggestion);
        $this->assertStringNotContainsString('new Option', $aiSuggestion);
    }

    public function test_ai_suggestion_writes_the_description_as_plain_text_never_html(): void
    {
        $js = $this->appJs();
        $aiSuggestion = substr($js, strpos($js, 'window.aiSuggestion ='));

        $this->assertStringContainsString('textarea.value = this.suggestion.description', $aiSuggestion);
        $this->assertStringNotContainsString('innerHTML', $aiSuggestion);
        $this->assertStringNotContainsString('insertAdjacentHTML', $aiSuggestion);
    }
}
