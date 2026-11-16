<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['amount' => null, 'muted' => false]));

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

foreach (array_filter((['amount' => null, 'muted' => false]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php ($formatted = \App\Payments\Money::format($amount)); ?>
<?php if($formatted === null): ?>
    <span <?php echo e($attributes->merge(['class' => 'text-faint'])); ?>><?php echo e(__('payments.not_available')); ?></span>
<?php else: ?>
    <span <?php echo e($attributes->merge(['class' => $muted ? 't-muted' : ''])); ?>><?php echo e($formatted); ?></span>
<?php endif; ?>
<?php /**PATH C:\Users\mofak\Desktop\SALE7LI-v3\resources\views/components/money.blade.php ENDPATH**/ ?>