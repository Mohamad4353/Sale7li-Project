<?php

namespace Tests\Feature\Worker;

use App\Models\User;
use App\Models\WorkerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * P14-21 — worker profile privacy/verification polish: a hint that the phone is
 * shown to customers, and the duplicated verification badge replaced with
 * state-specific guidance. No verification-state or authorization logic changes.
 */
class WorkerProfilePrivacyTest extends TestCase
{
    use RefreshDatabase;

    private function workerWith(string $status): User
    {
        $worker = User::factory()->worker()->create(['phone' => '+961 71 000 111']);
        WorkerProfile::factory()->for($worker, 'user')->create(['verification_status' => $status]);

        return $worker;
    }

    public function test_phone_field_tells_the_worker_it_is_shown_to_customers(): void
    {
        $this->actingAs($this->workerWith('verified'))
            ->get(route('worker.profile.edit'))->assertOk()
            ->assertSee(__('workers.phone_hint'))
            ->assertSee('WhatsApp');
    }

    public function test_phone_hint_renders_in_arabic(): void
    {
        $this->actingAs($this->workerWith('verified'))->withSession(['locale' => 'ar'])
            ->get(route('worker.profile.edit'))->assertOk()
            ->assertSee(__('workers.phone_hint', [], 'ar'));
    }

    /** @dataProvider verificationStates */
    public function test_each_verification_state_shows_its_status_and_guidance(string $status): void
    {
        $this->actingAs($this->workerWith($status))
            ->get(route('worker.profile.edit'))->assertOk()
            ->assertSee(__('status.verification.'.$status))
            ->assertSee(__('workers.verification_'.$status.'_hint'));
    }

    public static function verificationStates(): array
    {
        return [['verified'], ['pending'], ['rejected'], ['suspended']];
    }

    public function test_the_verification_status_pill_is_not_duplicated(): void
    {
        $html = $this->actingAs($this->workerWith('pending'))
            ->get(route('worker.profile.edit'))->assertOk()->getContent();

        // The <x-status> pill (a dotted <x-badge>) appears once — in the page
        // header. The body block is now guidance copy, not a second pill.
        $this->assertSame(1, substr_count($html, 'status-dot'));
    }
}
