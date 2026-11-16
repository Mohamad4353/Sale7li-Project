@extends('layouts.app')
@section('content')
@php($title = __('auth.reset_title'))

<div class="container-x flex min-h-[75vh] items-center justify-center py-16">
    <div class="w-full max-w-md">
        <div class="text-center">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-2.5" aria-label="SALE7LI">
                <span class="brand-mark">7</span>
                <span class="text-xl font-extrabold tracking-[-.03em]">SALE<span class="text-brand">7</span>LI</span>
            </a>
            <h1 class="mt-6 t-page">{{ __('auth.reset_title') }}</h1>
            <p class="mt-2 t-muted">{{ __('auth.reset_sub') }}</p>
        </div>

        <x-card class="mt-8">
            <form method="POST" action="{{ route('password.update') }}" class="grid gap-5">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <x-field :label="__('auth.email')" name="email" required>
                    <input id="f-email" name="email" type="email" value="{{ old('email', $email) }}" class="input" autocomplete="email" required>
                </x-field>

                <div x-data="{ show: false }">
                    <x-field :label="__('auth.new_password')" name="password" required>
                        <div class="relative">
                            <input id="f-password" name="password" :type="show ? 'text' : 'password'" class="input pe-16" autocomplete="new-password" required>
                            <button type="button" @click="show = !show" class="absolute inset-y-0 end-2 px-3 text-xs font-bold text-muted transition hover:text-ink"
                                    x-text="show ? '{{ __('auth.hide') }}' : '{{ __('auth.show') }}'"></button>
                        </div>
                    </x-field>
                    <x-field :label="__('auth.password_confirm')" name="password_confirmation" required class="mt-5">
                        <div class="relative">
                            <input id="f-password_confirmation" name="password_confirmation" :type="show ? 'text' : 'password'" class="input pe-16" autocomplete="new-password" required>
                        </div>
                    </x-field>
                </div>

                <x-button class="w-full">{{ __('auth.reset_button') }}</x-button>

                <p class="text-center t-muted">
                    <a href="{{ route('login') }}" class="font-bold text-ink hover:underline">{{ __('auth.back_to_login') }}</a>
                </p>
            </form>
        </x-card>
    </div>
</div>
@endsection
