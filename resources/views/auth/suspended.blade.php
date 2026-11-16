@extends('layouts.app')
@section('content')
@php($title = __('auth.suspended_title'))

<div class="container-app flex min-h-[70vh] items-center justify-center py-16">
    <div class="w-full max-w-md text-center">
        <a href="{{ route('home') }}" class="inline-flex items-center gap-2.5" aria-label="SALE7LI">
            <span class="brand-mark">7</span>
            <span class="text-xl font-extrabold tracking-[-.03em]">SALE<span class="text-brand">7</span>LI</span>
        </a>

        <x-card class="mt-8">
            <span class="mx-auto grid h-12 w-12 place-items-center rounded-2xl bg-amber-100 text-amber-700">
                <x-icon name="shield" class="h-6 w-6" />
            </span>
            <h1 class="mt-4 t-card">{{ __('auth.suspended_title') }}</h1>
            <p class="mt-2 t-muted">{{ __('auth.suspended_body') }}</p>

            <p class="mt-4 text-sm font-semibold">
                {{ __('auth.suspended_contact', ['email' => config('sale7li.support_email')]) }}
            </p>

            <x-button :href="route('login')" variant="secondary" class="mt-6 w-full">
                {{ __('auth.suspended_back') }}
            </x-button>
        </x-card>
    </div>
</div>
@endsection
