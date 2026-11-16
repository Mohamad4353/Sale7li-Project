<?php if (isset($component)) { $__componentOriginal02dabe41e86d897cdaedd60d683d8855 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal02dabe41e86d897cdaedd60d683d8855 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.lang-switch','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('lang-switch'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal02dabe41e86d897cdaedd60d683d8855)): ?>
<?php $attributes = $__attributesOriginal02dabe41e86d897cdaedd60d683d8855; ?>
<?php unset($__attributesOriginal02dabe41e86d897cdaedd60d683d8855); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal02dabe41e86d897cdaedd60d683d8855)): ?>
<?php $component = $__componentOriginal02dabe41e86d897cdaedd60d683d8855; ?>
<?php unset($__componentOriginal02dabe41e86d897cdaedd60d683d8855); ?>
<?php endif; ?><?php /**PATH C:\Users\mofak\Desktop\SALE7LI-v3\storage\framework/views/1a793832691bc85704be64a48acb815a.blade.php ENDPATH**/ ?>