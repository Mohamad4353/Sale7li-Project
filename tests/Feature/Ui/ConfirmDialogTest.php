<?php

namespace Tests\Feature\Ui;

use App\Models\Address;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * P14-11 — branded confirmation dialog. The visual confirm changes from
 * window.confirm() to a SALE7LI modal, but the load-bearing `data-confirm`
 * mechanism (message source, form action, method, CSRF) is unchanged.
 */
class ConfirmDialogTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_layout_mounts_exactly_one_alertdialog(): void
    {
        $html = $this->actingAs(User::factory()->customer()->create())
            ->get(route('customer.dashboard'))->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, 'role="alertdialog"'));
        $this->assertStringContainsString('x-data="confirmDialog(', $html);
    }

    public function test_the_dialog_exposes_localized_cancel_and_confirm_controls(): void
    {
        $this->actingAs(User::factory()->customer()->create())
            ->get(route('customer.dashboard'))->assertOk()
            ->assertSee(__('messages.cancel'))
            ->assertSee(__('messages.confirm'));
    }

    public function test_the_dialog_is_localized_in_arabic(): void
    {
        $this->actingAs(User::factory()->customer()->create())
            ->withSession(['locale' => 'ar'])
            ->get(route('customer.dashboard'))->assertOk()
            ->assertSee('هل أنت متأكد؟', false);
    }

    public function test_existing_data_confirm_forms_are_untouched(): void
    {
        $customer = User::factory()->customer()->create();
        Address::factory()->create(['user_id' => $customer->id]);

        $this->actingAs($customer)->get(route('customer.addresses.index'))->assertOk()
            ->assertSee('data-confirm="'.e(__('addresses.remove_confirm')).'"', false)
            ->assertSee('method="POST"', false);
    }

    public function test_app_js_keeps_the_data_confirm_safety_mechanism(): void
    {
        $js = file_get_contents(resource_path('js/app.js'));

        // message still sourced from the attribute
        $this->assertStringContainsString("getAttribute('data-confirm')", $js);
        // guarded re-submit so Confirm submits exactly once and never after Cancel
        $this->assertStringContainsString('requestSubmit', $js);
        $this->assertStringContainsString('confirmed', $js);
        // native confirm retained as the no-component fallback
        $this->assertStringContainsString('window.confirm', $js);
    }
}
