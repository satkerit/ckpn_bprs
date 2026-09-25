@php
    $navGroups = [
        'Ringkasan' => array_values(array_filter([
            ['route' => 'dashboard', 'label' => 'Dashboard', 'can' => 'dashboard.view', 'icon' => 'home'],
            ['route' => 'upload.riwayat', 'label' => 'Riwayat Upload', 'can' => 'dashboard.view', 'icon' => 'history'],
            ['route' => 'periode.index', 'label' => 'Periode CKPN', 'can' => 'periode.view', 'icon' => 'calendar'],
        ])),
        'Data' => array_values(array_filter([
            ['route' => 'data.index', 'params' => ['jenis' => 'pembiayaan'], 'label' => 'Pembiayaan', 'can' => 'upload.pembiayaan', 'icon' => 'upload'],
            ['route' => 'data.index', 'params' => ['jenis' => 'history'], 'label' => 'History Pembiayaan', 'can' => 'upload.history', 'icon' => 'history'],
            ['route' => 'data.index', 'params' => ['jenis' => 'jaminan'], 'label' => 'Data Jaminan', 'can' => 'upload.jaminan', 'icon' => 'shield'],
            ['route' => 'data.index', 'params' => ['jenis' => 'kantor'], 'label' => 'Data Kantor', 'can' => 'upload.kantor', 'icon' => 'home'],
            ['route' => 'data.index', 'params' => ['jenis' => 'produk'], 'label' => 'Data Produk', 'can' => 'upload.produk', 'icon' => 'clipboard'],
        ])),
        'Master Data' => array_values(array_filter([
            ['route' => 'master.kode-akad', 'label' => 'Kode Akad', 'can' => 'setup.manage', 'icon' => 'key'],
            ['route' => 'setup.ckpn-role', 'label' => 'Role CKPN', 'can' => 'setup.manage', 'icon' => 'clipboard'],
            ['route' => 'data.index', 'params' => ['jenis' => 'jaminan_setup'], 'label' => 'Setup Jaminan', 'can' => 'upload.jaminan_setup', 'icon' => 'shield'],
        ])),
        'Perhitungan' => array_values(array_filter([
            ['route' => 'pd.netflow', 'label' => 'PD Netflow', 'can' => 'pd.view', 'icon' => 'chart'],
            ['route' => 'pd.migration', 'label' => 'PD Migration', 'can' => 'pd.view', 'icon' => 'arrows'],
            ['route' => 'lgd.index', 'label' => 'LGD', 'can' => 'lgd.view', 'icon' => 'shield'],
            ['route' => 'klasifikasi.index', 'label' => 'Klasifikasi CKPN', 'can' => 'klasifikasi.view', 'icon' => 'clipboard'],
            ['route' => 'ckpn.index', 'label' => 'Perhitungan CKPN', 'can' => 'ckpn.view', 'icon' => 'calculator'],
        ])),
        'Administrasi' => array_values(array_filter([
            ['route' => 'admin.users', 'label' => 'User', 'can' => 'user.manage', 'icon' => 'users'],
            ['route' => 'admin.roles', 'label' => 'Role', 'can' => 'role.manage', 'icon' => 'key'],
            ['route' => 'admin.permissions', 'label' => 'Permission', 'can' => 'permission.manage', 'icon' => 'lock'],
            ['route' => 'admin.audit', 'label' => 'Audit Log', 'can' => 'audit.view', 'icon' => 'clipboard'],
        ])),
    ];

    $renderNavigation = function () use ($navGroups): string {
        $html = '';
        foreach ($navGroups as $groupLabel => $items) {
            $visibleItems = array_values(array_filter($items, fn (array $item): bool => auth()->user()?->can($item['can'])));
            if ($visibleItems === []) {
                continue;
            }
            $html .= '<div><p class="px-3 pb-2 text-[0.65rem] font-semibold uppercase tracking-widest text-surface-400/60">'.e($groupLabel).'</p><div class="space-y-1">';
            foreach ($visibleItems as $item) {
                $href = route($item['route'], $item['params'] ?? []);
                $routeName = $item['route'];
                $routeParams = $item['params'] ?? [];
                $isActive = request()->routeIs($routeName) && (empty($routeParams) || (request()->segment(2) === ($routeParams['jenis'] ?? null)));
                $html .= '<a href="'.$href.'"'.($isActive ? ' aria-current="page"' : '').' class="nav-link '.($isActive ? 'nav-link-active' : '').'">';
                $html .= view('components.nav-icon', ['name' => $item['icon']])->render();
                $html .= '<span>'.e($item['label']).'</span></a>';
            }
            $html .= '</div></div>';
        }
        return $html;
    };
@endphp

<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#020617">
    <title>{{ $title ?? config('app.name', 'CKPN BPRS') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-full bg-surface-100 text-surface-900">
    <a href="#konten-utama"
       class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-primary-600 focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-white">
        Lewati ke konten utama
    </a>

    <div class="flex min-h-screen">
        {{-- Sidebar desktop --}}
        <aside class="hidden w-64 shrink-0 flex-col bg-surface-950 md:flex" aria-label="Navigasi utama">
            <div class="flex items-center gap-3 border-b border-white/10 px-5 py-5">
                <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary-600 text-sm font-bold text-white" aria-hidden="true">CB</span>
                <div>
                    <p class="text-[0.65rem] font-semibold uppercase tracking-widest text-primary-400">CKPN</p>
                    <h1 class="text-base font-bold leading-tight text-white">BPRS Babel</h1>
                </div>
            </div>

            <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-5 text-sm">
                {!! $renderNavigation() !!}
            </nav>

            <div class="border-t border-white/10 px-5 py-4 text-xs text-surface-400/70">
                {{ now()->translatedFormat('F Y') }}
            </div>
        </aside>

        {{-- Sidebar mobile --}}
        <div data-mobile-navigation class="hidden fixed inset-0 z-40 md:hidden" role="dialog" aria-modal="true" aria-label="Navigasi utama">
            <div class="absolute inset-0 bg-surface-950/60"></div>
            <aside class="absolute inset-y-0 left-0 flex w-72 max-w-[85%] flex-col bg-surface-950">
                <div class="flex items-center justify-between border-b border-white/10 px-5 py-4">
                    <div class="flex items-center gap-3">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary-600 text-sm font-bold text-white" aria-hidden="true">CB</span>
                        <h1 class="text-base font-bold text-white">BPRS Babel</h1>
                    </div>
                    <button type="button" data-close-navigation
                            class="rounded-lg p-2.5 text-surface-300 transition hover:bg-surface-800 hover:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                            aria-label="Tutup navigasi">
                        <x-nav-icon name="close" />
                    </button>
                </div>

                <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-5 text-sm">
                    {!! $renderNavigation() !!}
                </nav>
            </aside>
        </div>

        {{-- Konten --}}
        <div class="flex min-w-0 flex-1 flex-col">
            <header class="sticky top-0 z-30 flex items-center justify-between gap-4 border-b border-surface-200 bg-white/90 px-4 py-3 backdrop-blur md:px-6">
                <div class="flex min-w-0 items-center gap-3">
                    <button type="button" data-open-navigation
                            class="rounded-lg border border-surface-300 p-2.5 text-surface-700 transition hover:bg-surface-50 focus:outline-none focus:ring-2 focus:ring-primary-500 md:hidden"
                            aria-label="Buka navigasi">
                        <x-nav-icon name="menu" />
                    </button>
                    <div class="min-w-0">
                        <h2 class="truncate text-base font-semibold text-surface-900">{{ $title ?? 'Dashboard' }}</h2>
                        <p class="truncate text-xs text-surface-500">{{ now()->translatedFormat('l, d F Y') }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <div class="hidden text-right sm:block">
                        <p class="text-sm font-semibold leading-tight text-surface-900">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-surface-500">{{ auth()->user()->getRoleNames()->first() ?? 'Pengguna' }}</p>
                    </div>
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-primary-100 text-sm font-bold text-primary-700" aria-hidden="true">
                        {{ strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}
                    </span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="btn btn-secondary px-3 py-2">Keluar</button>
                    </form>
                </div>
            </header>

            <main id="konten-utama" class="flex-1 p-4 md:p-6">
                @if (session('status'))
                    <x-alert type="success" class="mb-4">{{ session('status') }}</x-alert>
                @endif
                @if (session('error'))
                    <x-alert type="danger" class="mb-4">{{ session('error') }}</x-alert>
                @endif

                @hasSection('content')
                    @yield('content')
                @else
                    {{ $slot ?? '' }}
                @endif
            </main>
        </div>
    </div>

    @livewireScripts
    <x-upload-progress-dialog />
</body>
</html>
