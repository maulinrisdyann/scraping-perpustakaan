@extends('layouts.app')

@section('title', 'Overview')
@section('subtitle', 'Ringkasan ulasan dan sentimen dari semua platform')

@section('content')
<div class="space-y-6">

    @if (session('status'))
        <div class="glass rounded-none px-5 py-3.5 text-sm text-success border-success/20 fade-slide-in">
            <i class="fa-solid fa-circle-check mr-2"></i>{{ session('status') }}
        </div>
    @endif

    <div class="glass rounded-none p-6 fade-slide-in" style="animation-delay: 80ms">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-display font-semibold text-lg mb-1">Otomatisasi Scraping</h2>
                <p class="text-sm {{ $automationEnabled ? 'text-success' : 'text-txsecondary' }}">
                    <span class="font-medium">Status: {{ $automationEnabled ? 'AKTIF' : 'NONAKTIF' }}</span>
                </p>
                <p class="text-sm text-txsecondary mt-1">
                    {{ $automationEnabled ? 'Scheduler otomatis berjalan sesuai jadwal.' : 'Scheduler otomatis sedang dihentikan sementara.' }}
                </p>
            </div>

            <form method="POST" action="{{ route('dashboard.automation.toggle') }}">
                @csrf
                @method('PATCH')
                <button type="submit" class="rounded-xl px-5 py-2.5 text-sm font-medium transition-colors {{ $automationEnabled ? 'bg-violet text-bg hover:bg-violet/90' : 'bg-slate-700 text-white hover:bg-slate-600 dark:bg-white/10 dark:hover:bg-white/15 dark:text-white' }}">
                    {{ $automationEnabled ? 'Matikan Otomatisasi' : 'Aktifkan Otomatisasi' }}
                </button>
            </form>
        </div>
    </div>

    {{-- Grid statistik --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="glass rounded-none p-5 glow-hover fade-slide-in" style="animation-delay: 0ms">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs text-txsecondary">Total Ulasan</span>
                <i class="fa-solid fa-comments text-violet"></i>
            </div>
            <p class="font-display text-3xl font-semibold tabular-nums">{{ number_format($totalReviews) }}</p>
        </div>

        <div class="glass rounded-none p-5 glow-hover fade-slide-in" style="animation-delay: 80ms">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs text-txsecondary">Rata-rata Rating</span>
                <i class="fa-solid fa-star text-amber-400"></i>
            </div>
            <p class="font-display text-3xl font-semibold tabular-nums">{{ $avgRating ? number_format($avgRating, 1) : '—' }}</p>
        </div>

        <div class="glass rounded-none p-5 glow-hover fade-slide-in" style="animation-delay: 160ms">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs text-txsecondary">Komplain Pending</span>
                <i class="fa-solid fa-triangle-exclamation text-danger"></i>
            </div>
            <p class="font-display text-3xl font-semibold tabular-nums {{ $pendingComplaints > 0 ? 'text-danger' : '' }}">{{ number_format($pendingComplaints) }}</p>
        </div>

        <div class="glass rounded-none p-5 glow-hover fade-slide-in" style="animation-delay: 240ms">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs text-txsecondary">Belum Dianalisis</span>
                <i class="fa-solid fa-robot text-txsecondary"></i>
            </div>
            <p class="font-display text-3xl font-semibold tabular-nums">{{ number_format($unanalyzedCount) }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Donut sentimen — signature element --}}
        <div class="glass rounded-none p-6 lg:col-span-1 fade-slide-in" style="animation-delay: 320ms">
            <h2 class="font-display font-semibold text-lg mb-1">Sentimen Ulasan</h2>
            <p class="text-xs text-txsecondary mb-4">Diperbarui otomatis setelah analisis sentimen berjalan (Fase 3)</p>
            <div class="relative h-56 flex items-center justify-center">
                <canvas id="sentimentChart"></canvas>
            </div>
            <div class="mt-4 space-y-2">
                @foreach ($sentimentCounts as $label => $total)
                    <div class="flex items-center justify-between text-sm">
                        @php
                            $dotClass = match($label) {
                                'positive' => 'bg-positive',
                                'negative' => 'bg-danger',
                                'neutral' => 'bg-info',
                                default => 'bg-violet',
                            };
                        @endphp
                        <span class="flex items-center gap-2 text-txsecondary">
                            <span class="w-2 h-2 rounded-full {{ $dotClass }}"></span>
                            {{ ucfirst(str_replace('_', ' ', $label)) }}
                        </span>
                        <span class="tabular-nums text-txprimary">{{ number_format($total) }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Breakdown per source --}}
        <div class="glass rounded-none p-6 lg:col-span-1 fade-slide-in" style="animation-delay: 400ms">
            <h2 class="font-display font-semibold text-lg mb-4">Ulasan per Platform</h2>
            <div class="space-y-4">
                @foreach ($bySource as $source)
                    @php
                        $pct = $totalReviews > 0 ? round(($source->reviews_count / $totalReviews) * 100) : 0;
                    @endphp
                    <div>
                        <div class="flex items-center justify-between text-sm mb-1.5">
                            <span class="text-txprimary">{{ $source->label }}</span>
                            <span class="text-txsecondary tabular-nums">{{ number_format($source->reviews_count) }}</span>
                        </div>
                        <div class="h-1.5 rounded-full bg-black/5 dark:bg-white/5 overflow-hidden">
                            <div class="h-full rounded-full bg-gradient-to-r from-violet to-teal" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Ulasan terbaru --}}
        <div class="glass rounded-none p-6 lg:col-span-1 fade-slide-in" style="animation-delay: 480ms">
            <h2 class="font-display font-semibold text-lg mb-4">Ulasan Terbaru</h2>
            <div class="space-y-4">
                @forelse ($recentReviews as $review)
                    <div class="pb-4 border-b border-black/5 dark:border-white/5 last:border-0 last:pb-0">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm font-medium text-txprimary truncate">{{ $review->author_name ?? 'Anonim' }}</span>
                            @if ($review->rating)
                                <span class="text-xs text-amber-400 shrink-0">{{ str_repeat('★', $review->rating) }}</span>
                            @endif
                        </div>
                        <p class="text-xs text-txsecondary line-clamp-2">{{ $review->review_text ?? '—' }}</p>
                    </div>
                @empty
                    <p class="text-sm text-txsecondary">Belum ada ulasan.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
    const sentimentLabels = @json($sentimentCounts->keys());
    const sentimentData = @json($sentimentCounts->values());

    const ctx = document.getElementById('sentimentChart').getContext('2d');

    // Baca warna langsung dari CSS variable tema aktif (dark/light), biar
    // chart selalu konsisten sama palet UI tanpa hex terpisah di sini.
    const rootStyle = getComputedStyle(document.documentElement);
    const cssColor = (name) => `rgb(${rootStyle.getPropertyValue(name).trim()})`;

    const colorMap = {
        positive: cssColor('--color-positive'),
        negative: cssColor('--color-danger'),
        neutral: cssColor('--color-info'),
        belum_dianalisis: cssColor('--color-violet'),
    };

    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: sentimentLabels,
            datasets: [{
                data: sentimentData,
                backgroundColor: sentimentLabels.map(l => colorMap[l] ?? 'rgba(255,255,255,0.12)'),
                borderWidth: 0,
                borderRadius: 6,
            }],
        },
        options: {
            cutout: '72%',
            plugins: { legend: { display: false } },
        },
    });
</script>
@endpush
