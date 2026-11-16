<?php if (isset($component)) { $__componentOriginal76fa6c532ed1da00a6e6bd75bbdd62ba = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal76fa6c532ed1da00a6e6bd75bbdd62ba = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.chart-line','data' => ['series' => $series,'title' => 'Requests']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('chart-line'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['series' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($series),'title' => 'Requests']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal76fa6c532ed1da00a6e6bd75bbdd62ba)): ?>
<?php $attributes = $__attributesOriginal76fa6c532ed1da00a6e6bd75bbdd62ba; ?>
<?php unset($__attributesOriginal76fa6c532ed1da00a6e6bd75bbdd62ba); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal76fa6c532ed1da00a6e6bd75bbdd62ba)): ?>
<?php $component = $__componentOriginal76fa6c532ed1da00a6e6bd75bbdd62ba; ?>
<?php unset($__componentOriginal76fa6c532ed1da00a6e6bd75bbdd62ba); ?>
<?php endif; ?><?php /**PATH C:\Users\mofak\Desktop\SALE7LI-v3\storage\framework/views/1941bfeac953db93e5f20941faa96673.blade.php ENDPATH**/ ?>