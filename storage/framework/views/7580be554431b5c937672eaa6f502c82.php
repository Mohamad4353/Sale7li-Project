
<?php ($showPrice = $showPrice ?? false); ?>
<?php ($showRating = $showRating ?? false); ?>

<div class="space-y-6">
    <div>
        <p class="field-label"><?php echo e(__('services.category')); ?></p>
        <div class="mt-2 space-y-1.5">
            <label class="flex cursor-pointer items-center gap-2 text-sm">
                <input type="radio" name="category" value="" <?php if(! $filters->category): echo 'checked'; endif; ?> class="border-line">
                <?php echo e(__('services.all_categories')); ?>

            </label>
            <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <label class="flex cursor-pointer items-center gap-2 text-sm">
                    <input type="radio" name="category" value="<?php echo e($c->slug); ?>" <?php if($filters->category === $c->slug): echo 'checked'; endif; ?> class="border-line">
                    <span><?php echo e($c->icon); ?> <?php echo e($c->localized_name); ?></span>
                </label>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>

    <?php if($showPrice): ?>
        <div>
            <p class="field-label"><?php echo e(__('services.price')); ?> ($)</p>
            <div class="mt-2 flex items-center gap-2">
                <label class="sr-only" for="mf-min"><?php echo e(__('services.min_price')); ?></label>
                <input id="mf-min" type="number" inputmode="numeric" min="0" name="min_price" value="<?php echo e($filters->minPrice); ?>"
                       placeholder="<?php echo e($priceBounds[0] ?? __('services.min_price')); ?>" class="input">
                <span class="text-faint">–</span>
                <label class="sr-only" for="mf-max"><?php echo e(__('services.max_price')); ?></label>
                <input id="mf-max" type="number" inputmode="numeric" min="0" name="max_price" value="<?php echo e($filters->maxPrice); ?>"
                       placeholder="<?php echo e($priceBounds[1] ?? __('services.max_price')); ?>" class="input">
            </div>
        </div>
    <?php endif; ?>

    <?php if($showRating): ?>
        <div>
            <p class="field-label"><?php echo e(__('workers.min_rating')); ?></p>
            <div class="mt-2 space-y-1.5">
                <label class="flex cursor-pointer items-center gap-2 text-sm">
                    <input type="radio" name="min_rating" value="" <?php if(! $filters->minRating): echo 'checked'; endif; ?> class="border-line">
                    <?php echo e(__('workers.rating_any')); ?>

                </label>
                <?php $__currentLoopData = [5, 4, 3]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <label class="flex cursor-pointer items-center gap-2 text-sm">
                        <input type="radio" name="min_rating" value="<?php echo e($r); ?>" <?php if($filters->minRating === $r): echo 'checked'; endif; ?> class="border-line">
                        <?php if (isset($component)) { $__componentOriginaldaaa4f7e4868d5d2754fc46560cdd197 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginaldaaa4f7e4868d5d2754fc46560cdd197 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.rating','data' => ['value' => $r,'showValue' => false]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('rating'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($r),'showValue' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(false)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginaldaaa4f7e4868d5d2754fc46560cdd197)): ?>
<?php $attributes = $__attributesOriginaldaaa4f7e4868d5d2754fc46560cdd197; ?>
<?php unset($__attributesOriginaldaaa4f7e4868d5d2754fc46560cdd197); ?>
<?php endif; ?>
<?php if (isset($__componentOriginaldaaa4f7e4868d5d2754fc46560cdd197)): ?>
<?php $component = $__componentOriginaldaaa4f7e4868d5d2754fc46560cdd197; ?>
<?php unset($__componentOriginaldaaa4f7e4868d5d2754fc46560cdd197); ?>
<?php endif; ?>
                        <span><?php echo e(__('workers.rating_stars', ['n' => $r])); ?></span>
                    </label>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="flex flex-col gap-2 border-t border-line pt-4">
        <?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['type' => 'submit','size' => 'sm']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'submit','size' => 'sm']); ?><?php echo e(__('services.apply')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $attributes = $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $component = $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
        <?php if($filters->isActive()): ?>
            <a href="<?php echo e(url()->current()); ?>" class="text-center text-[13px] font-bold text-muted transition hover:text-ink">
                <?php echo e(__('services.clear_filters')); ?>

            </a>
        <?php endif; ?>
    </div>
</div>
<?php /**PATH C:\Users\mofak\Desktop\SALE7LI-v3\resources\views/partials/marketplace-filters.blade.php ENDPATH**/ ?>