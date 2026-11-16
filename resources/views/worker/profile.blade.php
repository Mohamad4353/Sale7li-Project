@extends('layouts.app')
@section('content')
@php($title = __('workers.profile_title'))

<div class="container-app max-w-3xl py-8 sm:py-10">
    <x-page-header :eyebrow="__('workers.profile_eyebrow')" :title="__('workers.profile_title')" :subtitle="__('workers.profile_sub')">
        <x-slot:actions>
            <x-status type="verification" :value="$profile->verification_status" />
        </x-slot:actions>
    </x-page-header>

    <x-card class="mt-8">
        <form method="POST" action="{{ route('worker.profile.update') }}" enctype="multipart/form-data" class="grid gap-6">
            @csrf
            @method('PUT')

            <x-field :label="__('workers.photo')" name="photo" :hint="__('workers.photo_hint')">
                <div class="flex items-center gap-4" x-data="{ preview: null }">
                    <template x-if="preview">
                        <img :src="preview" alt="" width="64" height="64" decoding="async" class="h-16 w-16 shrink-0 rounded-full object-cover">
                    </template>
                    @if ($profile->photo)
                        <img x-show="!preview" src="{{ route('workers.photo', auth()->user()) }}" alt=""
                             width="64" height="64" loading="lazy" decoding="async"
                             class="h-16 w-16 shrink-0 rounded-full bg-ink object-cover">
                    @else
                        <span x-show="!preview" class="grid h-16 w-16 shrink-0 place-items-center rounded-full bg-ink text-lg font-extrabold text-white" aria-hidden="true">
                            {{ mb_substr(auth()->user()->name, 0, 1) }}
                        </span>
                    @endif
                    <input id="f-photo" type="file" name="photo" accept="image/jpeg,image/png,image/webp"
                           class="input"
                           x-on:change="preview = $event.target.files.length ? URL.createObjectURL($event.target.files[0]) : null">
                </div>
            </x-field>

            <div class="grid gap-5 sm:grid-cols-2">
                <x-field :label="__('workers.name')" name="name" required>
                    <input id="f-name" class="input" name="name" value="{{ old('name', auth()->user()->name) }}" autocomplete="name" required>
                </x-field>
                <x-field :label="__('workers.phone')" name="phone" :hint="__('workers.phone_hint')">
                    <input id="f-phone" class="input" name="phone" value="{{ old('phone', auth()->user()->phone) }}" type="tel" inputmode="tel"
                           autocomplete="tel" pattern="^\+?[0-9][0-9 .()\-]{6,28}$" placeholder="+961 71 123 456">
                </x-field>
                <x-field :label="__('workers.experience_years')" name="experience_years" required>
                    <input id="f-experience_years" class="input" name="experience_years" type="number" min="0" max="60"
                           value="{{ old('experience_years', $profile->experience_years) }}" required>
                </x-field>
                <x-field :label="__('workers.service_radius')" name="service_radius_km" required>
                    <input id="f-service_radius_km" class="input" name="service_radius_km" type="number" min="1" max="200" step="0.1"
                           value="{{ old('service_radius_km', $profile->service_radius_km) }}" required>
                </x-field>
            </div>

            <x-field :label="__('workers.bio_en')" name="bio">
                <textarea id="f-bio" class="input min-h-28" name="bio" maxlength="3000">{{ old('bio', $profile->bio) }}</textarea>
            </x-field>
            <x-field :label="__('workers.bio_ar')" name="bio_ar">
                <textarea id="f-bio_ar" dir="rtl" class="input min-h-28" name="bio_ar" maxlength="3000">{{ old('bio_ar', $profile->bio_ar) }}</textarea>
            </x-field>

            <div class="rounded-2xl border border-line bg-paper p-5">
                <div class="t-label">{{ __('workers.verification_status') }}</div>
                <p class="mt-2 t-small">{{ __('workers.verification_'.$profile->verification_status.'_hint') }}</p>
            </div>

            <x-button class="w-full">{{ __('workers.save_profile') }}</x-button>
        </form>
    </x-card>
</div>
@endsection
