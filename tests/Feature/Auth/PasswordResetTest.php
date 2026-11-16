<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_links_to_the_forgot_password_page(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('href="'.route('password.request').'"', false);
    }

    public function test_forgot_password_screen_can_be_rendered(): void
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('name="email"', false);
    }

    public function test_a_reset_link_is_sent_to_a_known_user(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'known@example.com']);

        $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => 'known@example.com'])
            ->assertRedirect(route('password.request'))
            ->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_an_unknown_email_gets_the_same_response_and_no_notification(): void
    {
        Notification::fake();

        $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => 'nobody@example.com'])
            ->assertRedirect(route('password.request'))
            ->assertSessionHas('status')
            ->assertSessionHasNoErrors();

        Notification::assertNothingSent();
    }

    public function test_password_can_be_reset_with_a_valid_token_and_old_password_stops_working(): void
    {
        Notification::fake();
        $user = User::factory()->create([
            'email' => 'reset@example.com',
            'password' => 'OldPassword@1',
        ]);

        $this->post(route('password.email'), ['email' => $user->email]);

        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use (&$token) {
            $token = $notification->token;

            return true;
        });

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'BrandNew@2026',
            'password_confirmation' => 'BrandNew@2026',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('BrandNew@2026', $user->fresh()->password));

        // Old credentials must no longer authenticate.
        $this->post('/login', ['email' => $user->email, 'password' => 'OldPassword@1'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();

        // New credentials work.
        $this->post('/login', ['email' => $user->email, 'password' => 'BrandNew@2026'])
            ->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user->fresh());
    }

    public function test_reset_is_rejected_with_an_invalid_token(): void
    {
        $user = User::factory()->create([
            'email' => 'badtoken@example.com',
            'password' => 'OldPassword@1',
        ]);

        $this->from(route('password.reset', 'not-a-real-token'))
            ->post(route('password.update'), [
                'token' => 'not-a-real-token',
                'email' => $user->email,
                'password' => 'BrandNew@2026',
                'password_confirmation' => 'BrandNew@2026',
            ])
            ->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('OldPassword@1', $user->fresh()->password));
    }

    public function test_reset_requires_a_confirmed_password_of_minimum_length(): void
    {
        $user = User::factory()->create(['email' => 'weak@example.com']);
        $this->post(route('password.email'), ['email' => $user->email]);

        $this->from(route('password.reset', 'tok'))
            ->post(route('password.update'), [
                'token' => 'tok',
                'email' => $user->email,
                'password' => 'short',
                'password_confirmation' => 'different',
            ])
            ->assertSessionHasErrors('password');
    }

    public function test_admin_can_send_a_password_reset_link_to_a_user(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $target = User::factory()->create(['email' => 'target@example.com', 'password' => 'Keep@1234']);

        $this->actingAs($admin)
            ->from(route('admin.users.index'))
            ->post(route('admin.users.passwordResetLink', $target))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success');

        Notification::assertSentTo($target, ResetPassword::class);

        // The admin action never reveals or changes the password.
        $this->assertTrue(Hash::check('Keep@1234', $target->fresh()->password));
    }

    public function test_admin_reset_response_never_contains_a_password_or_token(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $target = User::factory()->create(['email' => 'target2@example.com']);

        $response = $this->actingAs($admin)->post(route('admin.users.passwordResetLink', $target));
        $response->assertRedirect();

        $this->assertStringNotContainsString('password', strtolower($response->getContent() ?: ''));
    }

    public function test_non_admins_cannot_trigger_the_admin_reset_action(): void
    {
        $target = User::factory()->create();

        $this->post(route('admin.users.passwordResetLink', $target))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->customer()->create())
            ->post(route('admin.users.passwordResetLink', $target))->assertForbidden();

        $this->actingAs(User::factory()->worker()->create())
            ->post(route('admin.users.passwordResetLink', $target))->assertForbidden();
    }
}
