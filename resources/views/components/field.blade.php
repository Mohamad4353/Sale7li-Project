@props([
    'label' => null,
    'name' => null,
    'required' => false,
    'hint' => null,
    'error' => null,   // override; otherwise pulled from the error bag by name
])
@php
    $errorMessage = $error ?? ($name ? $errors->first($name) : null);
    $fieldId = $name ? 'f-'.str_replace(['.', '[', ']'], ['-', '', ''], $name) : null;
    $errorId = $fieldId ? $fieldId.'-error' : 'field-error';
    $hintId = ($hint && $fieldId) ? $fieldId.'-hint' : null;

    // Server-side ARIA wiring: point the first control in the slot at its
    // describing text — the error when the field failed, otherwise the hint.
    // (The current design replaces the hint with the error, so only one is
    // ever present at a time.) The slot is our own controlled markup, so a
    // single targeted rewrite is safe and needs no JS.
    $describedBy = $errorMessage ? $errorId : ($hintId ?: null);

    $control = (string) $slot;
    if ($describedBy) {
        $control = preg_replace(
            '/<(input|textarea|select)\b/',
            '<$1'.($errorMessage ? ' aria-invalid="true"' : '').' aria-describedby="'.$describedBy.'"',
            $control,
            1,
        );
    }
@endphp
<div {{ $attributes->only('class') }}>
    @if ($label)
        <label @if ($fieldId) for="{{ $fieldId }}" @endif class="field-label">
            {{ $label }}
            @if ($required)<span class="field-req" aria-hidden="true">*</span><span class="sr-only">{{ __('messages.required') }}</span>@endif
        </label>
    @endif

    {!! $control !!}

    @if ($hint && ! $errorMessage)<p @if ($hintId) id="{{ $hintId }}" @endif class="field-hint">{{ $hint }}</p>@endif
    @if ($errorMessage)
        <p id="{{ $errorId }}" class="field-error" role="alert">
            <x-icon name="close" class="mt-px h-3.5 w-3.5 shrink-0" />
            <span>{{ $errorMessage }}</span>
        </p>
    @endif
</div>
