<?php

namespace App\Http\Controllers\Worker;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateWorkerServicesRequest;
use App\Models\ServiceCategory;
use App\Models\WorkerService;
use Illuminate\Support\Facades\DB;

/**
 * A professional's self-service catalog. Workers enable/disable the service
 * categories they offer and set an optional starting price. Every write is
 * scoped to the authenticated worker and to the real, active category list —
 * `worker_id` is never read from the request, and unknown category keys in the
 * payload are ignored because the update iterates the active categories.
 */
class ServiceCatalogController extends Controller
{
    public function edit()
    {
        $categories = ServiceCategory::active()->orderBy('name')->get();
        $current = auth()->user()->workerServices()->get()->keyBy('service_category_id');

        return view('worker.services', compact('categories', 'current'));
    }

    public function update(UpdateWorkerServicesRequest $request)
    {
        $workerId = auth()->id();
        $submitted = collect($request->validated()['services'] ?? []);
        $activeCategoryIds = ServiceCategory::active()->pluck('id');

        DB::transaction(function () use ($submitted, $activeCategoryIds, $workerId) {
            foreach ($activeCategoryIds as $categoryId) {
                $row = $submitted->get($categoryId) ?? $submitted->get((string) $categoryId);
                $enabled = $row && filter_var($row['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);

                if ($enabled) {
                    $price = $row['price_from'] ?? null;

                    WorkerService::updateOrCreate(
                        ['worker_id' => $workerId, 'service_category_id' => $categoryId],
                        ['is_active' => true, 'price_from' => ($price === '' || $price === null) ? null : $price],
                    );

                    continue;
                }

                WorkerService::where('worker_id', $workerId)
                    ->where('service_category_id', $categoryId)
                    ->update(['is_active' => false]);
            }
        });

        return back()->with('success', __('messages.flash.services_updated'));
    }
}
