<?php $__env->startSection('code', '419'); ?>
<?php $__env->startSection('title', app()->getLocale() === 'ar' ? 'انتهت صلاحية الصفحة' : 'Page expired'); ?>
<?php $__env->startSection('message', app()->getLocale() === 'ar' ? 'انتهت جلستك. يرجى تحديث الصفحة والمحاولة مرة أخرى.' : 'Your session expired. Please refresh and try again.'); ?>

<?php echo $__env->make('errors.minimal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\mofak\Desktop\SALE7LI-v3\resources\views/errors/419.blade.php ENDPATH**/ ?>