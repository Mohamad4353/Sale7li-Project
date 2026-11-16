@props([
    'icon' => 'inbox',
    'title',
    'message' => null,
])
<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center px-6 py-12 text-center']) }}>
    <span class="grid h-12 w-12 place-items-center rounded-2xl bg-surface-2 text-muted">
        <x-icon :name="$icon" class="h-6 w-6" />
    </span>
    <p class="mt-4 t-card">{{ $title }}</p>
    @if ($message)<p class="mt-1.5 t-muted max-w-sm">{{ $message }}</p>@endif
    @if (isset($action))<div class="mt-5">{{ $action }}</div>@endif
</div>
