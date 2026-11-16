<?php

namespace Tests\Feature;

use App\Models\ServiceCategory;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederProductionSafetyTest extends TestCase
{
    use RefreshDatabase;

    private function runSeeder(): void
    {
        // Call run() directly so this exercises the seeder's own logic, not the
        // db:seed console command's separate production confirmation prompt.
        $this->app->make(DatabaseSeeder::class)->run();
    }

    public function test_it_never_creates_demo_accounts_when_running_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $this->runSeeder();

        // The known-password demo accounts (including an admin) must not exist.
        $this->assertDatabaseMissing('users', ['email' => 'admin@sale7li.test']);
        $this->assertDatabaseMissing('users', ['email' => 'customer@sale7li.test']);
        $this->assertDatabaseMissing('users', ['email' => 'worker@sale7li.test']);
        $this->assertSame(0, User::query()->count());
        $this->assertDatabaseMissing('service_requests', ['reference' => 'SL-DEMO001']);

        // Reference data the application genuinely needs is still provisioned.
        $this->assertSame(8, ServiceCategory::query()->count());
        $this->assertDatabaseHas('service_categories', ['name' => 'Electrical']);
    }

    public function test_it_still_creates_demo_accounts_outside_production(): void
    {
        // The default test environment is "testing", not "production".
        $this->runSeeder();

        $this->assertDatabaseHas('users', ['email' => 'admin@sale7li.test', 'role' => 'admin']);
        $this->assertDatabaseHas('users', ['email' => 'worker@sale7li.test', 'role' => 'worker']);
        $this->assertDatabaseHas('service_categories', ['name' => 'Electrical']);
    }
}
