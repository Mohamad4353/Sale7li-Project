<?php

namespace Tests\Feature;

use App\Providers\AppServiceProvider;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Deployment gate: in production every generated URL — including the mailed
 * password-reset link — must be https, regardless of proxy TLS termination.
 * Local / testing / `php artisan serve` over http stay untouched.
 */
class ProductionUrlSchemeTest extends TestCase
{
    public function test_production_forces_https_on_generated_urls(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        (new AppServiceProvider($this->app))->boot();

        $this->assertStringStartsWith('https://', route('login'));
        $this->assertStringStartsWith('https://', route('password.request'));
        $this->assertStringStartsWith('https://', URL::route('password.reset', ['token' => 'x']));
    }

    public function test_non_production_does_not_force_https(): void
    {
        // The default testing environment must not be rewritten to https.
        $this->assertStringStartsWith('http://', route('login'));
    }
}
