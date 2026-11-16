<?php

namespace Tests\Feature;

use App\Models\Complaint;
use App\Models\Conversation;
use App\Models\Favorite;
use App\Models\Invoice;
use App\Models\Message;
use App\Models\Payment;
use App\Models\Review;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Models\WorkerProfile;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 31 Step 3 — the demo seed builds a deterministic, presentation-ready
 * walkthrough dataset and is safe to run more than once.
 */
class DatabaseSeederDemoDataTest extends TestCase
{
    use RefreshDatabase;

    private function runSeeder(): void
    {
        // Call run() directly (the default test env is "testing", not "production").
        $this->app->make(DatabaseSeeder::class)->run();
    }

    public function test_the_demo_dataset_covers_the_full_walkthrough(): void
    {
        $this->runSeeder();

        // Accounts: 1 admin, 4 customers, 6 workers — deliberately small.
        $this->assertSame(1, User::where('role', 'admin')->count());
        $this->assertSame(4, User::where('role', 'customer')->count());
        $this->assertSame(6, User::where('role', 'worker')->count());

        // Requests across the meaningful lifecycle states.
        $byStatus = ServiceRequest::query()->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');
        foreach (['pending', 'accepted', 'scheduled', 'in_progress', 'completed', 'cancelled', 'rejected'] as $state) {
            $this->assertGreaterThan(0, $byStatus[$state] ?? 0, "no seeded request in state [{$state}]");
        }
        $this->assertGreaterThanOrEqual(4, $byStatus['completed']);

        // Marketplace: multiple verified, marketplace-visible professionals + a
        // second verification state.
        $this->assertGreaterThanOrEqual(4, User::verifiedProfessional()->count());
        $this->assertGreaterThan(
            1,
            WorkerProfile::query()->distinct()->count('verification_status'),
        );

        // Payments captured through the real path — commission + worker-net set,
        // integer-cent consistent (amount = commission + net).
        $paid = Payment::where('status', 'paid')->get();
        $this->assertGreaterThanOrEqual(4, $paid->count());
        foreach ($paid as $p) {
            $this->assertNotNull($p->worker_net);
            $this->assertNotNull($p->commission_amount);
            $this->assertSame(
                (int) round($p->amount * 100),
                (int) round($p->commission_amount * 100) + (int) round($p->worker_net * 100),
            );
        }
        $this->assertSame(Payment::where('status', 'paid')->count(), Invoice::count());

        // Reviews aggregate onto the professional's profile.
        $this->assertGreaterThanOrEqual(3, Review::count());
        $ahmad = User::where('email', 'worker@sale7li.test')->first()->workerProfile;
        $this->assertGreaterThan(0, $ahmad->completed_jobs);
        $this->assertGreaterThan(0, $ahmad->rating_count);
        $this->assertGreaterThan(0, (float) $ahmad->rating_average);

        // Complaints: one open, one resolved; internal_notes stay on the record.
        $this->assertDatabaseHas('complaints', ['status' => 'open']);
        $this->assertDatabaseHas('complaints', ['status' => 'resolved']);
        $this->assertGreaterThan(0, Complaint::whereNotNull('internal_notes')->count());

        // Messaging + favourites for the primary customer.
        $this->assertGreaterThanOrEqual(2, Conversation::count());
        $this->assertGreaterThan(0, Message::count());
        $customer = User::where('email', 'customer@sale7li.test')->first();
        $this->assertGreaterThanOrEqual(3, $customer->favoriteWorkers()->count());

        // Time-series charts have a spread, not a single spike, inside the
        // 30-day analytics window.
        $window = now()->subDays(29)->startOfDay();
        $this->assertGreaterThanOrEqual(5, ServiceRequest::where('created_at', '>=', $window)->count());

        $requestDays = ServiceRequest::where('created_at', '>=', $window)->pluck('created_at')
            ->map->toDateString()->unique();
        $this->assertGreaterThanOrEqual(3, $requestDays->count(), 'requests should span several days');

        $revenueDays = Payment::where('status', 'paid')->where('paid_at', '>=', $window)->pluck('paid_at')
            ->map->toDateString()->unique();
        $this->assertGreaterThanOrEqual(3, $revenueDays->count(), 'revenue should span several days');
    }

    public function test_the_seeder_is_idempotent(): void
    {
        $this->runSeeder();

        $before = [
            'users' => User::count(),
            'requests' => ServiceRequest::count(),
            'payments' => Payment::count(),
            'reviews' => Review::count(),
            'complaints' => Complaint::count(),
            'favourites' => Favorite::count(),
            'conversations' => Conversation::count(),
        ];

        $this->runSeeder(); // run again

        foreach ($before as $key => $count) {
            $this->assertSame($count, match ($key) {
                'users' => User::count(),
                'requests' => ServiceRequest::count(),
                'payments' => Payment::count(),
                'reviews' => Review::count(),
                'complaints' => Complaint::count(),
                'favourites' => Favorite::count(),
                'conversations' => Conversation::count(),
            }, "re-seeding changed the [{$key}] count");
        }

        $this->assertSame(1, ServiceRequest::where('reference', 'SL-DEMO002')->count());
        $this->assertSame(1, User::where('email', 'layla.karam@sale7li.test')->count());
    }
}
