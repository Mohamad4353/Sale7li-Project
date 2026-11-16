@extends('layouts.app')
@section('content')
@php($title = __('workers.services_title'))

<div class="container-app max-w-3xl py-8 sm:py-10">
    <x-page-header :eyebrow="__('workers.profile_eyebrow')" :title="__('workers.services_title')" :subtitle="__('workers.services_sub')" />

    <x-card class="mt-8">
        <form method="POST" action="{{ route('worker.services.update') }}" class="grid gap-5">
            @csrf
            @method('PUT')

            @if ($categories->isEmpty())
                <x-empty icon="sliders" :title="__('workers.services_none_title')" :message="__('workers.services_none_hint')" />
            @else
                <p class="t-muted">{{ __('workers.services_help') }}</p>

                <div class="grid gap-3">
                    @foreach ($categories as $category)
                        @php($row = $current->get($category->id))
                        @php($enabled = (bool) old("services.{$category->id}.enabled", $row?->is_active))
                        <div class="rounded-xl border border-line p-4 has-[:checked]:border-ink has-[:checked]:bg-paper"
                             x-data="{ on: {{ $enabled ? 'true' : 'false' }} }">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <label class="flex items-center gap-3 font-bold">
                                    <input type="hidden" name="services[{{ $category->id }}][enabled]" value="0">
                                    <input type="checkbox" value="1" x-model="on"
                                           name="services[{{ $category->id }}][enabled]" @checked($enabled)
                                           class="h-4 w-4 rounded border-line text-ink focus-visible:ring-2 focus-visible:ring-ink/25">
                                    <span>{{ $category->icon }} {{ $category->localized_name }}</span>
                                </label>

                                <div class="flex items-center gap-2" :class="on ? '' : 'opacity-40'">
                                    <label for="price-{{ $category->id }}" class="t-small font-bold">{{ __('workers.price_from') }}</label>
                                    <div class="relative">
                                        <span class="pointer-events-none absolute inset-y-0 grid place-items-center t-small ps-3">$</span>
                                        <input id="price-{{ $category->id }}" type="number" min="0" max="100000" step="0.01"
                                               inputmode="decimal" placeholder="{{ number_format($category->base_price, 0) }}"
                                               name="services[{{ $category->id }}][price_from]"
                                               value="{{ old("services.{$category->id}.price_from", $row?->price_from ? rtrim(rtrim((string) $row->price_from, '0'), '.') : '') }}"
                                               class="input w-32 ps-7" x-bind:disabled="!on">
                                    </div>
                                </div>
                            </div>
                            @error("services.{$category->id}.price_from")
                                <p class="field-error mt-2"><x-icon name="close" class="mt-px h-3.5 w-3.5 shrink-0" /><span>{{ $message }}</span></p>
                            @enderror
                        </div>
                    @endforeach
                </div>

                <x-button class="w-full">{{ __('workers.save_services') }}</x-button>
            @endif
        </form>
    </x-card>
</div>
@endsection
