<?php

namespace Tests\Feature\Notifications;

use App\Models\Notification;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression net for the notification actions and chrome that the existing
 * suite left thin: the single "mark read" happy path (only the IDOR negative
 * was covered), "mark all read", the empty state, the nav bell badge count,
 * and the RTL render of the list. No production code changes.
 */
class NotificationActionsTest extends TestCase
{
    use RefreshDatabase;

    private function notify(User $user, ?ServiceRequest $request = null, bool $read = false): Notification
    {
        return Notification::create([
            'user_id' => $user->id,
            'type' => 'request_status',
            'title' => 't',
            'message' => 'm',
            'data' => $request ? ['reference' => $request->reference, 'status' => 'accepted'] : null,
            'service_request_id' => $request?->id,
            'read_at' => $read ? now() : null,
        ]);
    }

    public function test_the_owner_can_mark_a_single_notification_read(): void
    {
        $customer = User::factory()->customer()->create();
        $n = $this->notify($customer);

        $this->actingAs($customer)
            ->from(route('notifications.index'))
            ->post(route('notifications.read', $n))
            ->assertRedirect(route('notifications.index'));

        $this->assertNotNull($n->fresh()->read_at);
    }

    public function test_mark_all_read_clears_only_the_callers_unread(): void
    {
        $customer = User::factory()->customer()->create();
        $a = $this->notify($customer);
        $b = $this->notify($customer);
        $alreadyRead = $this->notify($customer, read: true);
        $readStamp = $alreadyRead->read_at;

        $stranger = User::factory()->customer()->create();
        $strangerUnread = $this->notify($stranger);

        $this->actingAs($customer)
            ->from(route('notifications.index'))
            ->post(route('notifications.readAll'))
            ->assertRedirect(route('notifications.index'));

        $this->assertNotNull($a->fresh()->read_at);
        $this->assertNotNull($b->fresh()->read_at);
        // An already-read row keeps its original timestamp (it was not in scope).
        $this->assertTrue($readStamp->equalTo($alreadyRead->fresh()->read_at));
        // Another user's unread is untouched.
        $this->assertNull($strangerUnread->fresh()->read_at);
    }

    public function test_a_guest_is_sent_to_login_from_the_mark_read_endpoints(): void
    {
        $customer = User::factory()->customer()->create();
        $n = $this->notify($customer);

        $this->post(route('notifications.read', $n))->assertRedirect(route('login'));
        $this->post(route('notifications.readAll'))->assertRedirect(route('login'));

        $this->assertNull($n->fresh()->read_at);
    }

    public function test_the_empty_state_is_localized_and_leaks_no_keys(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)->withSession(['locale' => 'en'])
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee(__('notifications.none_title', [], 'en'))
            ->assertDontSee(__('notifications.mark_all_read', [], 'en'))
            ->assertDontSee('notifications.none_');

        $this->actingAs($customer)->withSession(['locale' => 'ar'])
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('<html lang="ar" dir="rtl">', false)
            ->assertSee(__('notifications.none_title', [], 'ar'))
            ->assertDontSee('notifications.');
    }

    public function test_the_list_renders_right_to_left_in_arabic(): void
    {
        $customer = User::factory()->customer()->create();
        $request = ServiceRequest::factory()->for($customer, 'customer')->status('accepted')->create();
        $this->notify($customer, $request);

        $this->actingAs($customer)->withSession(['locale' => 'ar'])
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('<html lang="ar" dir="rtl">', false)
            ->assertSee(__('notifications.title', [], 'ar'))
            ->assertDontSee('notifications.messages.')
            ->assertDontSee('notifications.types.');
    }

    public function test_the_nav_bell_badge_counts_only_the_signed_in_users_unread(): void
    {
        $customer = User::factory()->customer()->create();
        $this->notify($customer);
        $this->notify($customer);
        $this->notify($customer, read: true); // read -> not counted

        $stranger = User::factory()->customer()->create();
        $this->notify($stranger);
        $this->notify($stranger);
        $this->notify($stranger);

        $this->actingAs($customer)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee(__('nav.notifications').' (2)', false)
            ->assertDontSee(__('nav.notifications').' (5)', false);
    }
}
