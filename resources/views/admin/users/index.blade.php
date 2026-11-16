@extends('layouts.app')
@section('content')
@php($title = __('admin.users_title'))

<div class="container-app py-8 sm:py-10">
    <x-page-header :eyebrow="__('admin.eyebrow')" :title="__('admin.users_title')" />

    <form class="mt-6 grid gap-3 sm:grid-cols-[1fr_180px_auto]" data-no-busy>
        <label for="u-q" class="sr-only">{{ __('admin.search_users') }}</label>
        <input id="u-q" name="q" value="{{ $q }}" class="input" placeholder="{{ __('admin.search_users') }}">
        <label for="u-role" class="sr-only">{{ __('admin.all_roles') }}</label>
        <select id="u-role" name="role" class="input">
            <option value="">{{ __('admin.all_roles') }}</option>
            <option value="customer" @selected($role === 'customer')>{{ __('admin.role_customer') }}</option>
            <option value="worker" @selected($role === 'worker')>{{ __('admin.role_worker') }}</option>
            <option value="admin" @selected($role === 'admin')>{{ __('admin.role_admin') }}</option>
        </select>
        <x-button type="submit">{{ __('messages.filter') }}</x-button>
    </form>

    <x-card padding="p-0" class="mt-6 overflow-hidden">
        <div class="table-wrap hidden sm:block">
            <table>
                <thead class="bg-paper text-xs uppercase tracking-wide text-muted">
                    <tr>
                        <th class="p-4 font-semibold">{{ __('admin.col_user') }}</th>
                        <th class="p-4 font-semibold">{{ __('admin.col_role') }}</th>
                        <th class="p-4 font-semibold">{{ __('admin.col_status') }}</th>
                        <th class="p-4 font-semibold">{{ __('admin.col_action') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($users as $u)
                        <tr>
                            <td class="p-4">
                                <div class="font-bold">{{ $u->name }}</div>
                                <div class="t-small">{{ $u->email }}</div>
                            </td>
                            <td class="p-4">{{ __('admin.role_'.$u->role) }}</td>
                            <td class="p-4">
                                <x-badge :tone="$u->status === 'active' ? 'success' : 'danger'">
                                    {{ $u->status === 'active' ? __('admin.status_active') : __('admin.status_suspended') }}
                                </x-badge>
                            </td>
                            <td class="p-4">
                                <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                    <form method="POST" action="{{ route('admin.users.toggle', $u) }}" data-confirm="{{ __('messages.are_you_sure') }}">
                                        @csrf
                                        <button class="table-action {{ $u->status === 'active' ? 'text-rose-600' : 'text-emerald-600' }}">
                                            {{ $u->status === 'active' ? __('admin.suspend') : __('admin.reactivate') }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.users.passwordResetLink', $u) }}">
                                        @csrf
                                        <button class="table-action text-muted hover:text-ink">{{ __('auth.send_reset_link') }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4"><x-empty icon="users" :title="__('admin.no_users')" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Mobile: one card per user (below sm) --}}
        <div class="divide-y divide-line sm:hidden" data-mobile-rows>
            @forelse ($users as $u)
                <x-table-card>
                    <x-slot:heading>
                        <div class="font-bold">{{ $u->name }}</div>
                        <div class="t-small break-all">{{ $u->email }}</div>
                    </x-slot:heading>
                    <x-slot:trailing>
                        <x-badge :tone="$u->status === 'active' ? 'success' : 'danger'">
                            {{ $u->status === 'active' ? __('admin.status_active') : __('admin.status_suspended') }}
                        </x-badge>
                    </x-slot:trailing>
                    <x-slot:rows>
                        <x-table-card-row :label="__('admin.col_role')">{{ __('admin.role_'.$u->role) }}</x-table-card-row>
                    </x-slot:rows>
                    <x-slot:actions>
                        <form method="POST" action="{{ route('admin.users.toggle', $u) }}" data-confirm="{{ __('messages.are_you_sure') }}">
                            @csrf
                            <button class="table-action {{ $u->status === 'active' ? 'text-rose-600' : 'text-emerald-600' }}">
                                {{ $u->status === 'active' ? __('admin.suspend') : __('admin.reactivate') }}
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.users.passwordResetLink', $u) }}">
                            @csrf
                            <button class="table-action text-muted hover:text-ink">{{ __('auth.send_reset_link') }}</button>
                        </form>
                    </x-slot:actions>
                </x-table-card>
            @empty
                <x-empty icon="users" :title="__('admin.no_users')" />
            @endforelse
        </div>
    </x-card>

    <div class="mt-5">{{ $users->links() }}</div>
</div>
@endsection
