@extends('layouts.app')
@section('content')
@php($title = __('favorites.title'))

<div class="container-app py-8 sm:py-10">
    <x-page-header :eyebrow="__('nav.dashboard')" :title="__('favorites.title')" :subtitle="__('favorites.sub')" />

    @if ($workers->isEmpty())
        <x-card padding="p-0" class="mt-8">
            <x-empty icon="star" :title="__('favorites.empty_title')" :message="__('favorites.empty_hint')">
                <x-slot:action>
                    <x-button :href="route('workers.index')">{{ __('favorites.browse') }}</x-button>
                </x-slot:action>
            </x-empty>
        </x-card>
    @else
        <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($workers as $worker)
                <div class="space-y-2">
                    <x-worker-card :worker="$worker" />
                    <x-favorite-toggle :worker="$worker" :favorited="true" />
                </div>
            @endforeach
        </div>
        <div class="mt-6">{{ $workers->links() }}</div>
    @endif
</div>
@endsection
