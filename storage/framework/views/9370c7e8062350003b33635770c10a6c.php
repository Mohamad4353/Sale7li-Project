<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['label', 'value', 'hint' => null]));

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

foreach (array_filter((['label', 'value', 'hint' => null]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>
<div <?php echo e($attributes->merge(['class' => 'rounded-2xl border border-line bg-surface p-5 shadow-card'])); ?>>
    <div class="t-small"><?php echo e($label); ?></div>
    <div class="mt-2 t-num"><?php echo e($value); ?></div>
    <?php if($hint): ?><div class="mt-1 t-small"><?php echo e($hint); ?></div><?php endif; ?>
</div>
<?php /**PATH C:\Users\mofak\Desktop\SALE7LI-v3\resources\views/components/stat.blade.php ENDPATH**/ ?>