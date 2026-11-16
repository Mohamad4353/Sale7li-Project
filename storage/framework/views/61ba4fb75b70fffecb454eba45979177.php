<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['tone' => 'neutral', 'dot' => false]));

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

foreach (array_filter((['tone' => 'neutral', 'dot' => false]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>
<?php
    $tones = [
        'neutral' => 'bg-slate-100 text-slate-600',
        'success' => 'bg-emerald-100 text-emerald-700',
        'warning' => 'bg-amber-100 text-amber-800',
        'danger' => 'bg-rose-100 text-rose-700',
        'info' => 'bg-sky-100 text-sky-700',
        'brand' => 'bg-brand/20 text-ink',
    ];
    $dots = [
        'neutral' => 'bg-slate-400', 'success' => 'bg-emerald-500', 'warning' => 'bg-amber-500',
        'danger' => 'bg-rose-500', 'info' => 'bg-sky-500', 'brand' => 'bg-brand',
    ];
    $t = $tones[$tone] ?? $tones['neutral'];
?>
<span <?php echo e($attributes->merge(['class' => "inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-extrabold $t"])); ?>>
    <?php if($dot): ?><span class="status-dot <?php echo e($dots[$tone] ?? $dots['neutral']); ?>"></span><?php endif; ?>
    <?php echo e($slot); ?>

</span>
<?php /**PATH C:\Users\mofak\Desktop\SALE7LI-v3\resources\views/components/badge.blade.php ENDPATH**/ ?>