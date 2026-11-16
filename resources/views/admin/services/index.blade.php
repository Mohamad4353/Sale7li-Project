@extends('layouts.app')
@section('content')
@php($title = __('admin.services_title'))

<div class="container-app py-8 sm:py-10">
    <x-page-header :eyebrow="__('admin.eyebrow')" :title="__('admin.services_title')" />

    <div class="mt-7 grid gap-6 lg:grid-cols-[1fr_380px]">
        <x-card padding="p-0" class="overflow-hidden">
            <div class="divide-y divide-line">
                @forelse ($categories as $c)
                    <div class="flex items-center justify-between gap-4 p-5">
                        <div class="min-w-0">
                            <div class="truncate font-extrabold">{{ $c->icon }} {{ $c->name }} · {{ $c->name_ar }}</div>
                            <div class="mt-1 flex items-center gap-2 t-small">
                                <span>{{ __('admin.base_price') }}: <x-money :amount="$c->base_price" /></span>
                                <x-badge :tone="$c->is_active ? 'success' : 'neutral'">
                                    {{ $c->is_active ? __('admin.active') : __('admin.inactive') }}
                                </x-badge>
                            </div>
                        </div>
                        <div class="flex shrink-0 items-center gap-1">
                            <a href="{{ route('admin.services.edit', $c) }}" class="table-action text-muted hover:text-ink">{{ __('admin.edit') }}</a>
                            <form method="POST" action="{{ route('admin.services.toggle', $c) }}">
                                @csrf
                                <button class="table-action {{ $c->is_active ? 'text-rose-600' : 'text-ink' }}">{{ $c->is_active ? __('admin.disable') : __('admin.enable') }}</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <x-empty icon="sliders" :title="__('admin.no_categories')" />
                @endforelse
            </div>
        </x-card>

        <x-card>
            <h2 class="t-card">{{ __('admin.add_service') }}</h2>
            <form method="POST" action="{{ route('admin.services.store') }}" class="mt-4 space-y-4">
                @csrf
                <x-field :label="__('admin.name_en')" name="name" required>
                    <input id="f-name" class="input" name="name" value="{{ old('name') }}" placeholder="{{ __('admin.name_en_eg') }}" required>
                </x-field>
                <x-field :label="__('admin.name_ar')" name="name_ar" required>
                    <input id="f-name_ar" class="input" dir="rtl" name="name_ar" value="{{ old('name_ar') }}" placeholder="{{ __('admin.name_ar_eg') }}" required>
                </x-field>
                <x-field :label="__('admin.icon')" name="icon" :hint="__('admin.icon_hint')">
                    <input id="f-icon" class="input" name="icon" value="{{ old('icon') }}" maxlength="8">
                </x-field>
                <x-field :label="__('admin.base_price')" name="base_price" required>
                    <input id="f-base_price" class="input" type="number" step="0.01" min="0" name="base_price" value="{{ old('base_price') }}" required>
                </x-field>
                <x-field :label="__('admin.description_en')" name="description">
                    <textarea id="f-description" class="input" name="description">{{ old('description') }}</textarea>
                </x-field>
                <x-field :label="__('admin.description_ar')" name="description_ar">
                    <textarea id="f-description_ar" class="input" dir="rtl" name="description_ar">{{ old('description_ar') }}</textarea>
                </x-field>
                <x-button class="w-full">{{ __('admin.create_category') }}</x-button>
            </form>
        </x-card>
    </div>
</div>
@endsection
