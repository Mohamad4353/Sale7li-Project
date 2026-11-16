
<div <?php echo e($attributes->merge(['class' => 'space-y-3 p-4'])); ?>>
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0 space-y-0.5"><?php echo e($heading); ?></div>
        <?php if(isset($trailing)): ?>
            <div class="shrink-0 text-end"><?php echo e($trailing); ?></div>
        <?php endif; ?>
    </div>

    <?php if(isset($rows)): ?>
        <dl class="space-y-1.5"><?php echo e($rows); ?></dl>
    <?php endif; ?>

    <?php if(isset($actions)): ?>
        <div class="flex flex-wrap items-center gap-2 pt-1"><?php echo e($actions); ?></div>
    <?php endif; ?>
</div>
<?php /**PATH C:\Users\mofak\Desktop\SALE7LI-v3\resources\views/components/table-card.blade.php ENDPATH**/ ?>