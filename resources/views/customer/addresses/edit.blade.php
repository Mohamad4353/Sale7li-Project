@extends('layouts.app')
@section('content')
@php($title = __('addresses.edit_title'))

<div class="container-app max-w-xl py-8 sm:py-10">
    <x-page-header
        :back="route('customer.addresses.index')"
        :backLabel="__('addresses.title')"
        :eyebrow="__('requests.eyebrow')"
        :title="__('addresses.edit_title')" />

    <x-card class="mt-8">
        <form method="POST" action="{{ route('customer.addresses.update', $address) }}" class="space-y-4">
            @csrf
            @method('PUT')

            <x-field :label="__('addresses.label')" name="title" required>
                <input id="f-title" class="input" name="title" value="{{ old('title', $address->title) }}" placeholder="{{ __('addresses.label_placeholder') }}" required>
            </x-field>
            <x-field :label="__('addresses.street')" name="street" required>
                <input id="f-street" class="input" name="street" value="{{ old('street', $address->street) }}" required>
            </x-field>
            <div class="grid grid-cols-2 gap-3">
                <x-field :label="__('addresses.city')" name="city" required>
                    <input id="f-city" class="input" name="city" value="{{ old('city', $address->city) }}" required>
                </x-field>
                <x-field :label="__('addresses.area')" name="area">
                    <input id="f-area" class="input" name="area" value="{{ old('area', $address->area) }}">
                </x-field>
            </div>
            <x-field :label="__('addresses.postal_code')" name="postal_code">
                <input id="f-postal_code" class="input" name="postal_code" value="{{ old('postal_code', $address->postal_code) }}">
            </x-field>
            <x-field :label="__('addresses.instructions')" name="instructions">
                <textarea id="f-instructions" class="input" name="instructions" placeholder="{{ __('addresses.instructions_placeholder') }}">{{ old('instructions', $address->instructions) }}</textarea>
            </x-field>
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="is_default" value="1" @checked(old('is_default', $address->is_default)) class="rounded border-line"> {{ __('addresses.make_default') }}
            </label>

            <div class="flex gap-2">
                <x-button class="flex-1">{{ __('addresses.save') }}</x-button>
                <x-button :href="route('customer.addresses.index')" variant="secondary">{{ __('messages.cancel') }}</x-button>
            </div>
        </form>
    </x-card>
</div>
@endsection
