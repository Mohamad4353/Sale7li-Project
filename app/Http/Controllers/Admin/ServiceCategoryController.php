<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateServiceCategoryRequest;
use App\Models\ServiceCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ServiceCategoryController extends Controller
{
    public function index()
    {
        return view('admin.services.index', ['categories' => ServiceCategory::latest()->paginate(15)]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'name_ar' => 'required|string|max:100',
            'description' => 'nullable|string|max:1000',
            'description_ar' => 'nullable|string|max:1000',
            'base_price' => 'required|numeric|min:0',
            // Same rule as UpdateServiceCategoryRequest: a short glyph only, no markup.
            'icon' => ['nullable', 'string', 'max:8', 'not_regex:/[<>&;\/=]/'],
        ]);
        $data['slug'] = Str::slug($data['name']).'-'.Str::lower(Str::random(5));
        $data['is_active'] = true;
        ServiceCategory::create($data);

        return back()->with('success', __('messages.flash.category_created'));
    }

    public function edit(ServiceCategory $serviceCategory)
    {
        return view('admin.services.edit', ['category' => $serviceCategory]);
    }

    /**
     * Update an existing category. The slug is deliberately left untouched so
     * bookmarked URLs and "?category=slug" deep links keep working.
     */
    public function update(UpdateServiceCategoryRequest $request, ServiceCategory $serviceCategory)
    {
        $serviceCategory->update($request->validated());

        return redirect()->route('admin.services.index')
            ->with('success', __('messages.flash.category_updated'));
    }

    public function toggle(ServiceCategory $serviceCategory)
    {
        $serviceCategory->update(['is_active' => ! $serviceCategory->is_active]);

        return back()->with('success', __('messages.flash.category_status_updated'));
    }
}
