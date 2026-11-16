
<?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['attributes' => $attributes->merge(['class' => 'bg-paper'])]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['attributes' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($attributes->merge(['class' => 'bg-paper']))]); ?>
    <h2 class="t-card"><?php echo e(__('bookings.next_title')); ?></h2>
    <ol class="mt-4 space-y-3">
        <?php $__currentLoopData = ['next_1', 'next_2', 'next_3', 'next_4', 'next_5', 'next_6']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $key): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <li class="flex items-start gap-3 text-sm">
                <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-ink text-xs font-bold text-white"><?php echo e($i + 1); ?></span>
                <span class="pt-0.5"><?php echo e(__('bookings.'.$key)); ?></span>
            </li>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </ol>
    <p class="mt-4 rounded-xl bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-900">
        <?php echo e(__('bookings.next_note')); ?>

    </p>
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
<?php /**PATH C:\Users\mofak\Desktop\SALE7LI-v3\resources\views/components/booking-next-steps.blade.php ENDPATH**/ ?>