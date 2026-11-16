<?php

namespace Tests\Feature\Payments;

use App\Models\Payment;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * One regression tripwire for every financial endpoint's access rules. If a
 * later change drops a middleware or a FormRequest::authorize(), this fails.
 */
class FinancialAccessMatrixTest extends TestCase
{
    use BuildsPaidJobs, RefreshDatabase;

    public function test_guests_are_denied_every_financial_endpoint(): void
    {
        $payment = Payment::factory()->paid()->create(['worker_id' => User::factory()->worker()]);
        $request = ServiceRequest::factory()->create();
        $login = route('login');

        $this->get(route('worker.earnings'))->assertRedirect($login);
        $this->get(route('customer.requests.invoice', $request))->assertRedirect($login);
        $this->get(route('admin.payments.index'))->assertRedirect($login);
        $this->get(route('admin.payments.reconciliation'))->assertRedirect($login);
        $this->get(route('admin.settings.commission.edit'))->assertRedirect($login);
        $this->post(route('admin.payments.refund', $payment))->assertRedirect($login);
        $this->put(route('admin.settings.commission.update'), ['rate' => '0.2'])->assertRedirect($login);
    }

    public function test_a_customer_is_denied_worker_and_admin_financial_endpoints(): void
    {
        $customer = User::factory()->customer()->create();
        $payment = Payment::factory()->paid()->create(['worker_id' => User::factory()->worker()]);

        $this->actingAs($customer)->get(route('worker.earnings'))->assertForbidden();
        $this->actingAs($customer)->get(route('admin.payments.index'))->assertForbidden();
        $this->actingAs($customer)->get(route('admin.payments.reconciliation'))->assertForbidden();
        $this->actingAs($customer)->get(route('admin.settings.commission.edit'))->assertForbidden();
        $this->actingAs($customer)->post(route('admin.payments.refund', $payment))->assertForbidden();
        $this->actingAs($customer)->put(route('admin.settings.commission.update'), ['rate' => '0.2'])->assertForbidden();
    }

    public function test_a_worker_is_denied_customer_and_admin_financial_endpoints(): void
    {
        $worker = User::factory()->worker()->create();
        $payment = Payment::factory()->paid()->create(['worker_id' => User::factory()->worker()]);
        $request = ServiceRequest::factory()->create();

        $this->actingAs($worker)->get(route('customer.requests.invoice', $request))->assertForbidden();
        $this->actingAs($worker)->get(route('admin.payments.index'))->assertForbidden();
        $this->actingAs($worker)->get(route('admin.payments.reconciliation'))->assertForbidden();
        $this->actingAs($worker)->get(route('admin.settings.commission.edit'))->assertForbidden();
        $this->actingAs($worker)->post(route('admin.payments.refund', $payment))->assertForbidden();
        $this->actingAs($worker)->put(route('admin.settings.commission.update'), ['rate' => '0.2'])->assertForbidden();
    }

    public function test_a_suspended_admin_is_denied_by_the_active_guard(): void
    {
        $login = route('login');

        $this->actingAs(User::factory()->admin()->suspended()->create())
            ->get(route('admin.payments.index'))->assertRedirect($login);

        $this->actingAs(User::factory()->admin()->suspended()->create())
            ->get(route('admin.payments.reconciliation'))->assertRedirect($login);

        $this->actingAs(User::factory()->admin()->suspended()->create())
            ->post(route('admin.payments.refund', Payment::factory()->paid()->create(['worker_id' => User::factory()->worker()])))
            ->assertRedirect($login);
    }

    public function test_the_correct_role_reaches_each_endpoint(): void
    {
        $this->actingAs(User::factory()->worker()->create())->get(route('worker.earnings'))->assertOk();

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get(route('admin.payments.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.payments.reconciliation'))->assertOk();
        $this->actingAs($admin)->get(route('admin.settings.commission.edit'))->assertOk();
    }
}
