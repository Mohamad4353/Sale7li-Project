{{--
    One record rendered as a stacked card, for viewport widths below `sm`.
    The real <table> is kept for `sm` and up — this is a presentation-only
    mirror of the same row data.

    Slots:
      heading   (required) — primary identity: title + a line of meta
      trailing  (optional) — the single most important figure/status, top-end
      rows      (optional) — <x-table-card-row> pairs for secondary fields
      actions   (optional) — existing row action forms/links
--}}
<div {{ $attributes->merge(['class' => 'space-y-3 p-4']) }}>
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0 space-y-0.5">{{ $heading }}</div>
        @isset($trailing)
            <div class="shrink-0 text-end">{{ $trailing }}</div>
        @endisset
    </div>

    @isset($rows)
        <dl class="space-y-1.5">{{ $rows }}</dl>
    @endisset

    @isset($actions)
        <div class="flex flex-wrap items-center gap-2 pt-1">{{ $actions }}</div>
    @endisset
</div>
