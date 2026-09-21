@extends('layouts.app')

@section('title', 'Ulasan')
@section('subtitle', 'Semua ulasan yang berhasil dikumpulkan dari platform yang dipantau')

@section('content')
<div class="space-y-5">

    @if (session('status'))
        <div class="glass rounded-none px-5 py-3.5 text-sm text-success fade-slide-in">
            <i class="fa-solid fa-circle-check mr-2"></i>{{ session('status') }}
        </div>
    @endif

    @if (session('warning'))
        <div class="glass rounded-none px-5 py-3.5 text-sm text-amber-400 fade-slide-in">
            <i class="fa-solid fa-circle-exclamation mr-2"></i>{{ session('warning') }}
        </div>
    @endif

    <div class="glass rounded-none p-6">
        <h2 class="font-display font-semibold text-lg mb-1">Input Komentar Monggo Lapor</h2>
        <p class="text-xs text-txsecondary mb-4">Sumber: <span class="font-medium text-txprimary">Monggo Lapor</span>. Komentar akan langsung dianalisis tanpa menunggu scheduler.</p>

        @if ($errors->any())
            <div class="mb-4 text-sm text-danger space-y-1">
                @foreach ($errors->all() as $error)
                    <p><i class="fa-solid fa-circle-exclamation mr-1.5"></i>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('reviews.monggo-lapor.store') }}" x-data="{ submitting: false }" @submit="submitting = true" class="space-y-4">
            @csrf
            <div>
                <label for="monggo_lapor_review_text" class="block text-xs text-txsecondary mb-1.5">Komentar/Laporan</label>
                <textarea name="review_text" id="monggo_lapor_review_text" rows="4" required
                    placeholder="Masukkan komentar atau laporan dari Monggo Lapor..."
                    class="w-full bg-black/5 dark:bg-white/5 border border-black/10 dark:border-white/10 rounded-none px-4 py-2.5 text-sm text-txprimary placeholder:text-txsecondary focus:outline-none focus:border-violet/60">{{ old('review_text') }}</textarea>
            </div>
            <button type="submit" :disabled="submitting" class="bg-violet text-bg font-medium text-sm px-5 py-2.5 rounded-none glow-hover disabled:opacity-60">
                <span x-show="!submitting">Input Komentar</span>
                <span x-show="submitting" x-cloak>Memproses komentar...</span>
            </button>
        </form>
    </div>

    @if ($manualReview)
        @php
            $manualSentiment = match($manualReview->sentiment_label) {
                'positive' => 'Positif',
                'negative' => 'Negatif',
                'neutral' => 'Netral',
                default => 'Belum dianalisis',
            };
        @endphp
        <div class="glass rounded-none p-6 fade-slide-in">
            <h2 class="font-display font-semibold text-lg mb-4">Hasil Komentar Monggo Lapor</h2>
            @if ($manualReview->sentiment_label === null)
                <p class="mb-4 text-sm text-txsecondary">Komentar tersimpan dan sedang menunggu proses analisis sentimen.</p>
            @endif
            <dl class="grid gap-4 md:grid-cols-2 text-sm">
                <div class="md:col-span-2">
                    <dt class="text-xs text-txsecondary mb-1">Komentar</dt>
                    <dd class="text-txprimary whitespace-pre-wrap">{{ $manualReview->review_text }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-txsecondary mb-1">Sentimen</dt>
                    <dd class="text-txprimary">{{ $manualSentiment }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-txsecondary mb-1">Skor Sentimen</dt>
                    <dd class="text-txprimary">{{ $manualReview->sentiment_score !== null ? number_format($manualReview->sentiment_score, 2) : '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-txsecondary mb-1">Rating Inferensi</dt>
                    <dd class="text-amber-400">{{ $manualReview->rating ? str_repeat('★', $manualReview->rating) . ' (' . $manualReview->rating . '/5)' : '—' }}</dd>
                </div>
            </dl>
        </div>
    @endif

    <form method="GET" action="{{ route('reviews.index') }}" class="glass rounded-none p-4 flex flex-wrap gap-3 items-center">
        <div class="relative flex-1 min-w-[220px]">
            <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-txsecondary text-sm"></i>
            <input
                type="text"
                name="q"
                value="{{ request('q') }}"
                placeholder="Cari nama atau isi ulasan..."
                class="w-full bg-black/5 dark:bg-white/5 border border-black/10 dark:border-white/10 rounded-none pl-10 pr-4 py-2.5 text-sm text-txprimary placeholder:text-txsecondary focus:outline-none focus:border-violet/60"
            >
        </div>

        <select
            name="source_id"
            class="appearance-none bg-black/5 dark:bg-white/5 border border-black/10 dark:border-white/10 rounded-none px-3.5 py-2.5 pr-10 text-sm text-txprimary focus:outline-none focus:border-violet/60"
            style="background-image:url('data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 20 20%27 fill=%27none%27 stroke=%27currentColor%27 stroke-width=%272%27 stroke-linecap=%27round%27 stroke-linejoin=%27round%27%3E%3Cpath d=%27M5 7l5 5 5-5%27/%3E%3C/svg%3E'); background-repeat:no-repeat; background-position:right 0.85rem center; background-size:1rem 1rem;"
        >
            <option value="">Semua Platform</option>
            @foreach ($sources as $source)
                <option value="{{ $source->id }}" @selected(request('source_id') == $source->id)>{{ $source->label }}</option>
            @endforeach
        </select>

        <select
            name="rating"
            class="appearance-none bg-black/5 dark:bg-white/5 border border-black/10 dark:border-white/10 rounded-none px-3.5 py-2.5 pr-10 text-sm text-txprimary focus:outline-none focus:border-violet/60"
            style="background-image:url('data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 20 20%27 fill=%27none%27 stroke=%27currentColor%27 stroke-width=%272%27 stroke-linecap=%27round%27 stroke-linejoin=%27round%27%3E%3Cpath d=%27M5 7l5 5 5-5%27/%3E%3C/svg%3E'); background-repeat:no-repeat; background-position:right 0.85rem center; background-size:1rem 1rem;"
        >
            <option value="">Semua Rating</option>
            @for ($i = 5; $i >= 1; $i--)
                <option value="{{ $i }}" @selected(request('rating') == $i)>{{ $i }} bintang</option>
            @endfor
        </select>

        <button type="submit" class="bg-gradient-to-r from-violet to-teal text-bg font-medium text-sm px-5 py-2.5 rounded-xl glow-hover">
            Filter
        </button>

        @if (request()->anyFilled(['q', 'source_id', 'rating']))
            <a href="{{ route('reviews.index') }}" class="text-sm text-txsecondary hover:text-txprimary px-2">Reset</a>
        @endif
    </form>

    <div class="glass rounded-none overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-black/10 dark:border-white/10 text-left text-txsecondary text-xs uppercase tracking-wide">
                        <th class="px-5 py-3.5 font-medium w-12">No</th>
                        <th class="px-5 py-3.5 font-medium">Penulis</th>
                        <th class="px-5 py-3.5 font-medium">Sumber</th>
                        <th class="px-5 py-3.5 font-medium">Platform</th>
                        <th class="px-5 py-3.5 font-medium">Rating</th>
                        <th class="px-5 py-3.5 font-medium">Sentimen</th>
                        <th class="px-5 py-3.5 font-medium">Ulasan</th>
                        <th class="px-5 py-3.5 font-medium">Waktu</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($reviews as $review)
                        <tr class="border-b border-black/5 dark:border-white/5 last:border-0 hover:bg-black/[0.02] dark:hover:bg-white/[0.03]">
                            <td class="px-5 py-4 align-top text-txsecondary tabular-nums">
                                {{ $reviews->firstItem() + $loop->index }}
                            </td>
                            <td class="px-5 py-4 align-top">
                                <span class="font-medium text-txprimary">{{ $review->author_name ?? 'Anonim' }}</span>
                            </td>
                            <td class="px-5 py-4 align-top whitespace-nowrap">
                                <span class="text-xs px-2.5 py-1 rounded-full bg-violet/10 text-violet font-medium">
                                    {{ $review->trackedUrl ? '#'.$review->trackedUrl->id : '—' }}
                                </span>
                            </td>
                            <td class="px-5 py-4 align-top">
                                <span class="text-xs px-2.5 py-1 rounded-full bg-black/5 dark:bg-white/5 text-txsecondary whitespace-nowrap">
                                    {{ $review->source->label ?? '—' }}
                                </span>
                            </td>
                            <td class="px-5 py-4 align-top">
                                @if ($review->rating)
                                    <span class="text-amber-400">{{ str_repeat('★', $review->rating) }}</span><span class="text-black/15 dark:text-white/15">{{ str_repeat('★', 5 - $review->rating) }}</span>
                                @else
                                    <span class="text-txsecondary">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 align-top">
                                @php
                                    $sentimentStyle = match($review->sentiment_label) {
                                        'positive' => ['bg-positive/15 text-positive', 'Positif'],
                                        'negative' => ['bg-danger/15 text-danger', 'Negatif'],
                                        'neutral' => ['bg-info/15 text-info', 'Netral'],
                                        default => ['bg-black/5 dark:bg-white/5 text-txsecondary', 'Belum'],
                                    };
                                @endphp
                                <span class="text-xs px-2.5 py-1 rounded-full whitespace-nowrap {{ $sentimentStyle[0] }}">
                                    {{ $sentimentStyle[1] }}
                                </span>
                            </td>
                            <td class="px-5 py-4 align-top max-w-xl">
                                <div
                                    x-data="{
                                        expanded: false,
                                        canExpand: false,

                                        checkOverflow() {
                                            const text = this.$refs.reviewText;
                                            if (!text) return;

                                            if (this.expanded) {
                                                this.canExpand = true;
                                                return;
                                            }

                                            this.canExpand = text.scrollHeight > text.clientHeight + 1;
                                        }
                                    }"
                                    x-init="
                                        checkOverflow();

                                        const observer = new ResizeObserver(() => checkOverflow());
                                        observer.observe($refs.reviewText);

                                        $cleanup(() => observer.disconnect());
                                    "
                                >
                                    <p
                                        x-ref="reviewText"
                                        class="text-sm text-txprimary/90 leading-relaxed"
                                        :class="!expanded ? 'line-clamp-3' : ''"
                                    >{{ $review->review_text ?? '—' }}</p>

                                    <button
                                        x-show="canExpand"
                                        type="button"
                                        @click="expanded = !expanded"
                                        class="mt-1 text-sm text-txsecondary hover:text-txprimary font-medium"
                                        x-text="expanded ? 'Lebih sedikit' : 'Lebih banyak'"
                                    ></button>
                                </div>

                                @if ($review->owner_reply_text)
                                    <div class="mt-2 pl-3 border-l-2 border-violet/40">
                                        <p class="text-xs text-teal font-medium mb-0.5">
                                            <i class="fa-solid fa-reply mr-1"></i>Balasan pemilik
                                        </p>
                                        <p class="text-xs text-txsecondary line-clamp-2">{{ $review->owner_reply_text }}</p>
                                    </div>
                                @endif
                            </td>
                            <td class="px-5 py-4 align-top text-txsecondary whitespace-nowrap">
                                {{ $review->review_relative_time ?? $review->review_date?->diffForHumans() ?? '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-10 text-center text-txsecondary">Tidak ada ulasan yang cocok.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($showManualReviews)
        <div class="glass rounded-none overflow-hidden">
            <div class="px-5 py-4 border-b border-black/10 dark:border-white/10">
                <h2 class="font-display font-semibold text-lg">Komentar Manual Monggo Lapor</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-black/10 dark:border-white/10 text-left text-txsecondary text-xs uppercase tracking-wide">
                            <th class="px-5 py-3.5 font-medium">Komentar</th>
                            <th class="px-5 py-3.5 font-medium">Sentimen</th>
                            <th class="px-5 py-3.5 font-medium">Rating Inferensi</th>
                            <th class="px-5 py-3.5 font-medium">Tanggal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($manualReviews as $manual)
                            @php($label = match($manual->sentiment_label) { 'positive' => 'Positif', 'negative' => 'Negatif', 'neutral' => 'Netral', default => 'Sedang dianalisis' })
                            <tr class="border-b border-black/5 dark:border-white/5 last:border-0">
                                <td class="px-5 py-4 text-txprimary max-w-xl">{{ $manual->review_text }}</td>
                                <td class="px-5 py-4 text-txsecondary">{{ $label }}@if($manual->sentiment_score !== null) ({{ number_format($manual->sentiment_score, 2) }}) @endif</td>
                                <td class="px-5 py-4 text-amber-400">{{ $manual->rating ? str_repeat('★', $manual->rating) . ' (' . $manual->rating . '/5)' : '—' }}</td>
                                <td class="px-5 py-4 text-txsecondary whitespace-nowrap">{{ $manual->review_date?->diffForHumans() }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-5 py-8 text-center text-txsecondary">Belum ada komentar manual Monggo Lapor.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="text-txprimary">
        {{ $reviews->links() }}
    </div>
</div>
@endsection
