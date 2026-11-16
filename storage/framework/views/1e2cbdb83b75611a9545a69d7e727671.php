<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'profile' => null,
    'busy' => null,        // pass a bool when the caller knows job state
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
    'profile' => null,
    'busy' => null,        // pass a bool when the caller knows job state
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>
<?php
    $status = $profile?->availabilityStatus($busy) ?? 'unavailable';
    $text = [
        'available_today' => 'text-emerald-700',
        'available' => 'text-emerald-700',
        'busy' => 'text-amber-700',
        'unavailable' => 'text-faint',
    ][$status];
    $dot = [
        'available_today' => 'bg-emerald-500',
        'available' => 'bg-emerald-400',
        'busy' => 'bg-amber-500',
        'unavailable' => 'bg-line-strong',
    ][$status];
?>
<span <?php echo e($attributes->merge(['class' => "inline-flex items-center gap-1.5 text-xs font-bold $text"])); ?>>
    <span class="h-1.5 w-1.5 shrink-0 rounded-full <?php echo e($dot); ?>" aria-hidden="true"></span>
    <?php echo e(__('workers.availability_status.'.$status)); ?>

</span>
<?php /**PATH C:\Users\mofak\Desktop\SALE7LI-v3\resources\views/components/availability-badge.blade.php ENDPATH**/ ?>