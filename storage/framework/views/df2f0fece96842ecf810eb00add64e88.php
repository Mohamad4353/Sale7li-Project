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
    $points = collect($series)->values();
    // Time flows in the reading direction: oldest -> newest, left -> right in
    // LTR and right -> left in RTL (so the SVG's index 0 sits at the leading edge).
    if (app()->getLocale() === 'ar') {
        $points = $points->reverse()->values();
    }

    $count = $points->count();
    $values = $points->pluck('value')->map(fn ($v) => (float) $v);
    $peak = (float) ($values->max() ?? 0);
    $hasData = $count > 0 && $peak > 0;
    $fmt = $formatValue ?? fn ($v) => number_format((float) $v);

    $w = 640; $h = 180;
    $padX = 8; $padTop = 12; $padBottom = 24;
    $plotW = $w - ($padX * 2);
    $plotH = $h - $padTop - $padBottom;
    $max = max($peak, 1.0);
    $px = fn ($i) => $padX + ($count <= 1 ? $plotW / 2 : ($i / ($count - 1)) * $plotW);
    $py = fn ($v) => $padTop + $plotH - (((float) $v / $max) * $plotH);

    $poly = $points
        ->map(fn ($p, $i) => round($px($i), 1).','.round($py($p['value']), 1))
        ->implode(' ');
    $area = $hasData
        ? round($px(0), 1).','.($padTop + $plotH).' '.$poly.' '.round($px($count - 1), 1).','.($padTop + $plotH)
        : '';
    $labelStep = max(1, (int) ceil($count / 6));
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
        <div class="mt-4 overflow-x-auto">
            <svg viewBox="0 0 <?php echo e($w); ?> <?php echo e($h); ?>" role="img"
                 aria-label="<?php echo e($title ?? __('dashboard.chart_line_label')); ?>"
                 class="block w-full min-w-[380px]" style="aspect-ratio: <?php echo e($w); ?> / <?php echo e($h); ?>">
                <polygon points="<?php echo e($area); ?>" class="fill-ink/10" />
                <polyline points="<?php echo e($poly); ?>" fill="none" class="stroke-ink" stroke-width="2"
                          stroke-linejoin="round" stroke-linecap="round" />
                <?php $__currentLoopData = $points; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php if($i % $labelStep === 0 || $i === $count - 1): ?>
                        <text x="<?php echo e(round($px($i), 1)); ?>" y="<?php echo e($h - 8); ?>" text-anchor="middle"
                              class="fill-muted" style="font-size:10px"><?php echo e($p['label']); ?></text>
                    <?php endif; ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </svg>
        </div>
        
        <ul class="sr-only">
            <?php $__currentLoopData = $points; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <li><?php echo e($p['label']); ?>: <?php echo e($fmt($p['value'])); ?></li>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </ul>
        <p class="mt-2 t-small text-muted"><?php echo e(__('dashboard.chart_peak', ['value' => $fmt($peak)])); ?></p>
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
<?php /**PATH C:\Users\mofak\Desktop\SALE7LI-v3\resources\views/components/chart-line.blade.php ENDPATH**/ ?>