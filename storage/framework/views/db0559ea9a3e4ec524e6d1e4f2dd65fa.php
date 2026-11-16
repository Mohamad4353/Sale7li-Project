<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'route',            // route name the pills link to
    'current' => null,  // active filter key (active|completed|cancelled) or null for "All"
    'ns' => 'requests', // translation namespace for the labels (requests | workers)
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
    'route',            // route name the pills link to
    'current' => null,  // active filter key (active|completed|cancelled) or null for "All"
    'ns' => 'requests', // translation namespace for the labels (requests | workers)
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>
<?php
    $pills = [
        null => __($ns.'.filter_all'),
        'active' => __($ns.'.filter_active'),
        'completed' => __($ns.'.filter_completed'),
        'cancelled' => __($ns.'.filter_cancelled'),
    ];
?>
<nav <?php echo e($attributes->merge(['class' => 'flex flex-wrap gap-2'])); ?> aria-label="<?php echo e(__($ns.'.filter_label')); ?>">
    <?php $__currentLoopData = $pills; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <a href="<?php echo e(route($route, $key ? ['status' => $key] : [])); ?>"
           class="<?php echo \Illuminate\Support\Arr::toCssClasses([
               'inline-flex items-center rounded-xl border px-3 py-2 text-[13px] font-bold transition',
               'border-ink bg-ink text-white' => $current === $key,
               'border-line bg-surface text-muted hover:text-ink' => $current !== $key,
           ]); ?>"
           <?php if($current === $key): ?> aria-current="page" <?php endif; ?>>
            <?php echo e($label); ?>

        </a>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</nav>
<?php /**PATH C:\Users\mofak\Desktop\SALE7LI-v3\resources\views/components/request-status-filter.blade.php ENDPATH**/ ?>