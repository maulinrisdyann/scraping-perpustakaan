{{-- Overlay gelap di belakang sidebar saat dibuka di mobile --}}
<div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false" class="fixed inset-0 bg-black/60 z-40 lg:hidden"
    x-transition:enter="transition-opacity ease-out duration-200" x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity ease-in duration-150"
    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>

<aside class="w-64 shrink-0 bg-surface/95 lg:bg-surface/60 border-r border-black/10 dark:border-white/10 flex flex-col
           fixed inset-y-0 left-0 z-50 lg:sticky lg:top-0 lg:h-screen lg:z-auto
           transition-transform duration-200 ease-out
           -translate-x-full lg:translate-x-0" :class="sidebarOpen && '!translate-x-0'">
    <div class="px-6 py-6 border-b border-black/10 dark:border-white/10 flex items-center justify-between">
        <div class="flex items-center gap-2.5 min-w-0">

            <div class="w-9 h-9 flex items-center justify-center shrink-0">
                <img src="{{ asset('images/logo-kota.png') }}" alt="Logo Perpusip Magelang"
                    class="w-9 h-9 object-contain">
            </div>

            <div class="min-w-0">
                <p class="font-display font-semibold text-sm text-txprimary leading-tight truncate">Disperpusip Kota Magelang
                </p>
                <p class="text-xs text-txsecondary leading-tight">Ulasan &amp; Sentimen</p>
            </div>
        </div>
        <button type="button" @click="sidebarOpen = false"
            class="lg:hidden shrink-0 w-8 h-8 flex items-center justify-center rounded-lg text-txsecondary hover:text-txprimary hover:bg-black/5 dark:hover:bg-white/5"
            aria-label="Tutup menu">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <nav class="flex-1 py-4 px-3 space-y-1">
        @php
            $pendingComplaintCount = \App\Models\Complaint::where('status', 'belum_dibalas')->count();
            $navItems = [
                ['route' => 'dashboard.overview', 'label' => 'Overview', 'icon' => 'fa-chart-pie'],
                ['route' => 'reviews.index', 'label' => 'Ulasan', 'icon' => 'fa-comments'],
                ['route' => 'complaints.index', 'label' => 'Komplain', 'icon' => 'fa-triangle-exclamation', 'badge' => $pendingComplaintCount],
                ['route' => 'tracked-urls.index', 'label' => 'Sumber Pantauan', 'icon' => 'fa-link'],
            ];
            if (auth()->user()?->isSuperAdmin()) {
                $navItems[] = ['route' => 'settings.index', 'label' => 'Pengaturan', 'icon' => 'fa-gear'];
                $navItems[] = ['route' => 'activity-logs.index', 'label' => 'History Log', 'icon' => 'fa-clock-rotate-left'];
            }
        @endphp

        @foreach ($navItems as $item)
            @php $active = request()->routeIs($item['route']); @endphp
            <a href="{{ route($item['route']) }}"
                class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm transition-colors relative
                          {{ $active ? 'bg-black/5 dark:bg-white/5 text-txprimary' : 'text-txsecondary hover:text-txprimary hover:bg-black/5 dark:hover:bg-white/5' }}">
                @if ($active)
                    <span
                        class="absolute right-0 top-1.5 bottom-1.5 w-0.5 rounded-full bg-gradient-to-b from-violet to-teal"></span>
                @endif
                <i class="fa-solid {{ $item['icon'] }} w-4 text-center {{ $active ? 'text-teal' : '' }}"></i>
                <span class="font-medium flex-1">{{ $item['label'] }}</span>
                @if (!empty($item['badge']))
                    <span
                        class="text-[11px] font-semibold px-1.5 py-0.5 rounded-full bg-danger/15 text-danger tabular-nums">{{ $item['badge'] }}</span>
                @endif
            </a>
        @endforeach
    </nav>

    <div class="px-4 py-4 border-t border-black/10 dark:border-white/10">
        <div class="flex items-center gap-2.5 px-2 mb-3">
            <div
                class="w-8 h-8 rounded-full bg-black/5 dark:bg-white/5 flex items-center justify-center shrink-0 text-txsecondary text-xs font-medium">
                {{ strtoupper(substr(auth()->user()->name ?? '?', 0, 1)) }}
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-sm text-txprimary truncate leading-tight">{{ auth()->user()->name }}</p>
                <p class="text-xs text-txsecondary truncate leading-tight">
                    {{ auth()->user()->isSuperAdmin() ? 'Super Admin' : 'Admin' }}</p>
            </div>
            <button type="button" x-data @click="
                    document.documentElement.classList.toggle('dark');
                    localStorage.theme = document.documentElement.classList.contains('dark') ? 'dark' : 'light';
                "
                class="w-8 h-8 flex items-center justify-center rounded-lg text-txsecondary hover:text-txprimary hover:bg-black/5 dark:hover:bg-white/5"
                aria-label="Ganti tema" title="Ganti tema terang/gelap">
                <i class="fa-solid fa-sun text-sm hidden dark:inline"></i>
                <i class="fa-solid fa-moon text-sm dark:hidden"></i>
            </button>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                    class="w-8 h-8 flex items-center justify-center rounded-lg text-txsecondary hover:text-danger hover:bg-danger/10"
                    aria-label="Keluar" title="Keluar">
                    <i class="fa-solid fa-right-from-bracket text-sm"></i>
                </button>
            </form>
        </div>
        <p class="text-[11px] text-txsecondary px-2">Dinas Perpustakaan &amp; Kearsipan Kota Magelang</p>
    </div>
</aside>