@extends('layouts.app')
@section('content')
@php($title = __('auth.forgot_title'))

<div class="container-x flex min-h-[75vh] items-center justify-center py-16">
    <div class="w-full max-w-md">
        <div class="text-center">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-2.5" aria-label="SALE7LI">
                <span class="brand-mark">7</span>
                <span class="text-xl font-extrabold tracking-[-.03em]">SALE<span class="text-brand">7</span>LI</span>
            </a>
            <h1 class="mt-6 t-page">{{ __('auth.forgot_title') }}</h1>
            <p class="mt-2 t-muted">{{ __('auth.forgot_sub') }}</p>
        </div>

        <x-card class="mt-8">
            <form method="POST" action="{{ route('password.email') }}" class="grid gap-5">
                @csrf

                <x-field :label="__('auth.email')" name="email" required>
                    <input id="f-email" name="email" type="email" value="{{ old('email') }}" class="input" autocomplete="email" required autofocus>
                </x-field>

                <x-button class="w-full">{{ __('auth.send_reset_link') }}</x-button>

                <p class="text-center t-muted">
                    <a href="{{ route('login') }}" class="font-bold text-ink hover:underline">{{ __('auth.back_to_login') }}</a>
                </p>
            </form>
        </x-card>
    </div>
</div>
@endsection
