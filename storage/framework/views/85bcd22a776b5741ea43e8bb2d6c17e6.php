<?php if (isset($component)) { $__componentOriginalce004ce0f9605516f6b02cec8844a982 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce004ce0f9605516f6b02cec8844a982 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.chart-bars','data' => ['series' => $series,'title' => 'Jobs by status']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('chart-bars'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['series' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($series),'title' => 'Jobs by status']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce004ce0f9605516f6b02cec8844a982)): ?>
<?php $attributes = $__attributesOriginalce004ce0f9605516f6b02cec8844a982; ?>
<?php unset($__attributesOriginalce004ce0f9605516f6b02cec8844a982); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce004ce0f9605516f6b02cec8844a982)): ?>
<?php $component = $__componentOriginalce004ce0f9605516f6b02cec8844a982; ?>
<?php unset($__componentOriginalce004ce0f9605516f6b02cec8844a982); ?>
<?php endif; ?><?php /**PATH C:\Users\mofak\Desktop\SALE7LI-v3\storage\framework/views/a545373ec79d9164051e3e5de6819198.blade.php ENDPATH**/ ?>