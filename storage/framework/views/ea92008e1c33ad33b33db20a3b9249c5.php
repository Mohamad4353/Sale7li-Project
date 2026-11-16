<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'variant' => 'primary',   // primary | accent | secondary | ghost | danger | link
    'size' => 'md',           // sm | md | lg
    'href' => null,
    'type' => 'submit',
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
    'variant' => 'primary',   // primary | accent | secondary | ghost | danger | link
    'size' => 'md',           // sm | md | lg
    'href' => null,
    'type' => 'submit',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>
<?php
    $base = 'inline-flex items-center justify-center gap-2 rounded-xl font-bold transition duration-200 focus:outline-none focus-visible:ring-4 disabled:pointer-events-none disabled:opacity-50';
    $variants = [
        'primary' => 'bg-ink text-white hover:bg-ink-soft focus-visible:ring-ink/15',
        'accent' => 'bg-brand text-ink font-extrabold hover:brightness-95 focus-visible:ring-brand/30',
        'secondary' => 'border border-line bg-surface text-ink hover:border-line-strong hover:bg-surface-2 focus-visible:ring-ink/15',
        'ghost' => 'border border-white/15 bg-white/5 text-white hover:bg-white/10 focus-visible:ring-white/20',
        'danger' => 'border border-rose-200 bg-surface text-rose-600 hover:bg-rose-50 focus-visible:ring-rose-500/20',
        'link' => 'font-bold text-ink underline decoration-2 decoration-brand underline-offset-4 hover:decoration-ink focus-visible:ring-ink/15',
    ];
    $sizes = [
        'sm' => 'min-h-[2.25rem] px-3 py-2 text-xs',
        'md' => 'min-h-[2.75rem] px-5 py-3 text-sm',
        'lg' => 'min-h-12 px-6 py-3.5 text-sm',
    ];
    $classes = $base.' '.($variants[$variant] ?? $variants['primary']).' '.($sizes[$size] ?? $sizes['md']);
?>
<?php if($href): ?>
    <a href="<?php echo e($href); ?>" <?php echo e($attributes->merge(['class' => $classes])); ?>><?php echo e($slot); ?></a>
<?php else: ?>
    <button type="<?php echo e($type); ?>" <?php echo e($attributes->merge(['class' => $classes])); ?>><?php echo e($slot); ?></button>
<?php endif; ?>
<?php /**PATH C:\Users\mofak\Desktop\SALE7LI-v3\resources\views/components/button.blade.php ENDPATH**/ ?>