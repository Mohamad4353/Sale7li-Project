<?php $__env->startSection('code', '429'); ?>
<?php $__env->startSection('title', app()->getLocale() === 'ar' ? 'طلبات كثيرة جدًا' : 'Too many requests'); ?>
<?php $__env->startSection('message', app()->getLocale() === 'ar' ? 'لقد أرسلت طلبات كثيرة. يرجى الانتظار قليلاً ثم المحاولة مجددًا.' : 'You have made too many requests. Please wait a moment and try again.'); ?>

<?php echo $__env->make('errors.minimal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\mofak\Desktop\SALE7LI-v3\resources\views/errors/429.blade.php ENDPATH**/ ?>