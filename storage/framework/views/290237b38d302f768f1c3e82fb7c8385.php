<?php ($otherLocale = app()->getLocale() === 'ar' ? 'en' : 'ar'); ?>
<header x-data="{ open: false }" @keydown.escape.window="open = false" class="sticky top-0 z-50 border-b border-line bg-paper">
    <div class="container-x flex h-16 items-center justify-between gap-6">
        <a href="<?php echo e(route('home')); ?>" class="flex items-center gap-2.5" aria-label="SALE7LI">
            <span class="brand-mark">7</span>
            <span class="text-[21px] font-extrabold tracking-[-.04em]">SALE<span class="text-brand">7</span>LI</span>
        </a>
        <nav class="hidden items-center gap-7 text-[13px] font-semibold text-muted lg:flex" aria-label="<?php echo e(__('nav.primary')); ?>">
            <a href="<?php echo e(route('home')); ?>" class="nav-link"><?php echo e(__('nav.home')); ?></a>
            <a href="<?php echo e(route('services.index')); ?>" class="nav-link"><?php echo e(__('nav.services')); ?></a>
            <a href="<?php echo e(route('workers.index')); ?>" class="nav-link"><?php echo e(__('nav.professionals')); ?></a>
            <a href="<?php echo e(route('home')); ?>#how" class="nav-link"><?php echo e(__('marketing.how_it_works')); ?></a>
            <a href="<?php echo e(route('home')); ?>#about" class="nav-link"><?php echo e(__('marketing.about')); ?></a>
        </nav>
        <div class="hidden items-center gap-2 lg:flex">
            <?php if(auth()->guard()->check()): ?>
                <a href="<?php echo e(route('dashboard')); ?>" class="btn-secondary"><?php echo e(__('nav.dashboard')); ?></a>
            <?php else: ?>
                <a href="<?php echo e(route('login')); ?>" class="px-3 py-2 text-[13px] font-bold text-muted transition hover:text-ink"><?php echo e(__('nav.login')); ?></a>
                <a href="<?php echo e(route('register')); ?>" class="btn-primary"><?php echo e(__('nav.register')); ?></a>
            <?php endif; ?>
            <?php if (isset($component)) { $__componentOriginal02dabe41e86d897cdaedd60d683d8855 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal02dabe41e86d897cdaedd60d683d8855 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.lang-switch','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('lang-switch'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal02dabe41e86d897cdaedd60d683d8855)): ?>
<?php $attributes = $__attributesOriginal02dabe41e86d897cdaedd60d683d8855; ?>
<?php unset($__attributesOriginal02dabe41e86d897cdaedd60d683d8855); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal02dabe41e86d897cdaedd60d683d8855)): ?>
<?php $component = $__componentOriginal02dabe41e86d897cdaedd60d683d8855; ?>
<?php unset($__componentOriginal02dabe41e86d897cdaedd60d683d8855); ?>
<?php endif; ?>
        </div>
        <div class="flex items-center gap-2 lg:hidden">
            <?php if (isset($component)) { $__componentOriginal02dabe41e86d897cdaedd60d683d8855 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal02dabe41e86d897cdaedd60d683d8855 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.lang-switch','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('lang-switch'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal02dabe41e86d897cdaedd60d683d8855)): ?>
<?php $attributes = $__attributesOriginal02dabe41e86d897cdaedd60d683d8855; ?>
<?php unset($__attributesOriginal02dabe41e86d897cdaedd60d683d8855); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal02dabe41e86d897cdaedd60d683d8855)): ?>
<?php $component = $__componentOriginal02dabe41e86d897cdaedd60d683d8855; ?>
<?php unset($__componentOriginal02dabe41e86d897cdaedd60d683d8855); ?>
<?php endif; ?>
            <button @click="open = !open" class="icon-button" :aria-expanded="open" aria-label="<?php echo e(__('nav.menu')); ?>">
                <?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'menu','class' => 'h-4 w-4','xShow' => '!open']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'menu','class' => 'h-4 w-4','x-show' => '!open']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
                <?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'close','class' => 'h-4 w-4','xShow' => 'open','xCloak' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'close','class' => 'h-4 w-4','x-show' => 'open','x-cloak' => true]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
            </button>
        </div>
    </div>
    <div x-show="open" x-cloak x-transition class="border-t border-line bg-surface lg:hidden">
        <nav class="container-x grid gap-1 py-4 text-sm font-bold" aria-label="<?php echo e(__('nav.primary')); ?>">
            <a @click="open = false" href="<?php echo e(route('home')); ?>" class="mobile-nav-link"><?php echo e(__('nav.home')); ?></a>
            <a @click="open = false" href="<?php echo e(route('services.index')); ?>" class="mobile-nav-link"><?php echo e(__('nav.services')); ?></a>
            <a @click="open = false" href="<?php echo e(route('workers.index')); ?>" class="mobile-nav-link"><?php echo e(__('nav.professionals')); ?></a>
            <a @click="open = false" href="<?php echo e(route('home')); ?>#how" class="mobile-nav-link"><?php echo e(__('marketing.how_it_works')); ?></a>
            <a @click="open = false" href="<?php echo e(route('home')); ?>#about" class="mobile-nav-link"><?php echo e(__('marketing.about')); ?></a>
            <?php if(auth()->guard()->check()): ?>
                <a href="<?php echo e(route('dashboard')); ?>" class="mobile-nav-link"><?php echo e(__('nav.dashboard')); ?></a>
            <?php else: ?>
                <a href="<?php echo e(route('login')); ?>" class="mobile-nav-link"><?php echo e(__('nav.login')); ?></a>
                <a href="<?php echo e(route('register')); ?>" class="mobile-nav-link"><?php echo e(__('nav.register')); ?></a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<?php /**PATH C:\Users\mofak\Desktop\SALE7LI-v3\resources\views/components/marketing-nav.blade.php ENDPATH**/ ?>