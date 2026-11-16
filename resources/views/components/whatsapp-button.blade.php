@props([
    'phone' => null,       // raw phone / WhatsApp number; non-digits are stripped
    'context' => null,     // e.g. a service name, woven into the pre-filled message
    'label' => null,       // button text; defaults to "Contact us on WhatsApp"
    'variant' => 'secondary',
])
@php
    // Digits only. A click-to-chat number needs a country code, so anything
    // shorter than 8 digits is treated as unusable and the button is hidden.
    $digits = preg_replace('/\D/', '', (string) $phone);
    $valid = strlen($digits) >= 8;

    $message = filled($context)
        ? __('whatsapp.prefill', ['service' => $context])
        : __('whatsapp.prefill_generic');

    // Host is fixed; the number is digits only; the message is URL-encoded.
    // There is no way for caller input to change the destination or inject markup.
    $href = 'https://wa.me/'.$digits.'?text='.rawurlencode($message);
@endphp
@if ($valid)
    {{--
        Colour override only: the base $variant still supplies layout, sizing,
        transitions and the disabled/focus-visible states from <x-button>; these
        classes reuse the app's EXISTING emerald "success" palette (already used
        by .verified-badge and <x-badge tone="success">) — no new design token —
        appended last so they win the matching utilities and make this action
        read as WhatsApp-branded rather than an ordinary secondary button.
    --}}
    <x-button :href="$href" :variant="$variant" target="_blank" rel="noopener noreferrer"
              {{ $attributes->merge(['class' => 'gap-2 border-emerald-300 bg-emerald-50 text-emerald-700 hover:border-emerald-400 hover:bg-emerald-100 focus-visible:ring-emerald-500/20']) }}>
        <x-icon name="whatsapp" class="h-4 w-4" />
        {{ $label ?? __('whatsapp.contact') }}
        <span class="sr-only">{{ __('whatsapp.opens_new_tab') }}</span>
    </x-button>
@endif
