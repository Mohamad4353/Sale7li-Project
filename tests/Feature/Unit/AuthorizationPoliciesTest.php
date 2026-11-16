<?php

namespace Tests\Feature\Unit;

use App\Models\Complaint;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Policies\ComplaintPolicy;
use App\Policies\MessagePolicy;
use App\Policies\ServiceRequestPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 28 — the authorization matrix asserted directly on the policy objects.
 *
 * The HTTP feature tests prove the net 403/allow; these pin which branch of each
 * policy method decides it, and cover the null-relationship guards that no
 * routed request currently reaches.
 */
class AuthorizationPoliciesTest extends TestCase
{
    use RefreshDatabase;

    /* --------------------------- ServiceRequestPolicy --------------------------- */

    public function test_service_request_policy_owner_worker_admin_stranger_matrix(): void
    {
        $policy = new ServiceRequestPolicy;

        $owner = User::factory()->customer()->create();
        $assigned = User::factory()->worker()->create();
        $admin = User::factory()->admin()->create();
        $strangerCustomer = User::factory()->customer()->create();
        $strangerWorker = User::factory()->worker()->create();

        $request = ServiceRequest::factory()->for($owner, 'customer')->status('accepted')
            ->create(['worker_id' => $assigned->id]);

        foreach (['cancel', 'complain', 'pay', 'review'] as $ability) {
            $this->assertTrue($policy->$ability($owner, $request), "owner passes {$ability}");
            $this->assertFalse($policy->$ability($assigned, $request), "assigned worker fails {$ability}");
            $this->assertFalse($policy->$ability($admin, $request), "admin fails {$ability}");
            $this->assertFalse($policy->$ability($strangerCustomer, $request), "stranger fails {$ability}");
        }

        foreach (['updateStatus', 'quote', 'acceptAssignment'] as $ability) {
            $this->assertTrue($policy->$ability($assigned, $request), "assigned worker passes {$ability}");
            $this->assertFalse($policy->$ability($owner, $request), "owner fails {$ability}");
            $this->assertFalse($policy->$ability($admin, $request), "admin fails {$ability}");
            $this->assertFalse($policy->$ability($strangerWorker, $request), "unrelated worker fails {$ability}");
        }

        foreach (['view', 'message'] as $ability) {
            $this->assertTrue($policy->$ability($owner, $request), "owner passes {$ability}");
            $this->assertTrue($policy->$ability($assigned, $request), "assigned worker passes {$ability}");
            $this->assertFalse($policy->$ability($strangerCustomer, $request), "stranger customer fails {$ability}");
            $this->assertFalse($policy->$ability($strangerWorker, $request), "unrelated worker fails {$ability}");
        }

        $this->assertTrue($policy->view($admin, $request), 'view is open to admin');
        $this->assertFalse($policy->message($admin, $request), 'message is not open to a non-party admin');
    }

    public function test_service_request_policy_treats_a_null_worker_id_as_no_worker(): void
    {
        $policy = new ServiceRequestPolicy;
        $owner = User::factory()->customer()->create();
        $someWorker = User::factory()->worker()->create();

        $unassigned = ServiceRequest::factory()->for($owner, 'customer')->status('pending')
            ->create(['worker_id' => null]);

        $this->assertFalse($policy->updateStatus($someWorker, $unassigned));
        $this->assertFalse($policy->quote($someWorker, $unassigned));
        $this->assertFalse($policy->acceptAssignment($someWorker, $unassigned));
        $this->assertFalse($policy->message($someWorker, $unassigned));
        $this->assertTrue($policy->view($owner, $unassigned));
    }

    /* ------------------------------ ComplaintPolicy ------------------------------ */

    public function test_complaint_policy_matrix_for_a_linked_complaint(): void
    {
        $policy = new ComplaintPolicy;

        $reporter = User::factory()->customer()->create();
        $assignedWorker = User::factory()->worker()->create();
        $admin = User::factory()->admin()->create();
        $strangerCustomer = User::factory()->customer()->create();
        $unrelatedWorker = User::factory()->worker()->create();

        $request = ServiceRequest::factory()->for($reporter, 'customer')->status('in_progress')
            ->create(['worker_id' => $assignedWorker->id]);
        $complaint = Complaint::create([
            'service_request_id' => $request->id, 'reporter_id' => $reporter->id,
            'category' => 'work_quality', 'description' => 'x', 'status' => 'open', 'priority' => 'medium',
        ]);

        $this->assertTrue($policy->view($admin, $complaint));
        $this->assertTrue($policy->view($reporter, $complaint));
        $this->assertTrue($policy->view($assignedWorker, $complaint));
        $this->assertFalse($policy->view($strangerCustomer, $complaint));
        $this->assertFalse($policy->view($unrelatedWorker, $complaint));
    }

    public function test_complaint_policy_denies_a_worker_when_the_complaint_has_no_request(): void
    {
        $policy = new ComplaintPolicy;

        $reporter = User::factory()->customer()->create();
        $worker = User::factory()->worker()->create();
        $admin = User::factory()->admin()->create();

        // service_request_id is nullable + nullOnDelete — an orphaned complaint is a real state.
        $orphan = Complaint::create([
            'service_request_id' => null, 'reporter_id' => $reporter->id,
            'category' => 'other', 'description' => 'y', 'status' => 'open', 'priority' => 'medium',
        ]);

        $this->assertTrue($policy->view($admin, $orphan));
        $this->assertTrue($policy->view($reporter, $orphan));
        $this->assertFalse($policy->view($worker, $orphan), 'no linked request => no worker claim');
    }

    /* ------------------------------- MessagePolicy ------------------------------ */

    public function test_message_policy_matrix(): void
    {
        $policy = new MessagePolicy;

        $customer = User::factory()->customer()->create();
        $worker = User::factory()->worker()->create();
        $admin = User::factory()->admin()->create();
        $stranger = User::factory()->customer()->create();

        $request = ServiceRequest::factory()->for($customer, 'customer')->status('accepted')
            ->create(['worker_id' => $worker->id]);
        $conversation = Conversation::create([
            'service_request_id' => $request->id, 'customer_id' => $customer->id, 'worker_id' => $worker->id,
        ]);
        $message = Message::create(['conversation_id' => $conversation->id, 'sender_id' => $customer->id, 'body' => 'hi']);

        $this->assertTrue($policy->view($admin, $message));
        $this->assertTrue($policy->view($customer, $message));
        $this->assertTrue($policy->view($worker, $message));
        $this->assertFalse($policy->view($stranger, $message));
    }

    public function test_message_policy_denies_everyone_when_there_is_no_parent_request(): void
    {
        $policy = new MessagePolicy;
        $admin = User::factory()->admin()->create();

        // A message with no resolvable conversation -> conversation?->request is null.
        $detached = new Message(['body' => 'orphan']);
        $detached->conversation_id = null;

        $this->assertFalse($policy->view($admin, $detached));
    }
}
