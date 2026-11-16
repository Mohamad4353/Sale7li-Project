<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'root',          // registered component name (see resources/js/react/index.jsx)
    'props' => [],   // JSON-safe, PUBLIC data only — never secrets/prices/tokens
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
    'root',          // registered component name (see resources/js/react/index.jsx)
    'props' => [],   // JSON-safe, PUBLIC data only — never secrets/prices/tokens
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>


<?php if (! $__env->hasRenderedOnce('9b0a065f-211a-477f-95cc-802d335cbbdd')): $__env->markAsRenderedOnce('9b0a065f-211a-477f-95cc-802d335cbbdd'); ?>
    <?php $__env->startPush('scripts'); ?>
        <?php echo app('Illuminate\Foundation\Vite')('resources/js/react/index.jsx'); ?>
    <?php $__env->stopPush(); ?>
<?php endif; ?>

<div data-react-root="<?php echo e($root); ?>"
     data-props="<?php echo e(json_encode($props, JSON_THROW_ON_ERROR)); ?>"
     <?php echo e($attributes); ?>>
    
    <?php echo e($slot); ?>

</div>
<?php /**PATH C:\Users\mofak\Desktop\SALE7LI-v3\resources\views/components/react.blade.php ENDPATH**/ ?>