<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssignWorkerRequest;
use App\Models\ActivityLog;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\RequestWorkflow;
use App\Support\CsvExport;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use LogicException;

class RequestController extends Controller
{
    /** Operational queue views layered on top of the plain status filter. */
    private const QUEUES = ['all', 'unassigned', 'emergency', 'stale'];

    /** Statuses from which an unassigned request can still be actioned/assigned. */
    private const ACTIONABLE_STATUSES = ['pending'];

    public function index(Request $request)
    {
        $status = $request->string('status')->value();
        $queue = $request->string('queue')->value();
        $queue = in_array($queue, self::QUEUES, true) ? $queue : 'all';

        $staleBefore = now()->subHours((int) config('sale7li.stale_request_hours', 24));

        // A fresh base builder honouring the status filter, reused for the list
        // and the per-queue badge counts.
        $base = fn () => ServiceRequest::query()
            ->when($status, fn ($query) => $query->where('status', $status));

        $list = $this->applyQueue($base()->with(['customer', 'worker', 'category']), $queue, $staleBefore);

        $requests = $this->orderedForQueue($list)
            ->paginate(15)
            ->withQueryString();

        $counts = [
            'unassigned' => $this->applyQueue($base(), 'unassigned', $staleBefore)->count(),
            'emergency' => $this->applyQueue($base(), 'emergency', $staleBefore)->count(),
            'stale' => $this->applyQueue($base(), 'stale', $staleBefore)->count(),
        ];

        $workers = User::query()
            ->where('role', 'worker')
            ->where('status', 'active')
            ->whereHas('workerProfile', fn ($query) => $query->where('verification_status', 'verified'))
            ->with(['workerProfile', 'workerServices' => fn ($query) => $query->where('is_active', true)])
            ->orderBy('name')
            ->get();

        return view('admin.requests.index', compact('requests', 'workers', 'status', 'queue', 'counts', 'staleBefore'));
    }

    /**
     * CSV of the request queue for the current status + queue filter — the same
     * rows index() would list, streamed instead of paginated.
     */
    public function export(Request $request)
    {
        $status = $request->string('status')->value();
        $queue = $request->string('queue')->value();
        $queue = in_array($queue, self::QUEUES, true) ? $queue : 'all';
        $staleBefore = now()->subHours((int) config('sale7li.stale_request_hours', 24));

        $list = $this->applyQueue(
            ServiceRequest::query()
                ->when($status, fn ($query) => $query->where('status', $status))
                ->with(['customer:id,name', 'worker:id,name', 'category:id,name,name_ar']),
            $queue,
            $staleBefore,
        );

        $query = $this->orderedForQueue($list);

        $headings = [
            __('requests.reference'),
            __('admin.col_status'),
            __('admin.emergency'),
            __('payments.col_customer'),
            __('payments.col_worker'),
            __('payments.col_service'),
            __('admin.created'),
            __('bookings.preferred_date'),
        ];

        $rows = function () use ($query) {
            foreach ($query->lazy() as $req) {
                yield [
                    $req->reference,
                    __('status.request.'.$req->status),
                    $req->is_emergency ? __('messages.yes') : __('messages.no'),
                    $req->customer?->name,
                    $req->worker?->name,
                    $req->category?->localized_name,
                    $req->created_at?->toDateString(),
                    $req->preferred_date,
                ];
            }
        };

        return CsvExport::download('requests-'.now()->toDateString().'.csv', $headings, $rows());
    }

    /** Apply an operational queue filter to a ServiceRequest builder. */
    private function applyQueue($query, string $queue, Carbon $staleBefore)
    {
        return match ($queue) {
            'unassigned' => $query->whereNull('worker_id')->whereIn('status', self::ACTIONABLE_STATUSES),
            'emergency' => $query->where('is_emergency', true)->whereNotIn('status', ['completed', 'cancelled', 'rejected']),
            'stale' => $query->whereNull('worker_id')->whereIn('status', self::ACTIONABLE_STATUSES)
                ->where('created_at', '<=', $staleBefore),
            default => $query,
        };
    }

    /** Shared ordering: emergencies first, then still-unassigned pending, then oldest. */
    private function orderedForQueue($query)
    {
        return $query
            ->orderByDesc('is_emergency')
            ->orderByRaw("CASE WHEN worker_id IS NULL AND status = 'pending' THEN 0 ELSE 1 END")
            ->orderBy('created_at');
    }

    /**
     * Read-only operational detail for one request. The only mutating action on
     * this page is the existing assignment form, which still posts to assign()
     * and is re-validated there — the eligible-worker list here is advisory only.
     */
    public function show(ServiceRequest $serviceRequest)
    {
        $this->authorize('view', $serviceRequest);

        $serviceRequest->load([
            'customer', 'worker.workerProfile', 'category', 'address', 'appointment',
            'quotations.worker', 'payment.refunds', 'invoice', 'review',
            'conversation.messages.sender', 'complaints.reporter',
        ]);

        $eligibleWorkers = User::query()
            ->where('role', 'worker')
            ->where('status', 'active')
            ->whereHas('workerProfile', fn ($query) => $query->where('verification_status', 'verified'))
            ->whereHas('workerServices', fn ($query) => $query
                ->where('service_category_id', $serviceRequest->service_category_id)
                ->where('is_active', true))
            ->with('workerProfile')
            ->orderBy('name')
            ->get();

        $activity = ActivityLog::query()
            ->where('subject_type', ServiceRequest::class)
            ->where('subject_id', $serviceRequest->id)
            ->with('user')
            ->latest()
            ->get();

        return view('admin.requests.show', compact('serviceRequest', 'eligibleWorkers', 'activity'));
    }

    public function assign(AssignWorkerRequest $request, ServiceRequest $serviceRequest, RequestWorkflow $workflow)
    {
        $data = $request->validated();

        $worker = User::query()
            ->whereKey($data['worker_id'])
            ->where('role', 'worker')
            ->where('status', 'active')
            ->whereHas('workerProfile', fn ($query) => $query->where('verification_status', 'verified'))
            ->whereHas('workerServices', fn ($query) => $query
                ->where('service_category_id', $serviceRequest->service_category_id)
                ->where('is_active', true))
            ->first();

        if (! $worker) {
            return back()->withErrors([
                'worker_id' => 'The selected professional is not verified, active, or qualified for this service.',
            ]);
        }

        try {
            $workflow->assign($serviceRequest, $worker);
        } catch (LogicException $e) {
            return back()->withErrors(['worker_id' => $e->getMessage()]);
        }

        return back()->with('success', __('messages.flash.assigned'));
    }
}
