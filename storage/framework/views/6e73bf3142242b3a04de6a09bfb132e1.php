<?php ($mine = $message->sender_id === auth()->id()); ?>
<div class="flex <?php echo e($mine ? 'justify-end' : 'justify-start'); ?>" data-message-id="<?php echo e($message->id); ?>">
    <div class="max-w-[80%] rounded-2xl px-4 py-3 <?php echo e($mine ? 'bg-ink text-white' : 'border border-line bg-surface text-ink'); ?>">
        <div class="whitespace-pre-wrap text-sm"><?php echo e($message->body); ?></div>
        <?php if(!empty($message->attachments)): ?>
            <div class="mt-2 flex flex-wrap gap-2">
                <?php $__currentLoopData = $message->attachments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $path): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <a href="<?php echo e(route('attachments.message', [$message, $i])); ?>" target="_blank" rel="noopener"
                       class="inline-flex items-center gap-1 rounded-lg bg-black/10 px-2 py-1 text-xs font-semibold">
                        <?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'clipboard','class' => 'h-3 w-3']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'clipboard','class' => 'h-3 w-3']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?><?php echo e(__('messages.chat.view')); ?>

                    </a>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        <?php endif; ?>
        <div class="mt-1 text-[10px] opacity-60"><?php echo e($message->created_at->translatedFormat('M d · H:i')); ?></div>
    </div>
</div>
<?php /**PATH C:\Users\mofak\Desktop\SALE7LI-v3\resources\views/messages/_bubble.blade.php ENDPATH**/ ?>