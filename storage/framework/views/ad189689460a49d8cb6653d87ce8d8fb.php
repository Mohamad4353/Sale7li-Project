<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'average' => 0,
    'count' => 0,
    'value' => null,       // an explicit single rating (one review); overrides average/count
    'size' => 'sm',        // sm | md
    'showValue' => true,    // show the numeric + count beside the frame
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
    'average' => 0,
    'count' => 0,
    'value' => null,       // an explicit single rating (one review); overrides average/count
    'size' => 'sm',        // sm | md
    'showValue' => true,    // show the numeric + count beside the frame
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>
<?php
    $single = $value !== null;
    $score = $single ? (float) $value : round((float) $average, 1);
    $filled = max(0, min(5, (int) round($score)));   // 0.x -> 0, never a forced star
    $count = (int) $count;
    $has = $single || $count > 0;
    $textSize = $size === 'md' ? 'text-sm' : 'text-xs';
    $aria = $single
        ? __('reviews.rating_out_of', ['value' => $filled])
        : __('reviews.rating_aria', ['value' => number_format($score, 1), 'count' => $count]);
?>
<?php if($has): ?>
    <span <?php echo e($attributes->merge(['class' => "inline-flex items-center gap-1.5 font-bold $textSize"])); ?>

          role="img" aria-label="<?php echo e($aria); ?>">
        <span aria-hidden="true"><span class="text-amber-500"><?php echo e(str_repeat('★', $filled)); ?></span><span class="text-line"><?php echo e(str_repeat('☆', 5 - $filled)); ?></span></span>
        <?php if($showValue && ! $single): ?>
            <span><?php echo e(number_format($score, 1)); ?></span><span class="text-faint">(<?php echo e($count); ?>)</span>
        <?php endif; ?>
    </span>
<?php else: ?>
    <span <?php echo e($attributes->merge(['class' => "inline-flex items-center gap-1 font-bold text-muted $textSize"])); ?>>
        <?php echo e(__('reviews.new_professional')); ?>

    </span>
<?php endif; ?>
<?php /**PATH C:\Users\mofak\Desktop\SALE7LI-v3\resources\views/components/rating.blade.php ENDPATH**/ ?>