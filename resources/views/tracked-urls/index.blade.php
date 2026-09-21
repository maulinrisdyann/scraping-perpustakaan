@extends('layouts.app')

@section('title', 'Sumber Pantauan')
@section('subtitle', 'Kelola url postingan Instagram/Facebook yang dipantau scraper')

@section('content')
<div class="space-y-6">

    @if (session('status'))
        <div class="glass rounded-none px-5 py-3.5 text-sm text-success border-success/20 fade-slide-in">
            <i class="fa-solid fa-circle-check mr-2"></i>{{ session('status') }}
        </div>
    @endif

    <div class="glass rounded-none p-6">
        <h2 class="font-display font-semibold text-lg mb-4">Tambah URL Baru</h2>

        @if ($errors->any())
            <div class="mb-4 text-sm text-danger space-y-1">
                @foreach ($errors->all() as $error)
                    <p><i class="fa-solid fa-circle-exclamation mr-1.5"></i>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('tracked-urls.store') }}" class="flex flex-wrap gap-3 items-start">
            @csrf

            <select
                name="source_id"
                class="appearance-none bg-black/5 dark:bg-white/5 border border-black/10 dark:border-white/10 rounded-none px-4 py-2.5 pr-10 text-sm text-txprimary focus:outline-none focus:border-violet/60"
                style="background-image:url('data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 20 20%27 fill=%27none%27 stroke=%27currentColor%27 stroke-width=%272%27 stroke-linecap=%27round%27 stroke-linejoin=%27round%27%3E%3Cpath d=%27M5 7l5 5 5-5%27/%3E%3C/svg%3E'); background-repeat:no-repeat; background-position:right 0.85rem center; background-size:1rem 1rem;"
            >
                @foreach ($sources as $source)
                    <option value="{{ $source->id }}" @selected(old('source_id') == $source->id)>{{ $source->label }}</option>
                @endforeach
            </select>

            <input
                type="text"
                name="label"
                value="{{ old('label') }}"
                placeholder="Label (mis. Postingan Peluncuran Buku)"
                class="bg-black/5 dark:bg-white/5 border border-black/10 dark:border-white/10 rounded-none px-4 py-2.5 text-sm text-txprimary placeholder:text-txsecondary focus:outline-none focus:border-violet/60 min-w-[240px]"
            >

            <input
                type="url"
                name="url"
                value="{{ old('url') }}"
                placeholder="https://www.instagram.com/p/..."
                class="flex-1 min-w-[280px] bg-black/5 dark:bg-white/5 border border-black/10 dark:border-white/10 rounded-none px-4 py-2.5 text-sm text-txprimary placeholder:text-txsecondary focus:outline-none focus:border-violet/60"
            >

            <button type="submit" class="bg-gradient-to-r from-violet to-teal text-bg font-medium text-sm px-5 py-2.5 rounded-xl glow-hover">
                Tambah
            </button>
        </form>
    </div>

    <div class="glass rounded-none overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-black/10 dark:border-white/10 text-left text-txsecondary text-xs uppercase tracking-wide">
                        <th class="px-4 py-3.5 font-medium w-12">No</th>
                        <th class="px-5 py-3.5 font-medium">Label</th>
                        <th class="px-5 py-3.5 font-medium">Platform</th>
                        <th class="px-5 py-3.5 font-medium">URL</th>
                        <th class="px-5 py-3.5 font-medium">Ulasan</th>
                        <th class="px-5 py-3.5 font-medium">Terakhir Discrape</th>
                        <th class="px-5 py-3.5 font-medium">Status</th>
                        <th class="px-5 py-3.5 font-medium"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($trackedUrls as $trackedUrl)
                        <tr class="border-b border-black/5 dark:border-white/5 last:border-0 hover:bg-black/[0.02] dark:hover:bg-white/[0.03]">
                            <td class="px-4 py-4 align-top text-txsecondary tabular-nums">
                                {{ $loop->iteration }}
                            </td>
                            <td class="px-5 py-4 align-top font-medium text-txprimary">{{ $trackedUrl->label }}</td>
                            <td class="px-5 py-4 align-top">
                                <span class="text-xs px-2.5 py-1 rounded-full bg-black/5 dark:bg-white/5 text-txsecondary whitespace-nowrap">
                                    {{ $trackedUrl->source->label ?? '—' }}
                                </span>
                            </td>
                            <td class="px-5 py-4 align-top max-w-xs">
                                @if ($trackedUrl->url)
                                    <a href="{{ $trackedUrl->url }}" target="_blank" rel="noopener" class="text-teal hover:underline truncate block">{{ $trackedUrl->url }}</a>
                                @else
                                    <span class="text-txsecondary">{{ $trackedUrl->search_query ?? '—' }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 align-top tabular-nums text-txprimary">{{ number_format($trackedUrl->reviews_count) }}</td>
                            <td class="px-5 py-4 align-top text-txsecondary whitespace-nowrap">
                                {{ $trackedUrl->last_scraped_at?->diffForHumans() ?? 'Belum pernah' }}
                            </td>
                            <td class="px-5 py-4 align-top">
                                @if ($trackedUrl->is_active)
                                    <span class="text-xs px-2.5 py-1 rounded-full bg-success/10 text-success">Aktif</span>
                                @else
                                    <span class="text-xs px-2.5 py-1 rounded-full bg-black/5 dark:bg-white/5 text-txsecondary">Nonaktif</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 align-top">
                                <div class="flex flex-col gap-2">
                                    <form method="GET" action="{{ route('tracked-urls.export', $trackedUrl) }}">
                                        <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-green-600 text-white text-xs font-medium px-3 py-2 hover:bg-green-500 transition-colors">
                                            Export
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ route('tracked-urls.toggle', $trackedUrl) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="text-xs text-txsecondary hover:text-txprimary underline">
                                            {{ $trackedUrl->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-10 text-center text-txsecondary">Belum ada url yang dipantau.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
