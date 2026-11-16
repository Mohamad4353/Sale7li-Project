{{--
    Branded confirmation dialog (P14-11). Mounted once from the app layout.
    Driven by resources/js/app.js: the `data-confirm` submit handler calls
    window.__sale7liConfirm(message, trigger) which this component registers.
    Falls back to native window.confirm() if this is somehow not on the page.

    Keyboard: Escape cancels; focus is trapped in the panel while open (Alpine
    focus plugin) and returned to the triggering control on close. Motion is a
    short opacity fade only — the global prefers-reduced-motion rule flattens it.
--}}
<div x-data="confirmDialog({ title: @js(__('messages.are_you_sure')), confirmLabel: @js(__('messages.confirm')) })"
     @keydown.escape.window="cancel()">

    {{-- Backdrop --}}
    <div x-show="open" x-cloak x-transition.opacity.duration.150ms
         @click="cancel()" aria-hidden="true"
         class="fixed inset-0 z-[80] bg-ink/50"></div>

    {{-- Panel --}}
    <div x-show="open" x-cloak x-transition.opacity.duration.150ms
         x-trap.noscroll="open"
         role="alertdialog" aria-modal="true"
         aria-labelledby="confirm-dialog-title" aria-describedby="confirm-dialog-message"
         class="fixed inset-0 z-[80] flex items-center justify-center p-4">
        <div @click.stop
             class="w-full max-w-sm rounded-2xl border border-line bg-surface p-6 shadow-modal">
            <h2 id="confirm-dialog-title" class="t-card" x-text="title">{{ __('messages.are_you_sure') }}</h2>
            <p id="confirm-dialog-message" class="mt-2 t-muted" x-text="message"></p>
            <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end sm:gap-3">
                <x-button type="button" variant="secondary" x-ref="cancelButton" @click="cancel()">
                    {{ __('messages.cancel') }}
                </x-button>
                <x-button type="button" variant="danger" @click="confirm()" x-text="confirmLabel">
                    {{ __('messages.confirm') }}
                </x-button>
            </div>
        </div>
    </div>
</div>
