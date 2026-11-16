<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_register_with_valid_phone(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '+961 71 123 456',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
            'role' => 'customer',
        ]);

        // Phase 23: a new account must confirm its email first.
        $response->assertRedirect(route('verification.notice'));
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'role' => 'customer',
            'phone' => '+961 71 123 456',
            'email_verified_at' => null,
        ]);
    }

    public function test_registration_rejects_letters_in_phone_number(): void
    {
        $response = $this->from('/register')->post('/register', [
            'name' => 'Test User',
            'email' => 'invalid-phone@example.com',
            'phone' => '+961 ABC 123',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
            'role' => 'customer',
        ]);

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors('phone');
        $this->assertDatabaseMissing('users', ['email' => 'invalid-phone@example.com']);
    }

    public function test_user_can_login(): void
    {
        $user = User::factory()->create([
            'email' => 'login@example.com',
            'password' => 'Password@123',
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'Password@123',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
    }
}
