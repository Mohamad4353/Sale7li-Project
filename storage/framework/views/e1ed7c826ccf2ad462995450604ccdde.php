<?php $__env->startSection('content'); ?>
<?php ($title = __('payments.recon_title')); ?>

<div class="container-app py-8 sm:py-10">
    <?php if (isset($component)) { $__componentOriginalf8d4ea307ab1e58d4e472a43c8548d8e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf8d4ea307ab1e58d4e472a43c8548d8e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.page-header','data' => ['back' => route('admin.payments.index'),'backLabel' => __('payments.admin_payments_title'),'eyebrow' => __('admin.eyebrow'),'title' => __('payments.recon_title'),'subtitle' => __('payments.recon_sub')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['back' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('admin.payments.index')),'backLabel' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('payments.admin_payments_title')),'eyebrow' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('admin.eyebrow')),'title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('payments.recon_title')),'subtitle' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('payments.recon_sub'))]); ?>
         <?php $__env->slot('actions', null, []); ?> 
            <?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['href' => route('admin.payments.reconciliation.export'),'variant' => 'secondary','size' => 'sm']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('admin.payments.reconciliation.export')),'variant' => 'secondary','size' => 'sm']); ?>
                <?php echo e(__('messages.export_csv')); ?>

             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $attributes = $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $component = $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
         <?php $__env->endSlot(); ?>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf8d4ea307ab1e58d4e472a43c8548d8e)): ?>
<?php $attributes = $__attributesOriginalf8d4ea307ab1e58d4e472a43c8548d8e; ?>
<?php unset($__attributesOriginalf8d4ea307ab1e58d4e472a43c8548d8e); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf8d4ea307ab1e58d4e472a43c8548d8e)): ?>
<?php $component = $__componentOriginalf8d4ea307ab1e58d4e472a43c8548d8e; ?>
<?php unset($__componentOriginalf8d4ea307ab1e58d4e472a43c8548d8e); ?>
<?php endif; ?>

    <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <?php if (isset($component)) { $__componentOriginal3b387acd2c997737a257e1ec014549fd = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3b387acd2c997737a257e1ec014549fd = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat','data' => ['label' => __('payments.recon_checked'),'value' => $report->checked]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('payments.recon_checked')),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($report->checked)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3b387acd2c997737a257e1ec014549fd)): ?>
<?php $attributes = $__attributesOriginal3b387acd2c997737a257e1ec014549fd; ?>
<?php unset($__attributesOriginal3b387acd2c997737a257e1ec014549fd); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3b387acd2c997737a257e1ec014549fd)): ?>
<?php $component = $__componentOriginal3b387acd2c997737a257e1ec014549fd; ?>
<?php unset($__componentOriginal3b387acd2c997737a257e1ec014549fd); ?>
<?php endif; ?>
        <?php if (isset($component)) { $__componentOriginal3b387acd2c997737a257e1ec014549fd = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3b387acd2c997737a257e1ec014549fd = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat','data' => ['label' => __('payments.recon_reconciled'),'value' => $report->reconciled]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('payments.recon_reconciled')),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($report->reconciled)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3b387acd2c997737a257e1ec014549fd)): ?>
<?php $attributes = $__attributesOriginal3b387acd2c997737a257e1ec014549fd; ?>
<?php unset($__attributesOriginal3b387acd2c997737a257e1ec014549fd); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3b387acd2c997737a257e1ec014549fd)): ?>
<?php $component = $__componentOriginal3b387acd2c997737a257e1ec014549fd; ?>
<?php unset($__componentOriginal3b387acd2c997737a257e1ec014549fd); ?>
<?php endif; ?>
        <?php if (isset($component)) { $__componentOriginal3b387acd2c997737a257e1ec014549fd = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3b387acd2c997737a257e1ec014549fd = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat','data' => ['label' => __('payments.recon_discrepancies'),'value' => $report->discrepancyCount()]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('payments.recon_discrepancies')),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($report->discrepancyCount())]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3b387acd2c997737a257e1ec014549fd)): ?>
<?php $attributes = $__attributesOriginal3b387acd2c997737a257e1ec014549fd; ?>
<?php unset($__attributesOriginal3b387acd2c997737a257e1ec014549fd); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3b387acd2c997737a257e1ec014549fd)): ?>
<?php $component = $__componentOriginal3b387acd2c997737a257e1ec014549fd; ?>
<?php unset($__componentOriginal3b387acd2c997737a257e1ec014549fd); ?>
<?php endif; ?>
        <?php if (isset($component)) { $__componentOriginal3b387acd2c997737a257e1ec014549fd = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3b387acd2c997737a257e1ec014549fd = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat','data' => ['label' => __('payments.recon_refunded_payments'),'value' => $report->refundedPayments]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('payments.recon_refunded_payments')),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($report->refundedPayments)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3b387acd2c997737a257e1ec014549fd)): ?>
<?php $attributes = $__attributesOriginal3b387acd2c997737a257e1ec014549fd; ?>
<?php unset($__attributesOriginal3b387acd2c997737a257e1ec014549fd); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3b387acd2c997737a257e1ec014549fd)): ?>
<?php $component = $__componentOriginal3b387acd2c997737a257e1ec014549fd; ?>
<?php unset($__componentOriginal3b387acd2c997737a257e1ec014549fd); ?>
<?php endif; ?>
    </div>

    <div class="mt-4 grid gap-4 sm:grid-cols-3">
        <?php if (isset($component)) { $__componentOriginal3b387acd2c997737a257e1ec014549fd = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3b387acd2c997737a257e1ec014549fd = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat','data' => ['label' => __('payments.recon_gross_captured'),'value' => \App\Payments\Money::format($report->grossCaptured)]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('payments.recon_gross_captured')),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(\App\Payments\Money::format($report->grossCaptured))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3b387acd2c997737a257e1ec014549fd)): ?>
<?php $attributes = $__attributesOriginal3b387acd2c997737a257e1ec014549fd; ?>
<?php unset($__attributesOriginal3b387acd2c997737a257e1ec014549fd); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3b387acd2c997737a257e1ec014549fd)): ?>
<?php $component = $__componentOriginal3b387acd2c997737a257e1ec014549fd; ?>
<?php unset($__componentOriginal3b387acd2c997737a257e1ec014549fd); ?>
<?php endif; ?>
        <?php if (isset($component)) { $__componentOriginal3b387acd2c997737a257e1ec014549fd = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3b387acd2c997737a257e1ec014549fd = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat','data' => ['label' => __('payments.recon_total_refunded'),'value' => \App\Payments\Money::format($report->totalRefunded)]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('payments.recon_total_refunded')),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(\App\Payments\Money::format($report->totalRefunded))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3b387acd2c997737a257e1ec014549fd)): ?>
<?php $attributes = $__attributesOriginal3b387acd2c997737a257e1ec014549fd; ?>
<?php unset($__attributesOriginal3b387acd2c997737a257e1ec014549fd); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3b387acd2c997737a257e1ec014549fd)): ?>
<?php $component = $__componentOriginal3b387acd2c997737a257e1ec014549fd; ?>
<?php unset($__componentOriginal3b387acd2c997737a257e1ec014549fd); ?>
<?php endif; ?>
        <?php if (isset($component)) { $__componentOriginal3b387acd2c997737a257e1ec014549fd = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3b387acd2c997737a257e1ec014549fd = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat','data' => ['label' => __('payments.recon_retained'),'value' => \App\Payments\Money::format($report->retained)]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('payments.recon_retained')),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(\App\Payments\Money::format($report->retained))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3b387acd2c997737a257e1ec014549fd)): ?>
<?php $attributes = $__attributesOriginal3b387acd2c997737a257e1ec014549fd; ?>
<?php unset($__attributesOriginal3b387acd2c997737a257e1ec014549fd); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3b387acd2c997737a257e1ec014549fd)): ?>
<?php $component = $__componentOriginal3b387acd2c997737a257e1ec014549fd; ?>
<?php unset($__componentOriginal3b387acd2c997737a257e1ec014549fd); ?>
<?php endif; ?>
    </div>

    <?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['padding' => 'p-0','class' => 'mt-8 overflow-hidden']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['padding' => 'p-0','class' => 'mt-8 overflow-hidden']); ?>
        <h2 class="t-section px-6 pt-6"><?php echo e(__('payments.recon_findings')); ?></h2>

        <?php if($report->isClean()): ?>
            <?php if (isset($component)) { $__componentOriginal4f22a152e0729cd34293e65bd200d933 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4f22a152e0729cd34293e65bd200d933 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.empty','data' => ['icon' => 'shield','title' => __('payments.recon_clean_title'),'message' => __('payments.recon_clean_hint')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('empty'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'shield','title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('payments.recon_clean_title')),'message' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('payments.recon_clean_hint'))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal4f22a152e0729cd34293e65bd200d933)): ?>
<?php $attributes = $__attributesOriginal4f22a152e0729cd34293e65bd200d933; ?>
<?php unset($__attributesOriginal4f22a152e0729cd34293e65bd200d933); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal4f22a152e0729cd34293e65bd200d933)): ?>
<?php $component = $__componentOriginal4f22a152e0729cd34293e65bd200d933; ?>
<?php unset($__componentOriginal4f22a152e0729cd34293e65bd200d933); ?>
<?php endif; ?>
        <?php else: ?>
            <div class="table-wrap mt-4">
                <table>
                    <thead class="bg-paper text-xs uppercase tracking-wide text-muted">
                        <tr>
                            <th class="p-4 font-semibold"><?php echo e(__('payments.recon_col_issue')); ?></th>
                            <th class="p-4 font-semibold"><?php echo e(__('payments.col_service')); ?></th>
                            <th class="p-4 font-semibold"><?php echo e(__('payments.col_customer')); ?></th>
                            <th class="p-4 font-semibold"><?php echo e(__('payments.col_worker')); ?></th>
                            <th class="p-4 font-semibold text-end"><?php echo e(__('payments.recon_col_expected')); ?></th>
                            <th class="p-4 font-semibold text-end"><?php echo e(__('payments.recon_col_actual')); ?></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        <?php $__currentLoopData = $report->discrepancies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $d): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td class="p-4">
                                    <div class="font-bold"><?php echo e($d->label()); ?></div>
                                    <?php if($d->paymentId): ?><div class="t-small"><?php echo e(__('payments.recon_payment_ref', ['id' => $d->paymentId])); ?></div><?php endif; ?>
                                </td>
                                <td class="p-4 t-small"><?php echo e($d->reference ?? __('messages.none')); ?></td>
                                <td class="p-4"><?php echo e($d->customer ?? __('messages.none')); ?></td>
                                <td class="p-4"><?php echo e($d->worker ?? __('messages.none')); ?></td>
                                <td class="p-4 text-end tabular-nums"><?php echo e($d->expected ?? __('messages.none')); ?></td>
                                <td class="p-4 text-end tabular-nums"><?php echo e($d->actual ?? __('messages.none')); ?></td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
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
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\mofak\Desktop\SALE7LI-v3\resources\views/admin/payments/reconciliation.blade.php ENDPATH**/ ?>