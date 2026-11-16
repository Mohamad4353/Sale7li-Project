<?php ($other = app()->getLocale() === 'ar' ? 'en' : 'ar'); ?>
<form method="POST" action="<?php echo e(route('lang')); ?>" data-no-busy <?php echo e($attributes); ?>>
    <?php echo csrf_field(); ?>
    <input type="hidden" name="locale" value="<?php echo e($other); ?>">
    <input type="hidden" name="redirect_to" value="<?php echo e(url()->current()); ?>">
    <button type="submit" class="lang-switch" aria-label="<?php echo e(__('nav.switch_language')); ?>">
        <?php echo e(app()->getLocale() === 'ar' ? 'EN' : 'ع'); ?>

    </button>
</form>
<?php /**PATH C:\Users\mofak\Desktop\SALE7LI-v3\resources\views/components/lang-switch.blade.php ENDPATH**/ ?>