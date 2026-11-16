<?php

namespace Tests\Feature\Security;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 31 Step 2 — trusted-proxy support is environment-driven and defaults to
 * trusting NOTHING. This guards the safe default: without TRUSTED_PROXIES set
 * (the test environment), a forged `X-Forwarded-Proto: https` from an untrusted
 * client must not make a plain-HTTP request look secure, and so must not
 * trigger HSTS. The test would fail if the app ever trusted proxies
 * unconditionally (e.g. `trustProxies(at: '*')` with no env guard).
 */
class TrustedProxyTest extends TestCase
{
    use RefreshDatabase;

    public function test_forged_forwarded_headers_are_ignored_when_no_proxy_is_trusted(): void
    {
        $response = $this->get('/', [
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-For' => '198.51.100.1',
            'X-Forwarded-Host' => 'evil.example',
        ]);

        $response->assertOk();

        // Not treated as a secure request -> no Strict-Transport-Security.
        $response->assertHeaderMissing('Strict-Transport-Security');

        // The forged forwarded host did not become the app host.
        $this->assertStringNotContainsString('evil.example', (string) $response->getContent());

        // Existing baseline headers are unaffected by the trusted-proxy wiring.
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }
}
