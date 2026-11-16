<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'series' => [],        // list<array{label:string, value:int|float}>
    'title' => null,
    'formatValue' => null, // ?callable(int|float): string
    'dataChart' => null,   // stable test/automation selector
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
    'series' => [],        // list<array{label:string, value:int|float}>
    'title' => null,
    'formatValue' => null, // ?callable(int|float): string
    'dataChart' => null,   // stable test/automation selector
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>
<?php
    $rows = collect($series)->values();
    $count = $rows->count();
    $max = max(1.0, (float) $rows->pluck('value')->map(fn ($v) => (float) $v)->max());
    $hasData = $count > 0;
    $fmt = $formatValue ?? fn ($v) => number_format((float) $v);
    $rtl = app()->getLocale() === 'ar';
?>

<?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['dataChart' => $dataChart]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['data-chart' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($dataChart)]); ?>
    <?php if($title): ?><h3 class="t-section"><?php echo e($title); ?></h3><?php endif; ?>

    <?php if (! ($hasData)): ?>
        <p class="mt-3 t-muted"><?php echo e(__('dashboard.chart_no_data')); ?></p>
    <?php else: ?>
        
        <ul class="mt-4 space-y-2.5" aria-label="<?php echo e($title ?? __('dashboard.chart_bars_label')); ?>">
            <?php $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                    $value = (float) $row['value'];
                    $pct = round(($value / $max) * 100, 2);
                    $drawPct = $value > 0 ? max($pct, 1.5) : 0;
                ?>
                <li class="flex items-center gap-3 text-sm">
                    <span class="w-36 shrink-0 text-end leading-snug break-words text-muted"><?php echo e($row['label']); ?></span>
                    <span class="h-6 flex-1 overflow-hidden rounded-md">
                        <svg viewBox="0 0 100 24" preserveAspectRatio="none" class="block h-full w-full" aria-hidden="true">
                            <rect x="0" y="0" width="100" height="24" class="fill-line" />
                            <?php if($drawPct > 0): ?>
                                <rect x="<?php echo e($rtl ? round(100 - $drawPct, 2) : 0); ?>" y="0" width="<?php echo e($drawPct); ?>" height="24" class="fill-ink" />
                            <?php endif; ?>
                        </svg>
                    </span>
                    <span class="w-12 shrink-0 text-end font-bold tabular-nums" dir="ltr"><?php echo e($fmt($value)); ?></span>
                </li>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </ul>
    <?php endif; ?>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal53747ceb358d30c0105769f8471417f6)): ?>
<?php $attributes = $__attributesOriginal53747ceb358d30c0105769f8471417f6; ?>
<?php unset($__attributesOriginal53747ceb358d30c0105769f8471417f6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal53747ceb358d30c0105769f8471417f6)): ?>
<?php $component = $__componentOriginal53747ceb358d30c0105769f8471417f6; ?>
<?php unset($__componentOriginal53747ceb358d30c0105769f8471417f6); ?>
<?php endif; ?>
<?php /**PATH C:\Users\mofak\Desktop\SALE7LI-v3\resources\views/components/chart-bars.blade.php ENDPATH**/ ?>