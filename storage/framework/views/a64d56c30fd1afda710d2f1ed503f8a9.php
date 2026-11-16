<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'availability' => null,   // iterable of WorkerAvailability rows
    'editable' => false,       // editable renders form inputs; parent supplies the <form>
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
    'availability' => null,   // iterable of WorkerAvailability rows
    'editable' => false,       // editable renders form inputs; parent supplies the <form>
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>
<?php
    // day_of_week: 0 = Monday … 6 = Sunday (matches the order shown on the
    // public profile). Names are localised through the app's Carbon locale.
    // Times are application local time — the `time` columns carry no timezone.
    $rows = collect($availability ?? []);
    $byDay = $rows->keyBy('day_of_week');
    $dayName = fn (int $i) => \Carbon\CarbonImmutable::now()->startOfWeek()->addDays($i)->translatedFormat('l');
    $hhmm = fn (?string $t) => $t ? \Illuminate\Support\Str::of($t)->substr(0, 5) : null;
    $published = $rows->where('is_available', true)->sortBy('day_of_week');
?>

<?php if($editable): ?>
    <div <?php echo e($attributes->merge(['class' => 'space-y-2.5'])); ?>>
        <?php for($i = 0; $i < 7; $i++): ?>
            <?php ($slot = $byDay->get($i)); ?>
            <?php ($isOn = (bool) old("days.$i.is_available", $slot?->is_available)); ?>
            <div class="rounded-xl border border-line p-3 sm:flex sm:items-center sm:gap-4"
                 x-data="{ on: <?php echo e($isOn ? 'true' : 'false'); ?> }">
                <label class="flex items-center gap-2.5 font-bold sm:w-40">
                    <input type="hidden" name="days[<?php echo e($i); ?>][is_available]" value="0">
                    <input type="checkbox" name="days[<?php echo e($i); ?>][is_available]" value="1" x-model="on"
                           <?php if($isOn): echo 'checked'; endif; ?>
                           class="h-4 w-4 rounded border-line text-ink focus-visible:ring-2 focus-visible:ring-ink/25">
                    <?php echo e($dayName($i)); ?>

                </label>
                <div class="mt-2 flex items-center gap-2 sm:mt-0" :class="on ? '' : 'opacity-40'">
                    <label for="av-<?php echo e($i); ?>-start" class="sr-only"><?php echo e(__('workers.av_start')); ?> — <?php echo e($dayName($i)); ?></label>
                    <input id="av-<?php echo e($i); ?>-start" type="time" name="days[<?php echo e($i); ?>][start]"
                           value="<?php echo e($hhmm(old("days.$i.start", $slot?->start_time))); ?>"
                           class="input w-32" x-bind:disabled="!on">
                    <span class="t-muted" aria-hidden="true">–</span>
                    <label for="av-<?php echo e($i); ?>-end" class="sr-only"><?php echo e(__('workers.av_end')); ?> — <?php echo e($dayName($i)); ?></label>
                    <input id="av-<?php echo e($i); ?>-end" type="time" name="days[<?php echo e($i); ?>][end]"
                           value="<?php echo e($hhmm(old("days.$i.end", $slot?->end_time))); ?>"
                           class="input w-32" x-bind:disabled="!on">
                </div>
            </div>
        <?php endfor; ?>
    </div>
<?php elseif($published->isNotEmpty()): ?>
    <ul <?php echo e($attributes->merge(['class' => 'space-y-1.5 t-muted'])); ?>>
        <?php $__currentLoopData = $published; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $slot): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <li class="flex items-center justify-between gap-4">
                <span class="font-semibold text-ink"><?php echo e($dayName((int) $slot->day_of_week)); ?></span>
                <span><?php echo e($hhmm($slot->start_time)); ?>–<?php echo e($hhmm($slot->end_time)); ?></span>
            </li>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </ul>
<?php else: ?>
    <p <?php echo e($attributes->merge(['class' => 't-small'])); ?>><?php echo e(__('workers.availability_none')); ?></p>
<?php endif; ?>
<?php /**PATH C:\Users\mofak\Desktop\SALE7LI-v3\resources\views/components/worker-availability.blade.php ENDPATH**/ ?>