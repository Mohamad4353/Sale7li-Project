<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'worker',
    'favorited' => null,   // pass a bool to avoid a per-render lookup
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
    'worker',
    'favorited' => null,   // pass a bool to avoid a per-render lookup
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>
<?php
    $isCustomer = auth()->check() && auth()->user()->role === 'customer';
    if ($isCustomer && $favorited === null) {
        $favorited = auth()->user()->favoriteWorkers()->whereKey($worker->id)->exists();
    }
?>
<?php if($isCustomer): ?>
    <form method="POST" action="<?php echo e(route('customer.favorites.toggle', $worker)); ?>" data-no-busy
          x-data="favoriteToggle(<?php echo e($favorited ? 'true' : 'false'); ?>)"
          @submit.prevent="toggle($event.target)"
          <?php echo e($attributes); ?>>
        <?php echo csrf_field(); ?>
        <button type="submit"
                class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-line bg-surface px-3 py-2 text-[13px] font-bold transition hover:bg-paper"
                :class="on ? 'text-rose-600' : 'text-muted'"
                :aria-pressed="on.toString()"
                :aria-label="on ? <?php echo \Illuminate\Support\Js::from(__('favorites.saved'))->toHtml() ?> : <?php echo \Illuminate\Support\Js::from(__('favorites.save'))->toHtml() ?>">
            <?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'star','class' => 'h-4 w-4',':class' => 'on ? \'fill-current\' : \'\'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'star','class' => 'h-4 w-4',':class' => 'on ? \'fill-current\' : \'\'']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
            <span x-show="!on"><?php echo e(__('favorites.save')); ?></span>
            <span x-show="on" x-cloak><?php echo e(__('favorites.saved')); ?></span>
        </button>
    </form>
<?php endif; ?>
<?php /**PATH C:\Users\mofak\Desktop\SALE7LI-v3\resources\views/components/favorite-toggle.blade.php ENDPATH**/ ?>