<?php
    $isRtl = app()->getLocale() === 'ar';
    $shell = trim($__env->yieldContent('shell')) ?: (auth()->check() ? 'app' : 'marketing');
?>
<!doctype html>
<html lang="<?php echo e(app()->getLocale()); ?>" dir="<?php echo e($isRtl ? 'rtl' : 'ltr'); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <meta name="description" content="<?php echo e(__('marketing.meta_description')); ?>">
    <title><?php echo e(($title ?? null) ? $title.' · SALE7LI' : config('app.name', 'SALE7LI')); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Noto+Kufi+Arabic:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    <?php echo $__env->yieldPushContent('head'); ?>
</head>
<body class="min-h-screen bg-paper text-ink">
    <div id="page-progress" aria-hidden="true"></div>
    <a href="#main" class="skip-link"><?php echo e(__('messages.skip_to_content')); ?></a>

    <?php if($shell === 'app' && auth()->check()): ?>
        <?php if (isset($component)) { $__componentOriginalbdec1eb7afa23623b27c63f424c1a1c3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalbdec1eb7afa23623b27c63f424c1a1c3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.app-nav','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('app-nav'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalbdec1eb7afa23623b27c63f424c1a1c3)): ?>
<?php $attributes = $__attributesOriginalbdec1eb7afa23623b27c63f424c1a1c3; ?>
<?php unset($__attributesOriginalbdec1eb7afa23623b27c63f424c1a1c3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalbdec1eb7afa23623b27c63f424c1a1c3)): ?>
<?php $component = $__componentOriginalbdec1eb7afa23623b27c63f424c1a1c3; ?>
<?php unset($__componentOriginalbdec1eb7afa23623b27c63f424c1a1c3); ?>
<?php endif; ?>
    <?php else: ?>
        <?php if (isset($component)) { $__componentOriginal1bd4c4b2161222d0752a9704f97f66a0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1bd4c4b2161222d0752a9704f97f66a0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.marketing-nav','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('marketing-nav'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal1bd4c4b2161222d0752a9704f97f66a0)): ?>
<?php $attributes = $__attributesOriginal1bd4c4b2161222d0752a9704f97f66a0; ?>
<?php unset($__attributesOriginal1bd4c4b2161222d0752a9704f97f66a0); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal1bd4c4b2161222d0752a9704f97f66a0)): ?>
<?php $component = $__componentOriginal1bd4c4b2161222d0752a9704f97f66a0; ?>
<?php unset($__componentOriginal1bd4c4b2161222d0752a9704f97f66a0); ?>
<?php endif; ?>
    <?php endif; ?>

    <?php if (isset($component)) { $__componentOriginal5168fdb0c14fd91c6598264bc4be63f2 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5168fdb0c14fd91c6598264bc4be63f2 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.flash','data' => ['shell' => $shell]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flash'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['shell' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($shell)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5168fdb0c14fd91c6598264bc4be63f2)): ?>
<?php $attributes = $__attributesOriginal5168fdb0c14fd91c6598264bc4be63f2; ?>
<?php unset($__attributesOriginal5168fdb0c14fd91c6598264bc4be63f2); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5168fdb0c14fd91c6598264bc4be63f2)): ?>
<?php $component = $__componentOriginal5168fdb0c14fd91c6598264bc4be63f2; ?>
<?php unset($__componentOriginal5168fdb0c14fd91c6598264bc4be63f2); ?>
<?php endif; ?>

    <main id="main"><?php echo e($slot ?? ''); ?><?php echo $__env->yieldContent('content'); ?></main>

    <?php if($shell !== 'app'): ?>
        <footer id="contact" class="mt-24 bg-ink text-white">
            <div class="container-x grid gap-10 py-14 sm:grid-cols-2 lg:grid-cols-[1.5fr_1fr_1fr_1fr]">
                <div>
                    <a href="<?php echo e(route('home')); ?>" class="flex items-center gap-2.5" aria-label="SALE7LI"><span class="brand-mark brand-mark-dark">7</span><span class="text-xl font-extrabold">SALE<span class="text-brand">7</span>LI</span></a>
                    <p class="mt-5 max-w-sm text-sm leading-7 text-white/70"><?php echo e(__('marketing.footer_description')); ?></p>
                </div>
                <div><h4 class="footer-heading"><?php echo e(__('nav.services')); ?></h4><div class="mt-4 flex flex-col gap-3 text-sm"><a href="<?php echo e(route('services.index')); ?>" class="footer-link"><?php echo e(__('marketing.all_services')); ?></a><a href="<?php echo e(route('home')); ?>#how" class="footer-link"><?php echo e(__('marketing.how_it_works')); ?></a></div></div>
                <div><h4 class="footer-heading"><?php echo e(__('marketing.platform')); ?></h4><div class="mt-4 flex flex-col gap-3 text-sm"><a href="<?php echo e(route('register')); ?>" class="footer-link"><?php echo e(__('marketing.join')); ?></a><a href="<?php echo e(route('login')); ?>" class="footer-link"><?php echo e(__('nav.login')); ?></a></div></div>
                <div><h4 class="footer-heading"><?php echo e(__('marketing.support')); ?></h4><div class="mt-4 text-sm leading-7 text-white/70"><?php ($supportEmail = config('sale7li.support_email')); ?><a href="mailto:<?php echo e($supportEmail); ?>" class="footer-link"><?php echo e($supportEmail); ?></a><br><?php echo e(__('marketing.support_hours')); ?></div></div>
            </div>
            <div class="border-t border-white/10"><div class="container-x flex flex-col gap-3 py-5 text-xs text-white/60 sm:flex-row sm:items-center sm:justify-between"><span>© <?php echo e(date('Y')); ?> SALE7LI. <?php echo e(__('marketing.rights')); ?></span><span><?php echo e(__('marketing.bilingual')); ?> · <?php echo e(__('marketing.secure_platform')); ?></span></div></div>
        </footer>
    <?php else: ?>
        <footer class="mt-16 border-t border-line">
            <div class="container-app flex flex-col gap-2 py-6 text-xs text-muted sm:flex-row sm:items-center sm:justify-between">
                <span>© <?php echo e(date('Y')); ?> SALE7LI</span>
                <span class="flex items-center gap-4">
                    <a href="<?php echo e(route('support')); ?>" class="font-bold transition hover:text-ink"><?php echo e(__('nav.support')); ?></a>
                    <span><?php echo e(__('marketing.secure_platform')); ?></span>
                </span>
            </div>
        </footer>
    <?php endif; ?>

    <?php if (isset($component)) { $__componentOriginalbd602bb95417eb29101c8ba335f03bf0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalbd602bb95417eb29101c8ba335f03bf0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.confirm-dialog','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('confirm-dialog'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalbd602bb95417eb29101c8ba335f03bf0)): ?>
<?php $attributes = $__attributesOriginalbd602bb95417eb29101c8ba335f03bf0; ?>
<?php unset($__attributesOriginalbd602bb95417eb29101c8ba335f03bf0); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalbd602bb95417eb29101c8ba335f03bf0)): ?>
<?php $component = $__componentOriginalbd602bb95417eb29101c8ba335f03bf0; ?>
<?php unset($__componentOriginalbd602bb95417eb29101c8ba335f03bf0); ?>
<?php endif; ?>

    
    <?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH C:\Users\mofak\Desktop\SALE7LI-v3\resources\views/layouts/app.blade.php ENDPATH**/ ?>