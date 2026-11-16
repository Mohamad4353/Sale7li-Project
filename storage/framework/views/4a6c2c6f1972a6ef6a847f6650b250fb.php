<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'label' => null,
    'name' => null,
    'required' => false,
    'hint' => null,
    'error' => null,   // override; otherwise pulled from the error bag by name
]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter(([
    'label' => null,
    'name' => null,
    'required' => false,
    'hint' => null,
    'error' => null,   // override; otherwise pulled from the error bag by name
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>
<?php
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
?>
<div <?php echo e($attributes->only('class')); ?>>
    <?php if($label): ?>
        <label <?php if($fieldId): ?> for="<?php echo e($fieldId); ?>" <?php endif; ?> class="field-label">
            <?php echo e($label); ?>

            <?php if($required): ?><span class="field-req" aria-hidden="true">*</span><span class="sr-only"><?php echo e(__('messages.required')); ?></span><?php endif; ?>
        </label>
    <?php endif; ?>

    <?php echo $control; ?>


    <?php if($hint && ! $errorMessage): ?><p <?php if($hintId): ?> id="<?php echo e($hintId); ?>" <?php endif; ?> class="field-hint"><?php echo e($hint); ?></p><?php endif; ?>
    <?php if($errorMessage): ?>
        <p id="<?php echo e($errorId); ?>" class="field-error" role="alert">
            <?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'close','class' => 'mt-px h-3.5 w-3.5 shrink-0']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'close','class' => 'mt-px h-3.5 w-3.5 shrink-0']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
            <span><?php echo e($errorMessage); ?></span>
        </p>
    <?php endif; ?>
</div>
<?php /**PATH C:\Users\mofak\Desktop\SALE7LI-v3\resources\views/components/field.blade.php ENDPATH**/ ?>