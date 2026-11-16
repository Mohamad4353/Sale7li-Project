<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReviewRequest;
use App\Models\Review;
use App\Models\ServiceRequest;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    public function store(StoreReviewRequest $request, ServiceRequest $serviceRequest)
    {
        if ($serviceRequest->status !== 'completed' || $serviceRequest->worker_id === null) {
            return back()->withErrors(['rating' => __('messages.flash.review_only_completed')]);
        }

        if ($serviceRequest->review()->exists()) {
            return back()->withErrors(['rating' => __('messages.flash.review_already_left')]);
        }

        $data = $request->validated();

        DB::transaction(function () use ($data, $serviceRequest) {
            Review::create([
                'service_request_id' => $serviceRequest->id,
                'customer_id' => auth()->id(),
                'worker_id' => $serviceRequest->worker_id,
                'rating' => $data['rating'],
                'comment' => $data['comment'] ?? null,
            ]);

            $profile = $serviceRequest->worker->workerProfile;
            $profile->update([
                'rating_count' => Review::where('worker_id', $serviceRequest->worker_id)->count(),
                'rating_average' => round((float) Review::where('worker_id', $serviceRequest->worker_id)->avg('rating'), 2),
            ]);
        });

        return back()->with('success', __('messages.flash.review_thanks'));
    }
}
