<?php $__env->startSection('content'); ?>
<?php ($title = __('payments.earnings_title')); ?>
<?php ($paidJobs = (int) $totals->jobs); ?>

<div class="container-app py-8 sm:py-10">
    <?php if (isset($component)) { $__componentOriginalf8d4ea307ab1e58d4e472a43c8548d8e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf8d4ea307ab1e58d4e472a43c8548d8e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.page-header','data' => ['eyebrow' => __('dashboard.worker_portal'),'title' => __('payments.earnings_title'),'subtitle' => __('payments.earnings_sub')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['eyebrow' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('dashboard.worker_portal')),'title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('payments.earnings_title')),'subtitle' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('payments.earnings_sub'))]); ?>
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

    <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <?php if (isset($component)) { $__componentOriginal3b387acd2c997737a257e1ec014549fd = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3b387acd2c997737a257e1ec014549fd = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat','data' => ['label' => __('payments.gross'),'value' => \App\Payments\Money::format($totals->gross)]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('payments.gross')),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(\App\Payments\Money::format($totals->gross))]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat','data' => ['label' => __('payments.platform_commission'),'value' => \App\Payments\Money::format($totals->commission)]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('payments.platform_commission')),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(\App\Payments\Money::format($totals->commission))]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat','data' => ['label' => __('payments.net_at_capture'),'value' => \App\Payments\Money::format($totals->net)]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('payments.net_at_capture')),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(\App\Payments\Money::format($totals->net))]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat','data' => ['label' => __('payments.retained'),'value' => \App\Payments\Money::format($retained),'hint' => __('payments.retained_hint')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('payments.retained')),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(\App\Payments\Money::format($retained)),'hint' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('payments.retained_hint'))]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat','data' => ['label' => __('payments.refunded'),'value' => \App\Payments\Money::format($refunded)]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('payments.refunded')),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(\App\Payments\Money::format($refunded))]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat','data' => ['label' => __('payments.paid_jobs'),'value' => $paidJobs]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('payments.paid_jobs')),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($paidJobs)]); ?>
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
        <h2 class="t-section px-6 pt-6"><?php echo e(__('payments.recent')); ?></h2>

        <?php if($payments->isEmpty()): ?>
            <?php if (isset($component)) { $__componentOriginal4f22a152e0729cd34293e65bd200d933 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4f22a152e0729cd34293e65bd200d933 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.empty','data' => ['icon' => 'wallet','title' => __('payments.no_paid_jobs'),'message' => __('payments.no_paid_jobs_hint')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('empty'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'wallet','title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('payments.no_paid_jobs')),'message' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('payments.no_paid_jobs_hint'))]); ?>
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
            
            <div class="table-wrap hidden sm:block mt-4">
                <table>
                    <thead class="bg-paper text-xs uppercase tracking-wide text-muted">
                        <tr>
                            <th class="p-4 font-semibold"><?php echo e(__('payments.col_service')); ?></th>
                            <th class="p-4 font-semibold"><?php echo e(__('payments.col_customer')); ?></th>
                            <th class="p-4 font-semibold text-end"><?php echo e(__('payments.col_gross')); ?></th>
                            <th class="p-4 font-semibold text-end"><?php echo e(__('payments.col_commission')); ?></th>
                            <th class="p-4 font-semibold text-end"><?php echo e(__('payments.col_net')); ?></th>
                            <th class="p-4 font-semibold text-end"><?php echo e(__('payments.col_rate')); ?></th>
                            <th class="p-4 font-semibold"><?php echo e(__('payments.col_paid_date')); ?></th>
                            <th class="p-4 font-semibold"><?php echo e(__('payments.col_status')); ?></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        <?php $__currentLoopData = $payments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $payment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td class="p-4">
                                    <div class="font-bold"><?php echo e($payment->request?->category?->localized_name ?? __('payments.not_available')); ?></div>
                                    <div class="t-small"><?php echo e($payment->request?->reference); ?></div>
                                </td>
                                <td class="p-4"><?php echo e($payment->customer?->name ?? __('reviews.anonymous')); ?></td>
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
                                <td class="p-4 text-end font-bold tabular-nums"><?php if (isset($component)) { $__componentOriginal3c51c5b308d311657bfae6be692a1470 = $component; } ?>
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
                                    <?php echo e(\App\Payments\Money::ratePercent($payment->commission_rate) ?? __('payments.not_available')); ?>

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
                                        <?php if($payment->refunds->firstWhere('status', 'completed')): ?>
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
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>

            
            <div class="mt-4 divide-y divide-line sm:hidden" data-mobile-rows>
                <?php $__currentLoopData = $payments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $payment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
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
<?php endif; ?></div>
                            <div class="t-small"><?php echo e(__('payments.col_net')); ?></div>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.table-card-row','data' => ['label' => __('payments.col_gross')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('table-card-row'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('payments.col_gross'))]); ?><?php if (isset($component)) { $__componentOriginal3c51c5b308d311657bfae6be692a1470 = $component; } ?>
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
                            <?php if($payment->refunds->firstWhere('status', 'completed')): ?>
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
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>

            <div class="p-5"><?php echo e($payments->links()); ?></div>
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

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\mofak\Desktop\SALE7LI-v3\resources\views/worker/earnings.blade.php ENDPATH**/ ?>