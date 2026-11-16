<?php

namespace Tests\Feature\Admin;

use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * P10-26 — admin category editing (name, localized name, descriptions, icon,
 * active state). Existing categories stay compatible: the slug is never rewritten
 * and unsafe icon markup is rejected.
 */
class AdminCategoryEditTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function category(array $attrs = []): ServiceCategory
    {
        return ServiceCategory::factory()->create($attrs);
    }

    public function test_edit_form_is_admin_only(): void
    {
        $category = $this->category();

        $this->get(route('admin.services.edit', $category))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->customer()->create())->get(route('admin.services.edit', $category))->assertForbidden();
        $this->actingAs(User::factory()->worker()->create())->get(route('admin.services.edit', $category))->assertForbidden();
        $this->actingAs($this->admin())->get(route('admin.services.edit', $category))->assertOk();
    }

    public function test_admin_can_update_localized_fields_and_icon(): void
    {
        $category = $this->category(['name' => 'Plumbing', 'name_ar' => 'سباكة', 'icon' => '🔧']);

        $this->actingAs($this->admin())->put(route('admin.services.update', $category), [
            'name' => 'Plumbing & Drainage',
            'name_ar' => 'السباكة والصرف',
            'description' => 'Pipes, taps, drains.',
            'description_ar' => 'أنابيب وصنابير ومصارف.',
            'icon' => '🚿',
            'base_price' => '45.00',
            'is_active' => '1',
        ])->assertRedirect(route('admin.services.index'));

        $category->refresh();
        $this->assertSame('Plumbing & Drainage', $category->name);
        $this->assertSame('السباكة والصرف', $category->name_ar);
        $this->assertSame('أنابيب وصنابير ومصارف.', $category->description_ar);
        $this->assertSame('🚿', $category->icon);
    }

    public function test_the_slug_is_never_rewritten_by_an_edit(): void
    {
        $category = $this->category(['name' => 'Old Name', 'slug' => 'old-name-abcde']);

        $this->actingAs($this->admin())->put(route('admin.services.update', $category), [
            'name' => 'Completely Different', 'name_ar' => 'مختلف', 'base_price' => '30',
        ])->assertRedirect();

        $this->assertSame('old-name-abcde', $category->fresh()->slug);
    }

    public function test_icon_rejects_html_and_svg(): void
    {
        $category = $this->category(['icon' => '🔧']);

        $this->actingAs($this->admin())->from(route('admin.services.edit', $category))
            ->put(route('admin.services.update', $category), [
                'name' => 'X', 'name_ar' => 'اكس', 'base_price' => '10',
                'icon' => '<svg onload=alert(1)>',
            ])->assertSessionHasErrors('icon');

        $this->assertSame('🔧', $category->fresh()->icon);
    }

    public function test_update_validates_required_fields(): void
    {
        $category = $this->category(['name' => 'Keep']);

        $this->actingAs($this->admin())->from(route('admin.services.edit', $category))
            ->put(route('admin.services.update', $category), ['name' => '', 'name_ar' => '', 'base_price' => 'abc'])
            ->assertSessionHasErrors(['name', 'name_ar', 'base_price']);

        $this->assertSame('Keep', $category->fresh()->name);
    }

    public function test_active_state_can_be_changed_from_the_edit_form(): void
    {
        $category = $this->category(['is_active' => true]);

        $this->actingAs($this->admin())->put(route('admin.services.update', $category), [
            'name' => $category->name, 'name_ar' => $category->name_ar, 'base_price' => '20',
            // is_active omitted => unchecked
        ])->assertRedirect();

        $this->assertFalse($category->fresh()->is_active);
    }

    public function test_services_empty_state_uses_a_category_specific_key(): void
    {
        $this->actingAs($this->admin())->get(route('admin.services.index'))
            ->assertOk()
            ->assertSee(__('admin.no_categories'))
            ->assertDontSee(__('admin.no_requests'));
    }
}
