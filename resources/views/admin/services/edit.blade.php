@extends('layouts.app')
@section('content')
@php($title = __('admin.edit_service'))

<div class="container-app max-w-xl py-8 sm:py-10">
    <x-page-header
        :back="route('admin.services.index')"
        :backLabel="__('admin.services_title')"
        :eyebrow="__('admin.eyebrow')"
        :title="__('admin.edit_service')" />

    <x-card class="mt-8">
        <form method="POST" action="{{ route('admin.services.update', $category) }}" class="space-y-4">
            @csrf
            @method('PUT')

            <x-field :label="__('admin.name_en')" name="name" required>
                <input id="f-name" class="input" name="name" value="{{ old('name', $category->name) }}" required>
            </x-field>
            <x-field :label="__('admin.name_ar')" name="name_ar" required>
                <input id="f-name_ar" class="input" dir="rtl" name="name_ar" value="{{ old('name_ar', $category->name_ar) }}" required>
            </x-field>
            <x-field :label="__('admin.icon')" name="icon" :hint="__('admin.icon_hint')">
                <input id="f-icon" class="input" name="icon" value="{{ old('icon', $category->icon) }}" maxlength="8">
            </x-field>
            <x-field :label="__('admin.base_price')" name="base_price" required>
                <input id="f-base_price" class="input" type="number" step="0.01" min="0" name="base_price" value="{{ old('base_price', $category->base_price) }}" required>
            </x-field>
            <x-field :label="__('admin.description_en')" name="description">
                <textarea id="f-description" class="input" name="description">{{ old('description', $category->description) }}</textarea>
            </x-field>
            <x-field :label="__('admin.description_ar')" name="description_ar">
                <textarea id="f-description_ar" class="input" dir="rtl" name="description_ar">{{ old('description_ar', $category->description_ar) }}</textarea>
            </x-field>
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $category->is_active)) class="rounded border-line">
                {{ __('admin.category_active') }}
            </label>

            <div class="flex gap-2">
                <x-button class="flex-1">{{ __('admin.save_category') }}</x-button>
                <x-button :href="route('admin.services.index')" variant="secondary">{{ __('messages.cancel') }}</x-button>
            </div>
        </form>
    </x-card>
</div>
@endsection
