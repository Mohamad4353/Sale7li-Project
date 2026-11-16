<?php

namespace Tests\Feature\Admin;

use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * P14-14 — the admin category Create form now offers the same icon field the
 * Edit form already had. Backend already supported `icon`; only the create
 * validation and the create markup change.
 */
class AdminServiceIconTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Gardening',
            'name_ar' => 'البستنة',
            'base_price' => '40',
        ], $overrides);
    }

    public function test_create_form_shows_the_icon_field(): void
    {
        $this->actingAs($this->admin())->get(route('admin.services.index'))->assertOk()
            ->assertSee(__('admin.icon'))
            ->assertSee(__('admin.icon_hint'))
            ->assertSee('name="icon"', false)
            ->assertSee('maxlength="8"', false);
    }

    public function test_icon_is_persisted_on_create(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.services.store'), $this->payload(['icon' => '🌿']))
            ->assertRedirect();

        $this->assertDatabaseHas('service_categories', ['name' => 'Gardening', 'icon' => '🌿']);
    }

    public function test_create_without_an_icon_still_works(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.services.store'), $this->payload())
            ->assertRedirect();

        $this->assertDatabaseHas('service_categories', ['name' => 'Gardening']);
    }

    public function test_create_rejects_markup_in_the_icon(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.services.store'), $this->payload(['icon' => '<b>x</b>']))
            ->assertSessionHasErrors('icon');

        $this->assertDatabaseMissing('service_categories', ['name' => 'Gardening']);
    }

    public function test_edit_still_updates_the_icon(): void
    {
        $category = ServiceCategory::factory()->create(['icon' => '🔧']);

        $this->actingAs($this->admin())->get(route('admin.services.edit', $category))->assertOk()
            ->assertSee('value="🔧"', false);

        $this->actingAs($this->admin())->put(route('admin.services.update', $category), [
            'name' => $category->name, 'name_ar' => $category->name_ar,
            'base_price' => $category->base_price, 'icon' => '🪚',
        ])->assertRedirect();

        $this->assertSame('🪚', $category->fresh()->icon);
    }

    public function test_create_form_renders_in_arabic(): void
    {
        $this->actingAs($this->admin())->withSession(['locale' => 'ar'])
            ->get(route('admin.services.index'))->assertOk()
            ->assertSee(__('admin.icon', [], 'ar'));
    }
}
