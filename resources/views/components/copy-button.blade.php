@props([
    'value',            // string to place on the clipboard
    'label' => null,    // accessible label; defaults to "Copy"
])
{{-- Progressive enhancement: the button only appears when the Clipboard API is
     available (secure context). Nothing here is required to complete a payment —
     the value is always shown as text alongside it. --}}
<button type="button"
        x-data="{
            ok: !!(navigator.clipboard && navigator.clipboard.writeText),
            copied: false,
            copy() {
                navigator.clipboard.writeText(@js((string) $value))
                    .then(() => { this.copied = true; setTimeout(() => { this.copied = false }, 1500) })
                    .catch(() => {});
            }
        }"
        x-show="ok" x-cloak
        x-on:click="copy()"
        :aria-label="copied ? @js(__('messages.copied')) : @js($label ?? __('messages.copy'))"
        {{ $attributes->merge(['class' => 'inline-flex shrink-0 items-center gap-1 rounded-lg border border-line bg-surface px-2 py-1 text-[11px] font-bold text-muted transition hover:text-ink']) }}>
    <x-icon name="clipboard" class="h-3.5 w-3.5" />
    <span x-show="!copied">{{ __('messages.copy') }}</span>
    <span x-show="copied" x-cloak class="text-emerald-600">{{ __('messages.copied') }}</span>
</button>
