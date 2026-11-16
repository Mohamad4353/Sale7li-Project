@extends('layouts.app')
@section('content')
@php($title = __('auth.sign_in'))

<div class="container-x flex min-h-[75vh] items-center justify-center py-16">
    <div class="w-full max-w-md">
        <div class="text-center">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-2.5" aria-label="SALE7LI">
                <span class="brand-mark">7</span>
                <span class="text-xl font-extrabold tracking-[-.03em]">SALE<span class="text-brand">7</span>LI</span>
            </a>
            <h1 class="mt-6 t-page">{{ __('auth.login_title') }}</h1>
            <p class="mt-2 t-muted">{{ __('auth.login_sub') }}</p>
        </div>

        <x-card class="mt-8">
            <form method="POST" action="{{ route('login.store') }}" class="grid gap-5">
                @csrf

                <x-field :label="__('auth.email')" name="email">
                    <input id="f-email" name="email" type="email" value="{{ old('email') }}" class="input" autocomplete="email" required autofocus>
                </x-field>

                <div x-data="{ show: false }">
                    <x-field :label="__('auth.password_label')" name="password">
                        <div class="relative">
                            <input id="f-password" name="password" :type="show ? 'text' : 'password'" class="input pe-16" autocomplete="current-password" required>
                            <button type="button" @click="show = !show" class="absolute inset-y-0 end-2 px-3 text-xs font-bold text-muted transition hover:text-ink"
                                    x-text="show ? @js(__('auth.hide')) : @js(__('auth.show'))"></button>
                        </div>
                    </x-field>
                </div>

                <div class="flex items-center justify-between gap-3">
                    <label class="flex items-center gap-2 t-muted">
                        <input type="checkbox" name="remember" value="1" class="rounded border-line"> {{ __('auth.remember_me') }}
                    </label>
                    <a href="{{ route('password.request') }}" class="text-[13px] font-bold text-ink hover:underline">{{ __('auth.forgot_link') }}</a>
                </div>

                <x-button class="w-full">{{ __('auth.sign_in') }}</x-button>

                <p class="text-center t-muted">
                    {{ __('auth.no_account') }}
                    <a href="{{ route('register') }}" class="font-bold text-ink hover:underline">{{ __('auth.create_account') }}</a>
                </p>
            </form>
        </x-card>
    </div>
</div>
@endsection
