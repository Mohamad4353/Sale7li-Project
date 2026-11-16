<?php

namespace Tests\Feature\Worker;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkerCompletenessTest extends TestCase
{
    use InteractsWithWorkers, RefreshDatabase;

    public function test_completeness_reflects_each_checklist_item(): void
    {
        $worker = $this->workerUser(profileAttrs: ['bio' => null, 'photo' => null]);
        $profile = $worker->workerProfile;

        $this->assertSame(0, $profile->fresh()->completeness());

        $profile->update(['bio' => 'Ten years fixing homes.']);
        $this->assertSame(25, $profile->fresh()->completeness());

        $profile->update(['photo' => 'worker-photos/abc.png']);
        $this->assertSame(50, $profile->fresh()->completeness());

        $this->activeService($worker);
        $this->assertSame(75, $profile->fresh()->completeness());

        $this->availabilityRow($worker, 1);
        $this->assertSame(100, $profile->fresh()->completeness());
    }

    public function test_can_go_live_requires_verified_active_and_a_service_but_not_availability(): void
    {
        $worker = $this->workerUser(verification: 'pending', profileAttrs: ['bio' => 'x', 'photo' => 'worker-photos/a.png']);
        $profile = $worker->workerProfile;
        $profile->setRelation('user', $worker);

        $this->assertFalse($profile->canGoLive(), 'pending profile with no service');

        $this->activeService($worker);
        $worker->workerProfile->update(['verification_status' => 'verified']);
        $fresh = $worker->fresh()->workerProfile;
        $fresh->setRelation('user', $worker->fresh());

        $this->assertTrue($fresh->canGoLive(), 'verified + active + one service is enough — availability not required');
    }

    public function test_a_suspended_or_serviceless_verified_worker_cannot_go_live(): void
    {
        $suspended = $this->workerUser(['status' => 'suspended']);
        $this->activeService($suspended);
        $suspended = $suspended->fresh();
        $sp = $suspended->workerProfile;
        $sp->setRelation('user', $suspended);
        $this->assertFalse($sp->canGoLive());

        $serviceless = $this->workerUser();
        $serviceless = $serviceless->fresh();
        $sl = $serviceless->workerProfile;
        $sl->setRelation('user', $serviceless);
        $this->assertFalse($sl->canGoLive());
    }

    public function test_the_dashboard_shows_the_go_live_checklist(): void
    {
        $worker = $this->workerUser(profileAttrs: ['bio' => 'Seasoned pro.']);

        $this->actingAs($worker)->get(route('worker.dashboard'))
            ->assertOk()
            ->assertSee(__('workers.go_live_title'))
            ->assertSee(__('workers.check_services'))
            ->assertSee('25%');
    }
}
