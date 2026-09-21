<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard') — Perpustakaan Kota Magelang</title>

    @include('partials.head-assets')

    @stack('head')
</head>
<body class="bg-bg text-txprimary min-h-screen" x-data="{ sidebarOpen: false }">
    <div class="flex min-h-screen">
        @include('partials.sidebar')

        <div class="flex-1 flex flex-col min-w-0">
            <header class="border-b border-black/10 dark:border-white/10 px-4 sm:px-8 py-5 flex items-center gap-3">
                <button
                    type="button"
                    @click="sidebarOpen = true"
                    class="lg:hidden shrink-0 w-9 h-9 flex items-center justify-center rounded-xl border border-black/10 dark:border-white/10 text-txsecondary hover:text-txprimary hover:bg-black/5 dark:hover:bg-white/5"
                    aria-label="Buka menu"
                >
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div class="min-w-0">
                    <h1 class="font-display text-2xl font-semibold text-txprimary truncate">@yield('title', 'Dashboard')</h1>
                    @hasSection('subtitle')
                        <p class="text-sm text-txsecondary mt-1">@yield('subtitle')</p>
                    @endif
                </div>
            </header>

            <main class="flex-1 px-4 sm:px-8 py-6 min-w-0">
                @yield('content')
            </main>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
