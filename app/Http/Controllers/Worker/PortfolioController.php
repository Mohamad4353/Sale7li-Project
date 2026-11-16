<?php

namespace App\Http\Controllers\Worker;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePortfolioItemRequest;
use App\Http\Requests\UpdatePortfolioItemRequest;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * A professional's own portfolio / gallery.
 *
 * Every read and write is scoped to the authenticated worker through
 * workerProfile->portfolioItems(); `worker_id` is set by that relationship and
 * is never read from the request. Images are stored on the private `local` disk
 * (like the profile photo) with a framework-generated name, and are streamed
 * back only to their owner — the public customer gallery is a later step.
 */
class PortfolioController extends Controller
{
    private const DISK = 'local';

    private const DIR = 'worker-portfolio';

    public function index()
    {
        return view('worker.portfolio.index', [
            'items' => $this->items()->orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }

    public function store(StorePortfolioItemRequest $request)
    {
        $data = $request->validated();

        $this->items()->create([
            'image_path' => $request->file('image')->store(self::DIR, self::DISK),
            'title' => $data['title'] ?? null,
            'description' => $data['description'] ?? null,
            'is_visible' => $data['is_visible'],
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        return back()->with('success', __('messages.flash.portfolio_saved'));
    }

    public function update(UpdatePortfolioItemRequest $request, string $portfolioItem)
    {
        $item = $this->items()->findOrFail($portfolioItem);
        $data = $request->validated();

        $attributes = [
            'title' => $data['title'] ?? null,
            'description' => $data['description'] ?? null,
            'is_visible' => $data['is_visible'],
            'sort_order' => $data['sort_order'] ?? 0,
        ];

        if ($request->hasFile('image')) {
            $attributes['image_path'] = $request->file('image')->store(self::DIR, self::DISK);
            Storage::disk(self::DISK)->delete($item->image_path);
        }

        $item->update($attributes);

        return back()->with('success', __('messages.flash.portfolio_saved'));
    }

    public function destroy(string $portfolioItem)
    {
        $item = $this->items()->findOrFail($portfolioItem);

        Storage::disk(self::DISK)->delete($item->image_path);
        $item->delete();

        return back()->with('success', __('messages.flash.portfolio_removed'));
    }

    public function image(string $portfolioItem): StreamedResponse
    {
        $item = $this->items()->findOrFail($portfolioItem);

        $disk = Storage::disk(self::DISK);
        abort_unless($disk->exists($item->image_path), 404);

        return $disk->response($item->image_path);
    }

    /** The authenticated worker's own portfolio items — the only rows this controller ever touches. */
    private function items(): HasMany
    {
        return auth()->user()->workerProfile->portfolioItems();
    }
}
