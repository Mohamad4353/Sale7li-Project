<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * P10-13 — a suspended user who supplies the correct password should land on a
 * clear explanatory screen, not the generic "wrong credentials" error. The
 * underlying rule (Auth::attempt requires status = active) is NOT weakened.
 */
class SuspendedAccountScreenTest extends TestCase
{
    use RefreshDatabase;

    public function test_suspended_user_with_correct_password_sees_the_explanatory_screen(): void
    {
        $user = User::factory()->customer()->create(['password' => 'password123']);
        $user->update(['status' => 'suspended']);

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('account.suspended'));
        $this->assertGuest();

        $this->get(route('account.suspended'))
            ->assertOk()
            ->assertSee(__('auth.suspended_title'))
            ->assertSee(config('sale7li.support_email'));
    }

    public function test_suspended_user_with_wrong_password_gets_the_generic_error_no_enumeration(): void
    {
        $user = User::factory()->worker()->create(['password' => 'password123']);
        $user->update(['status' => 'suspended']);

        $response = $this->from(route('login'))->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'the-wrong-password',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_unknown_email_gets_the_generic_error(): void
    {
        $this->from(route('login'))->post(route('login.store'), [
            'email' => 'nobody@example.com',
            'password' => 'whatever-123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_active_user_still_logs_in_normally(): void
    {
        $user = User::factory()->customer()->create(['password' => 'password123']);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password123',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_suspended_screen_is_public_and_shows_no_moderation_detail(): void
    {
        $response = $this->get(route('account.suspended'))->assertOk();

        $response->assertSee(__('auth.suspended_title'));
        $response->assertSee(config('sale7li.support_email'));
        $response->assertDontSee('admin_notes');
        $response->assertDontSee('internal');
    }

    public function test_suspended_screen_is_localized_in_arabic(): void
    {
        $this->withSession(['locale' => 'ar'])
            ->get(route('account.suspended'))
            ->assertOk()
            ->assertSee(__('auth.suspended_title', [], 'ar'))
            ->assertDontSee(__('auth.suspended_title', [], 'en'));
    }
}
