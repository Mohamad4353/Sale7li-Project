<?php $__env->startSection('content'); ?>
<?php ($title = __('payments.receipt_title')); ?>

<div class="container-app max-w-2xl py-8 sm:py-10">
    <?php if (isset($component)) { $__componentOriginalf8d4ea307ab1e58d4e472a43c8548d8e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf8d4ea307ab1e58d4e472a43c8548d8e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.page-header','data' => ['back' => route('customer.requests.show', $serviceRequest),'backLabel' => __('payments.receipt_back_to_request'),'eyebrow' => $serviceRequest->reference,'title' => __('payments.receipt_title')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['back' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('customer.requests.show', $serviceRequest)),'backLabel' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('payments.receipt_back_to_request')),'eyebrow' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($serviceRequest->reference),'title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('payments.receipt_title'))]); ?>
         <?php $__env->slot('actions', null, []); ?> 
            <button type="button" x-data @click="window.print()"
                    class="no-print inline-flex items-center gap-2 rounded-xl border border-line bg-surface px-3 py-2 text-[13px] font-bold text-ink transition hover:bg-paper">
                <?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'clipboard','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'clipboard','class' => 'h-4 w-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?><?php echo e(__('messages.print')); ?>

            </button>
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

    <?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['class' => 'print-sheet mt-8 text-center']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'print-sheet mt-8 text-center']); ?>
        <span class="mx-auto grid h-12 w-12 place-items-center rounded-2xl bg-emerald-100 text-emerald-700">
            <?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'check','class' => 'h-6 w-6']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'check','class' => 'h-6 w-6']); ?>
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
        <h2 class="mt-4 t-card"><?php echo e(__('payments.receipt_success')); ?></h2>
        <p class="mt-1 t-muted"><?php echo e(__('payments.receipt_success_hint')); ?></p>

        <dl class="mt-6 space-y-3 border-t border-line pt-6 text-start text-sm">
            <div class="flex justify-between gap-4">
                <dt class="t-muted"><?php echo e(__('payments.receipt_ref')); ?></dt>
                <dd class="font-bold"><?php echo e($serviceRequest->reference); ?> · <?php echo e($serviceRequest->category->localized_name); ?></dd>
            </div>
            <div class="flex justify-between gap-4">
                <dt class="t-muted"><?php echo e(__('payments.receipt_amount')); ?></dt>
                <dd class="font-black tabular-nums"><?php if (isset($component)) { $__componentOriginal3c51c5b308d311657bfae6be692a1470 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3c51c5b308d311657bfae6be692a1470 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.money','data' => ['amount' => $payment->amount]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('money'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['amount' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($payment->amount)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3c51c5b308d311657bfae6be692a1470)): ?>
<?php $attributes = $__attributesOriginal3c51c5b308d311657bfae6be692a1470; ?>
<?php unset($__attributesOriginal3c51c5b308d311657bfae6be692a1470); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3c51c5b308d311657bfae6be692a1470)): ?>
<?php $component = $__componentOriginal3c51c5b308d311657bfae6be692a1470; ?>
<?php unset($__componentOriginal3c51c5b308d311657bfae6be692a1470); ?>
<?php endif; ?></dd>
            </div>
            <div class="flex justify-between gap-4">
                <dt class="t-muted"><?php echo e(__('payments.receipt_date')); ?></dt>
                <dd class="font-bold"><?php if (isset($component)) { $__componentOriginal0da8235d2d96274f8680ccda5445231c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0da8235d2d96274f8680ccda5445231c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.date','data' => ['value' => $payment->paid_at,'time' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('date'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($payment->paid_at),'time' => true]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal0da8235d2d96274f8680ccda5445231c)): ?>
<?php $attributes = $__attributesOriginal0da8235d2d96274f8680ccda5445231c; ?>
<?php unset($__attributesOriginal0da8235d2d96274f8680ccda5445231c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0da8235d2d96274f8680ccda5445231c)): ?>
<?php $component = $__componentOriginal0da8235d2d96274f8680ccda5445231c; ?>
<?php unset($__componentOriginal0da8235d2d96274f8680ccda5445231c); ?>
<?php endif; ?></dd>
            </div>
            <div class="flex justify-between gap-4">
                <dt class="t-muted"><?php echo e(__('payments.method_label')); ?></dt>
                <dd class="font-bold"><?php echo e($payment->payment_method === 'whish' ? __('payments.method_whish') : __('payments.method_card')); ?></dd>
            </div>
            <?php if($payment->payment_method === 'whish' && $payment->whish_reference): ?>
                <div class="flex justify-between gap-4">
                    <dt class="t-muted"><?php echo e(__('payments.whish_col_reference')); ?></dt>
                    <dd class="font-mono text-xs"><?php echo e($payment->whish_reference); ?></dd>
                </div>
            <?php endif; ?>
            <div class="flex justify-between gap-4">
                <dt class="t-muted"><?php echo e(__('payments.receipt_txn')); ?></dt>
                <dd class="font-mono text-xs"><?php echo e($payment->transaction_reference); ?></dd>
            </div>
        </dl>

        <?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['href' => route('customer.requests.invoice', $serviceRequest),'variant' => 'secondary','class' => 'mt-6 w-full no-print']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('customer.requests.invoice', $serviceRequest)),'variant' => 'secondary','class' => 'mt-6 w-full no-print']); ?>
            <?php echo e(__('payments.receipt_view_invoice')); ?>

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

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\mofak\Desktop\SALE7LI-v3\resources\views/customer/payments/receipt.blade.php ENDPATH**/ ?>