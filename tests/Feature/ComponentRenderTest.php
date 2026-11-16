<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class ComponentRenderTest extends TestCase
{
    public function test_status_component_renders_a_localized_label_and_tone(): void
    {
        $html = Blade::render('<x-status type="request" value="completed" />');
        $this->assertStringContainsString(__('status.request.completed'), $html);
        $this->assertStringContainsString('bg-emerald-100', $html);

        $rtl = Blade::render('<x-status type="request" value="cancelled" />');
        $this->assertStringContainsString('bg-rose-100', $rtl);
    }

    public function test_status_component_falls_back_for_an_unknown_value(): void
    {
        $html = Blade::render('<x-status type="request" value="mystery_state" />');
        $this->assertStringContainsString('Mystery state', $html);
    }

    public function test_badge_tone_maps_to_a_single_style_source(): void
    {
        $this->assertStringContainsString('bg-amber-100', Blade::render('<x-badge tone="warning">x</x-badge>'));
        $this->assertStringContainsString('bg-sky-100', Blade::render('<x-badge tone="info">x</x-badge>'));
    }

    public function test_button_renders_as_a_link_when_href_is_given(): void
    {
        $html = Blade::render('<x-button href="/go">Continue</x-button>');
        $this->assertStringContainsString('<a href="/go"', $html);
        $this->assertStringContainsString('Continue', $html);

        $submit = Blade::render('<x-button>Save</x-button>');
        $this->assertStringContainsString('<button type="submit"', $submit);
    }

    public function test_empty_state_renders_title_and_message(): void
    {
        $html = Blade::render('<x-empty title="Nothing here" message="Add one to begin" />');
        $this->assertStringContainsString('Nothing here', $html);
        $this->assertStringContainsString('Add one to begin', $html);
    }

    public function test_field_shows_the_error_from_the_bag_and_a_required_marker(): void
    {
        $errors = new MessageBag(['email' => ['That email is taken.']]);
        View::share('errors', new ViewErrorBag);
        View::shared('errors')->put('default', $errors);

        $html = Blade::render('<x-field label="Email" name="email" :required="true"><input></x-field>');

        $this->assertStringContainsString('Email', $html);
        $this->assertStringContainsString('That email is taken.', $html);
        $this->assertStringContainsString('field-req', $html);
    }

    public function test_alert_renders_with_the_requested_tone(): void
    {
        $html = Blade::render('<x-alert tone="success">Saved</x-alert>');
        $this->assertStringContainsString('bg-emerald-50', $html);
        $this->assertStringContainsString('Saved', $html);
    }

    public function test_icon_is_decorative_by_default_but_exposes_a_name_when_labelled(): void
    {
        // The overwhelming majority of icons sit next to their own text label
        // and must stay out of the accessibility tree.
        $decorative = Blade::render('<x-icon name="shield" />');
        $this->assertStringContainsString('aria-hidden="true"', $decorative);
        $this->assertStringNotContainsString('role="img"', $decorative);

        // When a caller gives the icon an aria-label it is the only carrier of
        // that meaning (e.g. the "verified" shield on a compact professional
        // card) — it must be exposed, not silently hidden.
        $labelled = Blade::render('<x-icon name="shield" aria-label="Verified professional" />');
        $this->assertStringContainsString('aria-label="Verified professional"', $labelled);
        $this->assertStringContainsString('role="img"', $labelled);
        $this->assertStringNotContainsString('aria-hidden="true"', $labelled);
    }

    public function test_the_whatsapp_icon_renders_a_path_and_stays_decorative_by_default(): void
    {
        $html = Blade::render('<x-icon name="whatsapp" />');
        $this->assertStringContainsString('<path', $html);
        $this->assertStringContainsString('aria-hidden="true"', $html);
    }

    public function test_language_switch_posts_to_the_lang_route(): void
    {
        $html = Blade::render('<x-lang-switch />');
        $this->assertStringContainsString('method="POST"', $html);
        $this->assertStringContainsString('action="'.route('lang').'"', $html);
        $this->assertStringContainsString('name="locale"', $html);
    }
}
