<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'status',
    'history' => [],   // ['submitted' => Carbon, 'accepted' => Carbon, ...] from App\Support\RequestTimeline
    'worker' => null,   // the assigned professional, when one is attached
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
    'status',
    'history' => [],   // ['submitted' => Carbon, 'accepted' => Carbon, ...] from App\Support\RequestTimeline
    'worker' => null,   // the assigned professional, when one is attached
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>
<?php
    // Conceptual lifecycle. 'submitted' and 'assigned' are not statuses — they
    // map to request creation and worker_id being set while still 'pending'.
    $flow = ['submitted', 'assigned', 'accepted', 'scheduled', 'on_the_way', 'in_progress', 'completed'];
    $terminal = in_array($status, ['cancelled', 'rejected'], true);
    $assigned = $worker !== null || isset($history['assigned']);

    $indexOf = [
        'pending' => $assigned ? 1 : 0,
        'accepted' => 2, 'scheduled' => 3, 'on_the_way' => 4, 'in_progress' => 5, 'completed' => 6,
    ];
    $current = $terminal ? null : ($indexOf[$status] ?? 0);

    $fmt = fn ($ts) => $ts?->translatedFormat('M j, Y · H:i');
?>
<ol <?php echo e($attributes->merge(['class' => 'relative space-y-6'])); ?>>
    <?php $__currentLoopData = $flow; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $key): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php
            $ts = $history[$key] ?? null;
            $done = ! $terminal && $current !== null && ($i < $current || ($i === $current && $key === 'completed'));
            $isCurrent = ! $terminal && $i === $current && $key !== 'completed';
            $reached = $done || $isCurrent;
        ?>
        <li class="relative flex gap-4">
            <?php if (! ($loop->last && ! $terminal)): ?>
                <span aria-hidden="true"
                      class="absolute start-4 top-8 -bottom-6 w-px <?php echo e($done ? 'bg-ink' : 'bg-line'); ?>"></span>
            <?php endif; ?>

            <span class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                'relative z-10 grid h-8 w-8 shrink-0 place-items-center rounded-full border-2 text-xs font-extrabold',
                'border-ink bg-ink text-white' => $done,
                'border-ink bg-surface text-ink' => $isCurrent,
                'border-line bg-surface text-faint' => ! $reached,
            ]); ?>">
                <?php if($done): ?>
                    <?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'check','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'check','class' => 'h-4 w-4']); ?>
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
                <?php elseif($isCurrent): ?>
                    <span class="h-2.5 w-2.5 rounded-full bg-ink" aria-hidden="true"></span>
                <?php else: ?>
                    <?php echo e($i + 1); ?>

                <?php endif; ?>
            </span>

            <span class="min-w-0 pt-1">
                <span class="block font-extrabold <?php echo e($reached ? 'text-ink' : 'text-faint'); ?>">
                    <?php echo e(__('requests.steps.'.$key)); ?>

                    <span class="sr-only">— <?php echo e($done
                        ? __('requests.timeline_a11y.done')
                        : ($isCurrent ? __('requests.timeline_a11y.current') : __('requests.timeline_a11y.upcoming'))); ?></span>
                </span>

                <?php if($key === 'assigned' && $worker && $status === 'pending'): ?>
                    <span class="mt-0.5 block t-small"><?php echo e(__('requests.assigned_to', ['name' => $worker->name])); ?></span>
                <?php elseif($isCurrent): ?>
                    <span class="mt-0.5 block t-small"><?php echo e(__('status.request.'.$status)); ?></span>
                <?php endif; ?>

                <?php if($ts): ?>
                    <time datetime="<?php echo e($ts->toIso8601String()); ?>" class="mt-0.5 block t-small tabular-nums"><?php echo e($fmt($ts)); ?></time>
                <?php endif; ?>
            </span>
        </li>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

    <?php if($terminal): ?>
        <li class="relative flex gap-4">
            <span class="relative z-10 grid h-8 w-8 shrink-0 place-items-center rounded-full border-2 border-rose-300 bg-rose-50 text-rose-600">
                <?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'close','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'close','class' => 'h-4 w-4']); ?>
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
            </span>
            <span class="pt-1">
                <span class="block font-extrabold text-rose-600">
                    <?php echo e(__('status.request.'.$status)); ?>

                    <span class="sr-only">— <?php echo e(__('requests.timeline_a11y.current')); ?></span>
                </span>
                <?php if(($history[$status] ?? null)): ?>
                    <time datetime="<?php echo e($history[$status]->toIso8601String()); ?>" class="mt-0.5 block t-small tabular-nums"><?php echo e($fmt($history[$status])); ?></time>
                <?php endif; ?>
            </span>
        </li>
    <?php endif; ?>
</ol>
<?php /**PATH C:\Users\mofak\Desktop\SALE7LI-v3\resources\views/components/request-timeline.blade.php ENDPATH**/ ?>