<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WorkerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_worker_registration_creates_a_matching_worker_profile(): void
    {
        // Phase 23: registration now lands on the email-verification notice.
        $this->post('/register', [
            'name' => 'Pro Worker',
            'email' => 'pro@example.com',
            'phone' => '+961 71 123 456',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
            'role' => 'worker',
        ])->assertRedirect(route('verification.notice'));

        $worker = User::where('email', 'pro@example.com')->firstOrFail();
        $this->assertSame('worker', $worker->role);
        $this->assertNull($worker->email_verified_at, 'A fresh registration is unverified.');
        $this->assertNotNull($worker->workerProfile, 'A worker must always have a worker profile.');
        $this->assertSame('pending', $worker->workerProfile->verification_status);
    }

    public function test_an_invalid_phone_gives_a_localised_message_not_a_raw_translation_key(): void
    {
        foreach (['en', 'ar'] as $locale) {
            $this->withSession(['locale' => $locale])
                ->from('/register')
                ->post('/register', [
                    'name' => 'Phone Tester',
                    'email' => "phone-{$locale}@example.com",
                    'phone' => 'not a phone !!!',
                    'password' => 'Password@123',
                    'password_confirmation' => 'Password@123',
                    'role' => 'customer',
                ])
                ->assertSessionHasErrors('phone');

            $message = session('errors')->get('phone')[0];
            $this->assertStringNotContainsString('validation.', $message, "phone error leaked a raw key in {$locale}");
            $this->assertNotSame('validation.custom.phone.regex', $message);
        }

        $this->assertDatabaseMissing('users', ['email' => 'phone-en@example.com']);
    }

    public function test_worker_registration_is_atomic_when_profile_creation_fails(): void
    {
        WorkerProfile::creating(function () {
            throw new RuntimeException('Simulated profile insert failure.');
        });

        try {
            $this->post('/register', [
                'name' => 'Pro Worker',
                'email' => 'atomic@example.com',
                'phone' => '+961 71 123 456',
                'password' => 'Password@123',
                'password_confirmation' => 'Password@123',
                'role' => 'worker',
            ]);
        } catch (RuntimeException $e) {
            // The framework would normally render a 500 here; we only care that
            // the failed transaction left no half-created account behind.
        }

        $this->assertDatabaseMissing('users', ['email' => 'atomic@example.com']);
        $this->assertGuest();
    }
}
