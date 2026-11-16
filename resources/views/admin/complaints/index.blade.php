@extends('layouts.app')
@section('content')
@php($title = __('complaints.admin.title'))
@php($statuses = ['open', 'investigating', 'resolved', 'closed'])

<div class="container-app py-8 sm:py-10">
    <x-page-header :eyebrow="__('admin.eyebrow')" :title="__('complaints.admin.title')" :subtitle="__('complaints.admin.sub')" />

    <form class="mt-6 grid gap-3 sm:grid-cols-[1fr_200px_auto]" data-no-busy>
        <label for="c-q" class="sr-only">{{ __('complaints.admin.search') }}</label>
        <input id="c-q" name="q" value="{{ $q }}" class="input" placeholder="{{ __('complaints.admin.search') }}">
        <label for="c-status" class="sr-only">{{ __('complaints.admin.all_statuses') }}</label>
        <select id="c-status" name="status" class="input">
            <option value="">{{ __('complaints.admin.all_statuses') }}</option>
            @foreach ($statuses as $s)
                <option value="{{ $s }}" @selected($status === $s)>{{ __('status.complaint.'.$s) }}</option>
            @endforeach
        </select>
        <x-button type="submit">{{ __('messages.filter') }}</x-button>
    </form>

    <x-card padding="p-0" class="mt-6 overflow-hidden">
        <div class="table-wrap">
            <table>
                <thead class="bg-paper text-xs uppercase tracking-wide text-muted">
                    <tr>
                        <th class="p-4 font-semibold">{{ __('complaints.admin.col_reference') }}</th>
                        <th class="p-4 font-semibold">{{ __('complaints.admin.col_reporter') }}</th>
                        <th class="p-4 font-semibold">{{ __('complaints.admin.col_category') }}</th>
                        <th class="p-4 font-semibold">{{ __('complaints.admin.col_status') }}</th>
                        <th class="p-4 font-semibold">{{ __('complaints.admin.col_filed') }}</th>
                        <th class="p-4 font-semibold"><span class="sr-only">{{ __('admin.open') }}</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($complaints as $complaint)
                        <tr>
                            <td class="p-4 font-bold">{{ $complaint->request?->reference ?? __('complaints.admin.no_request') }}</td>
                            <td class="p-4">{{ $complaint->reporter?->name ?? __('messages.none') }}</td>
                            <td class="p-4">{{ __('complaints.categories.'.$complaint->category) }}</td>
                            <td class="p-4"><x-status type="complaint" :value="$complaint->status" /></td>
                            <td class="p-4 t-small"><x-date :value="$complaint->created_at" /></td>
                            <td class="p-4 text-end">
                                <a href="{{ route('admin.complaints.show', $complaint) }}" class="text-sm font-bold text-ink hover:underline">{{ __('admin.open') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-empty icon="chat" :title="__('complaints.admin.none')" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <div class="mt-5">{{ $complaints->links() }}</div>
</div>
@endsection
