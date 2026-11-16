<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'value',            // string to place on the clipboard
    'label' => null,    // accessible label; defaults to "Copy"
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
    'value',            // string to place on the clipboard
    'label' => null,    // accessible label; defaults to "Copy"
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<button type="button"
        x-data="{
            ok: !!(navigator.clipboard && navigator.clipboard.writeText),
            copied: false,
            copy() {
                navigator.clipboard.writeText(<?php echo \Illuminate\Support\Js::from((string) $value)->toHtml() ?>)
                    .then(() => { this.copied = true; setTimeout(() => { this.copied = false }, 1500) })
                    .catch(() => {});
            }
        }"
        x-show="ok" x-cloak
        x-on:click="copy()"
        :aria-label="copied ? <?php echo \Illuminate\Support\Js::from(__('messages.copied'))->toHtml() ?> : <?php echo \Illuminate\Support\Js::from($label ?? __('messages.copy'))->toHtml() ?>"
        <?php echo e($attributes->merge(['class' => 'inline-flex shrink-0 items-center gap-1 rounded-lg border border-line bg-surface px-2 py-1 text-[11px] font-bold text-muted transition hover:text-ink'])); ?>>
    <?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'clipboard','class' => 'h-3.5 w-3.5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'clipboard','class' => 'h-3.5 w-3.5']); ?>
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
    <span x-show="!copied"><?php echo e(__('messages.copy')); ?></span>
    <span x-show="copied" x-cloak class="text-emerald-600"><?php echo e(__('messages.copied')); ?></span>
</button>
<?php /**PATH C:\Users\mofak\Desktop\SALE7LI-v3\resources\views/components/copy-button.blade.php ENDPATH**/ ?>