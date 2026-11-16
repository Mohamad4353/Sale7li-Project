<?php

namespace Tests\Feature\Worker;

use App\Models\ServiceCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkerLocalizationTest extends TestCase
{
    use InteractsWithWorkers, RefreshDatabase;

    public function test_worker_pages_render_in_english_ltr(): void
    {
        $worker = $this->workerUser();
        ServiceCategory::factory()->create(['is_active' => true]);

        foreach (['worker.dashboard', 'worker.services.edit', 'worker.availability.edit', 'worker.profile.edit'] as $route) {
            $response = $this->actingAs($worker)->get(route($route))->assertOk();
            $response->assertSee('<html lang="en" dir="ltr">', false);
        }

        $this->actingAs($worker)->get(route('worker.services.edit'))
            ->assertSee(__('workers.services_title'))
            ->assertSee(__('workers.save_services'));
    }

    public function test_worker_pages_render_in_arabic_rtl_with_translated_controls(): void
    {
        $worker = $this->workerUser();
        ServiceCategory::factory()->create(['is_active' => true]);

        $services = $this->actingAs($worker)->withSession(['locale' => 'ar'])->get(route('worker.services.edit'))->assertOk();
        $services->assertSee('<html lang="ar" dir="rtl">', false);
        $services->assertSee('الخدمات التي تقدّمها');
        $services->assertSee(__('workers.save_services', [], 'ar'));

        $availability = $this->actingAs($worker)->withSession(['locale' => 'ar'])->get(route('worker.availability.edit'))->assertOk();
        $availability->assertSee('<html lang="ar" dir="rtl">', false);
        $availability->assertSee('التوفر الأسبوعي');
    }

    public function test_availability_validation_errors_are_localized(): void
    {
        $worker = $this->workerUser();

        $payload = $this->weekPayload([2], '17:00', '09:00');

        $this->actingAs($worker)->withSession(['locale' => 'ar'])
            ->from(route('worker.availability.edit'))
            ->put(route('worker.availability.update'), $payload)
            ->assertSessionHasErrors(['days.2.end' => __('workers.av_end_after_start', [], 'ar')]);
    }

    public function test_nav_shows_translated_worker_links_in_arabic(): void
    {
        $worker = $this->workerUser();

        $this->actingAs($worker)->withSession(['locale' => 'ar'])->get(route('worker.dashboard'))
            ->assertOk()
            ->assertSee(__('nav.availability', [], 'ar'))
            ->assertSee(__('nav.services', [], 'ar'));
    }
}
