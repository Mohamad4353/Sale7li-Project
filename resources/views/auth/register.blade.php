@extends('layouts.app')
@section('content')
@php($title = __('auth.create_account'))

<div class="container-x flex min-h-[75vh] items-center justify-center py-16">
    <div class="w-full max-w-lg">
        <div class="text-center">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-2.5" aria-label="SALE7LI">
                <span class="brand-mark">7</span>
                <span class="text-xl font-extrabold tracking-[-.03em]">SALE<span class="text-brand">7</span>LI</span>
            </a>
            <h1 class="mt-6 t-page">{{ __('auth.register_title') }}</h1>
            <p class="mt-2 t-muted">{{ __('auth.register_sub') }}</p>
        </div>

        <x-card class="mt-8">
            <form method="POST" action="{{ route('register.store') }}" class="grid gap-5">
                @csrf

                <x-field :label="__('auth.full_name')" name="name" required>
                    <input id="f-name" name="name" value="{{ old('name') }}" class="input" autocomplete="name" required>
                </x-field>

                <x-field :label="__('auth.email')" name="email" required>
                    <input id="f-email" name="email" type="email" value="{{ old('email') }}" class="input" autocomplete="email" required>
                </x-field>

                <x-field :label="__('auth.phone')" name="phone" :hint="__('auth.phone_hint')">
                    <input id="f-phone" name="phone" value="{{ old('phone') }}" class="input" type="tel" inputmode="tel"
                           autocomplete="tel" pattern="^\+?[0-9][0-9 .()\-]{6,28}$" placeholder="+961 71 123 456">
                </x-field>

                <x-field name="role">
                    <fieldset class="min-w-0">
                        <legend class="field-label">
                            {{ __('auth.account_type') }}
                            <span class="field-req" aria-hidden="true">*</span><span class="sr-only">{{ __('messages.required') }}</span>
                        </legend>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="cursor-pointer rounded-xl border border-line p-4 transition has-[:checked]:border-ink has-[:checked]:bg-surface-2">
                                <input type="radio" name="role" value="customer" class="me-2" @checked(old('role', 'customer') === 'customer')> {{ __('auth.as_customer') }}
                            </label>
                            <label class="cursor-pointer rounded-xl border border-line p-4 transition has-[:checked]:border-ink has-[:checked]:bg-surface-2">
                                <input type="radio" name="role" value="worker" class="me-2" @checked(old('role') === 'worker')> {{ __('auth.as_worker') }}
                            </label>
                        </div>
                    </fieldset>
                </x-field>

                <div x-data="{ show: false }">
                    <x-field :label="__('auth.password_label')" name="password" required>
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

                <x-button class="w-full">{{ __('auth.create_account') }}</x-button>

                <p class="text-center t-muted">
                    {{ __('auth.have_account') }}
                    <a href="{{ route('login') }}" class="font-bold text-ink hover:underline">{{ __('auth.sign_in') }}</a>
                </p>
            </form>
        </x-card>
    </div>
</div>
@endsection
