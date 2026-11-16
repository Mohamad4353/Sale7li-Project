<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingRequest;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Services\RequestWorkflow;
use App\Support\BookingAvailability;
use App\Support\BookingDraft;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class BookingController extends Controller
{
    /** Keep in step with StoreBookingRequest. */
    private const MAX_HORIZON_DAYS = 90;

    public function create(Request $request)
    {
        $categories = ServiceCategory::active()->orderBy('name')->get();
        $draft = BookingDraft::get();

        // A "Request this service" link from the marketplace preselects the
        // category (validated against the active list, never trusted raw); a
        // saved draft is the fallback.
        $preselected = $request->query('category')
            ? optional($categories->firstWhere('slug', $request->query('category')))->id
            : ($draft['service_category_id'] ?? null);

        return view('booking.create', [
            'categories' => $categories,
            'addresses' => auth()->user()->addresses()->latest()->get(),
            'preselected' => $preselected,
            'prefill' => $draft,
            'coverage' => $this->coverageHint($categories, $preselected, $draft),
        ]);
    }

    /**
     * "Book again" — open the booking form pre-filled from one of the customer's
     * own past requests (any status). This only seeds the form; submitting still
     * goes through store() and creates a brand-new request. Nothing financial is
     * copied or duplicated.
     */
    public function rebook(Request $request, ServiceRequest $serviceRequest)
    {
        abort_unless($request->user()->id === $serviceRequest->customer_id, 403);

        $categories = ServiceCategory::active()->orderBy('name')->get();

        return view('booking.create', [
            'categories' => $categories,
            'addresses' => $request->user()->addresses()->latest()->get(),
            'preselected' => $serviceRequest->service_category_id,
            'prefill' => [
                'problem_description' => $serviceRequest->problem_description,
                'address_id' => $serviceRequest->address_id,
                'is_emergency' => (bool) $serviceRequest->is_emergency,
            ],
        ]);
    }

    public function store(StoreBookingRequest $request, RequestWorkflow $workflow)
    {
        $data = $request->validated();

        $paths = [];
        foreach ($request->file('photos', []) as $file) {
            $paths[] = $file->store('requests', 'local');
        }

        $requestModel = $workflow->create([
            'customer_id' => auth()->id(),
            'service_category_id' => $data['service_category_id'],
            'address_id' => $data['address_id'],
            'problem_description' => $data['problem_description'],
            'preferred_date' => $data['preferred_date'],
            'preferred_time' => $data['preferred_time'],
            'is_emergency' => $request->boolean('is_emergency'),
            'problem_images' => $paths,
        ]);

        BookingDraft::forget();

        return redirect()->route('customer.requests.show', $requestModel)
            ->with('success', __('messages.flash.request_submitted'));
    }

    /**
     * Stash the in-progress booking form in the session before the customer
     * steps away to manage addresses, so nothing they typed is lost. The target
     * is strictly allow-listed (never a raw URL). Every stored value is
     * re-validated by store() on the real submit — this is convenience only.
     */
    public function draft(Request $request)
    {
        BookingDraft::put($request->all());

        return redirect()->route(
            $request->input('next') === 'addresses'
                ? 'customer.addresses.index'
                : 'customer.booking.create',
        );
    }

    /**
     * Advisory, read-only professional-coverage for a category + date (+ time).
     * Customer-only and rate-limited; returns only { count, covered } — never a
     * worker identity. Never a booking gate.
     */
    public function availability(Request $request)
    {
        $data = $request->validate([
            'category' => ['required', Rule::exists('service_categories', 'id')->where('is_active', true)],
            'date' => ['required', 'date', 'after_or_equal:today', 'before_or_equal:'.now()->addDays(self::MAX_HORIZON_DAYS)->toDateString()],
            'time' => ['nullable', 'date_format:H:i'],
        ]);

        return response()->json(BookingAvailability::coverage(
            (int) $data['category'],
            Carbon::parse($data['date']),
            $data['time'] ?? null,
        ));
    }

    /**
     * The advisory coverage line shown on the booking page itself, computed only
     * when a saved draft already pins both a valid category and an in-range date.
     *
     * @param  Collection<int, ServiceCategory>  $categories
     * @param  array<string, scalar>  $draft
     * @return array{count: int, covered: bool}|null
     */
    private function coverageHint(Collection $categories, int|string|null $preselected, array $draft): ?array
    {
        if (! $preselected
            || ! $categories->contains('id', (int) $preselected)
            || empty($draft['preferred_date'])) {
            return null;
        }

        try {
            $date = Carbon::parse((string) $draft['preferred_date']);
        } catch (\Throwable $e) {
            return null;
        }

        if ($date->lt(now()->startOfDay()) || $date->gt(now()->addDays(self::MAX_HORIZON_DAYS)->endOfDay())) {
            return null;
        }

        return BookingAvailability::coverage((int) $preselected, $date, $draft['preferred_time'] ?? null);
    }
}
