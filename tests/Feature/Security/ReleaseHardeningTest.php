<?php

namespace Tests\Feature\Security;

use App\Models\ServiceCategory;
use App\Models\User;
use App\Models\WorkerProfile;
use App\Models\WorkerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 31 Step 4 — regression guards for the release-hardening findings that
 * warranted a change (H3, H6) and for the one that was reviewed and left as-is
 * because it is already mitigated (H2).
 */
class ReleaseHardeningTest extends TestCase
{
    use RefreshDatabase;

    /* ── H2 — role/status cannot be escalated through registration ───────── */

    public function test_registration_rejects_an_injected_admin_role(): void
    {
        $this->post('/register', [
            'name' => 'Sneaky', 'email' => 'sneaky@example.com',
            'phone' => '+961 71 123 456',
            'password' => 'Password@123', 'password_confirmation' => 'Password@123',
            'role' => 'admin',
        ])->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'sneaky@example.com']);
    }

    public function test_registration_ignores_injected_status_and_verification_fields(): void
    {
        $this->post('/register', [
            'name' => 'Normal', 'email' => 'normal@example.com',
            'phone' => '+961 71 123 456',
            'password' => 'Password@123', 'password_confirmation' => 'Password@123',
            'role' => 'customer',
            // Not part of the register form — must be ignored, never mass-assigned.
            'status' => 'suspended',
            'email_verified_at' => now()->toDateTimeString(),
        ])->assertRedirect(route('verification.notice'));

        $user = User::where('email', 'normal@example.com')->firstOrFail();
        $this->assertSame('customer', $user->role);
        $this->assertSame('active', $user->status);
        $this->assertNull($user->email_verified_at, 'a fresh registration is never pre-verified');
    }

    /* ── H3 — the public service page bounds its professional list ───────── */

    public function test_service_show_bounds_the_professional_list(): void
    {
        $category = ServiceCategory::factory()->create(['is_active' => true]);

        $workers = collect(range(1, 26))->map(function () use ($category) {
            $worker = User::factory()->worker()->create();
            WorkerProfile::factory()->verified()->for($worker, 'user')->create();
            WorkerService::factory()->create([
                'worker_id' => $worker->id,
                'service_category_id' => $category->id,
                'is_active' => true,
            ]);

            return $worker;
        });

        $html = $this->get(route('services.show', $category))->assertOk()->getContent();

        $shown = $workers->filter(fn (User $w) => str_contains($html, 'href="'.route('workers.show', $w).'"'))->count();

        $this->assertLessThanOrEqual(24, $shown, 'the unpaginated public page must cap the list');
        $this->assertGreaterThanOrEqual(20, $shown, 'most professionals should still render');

        // The full, paginated list is one click away.
        $this->assertStringContainsString(route('workers.index', ['category' => $category->slug]), $html);
    }

    /* ── H6 — /dashboard denies an out-of-enum role instead of a 500 ────── */

    public function test_dashboard_denies_a_user_whose_role_is_not_a_known_portal(): void
    {
        $user = User::factory()->create();
        $user->role = 'nonsense'; // in-memory only; the column is a DB enum

        $this->actingAs($user)->get(route('dashboard'))->assertForbidden();
    }

    public function test_dashboard_still_routes_the_three_real_roles(): void
    {
        $this->actingAs(User::factory()->customer()->create())
            ->get(route('dashboard'))->assertRedirect(route('customer.dashboard'));

        $worker = User::factory()->worker()->create();
        WorkerProfile::factory()->for($worker, 'user')->create();
        $this->actingAs($worker)->get(route('dashboard'))->assertRedirect(route('worker.dashboard'));

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('dashboard'))->assertRedirect(route('admin.dashboard'));
    }
}
