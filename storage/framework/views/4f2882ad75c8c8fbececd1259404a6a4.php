<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'title',
    'eyebrow' => null,
    'subtitle' => null,
    'back' => null,        // url for a "back" link
    'backLabel' => null,
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
    'title',
    'eyebrow' => null,
    'subtitle' => null,
    'back' => null,        // url for a "back" link
    'backLabel' => null,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>
<div <?php echo e($attributes->merge(['class' => 'flex flex-col gap-4 md:flex-row md:items-end md:justify-between md:gap-6'])); ?>>
    <div class="min-w-0">
        <?php if($back): ?>
            <a href="<?php echo e($back); ?>" class="mb-2 -ms-1 inline-flex items-center gap-1.5 px-1 py-1 text-[13px] font-bold text-muted transition hover:text-ink">
                <?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'arrow-left','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'arrow-left','class' => 'h-4 w-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?><?php echo e($backLabel ?? __('messages.back')); ?>

            </a>
        <?php endif; ?>
        <?php if($eyebrow): ?><p class="t-eyebrow"><?php echo e($eyebrow); ?></p><?php endif; ?>
        <h1 class="t-page <?php echo e($eyebrow ? 'mt-1' : ''); ?>"><?php echo e($title); ?></h1>
        <?php if($subtitle): ?><p class="mt-2 t-muted max-w-2xl"><?php echo e($subtitle); ?></p><?php endif; ?>
    </div>
    <?php if(isset($actions)): ?>
        <div class="flex flex-wrap items-center gap-2"><?php echo e($actions); ?></div>
    <?php endif; ?>
</div>
<?php /**PATH C:\Users\mofak\Desktop\SALE7LI-v3\resources\views/components/page-header.blade.php ENDPATH**/ ?>