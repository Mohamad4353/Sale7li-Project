<?php

namespace App\Http\Controllers;

use App\Marketplace\DiscoveryFilters;
use App\Marketplace\PaginationSeo;
use App\Models\ServiceCategory;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    /** Allow-listed sort keys; the first is the default. */
    private const SORTS = ['recommended', 'price_low', 'price_high', 'newest', 'name'];

    public function index(Request $request)
    {
        $filters = DiscoveryFilters::fromRequest($request, self::SORTS);

        $services = ServiceCategory::active()
            ->when($filters->hasSearch(), fn ($q) => $q->where(function ($w) use ($filters) {
                $like = '%'.$filters->q.'%';
                $w->where('name', 'like', $like)
                    ->orWhere('name_ar', 'like', $like)
                    ->orWhere('description', 'like', $like)
                    ->orWhere('description_ar', 'like', $like);
            }))
            ->when($filters->category, fn ($q) => $q->where('slug', $filters->category))
            ->when($filters->minPrice !== null, fn ($q) => $q->where('base_price', '>=', $filters->minPrice))
            ->when($filters->maxPrice !== null, fn ($q) => $q->where('base_price', '<=', $filters->maxPrice))
            ->withCount(['workerServices as professionals_count' => fn ($q) => $q
                ->where('is_active', true)
                ->whereHas('worker', fn ($w) => $w->verifiedProfessional()),
            ])
            ->tap(fn ($q) => match ($filters->sort) {
                'price_low' => $q->orderBy('base_price')->orderBy('name'),
                'price_high' => $q->orderByDesc('base_price')->orderBy('name'),
                'newest' => $q->latest(),
                default => $q->orderBy('name'),
            })
            ->paginate(12)
            ->withQueryString();

        $categories = ServiceCategory::active()
            ->orderBy('name')
            ->get(['id', 'name', 'name_ar', 'slug', 'icon']);

        // /services honours q + category + price + sort (not min_rating), so the
        // canonical / rel=prev/next carry only those — never tracking params.
        $seoParams = collect($filters->except())->except('min_rating')->all();

        return view('services.index', [
            'services' => $services,
            'categories' => $categories,
            'filters' => $filters,
            'seo' => PaginationSeo::links('services.index', $seoParams, $services),
            'priceBounds' => [
                (int) ServiceCategory::active()->min('base_price'),
                (int) ceil((float) ServiceCategory::active()->max('base_price')),
            ],
        ]);
    }

    public function show(ServiceCategory $service)
    {
        abort_unless($service->is_active, 404);

        // Bounded: this is a public, unauthenticated page and is not paginated.
        // A category with hundreds of verified professionals must not load them
        // all (plus their profile/availability) into one response. The full,
        // paginated list is one click away via "Browse all professionals".
        $service->load([
            'workerServices' => fn ($q) => $q
                ->where('is_active', true)
                ->whereHas('worker', fn ($w) => $w->verifiedProfessional())
                ->with('worker.workerProfile.availability')
                ->limit(24),
        ]);

        return view('services.show', compact('service'));
    }
}
