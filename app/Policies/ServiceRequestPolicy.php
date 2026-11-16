<?php

namespace App\Policies;

use App\Models\ServiceRequest;
use App\Models\User;

class ServiceRequestPolicy
{
    private function owns(User $user, ServiceRequest $request): bool
    {
        return $user->id === $request->customer_id;
    }

    private function assignedWorker(User $user, ServiceRequest $request): bool
    {
        return $request->worker_id !== null && $user->id === $request->worker_id;
    }

    /** Customer who created it, the professional currently assigned, or an admin. */
    public function view(User $user, ServiceRequest $request): bool
    {
        return $user->role === 'admin'
            || $this->owns($user, $request)
            || $this->assignedWorker($user, $request);
    }

    public function cancel(User $user, ServiceRequest $request): bool
    {
        return $this->owns($user, $request);
    }

    /** The customer who created the request may report a problem about it. */
    public function complain(User $user, ServiceRequest $request): bool
    {
        return $this->owns($user, $request);
    }

    /** The customer who created the request may propose a new appointment time. */
    public function requestReschedule(User $user, ServiceRequest $request): bool
    {
        return $this->owns($user, $request);
    }

    public function pay(User $user, ServiceRequest $request): bool
    {
        return $this->owns($user, $request);
    }

    public function review(User $user, ServiceRequest $request): bool
    {
        return $this->owns($user, $request);
    }

    /** Only the assigned professional drives the job status machine. */
    public function updateStatus(User $user, ServiceRequest $request): bool
    {
        return $this->assignedWorker($user, $request);
    }

    public function quote(User $user, ServiceRequest $request): bool
    {
        return $this->assignedWorker($user, $request);
    }

    public function acceptAssignment(User $user, ServiceRequest $request): bool
    {
        return $this->assignedWorker($user, $request);
    }

    /** Either party on the request may use its conversation. */
    public function message(User $user, ServiceRequest $request): bool
    {
        return $this->owns($user, $request) || $this->assignedWorker($user, $request);
    }
}
