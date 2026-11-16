<?php
    $user = auth()->user();
    $role = $user?->role;

    $navByRole = [
        'customer' => [
            ['label' => __('nav.dashboard'), 'route' => 'customer.dashboard', 'active' => 'customer.dashboard', 'icon' => 'grid'],
            ['label' => __('nav.services'), 'route' => 'services.index', 'active' => 'services.*', 'icon' => 'home'],
            ['label' => __('nav.professionals'), 'route' => 'workers.index', 'active' => 'workers.*', 'icon' => 'users'],
            ['label' => __('nav.requests'), 'route' => 'customer.requests.index', 'active' => 'customer.requests.*', 'icon' => 'clipboard'],
            ['label' => __('nav.messages'), 'route' => 'messages.index', 'active' => ['messages.*', 'customer.messages.*'], 'icon' => 'chat'],
            ['label' => __('nav.complaints'), 'route' => 'customer.complaints.index', 'active' => 'customer.complaints.*', 'icon' => 'flag'],
            ['label' => __('nav.favorites'), 'route' => 'customer.favorites.index', 'active' => 'customer.favorites.*', 'icon' => 'star'],
            ['label' => __('nav.addresses'), 'route' => 'customer.addresses.index', 'active' => 'customer.addresses.*', 'icon' => 'home'],
        ],
        'worker' => [
            ['label' => __('nav.dashboard'), 'route' => 'worker.dashboard', 'active' => 'worker.dashboard', 'icon' => 'grid'],
            ['label' => __('nav.jobs'), 'route' => 'worker.jobs.index', 'active' => 'worker.jobs.*', 'icon' => 'clipboard'],
            ['label' => __('nav.messages'), 'route' => 'messages.index', 'active' => ['messages.*', 'worker.messages.*'], 'icon' => 'chat'],
            ['label' => __('nav.services'), 'route' => 'worker.services.edit', 'active' => 'worker.services.*', 'icon' => 'sliders'],
            ['label' => __('nav.availability'), 'route' => 'worker.availability.edit', 'active' => 'worker.availability.*', 'icon' => 'calendar'],
            ['label' => __('nav.portfolio'), 'route' => 'worker.portfolio.index', 'active' => 'worker.portfolio.*', 'icon' => 'image'],
            ['label' => __('nav.earnings'), 'route' => 'worker.earnings', 'active' => 'worker.earnings', 'icon' => 'wallet'],
            ['label' => __('nav.complaints'), 'route' => 'worker.complaints.index', 'active' => 'worker.complaints.*', 'icon' => 'flag'],
            ['label' => __('nav.profile'), 'route' => 'worker.profile.edit', 'active' => 'worker.profile.*', 'icon' => 'user'],
        ],
        'admin' => [
            ['label' => __('nav.dashboard'), 'route' => 'admin.dashboard', 'active' => 'admin.dashboard', 'icon' => 'grid'],
            ['label' => __('nav.requests'), 'route' => 'admin.requests.index', 'active' => 'admin.requests.*', 'icon' => 'clipboard'],
            ['label' => __('nav.complaints'), 'route' => 'admin.complaints.index', 'active' => 'admin.complaints.*', 'icon' => 'flag'],
            ['label' => __('nav.payments'), 'route' => 'admin.payments.index', 'active' => ['admin.payments.*', 'admin.settings.*'], 'icon' => 'wallet'],
            ['label' => __('nav.users'), 'route' => 'admin.users.index', 'active' => 'admin.users.*', 'icon' => 'users'],
            ['label' => __('nav.services'), 'route' => 'admin.services.index', 'active' => 'admin.services.*', 'icon' => 'sliders'],
        ],
    ];
    $links = $navByRole[$role] ?? [];
    $unread = $user ? $user->notifications()->whereNull('read_at')->count() : 0;
    $unreadMessages = $user
        ? \App\Models\Message::query()
            ->whereNull('read_at')
            ->where('sender_id', '!=', $user->id)
            ->whereHas('conversation', fn ($q) => $q
                ->where('customer_id', $user->id)->orWhere('worker_id', $user->id))
            ->count()
        : 0;
    $otherLocale = app()->getLocale() === 'ar' ? 'en' : 'ar';
?>

<header x-data="{ open: false, menu: false }" @keydown.escape.window="open = false; menu = false" class="sticky top-0 z-50 border-b border-line bg-surface">
    <div class="container-app flex h-16 items-center justify-between gap-4">
        <div class="flex items-center gap-6">
            <a href="<?php echo e(route('dashboard')); ?>" class="flex items-center gap-2.5" aria-label="SALE7LI">
                <span class="brand-mark">7</span>
                <span class="text-lg font-extrabold tracking-[-.04em]">SALE<span class="text-brand">7</span>LI</span>
            </a>
            <nav class="hidden items-center gap-1 lg:flex" aria-label="<?php echo e(__('nav.primary')); ?>">
                <?php $__currentLoopData = $links; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $link): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <a href="<?php echo e(route($link['route'])); ?>" class="app-nav-link"
                       <?php if(request()->routeIs(...(array) $link['active'])): ?> aria-current="page" <?php endif; ?>>
                        <?php echo e($link['label']); ?>

                        <?php if($link['route'] === 'messages.index' && $unreadMessages): ?>
                            <span class="ms-1 grid h-4 min-w-4 place-items-center rounded-full bg-brand px-1 text-[10px] font-extrabold text-ink"><?php echo e($unreadMessages > 9 ? '9+' : $unreadMessages); ?></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </nav>
        </div>

        <div class="flex items-center gap-1.5">
            <a href="<?php echo e(route('notifications.index')); ?>"
               class="relative grid h-11 w-11 place-items-center rounded-xl border border-line bg-surface text-muted transition hover:border-line-strong hover:bg-surface-2 hover:text-ink"
               aria-label="<?php echo e(__('nav.notifications')); ?><?php echo e($unread ? ' ('.$unread.')' : ''); ?>">
                <?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'bell','class' => 'h-[18px] w-[18px]']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'bell','class' => 'h-[18px] w-[18px]']); ?>
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
                <?php if($unread): ?>
                    <span class="absolute -end-1 -top-1 grid h-4 min-w-4 place-items-center rounded-full bg-brand px-1 text-[10px] font-extrabold text-ink"><?php echo e($unread > 9 ? '9+' : $unread); ?></span>
                <?php endif; ?>
            </a>

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

            <div class="relative hidden lg:block" @keydown.escape="menu = false">
                <button @click="menu = !menu" class="flex min-h-[44px] items-center gap-2 rounded-xl border border-line bg-surface py-1.5 pe-2 ps-1.5 transition hover:border-line-strong hover:bg-surface-2"
                        :aria-expanded="menu" aria-haspopup="true">
                    <?php if (isset($component)) { $__componentOriginal8ca5b43b8fff8bb34ab2ba4eb4bdd67b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8ca5b43b8fff8bb34ab2ba4eb4bdd67b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.avatar','data' => ['name' => $user->name,'size' => 'sm']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('avatar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($user->name),'size' => 'sm']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8ca5b43b8fff8bb34ab2ba4eb4bdd67b)): ?>
<?php $attributes = $__attributesOriginal8ca5b43b8fff8bb34ab2ba4eb4bdd67b; ?>
<?php unset($__attributesOriginal8ca5b43b8fff8bb34ab2ba4eb4bdd67b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8ca5b43b8fff8bb34ab2ba4eb4bdd67b)): ?>
<?php $component = $__componentOriginal8ca5b43b8fff8bb34ab2ba4eb4bdd67b; ?>
<?php unset($__componentOriginal8ca5b43b8fff8bb34ab2ba4eb4bdd67b); ?>
<?php endif; ?>
                    <span class="max-w-[8rem] truncate text-[13px] font-bold"><?php echo e($user->name); ?></span>
                </button>
                <div x-show="menu" x-cloak x-transition @click.outside="menu = false"
                     class="absolute end-0 mt-2 w-52 rounded-2xl border border-line bg-surface p-2 shadow-pop">
                    <div class="px-3 py-2">
                        <p class="truncate text-sm font-bold"><?php echo e($user->name); ?></p>
                        <p class="truncate t-small"><?php echo e($user->email); ?></p>
                    </div>
                    <?php if($role === 'worker'): ?>
                        <a href="<?php echo e(route('worker.profile.edit')); ?>" class="app-drawer-link"><?php echo e(__('nav.profile')); ?></a>
                    <?php endif; ?>
                    <form method="POST" action="<?php echo e(route('logout')); ?>" data-no-busy>
                        <?php echo csrf_field(); ?>
                        <button class="app-drawer-link w-full text-rose-600 hover:bg-rose-50">
                            <?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'logout','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'logout','class' => 'h-4 w-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?><?php echo e(__('nav.logout')); ?>

                        </button>
                    </form>
                </div>
            </div>

            <button @click="open = !open" class="icon-button lg:hidden" :aria-expanded="open" aria-label="<?php echo e(__('nav.menu')); ?>">
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
        <nav class="container-app grid gap-1 py-4" aria-label="<?php echo e(__('nav.primary')); ?>">
            <?php $__currentLoopData = $links; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $link): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <a @click="open = false" href="<?php echo e(route($link['route'])); ?>" class="app-drawer-link"
                   <?php if(request()->routeIs(...(array) $link['active'])): ?> aria-current="page" <?php endif; ?>>
                    <?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => $link['icon'],'class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($link['icon']),'class' => 'h-4 w-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?><?php echo e($link['label']); ?>

                    <?php if($link['route'] === 'messages.index' && $unreadMessages): ?>
                        <span class="ms-auto rounded-full bg-brand px-1.5 text-[10px] font-extrabold text-ink"><?php echo e($unreadMessages); ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <a @click="open = false" href="<?php echo e(route('notifications.index')); ?>" class="app-drawer-link">
                <?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'bell','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'bell','class' => 'h-4 w-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?><?php echo e(__('nav.notifications')); ?>

                <?php if($unread): ?><span class="ms-auto rounded-full bg-brand px-1.5 text-[10px] font-extrabold text-ink"><?php echo e($unread); ?></span><?php endif; ?>
            </a>
            <form method="POST" action="<?php echo e(route('logout')); ?>" data-no-busy class="mt-1 border-t border-line pt-1">
                <?php echo csrf_field(); ?>
                <button class="app-drawer-link w-full text-rose-600 hover:bg-rose-50">
                    <?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'logout','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'logout','class' => 'h-4 w-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?><?php echo e(__('nav.logout')); ?>

                </button>
            </form>
        </nav>
    </div>
</header>
<?php /**PATH C:\Users\mofak\Desktop\SALE7LI-v3\resources\views/components/app-nav.blade.php ENDPATH**/ ?>