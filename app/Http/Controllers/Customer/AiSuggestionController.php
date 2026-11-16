<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuggestCategoryRequest;
use App\Models\ServiceCategory;
use App\Services\AiCategorySuggestionService;
use Illuminate\Http\JsonResponse;

/**
 * Advisory-only AI Service Assistant endpoint: turns a customer's free-text
 * problem description into a suggested (existing, active) category + a
 * clearer description. Read-only with respect to the platform — it never
 * creates or touches a ServiceRequest/Quotation/Payment/Notification and never
 * calls RequestWorkflow. The existing booking form remains the only way to
 * actually submit a request; this only pre-fills it.
 *
 * Only the description, the active category catalogue, and the current locale
 * are ever passed to the service — never the authenticated user model, their
 * profile, address book, request history, or anything financial.
 */
class AiSuggestionController extends Controller
{
    public function store(SuggestCategoryRequest $request, AiCategorySuggestionService $service): JsonResponse
    {
        $categories = ServiceCategory::active()->get();

        $suggestion = $service->suggest(
            $request->validated()['description'],
            $categories,
            app()->getLocale(),
        );

        if (! $suggestion->available) {
            return response()->json(['available' => false]);
        }

        // The service already matched categorySlug against $categories (or
        // set it null) — this lookup only resolves the localized label for an
        // already-validated slug, it is never a query built from model output.
        $category = $suggestion->categorySlug !== null
            ? $categories->firstWhere('slug', $suggestion->categorySlug)
            : null;

        return response()->json([
            'available' => true,
            'category' => $category ? ['slug' => $category->slug, 'label' => $category->localized_name] : null,
            'structured_description' => $suggestion->structuredDescription,
        ]);
    }
}
