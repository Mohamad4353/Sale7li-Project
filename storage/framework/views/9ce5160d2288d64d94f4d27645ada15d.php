<?php $__env->startSection('code', '500'); ?>
<?php $__env->startSection('title', app()->getLocale() === 'ar' ? 'حدث خطأ ما' : 'Something went wrong'); ?>
<?php $__env->startSection('message', app()->getLocale() === 'ar' ? 'حدث خطأ غير متوقع لدينا. يرجى المحاولة لاحقًا.' : 'An unexpected error occurred on our side. Please try again later.'); ?>

<?php echo $__env->make('errors.minimal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\mofak\Desktop\SALE7LI-v3\resources\views/errors/500.blade.php ENDPATH**/ ?>