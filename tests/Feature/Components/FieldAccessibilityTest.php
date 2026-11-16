<?php

namespace Tests\Feature\Components;

use Tests\TestCase;

/**
 * P10-23 — <x-field> must wire ARIA for failed validation: the control gets
 * aria-invalid="true" and aria-describedby pointing at a stable error id.
 * Existing markup (label, required marker, hint, error text) is preserved.
 */
class FieldAccessibilityTest extends TestCase
{
    public function test_a_failed_field_marks_the_control_invalid_and_links_its_error(): void
    {
        $html = (string) $this->withViewErrors(['email' => 'That email looks wrong.'])
            ->blade('<x-field :label="\'Email\'" name="email"><input id="f-email" name="email" class="input"></x-field>');

        $this->assertStringContainsString('aria-invalid="true"', $html);
        $this->assertStringContainsString('aria-describedby="f-email-error"', $html);
        $this->assertStringContainsString('id="f-email-error"', $html);
        $this->assertStringContainsString('That email looks wrong.', $html);
    }

    public function test_a_valid_field_has_no_aria_invalid(): void
    {
        $html = (string) $this->withViewErrors([])->blade('<x-field :label="\'Email\'" name="email"><input id="f-email" name="email"></x-field>');

        $this->assertStringNotContainsString('aria-invalid', $html);
        $this->assertStringNotContainsString('aria-describedby', $html);
    }

    public function test_label_required_marker_and_hint_are_preserved(): void
    {
        $html = (string) $this->withViewErrors([])->blade('<x-field :label="\'Street\'" name="street" required :hint="\'Include the building number\'"><input name="street"></x-field>');

        $this->assertStringContainsString('Street', $html);
        $this->assertStringContainsString('field-req', $html);
        $this->assertStringContainsString('Include the building number', $html);
    }

    public function test_the_error_replaces_the_hint_when_both_are_present(): void
    {
        $html = (string) $this->withViewErrors(['street' => 'Street is required.'])
            ->blade('<x-field name="street" :hint="\'Include the building number\'"><input name="street"></x-field>');

        $this->assertStringContainsString('Street is required.', $html);
        $this->assertStringNotContainsString('Include the building number', $html);
    }

    /* ---- B-6: hint is programmatically associated with the control ---- */

    public function test_a_hint_gets_a_stable_id_and_is_linked_via_aria_describedby(): void
    {
        $html = (string) $this->withViewErrors([])
            ->blade('<x-field :label="\'Phone\'" name="phone" :hint="\'Digits only\'"><input id="f-phone" name="phone"></x-field>');

        $this->assertStringContainsString('id="f-phone-hint"', $html);
        $this->assertStringContainsString('aria-describedby="f-phone-hint"', $html);
        $this->assertStringNotContainsString('aria-invalid', $html);
    }

    public function test_a_field_with_no_hint_and_no_error_gets_no_aria_describedby(): void
    {
        $html = (string) $this->withViewErrors([])
            ->blade('<x-field :label="\'Phone\'" name="phone"><input id="f-phone" name="phone"></x-field>');

        $this->assertStringNotContainsString('aria-describedby', $html);
        $this->assertStringNotContainsString('-hint', $html);
    }

    public function test_when_an_error_is_present_the_control_is_described_by_the_error_not_the_hint(): void
    {
        $html = (string) $this->withViewErrors(['phone' => 'Phone looks wrong.'])
            ->blade('<x-field name="phone" :hint="\'Digits only\'"><input id="f-phone" name="phone"></x-field>');

        $this->assertStringContainsString('aria-invalid="true"', $html);
        $this->assertStringContainsString('aria-describedby="f-phone-error"', $html);
        $this->assertStringNotContainsString('f-phone-hint', $html);
        $this->assertStringNotContainsString('Digits only', $html);
    }
}
