<?php

namespace Tests\Feature\Security;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HttpSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_baseline_security_headers_are_present(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
    }

    public function test_user_supplied_text_is_escaped_in_rendered_pages(): void
    {
        $payload = '<script>alert(1)</script>';
        $customer = User::factory()->customer()->create();
        $worker = User::factory()->worker()->create();
        $request = ServiceRequest::factory()->for($customer, 'customer')->status('accepted')->create([
            'worker_id' => $worker->id,
            'problem_description' => $payload,
        ]);

        $conversation = Conversation::create([
            'service_request_id' => $request->id,
            'customer_id' => $customer->id,
            'worker_id' => $worker->id,
        ]);
        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $worker->id,
            'body' => $payload,
        ]);

        $detail = $this->actingAs($customer)->get(route('customer.requests.show', $request));
        $detail->assertOk();
        $detail->assertDontSee($payload, false);
        $detail->assertSee('&lt;script&gt;', false);

        $chat = $this->actingAs($customer)->get(route('customer.messages.show', $request));
        $chat->assertOk();
        $chat->assertDontSee($payload, false);
    }
}
