<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_english_home_renders_ltr(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('<html lang="en" dir="ltr">', false);
    }

    public function test_arabic_home_renders_rtl(): void
    {
        $response = $this->withSession(['locale' => 'ar'])->get('/');

        $response->assertOk();
        $response->assertSee('<html lang="ar" dir="rtl">', false);
    }

    public function test_guest_can_switch_language_and_it_persists_in_the_session(): void
    {
        $this->post(route('lang'), ['locale' => 'ar'])
            ->assertRedirect();
        $this->assertSame('ar', session('locale'));

        $this->get('/')->assertSee('<html lang="ar" dir="rtl">', false);

        $this->post(route('lang'), ['locale' => 'en']);
        $this->assertSame('en', session('locale'));
    }

    public function test_authenticated_language_switch_updates_the_user_record(): void
    {
        $user = User::factory()->customer()->create(['locale' => 'en']);

        $this->actingAs($user)->post(route('lang'), ['locale' => 'ar'])->assertRedirect();

        $this->assertSame('ar', $user->fresh()->locale);
    }

    public function test_unsupported_locale_is_rejected(): void
    {
        $this->post(route('lang'), ['locale' => 'fr'])->assertStatus(422);
        $this->post(route('lang'), ['locale' => 'ar; DROP'])->assertStatus(422);
    }

    public function test_language_switch_does_not_follow_an_external_redirect(): void
    {
        $response = $this->post(route('lang'), [
            'locale' => 'ar',
            'redirect_to' => 'https://evil.example/steal',
        ]);

        $response->assertRedirect();
        $this->assertStringNotContainsString('evil.example', $response->headers->get('Location'));
    }
}
