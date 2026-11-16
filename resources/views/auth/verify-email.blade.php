@extends('layouts.app')
@section('content')
@php($title = __('auth.verify_email.heading'))

<div class="container-x flex min-h-[75vh] items-center justify-center py-16">
    <div class="w-full max-w-lg">
        <div class="text-center">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-2.5" aria-label="SALE7LI">
                <span class="brand-mark">7</span>
                <span class="text-xl font-extrabold tracking-[-.03em]">SALE<span class="text-brand">7</span>LI</span>
            </a>
            <h1 class="mt-6 t-page">{{ __('auth.verify_email.heading') }}</h1>
            <p class="mt-3 t-muted">{{ __('auth.verify_email.body', ['email' => auth()->user()->email]) }}</p>
        </div>

        <x-card class="mt-8">
            <div class="grid gap-5">
                <form method="POST" action="{{ route('verification.send') }}" data-no-busy>
                    @csrf
                    <x-button class="w-full">{{ __('auth.verify_email.resend') }}</x-button>
                </form>

                <p class="t-small text-center">{{ __('auth.verify_email.resend_hint') }}</p>

                <form method="POST" action="{{ route('logout') }}" class="text-center">
                    @csrf
                    <button type="submit" class="text-[13px] font-bold text-muted transition hover:text-ink">
                        {{ __('auth.verify_email.logout') }}
                    </button>
                </form>
            </div>
        </x-card>
    </div>
</div>
@endsection
