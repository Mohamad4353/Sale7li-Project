@extends('layouts.app')
@section('content')
@php($title = __('workers.availability_title'))

<div class="container-app max-w-2xl py-8 sm:py-10">
    <x-page-header :eyebrow="__('workers.profile_eyebrow')" :title="__('workers.availability_title')" :subtitle="__('workers.availability_sub')" />

    @error('days')
        <x-alert tone="danger" class="mt-6">{{ $message }}</x-alert>
    @enderror

    <x-card class="mt-6">
        <form method="POST" action="{{ route('worker.availability.update') }}" class="grid gap-5">
            @csrf
            @method('PUT')

            <p class="t-muted">{{ __('workers.availability_help') }}</p>

            <x-worker-availability :availability="$availability" editable />

            <p class="t-small">{{ __('workers.availability_tz_note') }}</p>

            <x-button class="w-full">{{ __('workers.save_availability') }}</x-button>
        </form>
    </x-card>
</div>
@endsection
