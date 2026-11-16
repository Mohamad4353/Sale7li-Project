<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WorkerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\Feature\Worker\InteractsWithWorkers;
use Tests\TestCase;

/**
 * UI/UX audit regression: the "verified" shield on the compact professional
 * cards (marketplace list, favourites, service detail) is icon-only, so it must
 * carry an accessible name — previously the icon was rendered aria-hidden and
 * the label was silently dropped.
 */
class UiAccessibilityAuditTest extends TestCase
{
    use InteractsWithWorkers, RefreshDatabase;

    public function test_worker_card_verified_shield_has_an_accessible_name(): void
    {
        $worker = $this->workerUser(['name' => 'Verified Vera']);
        $this->activeService($worker);
        $worker->load(['workerProfile', 'workerServices.category']);

        $html = Blade::render('<x-worker-card :worker="$worker" />', ['worker' => $worker]);

        $this->assertStringContainsString(__('status.verification.verified'), $html);
        $this->assertStringContainsString('role="img"', $html);
    }

    public function test_unverified_worker_card_shows_no_verified_shield(): void
    {
        $worker = $this->workerUser(['name' => 'Pending Pam'], 'pending');
        $this->activeService($worker);
        $worker->load(['workerProfile', 'workerServices.category']);

        $html = Blade::render('<x-worker-card :worker="$worker" />', ['worker' => $worker]);

        $this->assertStringNotContainsString(__('status.verification.verified'), $html);
    }

    public function test_decorative_icons_stay_out_of_the_accessibility_tree(): void
    {
        // A page dense with decorative icons: every <x-icon> without a caller
        // label must remain aria-hidden.
        WorkerProfile::factory()->verified()->create();
        $customer = User::factory()->customer()->create();

        $html = $this->actingAs($customer)->get(route('workers.index'))->assertOk()->getContent();

        // The nav bell, chevrons, etc. are decorative.
        $this->assertStringContainsString('aria-hidden="true"', $html);
    }
}
