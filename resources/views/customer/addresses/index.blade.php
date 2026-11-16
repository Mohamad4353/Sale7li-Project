@extends('layouts.app')
@section('content')
@php($title = __('addresses.title'))

<div class="container-app py-8 sm:py-10">
    <x-page-header :eyebrow="__('requests.eyebrow')" :title="__('addresses.title')" :subtitle="__('addresses.sub')" />

    <div class="mt-8 grid gap-6 lg:grid-cols-[1fr_380px]">
        <div class="space-y-3">
            @forelse ($addresses as $a)
                <x-card class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 font-extrabold">
                            {{ $a->title }}
                            @if ($a->is_default)<x-badge tone="success">{{ __('addresses.default') }}</x-badge>@endif
                        </div>
                        <div class="mt-1 t-muted">{{ $a->street }}, {{ $a->area }}, {{ $a->city }} {{ $a->postal_code }}</div>
                        @if ($a->instructions)<div class="mt-1 t-small">{{ $a->instructions }}</div>@endif
                    </div>
                    <div class="flex shrink-0 flex-wrap items-center justify-end gap-x-3 gap-y-1">
                        @unless ($a->is_default)
                            <form method="POST" action="{{ route('customer.addresses.default', $a) }}">
                                @csrf
                                <button class="text-sm font-bold text-muted transition hover:text-ink">{{ __('addresses.set_default') }}</button>
                            </form>
                        @endunless
                        <a href="{{ route('customer.addresses.edit', $a) }}" class="text-sm font-bold text-muted transition hover:text-ink">{{ __('addresses.edit') }}</a>
                        <form method="POST" action="{{ route('customer.addresses.destroy', $a) }}" data-confirm="{{ __('addresses.remove_confirm') }}">
                            @csrf @method('DELETE')
                            <button class="text-sm font-bold text-rose-600 transition hover:text-rose-700">{{ __('addresses.remove') }}</button>
                        </form>
                    </div>
                </x-card>
            @empty
                <x-card padding="p-0">
                    <x-empty icon="home" :title="__('addresses.none_title')" :message="__('addresses.none_hint')" />
                </x-card>
            @endforelse
        </div>

        <x-card>
            <h2 class="t-card">{{ __('addresses.add') }}</h2>
            <form method="POST" action="{{ route('customer.addresses.store') }}" class="mt-4 space-y-4">
                @csrf
                <x-field :label="__('addresses.label')" name="title" required>
                    <input id="f-title" class="input" name="title" value="{{ old('title') }}" placeholder="{{ __('addresses.label_placeholder') }}" required>
                </x-field>
                <x-field :label="__('addresses.street')" name="street" required>
                    <input id="f-street" class="input" name="street" value="{{ old('street') }}" required>
                </x-field>
                <div class="grid grid-cols-2 gap-3">
                    <x-field :label="__('addresses.city')" name="city" required>
                        <input id="f-city" class="input" name="city" value="{{ old('city') }}" required>
                    </x-field>
                    <x-field :label="__('addresses.area')" name="area">
                        <input id="f-area" class="input" name="area" value="{{ old('area') }}">
                    </x-field>
                </div>
                <x-field :label="__('addresses.postal_code')" name="postal_code">
                    <input id="f-postal_code" class="input" name="postal_code" value="{{ old('postal_code') }}">
                </x-field>
                <x-field :label="__('addresses.instructions')" name="instructions">
                    <textarea id="f-instructions" class="input" name="instructions" placeholder="{{ __('addresses.instructions_placeholder') }}">{{ old('instructions') }}</textarea>
                </x-field>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="is_default" value="1" @checked(old('is_default')) class="rounded border-line"> {{ __('addresses.make_default') }}
                </label>
                <x-button class="w-full">{{ __('addresses.save') }}</x-button>
            </form>
        </x-card>
    </div>
</div>
@endsection
