<?php $__env->startSection('content'); ?>
<?php ($title = __('payments.admin_payments_title')); ?>

<div class="container-app py-8 sm:py-10">
    <?php if (isset($component)) { $__componentOriginalf8d4ea307ab1e58d4e472a43c8548d8e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf8d4ea307ab1e58d4e472a43c8548d8e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.page-header','data' => ['eyebrow' => __('admin.eyebrow'),'title' => __('payments.admin_payments_title'),'subtitle' => __('payments.admin_payments_sub')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['eyebrow' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('admin.eyebrow')),'title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('payments.admin_payments_title')),'subtitle' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('payments.admin_payments_sub'))]); ?>
         <?php $__env->slot('actions', null, []); ?> 
            <?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['href' => route('admin.payments.whish'),'variant' => 'secondary','size' => 'sm']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('admin.payments.whish')),'variant' => 'secondary','size' => 'sm']); ?>
                <?php echo e(__('payments.whish_queue_link')); ?><?php if($whishPending): ?> <span class="ms-1 rounded-full bg-brand px-1.5 text-[10px] font-black text-ink"><?php echo e($whishPending); ?></span><?php endif; ?>
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
            <?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['href' => route('admin.payments.export', request()->query()),'variant' => 'secondary','size' => 'sm']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('admin.payments.export', request()->query())),'variant' => 'secondary','size' => 'sm']); ?>
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
            <?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['href' => route('admin.payments.reconciliation'),'variant' => 'secondary','size' => 'sm']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('admin.payments.reconciliation')),'variant' => 'secondary','size' => 'sm']); ?>
                <?php echo e(__('payments.recon_title')); ?>

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
            <?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['href' => route('admin.settings.commission.edit'),'variant' => 'secondary','size' => 'sm']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('admin.settings.commission.edit')),'variant' => 'secondary','size' => 'sm']); ?>
                <?php echo e(__('payments.commission_settings_title')); ?>

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

    <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <?php if (isset($component)) { $__componentOriginal3b387acd2c997737a257e1ec014549fd = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3b387acd2c997737a257e1ec014549fd = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat','data' => ['label' => __('payments.summary_gross_volume'),'value' => \App\Payments\Money::format($summary->gross)]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('payments.summary_gross_volume')),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(\App\Payments\Money::format($summary->gross))]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat','data' => ['label' => __('payments.platform_commission'),'value' => \App\Payments\Money::format($summary->commission)]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('payments.platform_commission')),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(\App\Payments\Money::format($summary->commission))]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat','data' => ['label' => __('payments.worker_net'),'value' => \App\Payments\Money::format($summary->net)]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('payments.worker_net')),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(\App\Payments\Money::format($summary->net))]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat','data' => ['label' => __('payments.summary_paid_count'),'value' => (int) $summary->paid_count]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('payments.summary_paid_count')),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute((int) $summary->paid_count)]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat','data' => ['label' => __('payments.summary_avg'),'value' => \App\Payments\Money::format($summary->average)]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('payments.summary_avg')),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(\App\Payments\Money::format($summary->average))]); ?>
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

    <form class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_160px_160px_auto]" data-no-busy>
        <label for="f-worker" class="sr-only"><?php echo e(__('payments.filter_worker')); ?></label>
        <input id="f-worker" name="worker" value="<?php echo e($filters['worker']); ?>" class="input" placeholder="<?php echo e(__('payments.filter_worker')); ?>">
        <label for="f-customer" class="sr-only"><?php echo e(__('payments.filter_customer')); ?></label>
        <input id="f-customer" name="customer" value="<?php echo e($filters['customer']); ?>" class="input" placeholder="<?php echo e(__('payments.filter_customer')); ?>">
        <label for="f-status" class="sr-only"><?php echo e(__('payments.filter_status')); ?></label>
        <select id="f-status" name="status" class="input">
            <option value=""><?php echo e(__('payments.all_statuses')); ?></option>
            <?php $__currentLoopData = $statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($s); ?>" <?php if($filters['status'] === $s): echo 'selected'; endif; ?>><?php echo e(__('status.payment.'.$s)); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <div class="grid grid-cols-2 gap-2 sm:col-span-2 lg:col-span-2 lg:grid-cols-2">
            <label for="f-from" class="sr-only"><?php echo e(__('payments.filter_from')); ?></label>
            <input id="f-from" type="date" name="from" value="<?php echo e($filters['from']); ?>" class="input" aria-label="<?php echo e(__('payments.filter_from')); ?>">
            <label for="f-to" class="sr-only"><?php echo e(__('payments.filter_to')); ?></label>
            <input id="f-to" type="date" name="to" value="<?php echo e($filters['to']); ?>" class="input" aria-label="<?php echo e(__('payments.filter_to')); ?>">
        </div>
        <?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['type' => 'submit']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'submit']); ?><?php echo e(__('messages.filter')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $attributes = $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $component = $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
    </form>

    <?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['padding' => 'p-0','class' => 'mt-6 overflow-hidden']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['padding' => 'p-0','class' => 'mt-6 overflow-hidden']); ?>
        <div class="table-wrap hidden sm:block">
            <table>
                <thead class="bg-paper text-xs uppercase tracking-wide text-muted">
                    <tr>
                        <th class="p-4 font-semibold"><?php echo e(__('payments.col_service')); ?></th>
                        <th class="p-4 font-semibold"><?php echo e(__('payments.col_customer')); ?></th>
                        <th class="p-4 font-semibold"><?php echo e(__('payments.col_worker')); ?></th>
                        <th class="p-4 font-semibold text-end"><?php echo e(__('payments.col_gross')); ?></th>
                        <th class="p-4 font-semibold text-end"><?php echo e(__('payments.col_rate')); ?></th>
                        <th class="p-4 font-semibold text-end"><?php echo e(__('payments.col_commission')); ?></th>
                        <th class="p-4 font-semibold text-end"><?php echo e(__('payments.col_net')); ?></th>
                        <th class="p-4 font-semibold text-end"><?php echo e(__('payments.col_refunded')); ?></th>
                        <th class="p-4 font-semibold"><?php echo e(__('payments.col_paid_date')); ?></th>
                        <th class="p-4 font-semibold"><?php echo e(__('payments.col_status')); ?></th>
                        <th class="p-4 font-semibold"><span class="sr-only"><?php echo e(__('payments.refund_action')); ?></span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    <?php $__empty_1 = true; $__currentLoopData = $payments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $payment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php ($refund = $refundMap[$payment->id] ?? ['refunded' => null, 'is_refunded' => false]); ?>
                        <tr>
                            <td class="p-4">
                                <div class="font-bold"><?php echo e($payment->request?->category?->localized_name ?? __('payments.not_available')); ?></div>
                                <div class="t-small"><?php echo e($payment->request?->reference); ?></div>
                            </td>
                            <td class="p-4"><?php echo e($payment->customer?->name ?? __('reviews.anonymous')); ?></td>
                            <td class="p-4"><?php echo e($payment->worker?->name ?? __('payments.not_available')); ?></td>
                            <td class="p-4 text-end tabular-nums"><?php if (isset($component)) { $__componentOriginal3c51c5b308d311657bfae6be692a1470 = $component; } ?>
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
<?php endif; ?></td>
                            <td class="p-4 text-end tabular-nums"><?php echo e(\App\Payments\Money::ratePercent($payment->commission_rate) ?? __('payments.not_available')); ?></td>
                            <td class="p-4 text-end tabular-nums"><?php if (isset($component)) { $__componentOriginal3c51c5b308d311657bfae6be692a1470 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3c51c5b308d311657bfae6be692a1470 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.money','data' => ['amount' => $payment->commission_amount]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('money'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['amount' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($payment->commission_amount)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3c51c5b308d311657bfae6be692a1470)): ?>
<?php $attributes = $__attributesOriginal3c51c5b308d311657bfae6be692a1470; ?>
<?php unset($__attributesOriginal3c51c5b308d311657bfae6be692a1470); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3c51c5b308d311657bfae6be692a1470)): ?>
<?php $component = $__componentOriginal3c51c5b308d311657bfae6be692a1470; ?>
<?php unset($__componentOriginal3c51c5b308d311657bfae6be692a1470); ?>
<?php endif; ?></td>
                            <td class="p-4 text-end tabular-nums"><?php if (isset($component)) { $__componentOriginal3c51c5b308d311657bfae6be692a1470 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3c51c5b308d311657bfae6be692a1470 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.money','data' => ['amount' => $payment->worker_net]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('money'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['amount' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($payment->worker_net)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3c51c5b308d311657bfae6be692a1470)): ?>
<?php $attributes = $__attributesOriginal3c51c5b308d311657bfae6be692a1470; ?>
<?php unset($__attributesOriginal3c51c5b308d311657bfae6be692a1470); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3c51c5b308d311657bfae6be692a1470)): ?>
<?php $component = $__componentOriginal3c51c5b308d311657bfae6be692a1470; ?>
<?php unset($__componentOriginal3c51c5b308d311657bfae6be692a1470); ?>
<?php endif; ?></td>
                            <td class="p-4 text-end tabular-nums">
                                <?php if($refund['refunded'] !== null): ?>
                                    <?php echo e($refund['refunded']); ?>

                                <?php else: ?>
                                    <span class="text-faint"><?php echo e(__('payments.not_available')); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="p-4 t-small"><?php if (isset($component)) { $__componentOriginal0da8235d2d96274f8680ccda5445231c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0da8235d2d96274f8680ccda5445231c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.date','data' => ['value' => $payment->paid_at]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('date'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($payment->paid_at)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal0da8235d2d96274f8680ccda5445231c)): ?>
<?php $attributes = $__attributesOriginal0da8235d2d96274f8680ccda5445231c; ?>
<?php unset($__attributesOriginal0da8235d2d96274f8680ccda5445231c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0da8235d2d96274f8680ccda5445231c)): ?>
<?php $component = $__componentOriginal0da8235d2d96274f8680ccda5445231c; ?>
<?php unset($__componentOriginal0da8235d2d96274f8680ccda5445231c); ?>
<?php endif; ?></td>
                            <td class="p-4">
                                <div class="flex items-center gap-1.5">
                                    <?php if (isset($component)) { $__componentOriginal51ed764111e345fc11534f121cfeb451 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal51ed764111e345fc11534f121cfeb451 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.status','data' => ['type' => 'payment','value' => $payment->status]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('status'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'payment','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($payment->status)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal51ed764111e345fc11534f121cfeb451)): ?>
<?php $attributes = $__attributesOriginal51ed764111e345fc11534f121cfeb451; ?>
<?php unset($__attributesOriginal51ed764111e345fc11534f121cfeb451); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal51ed764111e345fc11534f121cfeb451)): ?>
<?php $component = $__componentOriginal51ed764111e345fc11534f121cfeb451; ?>
<?php unset($__componentOriginal51ed764111e345fc11534f121cfeb451); ?>
<?php endif; ?>
                                    <?php if($refund['is_refunded']): ?>
                                        <?php if (isset($component)) { $__componentOriginal2ddbc40e602c342e508ac696e52f8719 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2ddbc40e602c342e508ac696e52f8719 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.badge','data' => ['tone' => 'neutral']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['tone' => 'neutral']); ?><?php echo e(__('payments.refunded')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2ddbc40e602c342e508ac696e52f8719)): ?>
<?php $attributes = $__attributesOriginal2ddbc40e602c342e508ac696e52f8719; ?>
<?php unset($__attributesOriginal2ddbc40e602c342e508ac696e52f8719); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2ddbc40e602c342e508ac696e52f8719)): ?>
<?php $component = $__componentOriginal2ddbc40e602c342e508ac696e52f8719; ?>
<?php unset($__componentOriginal2ddbc40e602c342e508ac696e52f8719); ?>
<?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="p-4 text-end">
                                <?php if($payment->status === 'paid' && ! $refund['is_refunded']): ?>
                                    <form method="POST" action="<?php echo e(route('admin.payments.refund', $payment)); ?>"
                                          data-confirm="<?php echo e(__('payments.refund_confirm', ['amount' => \App\Payments\Money::format($payment->amount), 'reference' => $payment->request?->reference])); ?>">
                                        <?php echo csrf_field(); ?>
                                        <?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['type' => 'submit','variant' => 'danger','size' => 'sm']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'submit','variant' => 'danger','size' => 'sm']); ?><?php echo e(__('payments.refund_action')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $attributes = $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $component = $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="11"><?php if (isset($component)) { $__componentOriginal4f22a152e0729cd34293e65bd200d933 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4f22a152e0729cd34293e65bd200d933 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.empty','data' => ['icon' => 'wallet','title' => __('payments.no_payments'),'message' => __('payments.no_payments_hint')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('empty'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'wallet','title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('payments.no_payments')),'message' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('payments.no_payments_hint'))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal4f22a152e0729cd34293e65bd200d933)): ?>
<?php $attributes = $__attributesOriginal4f22a152e0729cd34293e65bd200d933; ?>
<?php unset($__attributesOriginal4f22a152e0729cd34293e65bd200d933); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal4f22a152e0729cd34293e65bd200d933)): ?>
<?php $component = $__componentOriginal4f22a152e0729cd34293e65bd200d933; ?>
<?php unset($__componentOriginal4f22a152e0729cd34293e65bd200d933); ?>
<?php endif; ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        
        <div class="divide-y divide-line sm:hidden" data-mobile-rows>
            <?php $__empty_1 = true; $__currentLoopData = $payments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $payment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <?php ($refund = $refundMap[$payment->id] ?? ['refunded' => null, 'is_refunded' => false]); ?>
                <?php if (isset($component)) { $__componentOriginal00be98f2cda19e6dc66afc21b5371ed8 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal00be98f2cda19e6dc66afc21b5371ed8 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.table-card','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('table-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
                     <?php $__env->slot('heading', null, []); ?> 
                        <div class="font-bold"><?php echo e($payment->request?->category?->localized_name ?? __('payments.not_available')); ?></div>
                        <div class="t-small"><?php echo e($payment->request?->reference); ?></div>
                     <?php $__env->endSlot(); ?>
                     <?php $__env->slot('trailing', null, []); ?> 
                        <div class="font-black tabular-nums"><?php if (isset($component)) { $__componentOriginal3c51c5b308d311657bfae6be692a1470 = $component; } ?>
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
<?php endif; ?></div>
                        <div class="t-small"><?php echo e(__('payments.col_gross')); ?></div>
                     <?php $__env->endSlot(); ?>
                     <?php $__env->slot('rows', null, []); ?> 
                        <?php if (isset($component)) { $__componentOriginal7477fcaca32db0e891cf0760c5afab81 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7477fcaca32db0e891cf0760c5afab81 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.table-card-row','data' => ['label' => __('payments.col_customer')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('table-card-row'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('payments.col_customer'))]); ?><?php echo e($payment->customer?->name ?? __('reviews.anonymous')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal7477fcaca32db0e891cf0760c5afab81)): ?>
<?php $attributes = $__attributesOriginal7477fcaca32db0e891cf0760c5afab81; ?>
<?php unset($__attributesOriginal7477fcaca32db0e891cf0760c5afab81); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal7477fcaca32db0e891cf0760c5afab81)): ?>
<?php $component = $__componentOriginal7477fcaca32db0e891cf0760c5afab81; ?>
<?php unset($__componentOriginal7477fcaca32db0e891cf0760c5afab81); ?>
<?php endif; ?>
                        <?php if (isset($component)) { $__componentOriginal7477fcaca32db0e891cf0760c5afab81 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7477fcaca32db0e891cf0760c5afab81 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.table-card-row','data' => ['label' => __('payments.col_worker')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('table-card-row'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('payments.col_worker'))]); ?><?php echo e($payment->worker?->name ?? __('payments.not_available')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal7477fcaca32db0e891cf0760c5afab81)): ?>
<?php $attributes = $__attributesOriginal7477fcaca32db0e891cf0760c5afab81; ?>
<?php unset($__attributesOriginal7477fcaca32db0e891cf0760c5afab81); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal7477fcaca32db0e891cf0760c5afab81)): ?>
<?php $component = $__componentOriginal7477fcaca32db0e891cf0760c5afab81; ?>
<?php unset($__componentOriginal7477fcaca32db0e891cf0760c5afab81); ?>
<?php endif; ?>
                        <?php if (isset($component)) { $__componentOriginal7477fcaca32db0e891cf0760c5afab81 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7477fcaca32db0e891cf0760c5afab81 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.table-card-row','data' => ['label' => __('payments.col_rate')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('table-card-row'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('payments.col_rate'))]); ?><?php echo e(\App\Payments\Money::ratePercent($payment->commission_rate) ?? __('payments.not_available')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal7477fcaca32db0e891cf0760c5afab81)): ?>
<?php $attributes = $__attributesOriginal7477fcaca32db0e891cf0760c5afab81; ?>
<?php unset($__attributesOriginal7477fcaca32db0e891cf0760c5afab81); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal7477fcaca32db0e891cf0760c5afab81)): ?>
<?php $component = $__componentOriginal7477fcaca32db0e891cf0760c5afab81; ?>
<?php unset($__componentOriginal7477fcaca32db0e891cf0760c5afab81); ?>
<?php endif; ?>
                        <?php if (isset($component)) { $__componentOriginal7477fcaca32db0e891cf0760c5afab81 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7477fcaca32db0e891cf0760c5afab81 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.table-card-row','data' => ['label' => __('payments.col_commission')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('table-card-row'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('payments.col_commission'))]); ?><?php if (isset($component)) { $__componentOriginal3c51c5b308d311657bfae6be692a1470 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3c51c5b308d311657bfae6be692a1470 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.money','data' => ['amount' => $payment->commission_amount]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('money'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['amount' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($payment->commission_amount)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3c51c5b308d311657bfae6be692a1470)): ?>
<?php $attributes = $__attributesOriginal3c51c5b308d311657bfae6be692a1470; ?>
<?php unset($__attributesOriginal3c51c5b308d311657bfae6be692a1470); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3c51c5b308d311657bfae6be692a1470)): ?>
<?php $component = $__componentOriginal3c51c5b308d311657bfae6be692a1470; ?>
<?php unset($__componentOriginal3c51c5b308d311657bfae6be692a1470); ?>
<?php endif; ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal7477fcaca32db0e891cf0760c5afab81)): ?>
<?php $attributes = $__attributesOriginal7477fcaca32db0e891cf0760c5afab81; ?>
<?php unset($__attributesOriginal7477fcaca32db0e891cf0760c5afab81); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal7477fcaca32db0e891cf0760c5afab81)): ?>
<?php $component = $__componentOriginal7477fcaca32db0e891cf0760c5afab81; ?>
<?php unset($__componentOriginal7477fcaca32db0e891cf0760c5afab81); ?>
<?php endif; ?>
                        <?php if (isset($component)) { $__componentOriginal7477fcaca32db0e891cf0760c5afab81 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7477fcaca32db0e891cf0760c5afab81 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.table-card-row','data' => ['label' => __('payments.col_net')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('table-card-row'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('payments.col_net'))]); ?><?php if (isset($component)) { $__componentOriginal3c51c5b308d311657bfae6be692a1470 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3c51c5b308d311657bfae6be692a1470 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.money','data' => ['amount' => $payment->worker_net]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('money'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['amount' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($payment->worker_net)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3c51c5b308d311657bfae6be692a1470)): ?>
<?php $attributes = $__attributesOriginal3c51c5b308d311657bfae6be692a1470; ?>
<?php unset($__attributesOriginal3c51c5b308d311657bfae6be692a1470); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3c51c5b308d311657bfae6be692a1470)): ?>
<?php $component = $__componentOriginal3c51c5b308d311657bfae6be692a1470; ?>
<?php unset($__componentOriginal3c51c5b308d311657bfae6be692a1470); ?>
<?php endif; ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal7477fcaca32db0e891cf0760c5afab81)): ?>
<?php $attributes = $__attributesOriginal7477fcaca32db0e891cf0760c5afab81; ?>
<?php unset($__attributesOriginal7477fcaca32db0e891cf0760c5afab81); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal7477fcaca32db0e891cf0760c5afab81)): ?>
<?php $component = $__componentOriginal7477fcaca32db0e891cf0760c5afab81; ?>
<?php unset($__componentOriginal7477fcaca32db0e891cf0760c5afab81); ?>
<?php endif; ?>
                        <?php if($refund['refunded'] !== null): ?>
                            <?php if (isset($component)) { $__componentOriginal7477fcaca32db0e891cf0760c5afab81 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7477fcaca32db0e891cf0760c5afab81 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.table-card-row','data' => ['label' => __('payments.col_refunded')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('table-card-row'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('payments.col_refunded'))]); ?><?php echo e($refund['refunded']); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal7477fcaca32db0e891cf0760c5afab81)): ?>
<?php $attributes = $__attributesOriginal7477fcaca32db0e891cf0760c5afab81; ?>
<?php unset($__attributesOriginal7477fcaca32db0e891cf0760c5afab81); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal7477fcaca32db0e891cf0760c5afab81)): ?>
<?php $component = $__componentOriginal7477fcaca32db0e891cf0760c5afab81; ?>
<?php unset($__componentOriginal7477fcaca32db0e891cf0760c5afab81); ?>
<?php endif; ?>
                        <?php endif; ?>
                        <?php if (isset($component)) { $__componentOriginal7477fcaca32db0e891cf0760c5afab81 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7477fcaca32db0e891cf0760c5afab81 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.table-card-row','data' => ['label' => __('payments.col_paid_date')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('table-card-row'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('payments.col_paid_date'))]); ?><?php if (isset($component)) { $__componentOriginal0da8235d2d96274f8680ccda5445231c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0da8235d2d96274f8680ccda5445231c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.date','data' => ['value' => $payment->paid_at]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('date'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($payment->paid_at)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal0da8235d2d96274f8680ccda5445231c)): ?>
<?php $attributes = $__attributesOriginal0da8235d2d96274f8680ccda5445231c; ?>
<?php unset($__attributesOriginal0da8235d2d96274f8680ccda5445231c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0da8235d2d96274f8680ccda5445231c)): ?>
<?php $component = $__componentOriginal0da8235d2d96274f8680ccda5445231c; ?>
<?php unset($__componentOriginal0da8235d2d96274f8680ccda5445231c); ?>
<?php endif; ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal7477fcaca32db0e891cf0760c5afab81)): ?>
<?php $attributes = $__attributesOriginal7477fcaca32db0e891cf0760c5afab81; ?>
<?php unset($__attributesOriginal7477fcaca32db0e891cf0760c5afab81); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal7477fcaca32db0e891cf0760c5afab81)): ?>
<?php $component = $__componentOriginal7477fcaca32db0e891cf0760c5afab81; ?>
<?php unset($__componentOriginal7477fcaca32db0e891cf0760c5afab81); ?>
<?php endif; ?>
                     <?php $__env->endSlot(); ?>
                     <?php $__env->slot('actions', null, []); ?> 
                        <?php if (isset($component)) { $__componentOriginal51ed764111e345fc11534f121cfeb451 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal51ed764111e345fc11534f121cfeb451 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.status','data' => ['type' => 'payment','value' => $payment->status]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('status'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'payment','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($payment->status)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal51ed764111e345fc11534f121cfeb451)): ?>
<?php $attributes = $__attributesOriginal51ed764111e345fc11534f121cfeb451; ?>
<?php unset($__attributesOriginal51ed764111e345fc11534f121cfeb451); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal51ed764111e345fc11534f121cfeb451)): ?>
<?php $component = $__componentOriginal51ed764111e345fc11534f121cfeb451; ?>
<?php unset($__componentOriginal51ed764111e345fc11534f121cfeb451); ?>
<?php endif; ?>
                        <?php if($refund['is_refunded']): ?>
                            <?php if (isset($component)) { $__componentOriginal2ddbc40e602c342e508ac696e52f8719 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2ddbc40e602c342e508ac696e52f8719 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.badge','data' => ['tone' => 'neutral']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['tone' => 'neutral']); ?><?php echo e(__('payments.refunded')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2ddbc40e602c342e508ac696e52f8719)): ?>
<?php $attributes = $__attributesOriginal2ddbc40e602c342e508ac696e52f8719; ?>
<?php unset($__attributesOriginal2ddbc40e602c342e508ac696e52f8719); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2ddbc40e602c342e508ac696e52f8719)): ?>
<?php $component = $__componentOriginal2ddbc40e602c342e508ac696e52f8719; ?>
<?php unset($__componentOriginal2ddbc40e602c342e508ac696e52f8719); ?>
<?php endif; ?>
                        <?php endif; ?>
                        <?php if($payment->status === 'paid' && ! $refund['is_refunded']): ?>
                            <form method="POST" action="<?php echo e(route('admin.payments.refund', $payment)); ?>" class="w-full"
                                  data-confirm="<?php echo e(__('payments.refund_confirm', ['amount' => \App\Payments\Money::format($payment->amount), 'reference' => $payment->request?->reference])); ?>">
                                <?php echo csrf_field(); ?>
                                <?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['type' => 'submit','variant' => 'danger','class' => 'w-full']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'submit','variant' => 'danger','class' => 'w-full']); ?><?php echo e(__('payments.refund_action')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $attributes = $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $component = $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
                            </form>
                        <?php endif; ?>
                     <?php $__env->endSlot(); ?>
                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal00be98f2cda19e6dc66afc21b5371ed8)): ?>
<?php $attributes = $__attributesOriginal00be98f2cda19e6dc66afc21b5371ed8; ?>
<?php unset($__attributesOriginal00be98f2cda19e6dc66afc21b5371ed8); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal00be98f2cda19e6dc66afc21b5371ed8)): ?>
<?php $component = $__componentOriginal00be98f2cda19e6dc66afc21b5371ed8; ?>
<?php unset($__componentOriginal00be98f2cda19e6dc66afc21b5371ed8); ?>
<?php endif; ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <?php if (isset($component)) { $__componentOriginal4f22a152e0729cd34293e65bd200d933 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4f22a152e0729cd34293e65bd200d933 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.empty','data' => ['icon' => 'wallet','title' => __('payments.no_payments'),'message' => __('payments.no_payments_hint')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('empty'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'wallet','title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('payments.no_payments')),'message' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('payments.no_payments_hint'))]); ?>
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
            <?php endif; ?>
        </div>
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

    <div class="mt-5"><?php echo e($payments->links()); ?></div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\mofak\Desktop\SALE7LI-v3\resources\views/admin/payments/index.blade.php ENDPATH**/ ?>