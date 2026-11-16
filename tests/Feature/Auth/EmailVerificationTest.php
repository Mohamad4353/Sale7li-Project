<?php

namespace Tests\Feature\Auth;

use App\Models\Quotation;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Phase 23 — email verification. New registrations must verify their address
 * before a small set of trust/value-bearing customer actions; everything else
 * (browsing, account access, request tracking) stays open.
 */
class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function unverifiedCustomer(): User
    {
        return User::factory()->customer()->create(['email_verified_at' => null]);
    }

    private function register(array $overrides = [])
    {
        return $this->post('/register', array_merge([
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'phone' => '+961 71 123 456',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
            'role' => 'customer',
        ], $overrides));
    }

    private function requestFor(User $customer): ServiceRequest
    {
        $category = ServiceCategory::factory()->create(['is_active' => true]);

        return ServiceRequest::factory()->for($customer, 'customer')->create([
            'service_category_id' => $category->id,
        ]);
    }

    /* ---- registration flow ---- */

    public function test_registration_redirects_to_the_verification_notice(): void
    {
        $this->register()->assertRedirect(route('verification.notice'));

        $user = User::where('email', 'newuser@example.com')->firstOrFail();
        $this->assertNull($user->email_verified_at);
        $this->assertAuthenticatedAs($user);
    }

    public function test_registration_dispatches_the_verification_notification(): void
    {
        Notification::fake();

        $this->register();

        Notification::assertSentTo(
            User::where('email', 'newuser@example.com')->firstOrFail(),
            VerifyEmail::class,
        );
    }

    public function test_verification_notice_requires_authentication(): void
    {
        $this->get(route('verification.notice'))->assertRedirect(route('login'));
    }

    /* ---- browsing stays open ---- */

    public function test_unverified_user_can_browse_public_and_account_pages(): void
    {
        ServiceCategory::factory()->create(['is_active' => true]);
        $this->actingAs($this->unverifiedCustomer());

        $this->get('/')->assertOk();
        $this->get(route('services.index'))->assertOk();
        $this->get(route('workers.index'))->assertOk();
        $this->get('/explore')->assertStatus(301);                 // marketplace redirect, not a block
        $this->getJson(route('api.marketplace.workers'))->assertOk();
        $this->get(route('customer.dashboard'))->assertOk();       // dashboard is NOT gated
        $this->get(route('customer.requests.index'))->assertOk();  // request tracking is NOT gated
        $this->get(route('notifications.index'))->assertOk();
    }

    /* ---- gated actions ---- */

    public function test_unverified_user_is_redirected_from_every_gated_action_and_nothing_happens(): void
    {
        $customer = $this->unverifiedCustomer();
        $notice = route('verification.notice');
        $sr = $this->requestFor($customer);
        $quotation = Quotation::factory()->create(['service_request_id' => $sr->id]);

        $cases = [
            ['get', route('customer.booking.create')],
            ['post', route('customer.booking.store')],
            ['post', route('customer.payments.pay', $sr)],
            ['get', route('customer.payments.whish', $sr)],
            ['post', route('customer.payments.whish.submit', $sr)],
            ['post', route('customer.quotations.accept', $quotation)],
            ['post', route('customer.complaints.store', $sr)],
            ['post', route('customer.reviews.store', $sr)],
        ];

        foreach ($cases as [$method, $url]) {
            $this->actingAs($customer)->{$method}($url)->assertRedirect($notice);
        }

        // No protected side-effects were produced.
        $this->assertDatabaseCount('reviews', 0);
        $this->assertDatabaseCount('complaints', 0);
        $this->assertDatabaseCount('payments', 0);
        $this->assertSame('pending', $quotation->fresh()->status);
    }

    public function test_reject_a_quotation_is_not_gated(): void
    {
        $customer = $this->unverifiedCustomer();
        $sr = $this->requestFor($customer);
        $quotation = Quotation::factory()->create(['service_request_id' => $sr->id, 'status' => 'pending']);

        // Rejecting does not require a verified email — only accepting does.
        $this->actingAs($customer)
            ->post(route('customer.quotations.reject', $quotation))
            ->assertRedirect();
        $this->assertNotSame(route('verification.notice'), url()->previous());
    }

    public function test_verified_user_reaches_a_previously_gated_action(): void
    {
        ServiceCategory::factory()->create(['is_active' => true]);
        $customer = User::factory()->customer()->create(); // factory verifies

        $this->actingAs($customer)->get(route('customer.booking.create'))->assertOk();
    }

    /* ---- signed verification link ---- */

    private function signedLink(User $user, ?\DateTimeInterface $expiry = null): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            $expiry ?? now()->addMinutes(60),
            ['id' => $user->getKey(), 'hash' => sha1($user->getEmailForVerification())],
        );
    }

    public function test_a_valid_signed_link_verifies_the_user_and_sets_the_timestamp(): void
    {
        $user = $this->unverifiedCustomer();

        $this->actingAs($user)->get($this->signedLink($user))
            ->assertRedirect();

        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_an_invalid_signature_is_rejected(): void
    {
        $user = $this->unverifiedCustomer();

        $this->actingAs($user)->get($this->signedLink($user).'tampered')->assertForbidden();
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_an_expired_link_is_rejected(): void
    {
        $user = $this->unverifiedCustomer();

        $this->actingAs($user)->get($this->signedLink($user, now()->subMinute()))->assertForbidden();
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_another_users_link_is_rejected_and_changes_no_state(): void
    {
        $alice = $this->unverifiedCustomer();
        $bob = User::factory()->customer()->create(['email_verified_at' => null, 'email' => 'bob@example.com']);

        $this->actingAs($bob)->get($this->signedLink($alice))->assertForbidden();

        $this->assertNull($alice->fresh()->email_verified_at);
        $this->assertNull($bob->fresh()->email_verified_at);
    }

    public function test_already_verified_user_visiting_the_notice_is_redirected_away(): void
    {
        $user = User::factory()->customer()->create(); // verified

        $this->actingAs($user)->get(route('verification.notice'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_already_verified_user_following_a_link_stays_verified(): void
    {
        $user = User::factory()->customer()->create();
        $stamp = $user->email_verified_at;

        $this->actingAs($user)->get($this->signedLink($user))->assertRedirect();

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->assertEquals($stamp, $user->fresh()->email_verified_at);
    }

    /* ---- resend ---- */

    public function test_resend_dispatches_a_verification_notification(): void
    {
        Notification::fake();
        $user = $this->unverifiedCustomer();

        $this->actingAs($user)->post(route('verification.send'))->assertRedirect();

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_resend_is_throttled(): void
    {
        $user = $this->unverifiedCustomer();

        for ($i = 0; $i < 6; $i++) {
            $this->actingAs($user)->post(route('verification.send'))->assertRedirect();
        }

        $this->actingAs($user)->post(route('verification.send'))->assertStatus(429);
    }

    public function test_resend_is_a_noop_for_an_already_verified_user(): void
    {
        Notification::fake();
        $user = User::factory()->customer()->create();

        $this->actingAs($user)->post(route('verification.send'))->assertRedirect();

        Notification::assertNothingSent();
    }

    /* ---- localisation ---- */

    public function test_the_notice_renders_localised_strings_with_no_raw_keys(): void
    {
        $user = $this->unverifiedCustomer();

        foreach (['en', 'ar'] as $locale) {
            $html = $this->withSession(['locale' => $locale])
                ->actingAs($user)
                ->get(route('verification.notice'))
                ->assertOk()
                ->assertSee(__('auth.verify_email.heading', [], $locale))
                ->assertSee(__('auth.verify_email.resend', [], $locale))
                ->getContent();

            $this->assertStringNotContainsString('auth.verify_email.', $html, "raw key leaked in {$locale}");
        }

        // Arabic actually renders Arabic.
        $this->withSession(['locale' => 'ar'])->actingAs($user)
            ->get(route('verification.notice'))
            ->assertSee('تأكيد', false);
    }

    public function test_password_reset_screens_are_unaffected(): void
    {
        $this->get(route('password.request'))->assertOk();
        $this->post(route('password.email'), ['email' => 'nobody@example.com'])->assertRedirect();
    }
}
