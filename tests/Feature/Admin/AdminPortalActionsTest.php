<?php

namespace Tests\Feature\Admin;

use App\Models\ServiceCategory;
use App\Models\User;
use App\Models\WorkerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Audit gap-fill for three admin mutations whose authorization / behaviour was
 * not directly asserted: user activate-suspend toggle, service-category
 * activate toggle, and worker-verification status. No production code changes.
 */
class AdminPortalActionsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    /* ------------------------- users: status toggle ------------------------- */

    public function test_admin_toggles_a_users_status_both_ways(): void
    {
        $admin = $this->admin();
        $target = User::factory()->customer()->create(['status' => 'active']);

        $this->actingAs($admin)->from(route('admin.users.index'))
            ->post(route('admin.users.toggle', $target))->assertRedirect();
        $this->assertSame('suspended', $target->fresh()->status);

        $this->actingAs($admin)->from(route('admin.users.index'))
            ->post(route('admin.users.toggle', $target))->assertRedirect();
        $this->assertSame('active', $target->fresh()->status);
    }

    public function test_an_admin_cannot_suspend_themselves(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.users.toggle', $admin))->assertStatus(422);

        $this->assertSame('active', $admin->fresh()->status);
    }

    public function test_non_admins_and_guests_cannot_toggle_a_user(): void
    {
        $target = User::factory()->worker()->create(['status' => 'active']);

        $this->post(route('admin.users.toggle', $target))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->customer()->create())
            ->post(route('admin.users.toggle', $target))->assertForbidden();
        $this->actingAs(User::factory()->worker()->create())
            ->post(route('admin.users.toggle', $target))->assertForbidden();

        $this->assertSame('active', $target->fresh()->status);
    }

    /* --------------------- service category: is_active -------------------- */

    public function test_admin_toggles_a_service_category_active_flag_both_ways(): void
    {
        $admin = $this->admin();
        $category = ServiceCategory::factory()->create(['is_active' => true]);

        $this->actingAs($admin)->from(route('admin.services.index'))
            ->post(route('admin.services.toggle', $category))->assertRedirect();
        $this->assertFalse((bool) $category->fresh()->is_active);

        $this->actingAs($admin)->from(route('admin.services.index'))
            ->post(route('admin.services.toggle', $category))->assertRedirect();
        $this->assertTrue((bool) $category->fresh()->is_active);
    }

    public function test_non_admins_and_guests_cannot_toggle_a_service_category(): void
    {
        $category = ServiceCategory::factory()->create(['is_active' => true]);

        $this->post(route('admin.services.toggle', $category))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->customer()->create())
            ->post(route('admin.services.toggle', $category))->assertForbidden();
        $this->actingAs(User::factory()->worker()->create())
            ->post(route('admin.services.toggle', $category))->assertForbidden();

        $this->assertTrue((bool) $category->fresh()->is_active);
    }

    /* --------------------- worker verification status -------------------- */

    public function test_verification_status_rejects_values_outside_the_allow_list(): void
    {
        $admin = $this->admin();
        $profile = WorkerProfile::factory()->for(User::factory()->worker(), 'user')->create([
            'verification_status' => 'pending',
        ]);

        $this->actingAs($admin)->from(route('admin.users.index'))
            ->post(route('admin.workers.verification', $profile), ['verification_status' => 'banished'])
            ->assertSessionHasErrors('verification_status');

        $this->assertSame('pending', $profile->fresh()->verification_status);
    }

    public function test_non_admins_and_guests_cannot_change_verification_status(): void
    {
        $profile = WorkerProfile::factory()->for(User::factory()->worker(), 'user')->create([
            'verification_status' => 'pending',
        ]);
        $payload = ['verification_status' => 'verified'];

        $this->post(route('admin.workers.verification', $profile), $payload)->assertRedirect(route('login'));
        $this->actingAs(User::factory()->customer()->create())
            ->post(route('admin.workers.verification', $profile), $payload)->assertForbidden();
        $this->actingAs(User::factory()->worker()->create())
            ->post(route('admin.workers.verification', $profile), $payload)->assertForbidden();

        $this->assertSame('pending', $profile->fresh()->verification_status);
    }
}
