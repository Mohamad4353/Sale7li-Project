@php
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
@endphp

<header x-data="{ open: false, menu: false }" @keydown.escape.window="open = false; menu = false" class="sticky top-0 z-50 border-b border-line bg-surface">
    <div class="container-app flex h-16 items-center justify-between gap-4">
        <div class="flex items-center gap-6">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5" aria-label="SALE7LI">
                <span class="brand-mark">7</span>
                <span class="text-lg font-extrabold tracking-[-.04em]">SALE<span class="text-brand">7</span>LI</span>
            </a>
            <nav class="hidden items-center gap-1 lg:flex" aria-label="{{ __('nav.primary') }}">
                @foreach ($links as $link)
                    <a href="{{ route($link['route']) }}" class="app-nav-link"
                       @if (request()->routeIs(...(array) $link['active'])) aria-current="page" @endif>
                        {{ $link['label'] }}
                        @if ($link['route'] === 'messages.index' && $unreadMessages)
                            <span class="ms-1 grid h-4 min-w-4 place-items-center rounded-full bg-brand px-1 text-[10px] font-extrabold text-ink">{{ $unreadMessages > 9 ? '9+' : $unreadMessages }}</span>
                        @endif
                    </a>
                @endforeach
            </nav>
        </div>

        <div class="flex items-center gap-1.5">
            <a href="{{ route('notifications.index') }}"
               class="relative grid h-11 w-11 place-items-center rounded-xl border border-line bg-surface text-muted transition hover:border-line-strong hover:bg-surface-2 hover:text-ink"
               aria-label="{{ __('nav.notifications') }}{{ $unread ? ' ('.$unread.')' : '' }}">
                <x-icon name="bell" class="h-[18px] w-[18px]" />
                @if ($unread)
                    <span class="absolute -end-1 -top-1 grid h-4 min-w-4 place-items-center rounded-full bg-brand px-1 text-[10px] font-extrabold text-ink">{{ $unread > 9 ? '9+' : $unread }}</span>
                @endif
            </a>

            <x-lang-switch />

            <div class="relative hidden lg:block" @keydown.escape="menu = false">
                <button @click="menu = !menu" class="flex min-h-[44px] items-center gap-2 rounded-xl border border-line bg-surface py-1.5 pe-2 ps-1.5 transition hover:border-line-strong hover:bg-surface-2"
                        :aria-expanded="menu" aria-haspopup="true">
                    <x-avatar :name="$user->name" size="sm" />
                    <span class="max-w-[8rem] truncate text-[13px] font-bold">{{ $user->name }}</span>
                </button>
                <div x-show="menu" x-cloak x-transition @click.outside="menu = false"
                     class="absolute end-0 mt-2 w-52 rounded-2xl border border-line bg-surface p-2 shadow-pop">
                    <div class="px-3 py-2">
                        <p class="truncate text-sm font-bold">{{ $user->name }}</p>
                        <p class="truncate t-small">{{ $user->email }}</p>
                    </div>
                    @if ($role === 'worker')
                        <a href="{{ route('worker.profile.edit') }}" class="app-drawer-link">{{ __('nav.profile') }}</a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}" data-no-busy>
                        @csrf
                        <button class="app-drawer-link w-full text-rose-600 hover:bg-rose-50">
                            <x-icon name="logout" class="h-4 w-4" />{{ __('nav.logout') }}
                        </button>
                    </form>
                </div>
            </div>

            <button @click="open = !open" class="icon-button lg:hidden" :aria-expanded="open" aria-label="{{ __('nav.menu') }}">
                <x-icon name="menu" class="h-4 w-4" x-show="!open" />
                <x-icon name="close" class="h-4 w-4" x-show="open" x-cloak />
            </button>
        </div>
    </div>

    <div x-show="open" x-cloak x-transition class="border-t border-line bg-surface lg:hidden">
        <nav class="container-app grid gap-1 py-4" aria-label="{{ __('nav.primary') }}">
            @foreach ($links as $link)
                <a @click="open = false" href="{{ route($link['route']) }}" class="app-drawer-link"
                   @if (request()->routeIs(...(array) $link['active'])) aria-current="page" @endif>
                    <x-icon :name="$link['icon']" class="h-4 w-4" />{{ $link['label'] }}
                    @if ($link['route'] === 'messages.index' && $unreadMessages)
                        <span class="ms-auto rounded-full bg-brand px-1.5 text-[10px] font-extrabold text-ink">{{ $unreadMessages }}</span>
                    @endif
                </a>
            @endforeach
            <a @click="open = false" href="{{ route('notifications.index') }}" class="app-drawer-link">
                <x-icon name="bell" class="h-4 w-4" />{{ __('nav.notifications') }}
                @if ($unread)<span class="ms-auto rounded-full bg-brand px-1.5 text-[10px] font-extrabold text-ink">{{ $unread }}</span>@endif
            </a>
            <form method="POST" action="{{ route('logout') }}" data-no-busy class="mt-1 border-t border-line pt-1">
                @csrf
                <button class="app-drawer-link w-full text-rose-600 hover:bg-rose-50">
                    <x-icon name="logout" class="h-4 w-4" />{{ __('nav.logout') }}
                </button>
            </form>
        </nav>
    </div>
</header>
