<?php $__env->startSection('code', '404'); ?>
<?php $__env->startSection('title', app()->getLocale() === 'ar' ? 'الصفحة غير موجودة' : 'Page not found'); ?>
<?php $__env->startSection('message', app()->getLocale() === 'ar' ? 'تعذر العثور على الصفحة التي تبحث عنها.' : 'The page you are looking for could not be found.'); ?>

<?php echo $__env->make('errors.minimal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\mofak\Desktop\SALE7LI-v3\resources\views/errors/404.blade.php ENDPATH**/ ?>