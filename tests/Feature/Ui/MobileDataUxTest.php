<?php

namespace Tests\Feature\Ui;

use App\Models\Payment;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 14 Batch 4 — mobile & data UX.
 *  P14-02  responsive data tables (desktop table + <sm card list)
 *  P14-15  themed, localized pagination
 *  P14-23  larger table-action touch targets
 * None of this changes controller queries, values, routes or authorization.
 */
class MobileDataUxTest extends TestCase
{
    use RefreshDatabase;

    /* ---------------------------------------------------------------- P14-02 */

    public function test_worker_earnings_keeps_the_desktop_table_and_adds_a_mobile_card_list(): void
    {
        $worker = User::factory()->worker()->create();
        Payment::factory()->paid()->create([
            'worker_id' => $worker->id,
            'amount' => 123, 'commission_rate' => '0.1000', 'commission_amount' => 12, 'worker_net' => 111,
        ]);

        $html = $this->actingAs($worker)->get(route('worker.earnings'))->assertOk()->getContent();

        $this->assertStringContainsString('table-wrap hidden sm:block', $html);
        $this->assertStringContainsString('data-mobile-rows', $html);
        $this->assertStringContainsString('<table>', $html);
        $this->assertStringContainsString('$111.00', $html);   // worker net still rendered
    }

    public function test_admin_payments_renders_both_layouts_and_keeps_the_refund_action(): void
    {
        $admin = User::factory()->admin()->create();
        $payment = Payment::factory()->paid()->create([
            'worker_id' => User::factory()->worker(),
            'amount' => 300, 'commission_rate' => '0.1000', 'commission_amount' => 30, 'worker_net' => 270,
        ]);

        $html = $this->actingAs($admin)->get(route('admin.payments.index'))->assertOk()->getContent();

        $this->assertStringContainsString('table-wrap hidden sm:block', $html);
        $this->assertStringContainsString('data-mobile-rows', $html);
        $this->assertStringContainsString('$300.00', $html);
        // refund control appears once in the table, once in the mobile card
        $this->assertSame(2, substr_count($html, 'action="'.route('admin.payments.refund', $payment).'"'));
    }

    public function test_admin_users_renders_both_layouts_and_keeps_row_actions(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->customer()->create(['name' => 'Mona Card', 'email' => 'mona@example.test']);

        $html = $this->actingAs($admin)->get(route('admin.users.index'))->assertOk()->getContent();

        $this->assertStringContainsString('table-wrap hidden sm:block', $html);
        $this->assertStringContainsString('data-mobile-rows', $html);
        $this->assertStringContainsString('Mona Card', $html);
        $this->assertStringContainsString('mona@example.test', $html);
        $this->assertSame(2, substr_count($html, 'action="'.route('admin.users.toggle', $target).'"'));
    }

    public function test_worker_earnings_values_are_unchanged_for_desktop(): void
    {
        $worker = User::factory()->worker()->create();
        Payment::factory()->paid()->create([
            'worker_id' => $worker->id,
            'amount' => 115, 'commission_rate' => '0.1000', 'commission_amount' => 11.5, 'worker_net' => 103.5,
        ]);

        $this->actingAs($worker)->get(route('worker.earnings'))->assertOk()
            ->assertSee('$115.00')->assertSee('$11.50')->assertSee('$103.50');
    }

    /* ---------------------------------------------------------------- P14-15 */

    public function test_pagination_is_themed_and_drops_the_default_chrome(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->count(20)->customer()->create();

        $res = $this->actingAs($admin)->get(route('admin.users.index'))->assertOk();

        $res->assertSee('page=2', false);
        $res->assertSee('aria-current="page"', false);
        $res->assertSee('rel="next"', false);
        $res->assertSee(__('pagination.next'), false);
        $res->assertDontSee('Showing');   // Laravel default tailwind view text
    }

    public function test_pagination_previous_next_are_localized_in_arabic(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->count(20)->customer()->create();

        $this->actingAs($admin)->withSession(['locale' => 'ar'])
            ->get(route('admin.users.index'))->assertOk()
            ->assertSee(__('pagination.next', [], 'ar'), false)
            ->assertSee('التالي', false);
    }

    public function test_pagination_query_string_filters_survive_page_links(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->count(20)->customer()->create(['name' => 'Filterable Person']);

        $this->actingAs($admin)->get(route('admin.users.index', ['role' => 'customer']))->assertOk()
            ->assertSee('role=customer', false)
            ->assertSee('page=2', false);
    }

    /* ---------------------------------------------------------------- P14-23 */

    public function test_admin_table_actions_use_the_shared_touch_target_class(): void
    {
        $admin = User::factory()->admin()->create();
        ServiceCategory::factory()->create();
        ServiceRequest::factory()->for(User::factory()->customer()->create(), 'customer')->create([
            'service_category_id' => ServiceCategory::factory()->create()->id,
        ]);

        $this->actingAs($admin)->get(route('admin.users.index'))->assertOk()->assertSee('table-action', false);
        $this->actingAs($admin)->get(route('admin.services.index'))->assertOk()->assertSee('table-action', false);
        $this->actingAs($admin)->get(route('admin.requests.index'))->assertOk()->assertSee('table-action', false);
    }

    public function test_admin_user_actions_keep_their_confirmation_and_routes(): void
    {
        $admin = User::factory()->admin()->create();
        $u = User::factory()->customer()->create();

        $this->actingAs($admin)->get(route('admin.users.index'))->assertOk()
            ->assertSee('data-confirm="'.e(__('messages.are_you_sure')).'"', false)
            ->assertSee('action="'.route('admin.users.toggle', $u).'"', false)
            ->assertSee('action="'.route('admin.users.passwordResetLink', $u).'"', false);
    }
}
