<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Notification;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Models\WorkerProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;

class RequestWorkflow
{
    private const TRANSITIONS = [
        'pending' => ['accepted', 'rejected', 'cancelled'],
        'accepted' => ['scheduled', 'cancelled'],
        'scheduled' => ['scheduled', 'on_the_way', 'cancelled'],
        'on_the_way' => ['in_progress', 'cancelled'],
        'in_progress' => ['completed'],
        'completed' => [],
        'cancelled' => [],
        'rejected' => [],
    ];

    public function create(array $data): ServiceRequest
    {
        return DB::transaction(function () use ($data) {
            $data['reference'] = $this->reference();
            $request = ServiceRequest::create($data);

            $this->notify($request->customer_id, 'request_submitted', $request->id, [
                'reference' => $request->reference,
            ]);

            $this->log($request, 'request.created', ['status' => $request->status]);

            return $request;
        });
    }

    /**
     * Assign a professional to a pending request. The request stays `pending`
     * until the professional explicitly accepts it (see workerAccept/workerDecline).
     */
    public function assign(ServiceRequest $request, User $worker): ServiceRequest
    {
        return DB::transaction(function () use ($request, $worker) {
            if ($request->status !== 'pending') {
                throw new LogicException("Request {$request->reference} cannot be assigned in its current state.");
            }

            $request->update(['worker_id' => $worker->id]);

            $this->notify($worker->id, 'request_assigned', $request->id, [
                'reference' => $request->reference,
            ]);

            $this->notify($request->customer_id, 'professional_assigned', $request->id, [
                'reference' => $request->reference,
            ]);

            $this->log($request, 'request.assigned', ['worker_id' => $worker->id]);

            return $request->fresh();
        });
    }

    /**
     * The assigned professional confirms the job, moving it from `pending` to `accepted`.
     */
    public function workerAccept(ServiceRequest $request, User $worker): ServiceRequest
    {
        if ($request->worker_id !== $worker->id) {
            throw new LogicException("Request {$request->reference} is not assigned to this professional.");
        }

        return $this->transition($request, 'accepted');
    }

    /**
     * The assigned professional declines the job. The request returns to the
     * unassigned pending pool so an admin can reassign it.
     */
    public function workerDecline(ServiceRequest $request, User $worker): ServiceRequest
    {
        if ($request->worker_id !== $worker->id || $request->status !== 'pending') {
            throw new LogicException("Request {$request->reference} cannot be declined in its current state.");
        }

        return DB::transaction(function () use ($request, $worker) {
            $request->update(['worker_id' => null]);

            $this->notify($request->customer_id, 'professional_declined', $request->id, [
                'reference' => $request->reference,
            ]);

            foreach (User::where('role', 'admin')->pluck('id') as $adminId) {
                $this->notify($adminId, 'request_unassigned', $request->id, [
                    'reference' => $request->reference,
                ]);
            }

            $this->log($request, 'request.declined', ['worker_id' => $worker->id]);

            return $request->fresh();
        });
    }

    /**
     * @param  string|null  $cancelledBy  who ended the request ('customer' | 'worker' | 'admin'),
     *                                    stored only when $status is cancelled/rejected. Never
     *                                    sourced from request input.
     */
    public function transition(ServiceRequest $request, string $status, ?string $cancelledBy = null, ?string $cancellationReason = null): ServiceRequest
    {
        if (! in_array($status, self::TRANSITIONS[$request->status] ?? [], true)) {
            throw new LogicException("Invalid status transition {$request->status} -> {$status}");
        }

        return DB::transaction(function () use ($request, $status, $cancelledBy, $cancellationReason) {
            $attributes = [
                'status' => $status,
                'completed_at' => $status === 'completed' ? now() : $request->completed_at,
            ];

            // Freeze the chargeable amount when the job completes. The accepted
            // quotation total is represented on the request as `quoted_total`;
            // that is the single authoritative source. It is only snapshotted
            // once (never overwritten) and is left NULL — never fabricated as
            // 0 — when the job was completed without an accepted quotation.
            if ($status === 'completed' && $request->final_total === null) {
                $attributes['final_total'] = $request->quoted_total;
            }

            $request->fill($attributes);

            // Attribution columns are intentionally NOT fillable — set directly
            // so no request payload can ever forge them.
            if (in_array($status, ['cancelled', 'rejected'], true) && $cancelledBy !== null) {
                $request->cancelled_by = $cancelledBy;
                $request->cancellation_reason = $cancellationReason;
            }

            $request->save();

            $statusData = ['reference' => $request->reference, 'status' => $status];
            $this->notify($request->customer_id, 'request_status', $request->id, $statusData);

            if ($request->worker_id) {
                $this->notify($request->worker_id, 'request_status', $request->id, $statusData);
            }

            $this->log($request, 'request.status_changed', ['status' => $status]);

            if ($request->worker_id && in_array($status, ['completed', 'cancelled', 'rejected'], true)) {
                $this->recomputeWorkerReputation($request->worker_id);
            }

            return $request->fresh();
        });
    }

    /**
     * Recompute a professional's reputation counters from real request history
     * rather than trusting stored/seeded values.
     *
     * completion_rate  = completed / (completed + worker-attributed cancellations)
     * cancellation_rate = worker-attributed cancellations / (same denominator)
     *
     * Only cancellations attributed to the worker (or legacy rows with no
     * attribution, kept for historical continuity) count against reputation.
     * Customer- and admin/system-initiated cancellations are excluded — a
     * customer changing their mind must never damage the worker's rates.
     */
    private function recomputeWorkerReputation(int $workerId): void
    {
        $profile = WorkerProfile::where('user_id', $workerId)->first();

        if (! $profile) {
            return;
        }

        $completed = ServiceRequest::where('worker_id', $workerId)->where('status', 'completed')->count();
        $unfinished = ServiceRequest::where('worker_id', $workerId)
            ->whereIn('status', ['cancelled', 'rejected'])
            ->where(fn ($query) => $query->whereNull('cancelled_by')->orWhere('cancelled_by', 'worker'))
            ->count();
        $total = $completed + $unfinished;

        $profile->update([
            'completed_jobs' => $completed,
            'completion_rate' => $total > 0 ? round($completed / $total * 100, 2) : 100,
            'cancellation_rate' => $total > 0 ? round($unfinished / $total * 100, 2) : 0,
        ]);
    }

    public function allowedNextStatuses(ServiceRequest $request): array
    {
        return self::TRANSITIONS[$request->status] ?? [];
    }

    private function reference(): string
    {
        do {
            $reference = 'SL-'.strtoupper(Str::random(8));
        } while (ServiceRequest::where('reference', $reference)->exists());

        return $reference;
    }

    /**
     * Persist a structured notification. Human-readable text is rendered from
     * the notifications translation files at display time; see Notification::record().
     *
     * @param  array<string, mixed>  $data
     */
    private function notify(int $userId, string $type, int $requestId, array $data = []): void
    {
        Notification::record($userId, $type, $requestId, $data);
    }

    private function log(ServiceRequest $request, string $action, array $metadata = []): void
    {
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'subject_type' => ServiceRequest::class,
            'subject_id' => $request->id,
            'metadata' => $metadata,
            'ip_address' => request()->ip(),
        ]);
    }
}
