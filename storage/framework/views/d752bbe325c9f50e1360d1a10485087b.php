<?php $__env->startSection('code', '403'); ?>
<?php $__env->startSection('title', app()->getLocale() === 'ar' ? 'غير مسموح' : 'Forbidden'); ?>
<?php $__env->startSection('message', app()->getLocale() === 'ar' ? 'ليس لديك إذن للوصول إلى هذه الصفحة.' : 'You do not have permission to access this page.'); ?>

<?php echo $__env->make('errors.minimal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\mofak\Desktop\SALE7LI-v3\resources\views/errors/403.blade.php ENDPATH**/ ?>