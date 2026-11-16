<?php if (isset($component)) { $__componentOriginaldcabf38f727f3bd9304c0d0f1f7d11cf = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginaldcabf38f727f3bd9304c0d0f1f7d11cf = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.worker-card','data' => ['worker' => $worker]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('worker-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['worker' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($worker)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginaldcabf38f727f3bd9304c0d0f1f7d11cf)): ?>
<?php $attributes = $__attributesOriginaldcabf38f727f3bd9304c0d0f1f7d11cf; ?>
<?php unset($__attributesOriginaldcabf38f727f3bd9304c0d0f1f7d11cf); ?>
<?php endif; ?>
<?php if (isset($__componentOriginaldcabf38f727f3bd9304c0d0f1f7d11cf)): ?>
<?php $component = $__componentOriginaldcabf38f727f3bd9304c0d0f1f7d11cf; ?>
<?php unset($__componentOriginaldcabf38f727f3bd9304c0d0f1f7d11cf); ?>
<?php endif; ?><?php /**PATH C:\Users\mofak\Desktop\SALE7LI-v3\storage\framework/views/eeae773c8852795cd07618edfc1a4b43.blade.php ENDPATH**/ ?>