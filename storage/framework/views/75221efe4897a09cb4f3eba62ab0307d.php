<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'value' => null,
    'time' => false,   // include the time of day
    'diff' => false,    // append a relative "(3 days ago)"
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
    'value' => null,
    'time' => false,   // include the time of day
    'diff' => false,    // append a relative "(3 days ago)"
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $c = $value instanceof \Carbon\CarbonInterface
        ? $value
        : (filled($value) ? \Illuminate\Support\Carbon::parse($value) : null);
    $fmt = $time ? 'M j, Y · H:i' : 'M j, Y';
?>
<?php if($c): ?>
    <time datetime="<?php echo e($c->toIso8601String()); ?>" <?php echo e($attributes); ?>><?php echo e($c->translatedFormat($fmt)); ?><?php if($diff): ?> <span class="text-faint">(<?php echo e($c->diffForHumans()); ?>)</span><?php endif; ?></time>
<?php else: ?>
    <span <?php echo e($attributes); ?>><?php echo e(__('messages.none')); ?></span>
<?php endif; ?>
<?php /**PATH C:\Users\mofak\Desktop\SALE7LI-v3\resources\views/components/date.blade.php ENDPATH**/ ?>