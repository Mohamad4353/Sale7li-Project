<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['name' => '', 'size' => 'md', 'src' => null]));

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

foreach (array_filter((['name' => '', 'size' => 'md', 'src' => null]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>
<?php
    $initials = collect(preg_split('/\s+/', trim($name)))
        ->filter()
        ->map(fn ($p) => mb_substr($p, 0, 1))
        ->take(2)
        ->implode('');
    $sizes = ['sm' => 'h-9 w-9 text-[11px]', 'md' => 'h-11 w-11 text-xs', 'lg' => 'h-16 w-16 text-lg'];
    $dim = $sizes[$size] ?? $sizes['md'];
    // Intrinsic px per size — lets the browser reserve space and skip work for
    // off-screen avatars. No image processing (runtime has no GD/Imagick).
    $px = ['sm' => 36, 'md' => 44, 'lg' => 64][$size] ?? 44;
?>
<?php if($src): ?>
    <img src="<?php echo e($src); ?>" alt="" width="<?php echo e($px); ?>" height="<?php echo e($px); ?>" loading="lazy" decoding="async"
         <?php echo e($attributes->merge(['class' => 'shrink-0 rounded-full bg-ink object-cover '.$dim])); ?>>
<?php else: ?>
    <span <?php echo e($attributes->merge(['class' => 'grid shrink-0 place-items-center rounded-full bg-ink font-extrabold text-white '.$dim])); ?>

          aria-hidden="true">
        <?php echo e($initials ?: '?'); ?>

    </span>
<?php endif; ?>
<?php /**PATH C:\Users\mofak\Desktop\SALE7LI-v3\resources\views/components/avatar.blade.php ENDPATH**/ ?>