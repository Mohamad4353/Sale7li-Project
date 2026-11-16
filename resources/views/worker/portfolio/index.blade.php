@extends('layouts.app')
@section('content')
@php($title = __('workers.portfolio_title'))

<div class="container-app py-8 sm:py-10">
    <x-page-header :eyebrow="__('workers.profile_eyebrow')" :title="__('workers.portfolio_title')" :subtitle="__('workers.portfolio_sub')" />

    <div class="mt-8 grid gap-6 lg:grid-cols-[1fr_380px]">
        <div class="space-y-3">
            @forelse ($items as $item)
                <x-card>
                    <div class="flex flex-col gap-4 sm:flex-row">
                        <img src="{{ route('worker.portfolio.image', $item) }}?v={{ $item->updated_at?->timestamp }}"
                             alt="{{ $item->title ?: __('workers.portfolio_image') }}"
                             width="160" height="160" loading="lazy" decoding="async"
                             class="h-44 w-full shrink-0 rounded-xl bg-paper object-cover sm:h-28 sm:w-28">

                        <div class="min-w-0 flex-1">
                            <form method="POST" action="{{ route('worker.portfolio.update', $item) }}"
                                  enctype="multipart/form-data" class="space-y-3">
                                @csrf
                                @method('PUT')

                                <div>
                                    <label for="pf-{{ $item->id }}-title" class="field-label">{{ __('workers.portfolio_item_title') }}</label>
                                    <input id="pf-{{ $item->id }}-title" class="input" name="title" maxlength="120"
                                           value="{{ old('title', $item->title) }}">
                                </div>

                                <div>
                                    <label for="pf-{{ $item->id }}-description" class="field-label">{{ __('workers.portfolio_item_description') }}</label>
                                    <textarea id="pf-{{ $item->id }}-description" class="input min-h-20" name="description"
                                              maxlength="2000">{{ old('description', $item->description) }}</textarea>
                                </div>

                                <div class="flex flex-wrap items-end gap-x-5 gap-y-3">
                                    <label class="flex items-center gap-2 text-sm font-bold">
                                        <input type="hidden" name="is_visible" value="0">
                                        <input type="checkbox" name="is_visible" value="1" @checked($item->is_visible)
                                               class="h-4 w-4 rounded border-line text-ink focus-visible:ring-2 focus-visible:ring-ink/25">
                                        {{ __('workers.portfolio_visible') }}
                                    </label>

                                    <div class="w-24">
                                        <label for="pf-{{ $item->id }}-order" class="field-label">{{ __('workers.portfolio_order') }}</label>
                                        <input id="pf-{{ $item->id }}-order" class="input" type="number" min="0" max="9999"
                                               name="sort_order" value="{{ old('sort_order', $item->sort_order) }}">
                                    </div>

                                    @unless ($item->is_visible)
                                        <x-badge tone="neutral">{{ __('workers.portfolio_hidden_badge') }}</x-badge>
                                    @endunless
                                </div>

                                <div>
                                    <label for="pf-{{ $item->id }}-image" class="field-label">{{ __('workers.portfolio_replace_image') }}</label>
                                    <input id="pf-{{ $item->id }}-image" type="file" name="image"
                                           accept="image/jpeg,image/png,image/webp" class="input">
                                    <p class="field-hint">{{ __('workers.portfolio_image_hint') }}</p>
                                </div>

                                <x-button size="sm">{{ __('workers.portfolio_save_item') }}</x-button>
                            </form>

                            <form method="POST" action="{{ route('worker.portfolio.destroy', $item) }}"
                                  class="mt-2" data-confirm="{{ __('workers.portfolio_delete_confirm') }}">
                                @csrf
                                @method('DELETE')
                                <button class="text-sm font-bold text-rose-600 transition hover:text-rose-700">{{ __('workers.portfolio_delete') }}</button>
                            </form>
                        </div>
                    </div>
                </x-card>
            @empty
                <x-card padding="p-0">
                    <x-empty icon="image" :title="__('workers.portfolio_none_title')" :message="__('workers.portfolio_none_hint')">
                        <x-slot:action>
                            <x-button href="#add-portfolio-item" size="sm">{{ __('workers.portfolio_add') }}</x-button>
                        </x-slot:action>
                    </x-empty>
                </x-card>
            @endforelse
        </div>

        <x-card id="add-portfolio-item" class="self-start scroll-mt-24">
            <h2 class="t-card">{{ __('workers.portfolio_add') }}</h2>
            <form method="POST" action="{{ route('worker.portfolio.store') }}" enctype="multipart/form-data" class="mt-4 space-y-4">
                @csrf

                <x-field :label="__('workers.portfolio_image')" name="image" :hint="__('workers.portfolio_image_hint')" required>
                    <input id="f-image" type="file" name="image" accept="image/jpeg,image/png,image/webp" class="input" required>
                </x-field>

                <x-field :label="__('workers.portfolio_item_title')" name="title">
                    <input id="f-title" class="input" name="title" maxlength="120" value="{{ old('title') }}">
                </x-field>

                <x-field :label="__('workers.portfolio_item_description')" name="description">
                    <textarea id="f-description" class="input min-h-24" name="description" maxlength="2000">{{ old('description') }}</textarea>
                </x-field>

                <div class="flex flex-wrap items-end gap-x-5 gap-y-3">
                    <label class="flex items-center gap-2 text-sm font-bold">
                        <input type="hidden" name="is_visible" value="0">
                        <input type="checkbox" name="is_visible" value="1" @checked(old('is_visible', true))
                               class="h-4 w-4 rounded border-line text-ink focus-visible:ring-2 focus-visible:ring-ink/25">
                        {{ __('workers.portfolio_visible') }}
                    </label>

                    <x-field :label="__('workers.portfolio_order')" name="sort_order" class="w-24" :hint="__('workers.portfolio_order_hint')">
                        <input id="f-sort_order" class="input" type="number" min="0" max="9999" name="sort_order" value="{{ old('sort_order', 0) }}">
                    </x-field>
                </div>

                <x-button class="w-full">{{ __('workers.portfolio_save_new') }}</x-button>
            </form>
        </x-card>
    </div>
</div>
@endsection
