@extends('layouts.app')

@section('title', 'Komplain')
@section('subtitle', 'Ulasan bersentimen negatif yang perlu ditindaklanjuti')

@section('content')
<div class="space-y-5">

    @if (session('status'))
        <div class="glass rounded-none px-5 py-3.5 text-sm text-success fade-slide-in">
            <i class="fa-solid fa-circle-check mr-2"></i>{{ session('status') }}
        </div>
    @endif

    <div class="flex flex-wrap gap-3">
        @php
            $statusTabs = [
                '' => 'Semua',
                'belum_dibalas' => 'Belum Dibalas',
                'sudah_dibalas' => 'Sudah Dibalas',
                'selesai' => 'Selesai',
            ];
        @endphp
        @foreach ($statusTabs as $value => $label)
            @php $isActive = request('status', '') === $value; @endphp
            <a href="{{ route('complaints.index', $value ? ['status' => $value] : []) }}"
               class="px-4 py-2 rounded-xl text-sm font-medium transition-colors
                      {{ $isActive ? 'bg-gradient-to-r from-violet to-teal text-bg' : 'glass text-txsecondary hover:text-txprimary' }}">
                {{ $label }}
                @if ($value && $statusCounts->get($value))
                    <span class="tabular-nums">({{ $statusCounts->get($value) }})</span>
                @endif
            </a>
        @endforeach
    </div>

    <div class="space-y-4">
        @forelse ($complaints as $complaint)
            @php
                $review = $complaint->review;
                $statusStyle = match($complaint->status) {
                    'belum_dibalas' => 'bg-danger/15 text-danger',
                    'sudah_dibalas' => 'bg-amber-400/15 text-amber-400',
                    'selesai' => 'bg-success/15 text-success',
                };
                $statusLabel = match($complaint->status) {
                    'belum_dibalas' => 'Belum Dibalas',
                    'sudah_dibalas' => 'Sudah Dibalas',
                    'selesai' => 'Selesai',
                };
            @endphp
            <div class="glass rounded-none p-6 glow-hover">
                <div class="flex items-start justify-between gap-4 mb-3">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap mb-1.5">
                            <span class="font-medium text-txprimary">{{ $review->author_name ?? 'Anonim' }}</span>
                            <span class="text-xs px-2 py-0.5 rounded-full bg-black/5 dark:bg-white/5 text-txsecondary whitespace-nowrap">{{ $review->source->label ?? '—' }}</span>
                            @if ($review->rating)
                                <span class="text-xs text-amber-400">{{ str_repeat('★', $review->rating) }}</span>
                            @endif
                            <span class="text-xs px-2 py-0.5 rounded-full {{ $statusStyle }}">{{ $statusLabel }}</span>
                        </div>
                        <p class="text-sm text-txprimary/90">{{ $review->review_text }}</p>
                        <p class="text-xs text-txsecondary mt-2">
                            {{ $review->trackedUrl->label ?? '' }}
                            &middot; {{ $review->review_relative_time ?? $review->review_date?->diffForHumans() ?? $review->scraped_at?->diffForHumans() }}
                            @if ($review->sentiment_score)
                                &middot; confidence {{ number_format($review->sentiment_score * 100, 1) }}%
                            @endif
                        </p>
                        @if ($review->owner_reply_text)
                            <div class="mt-3 pl-3 border-l-2 border-teal/50 bg-teal/5 rounded-r-lg py-2 pr-3">
                                <p class="text-xs text-teal font-medium mb-1">
                                    <i class="fa-solid fa-circle-check mr-1"></i>Sudah dibalas via Google Maps
                                    @if ($review->owner_reply_relative_time)
                                        <span class="text-txsecondary font-normal">&middot; {{ $review->owner_reply_relative_time }}</span>
                                    @endif
                                </p>
                                <p class="text-xs text-txsecondary">{{ $review->owner_reply_text }}</p>
                            </div>
                        @endif
                    </div>
                </div>

                <form method="POST" action="{{ route('complaints.update', $complaint) }}" class="flex flex-wrap gap-3 items-start pt-4 border-t border-black/5 dark:border-white/5">
                    @csrf
                    @method('PATCH')

                    <select
                        name="status"
                        class="appearance-none bg-black/5 dark:bg-white/5 border border-black/10 dark:border-white/10 rounded-none px-3.5 py-2.5 pr-10 text-sm text-txprimary focus:outline-none focus:border-violet/60"
                        style="background-image:url('data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 20 20%27 fill=%27none%27 stroke=%27currentColor%27 stroke-width=%272%27 stroke-linecap=%27round%27 stroke-linejoin=%27round%27%3E%3Cpath d=%27M5 7l5 5 5-5%27/%3E%3C/svg%3E'); background-repeat:no-repeat; background-position:right 0.85rem center; background-size:1rem 1rem; color-scheme: dark;"
                    >
                        <option value="belum_dibalas" @selected($complaint->status === 'belum_dibalas')>Belum Dibalas</option>
                        <option value="sudah_dibalas" @selected($complaint->status === 'sudah_dibalas')>Sudah Dibalas</option>
                        <option value="selesai" @selected($complaint->status === 'selesai')>Selesai</option>
                    </select>

                    <select
                        name="category_id"
                        class="appearance-none bg-black/5 dark:bg-white/5 border border-black/10 dark:border-white/10 rounded-none px-3.5 py-2.5 pr-10 text-sm text-txprimary focus:outline-none focus:border-violet/60"
                        style="background-image:url('data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 20 20%27 fill=%27none%27 stroke=%27currentColor%27 stroke-width=%272%27 stroke-linecap=%27round%27 stroke-linejoin=%27round%27%3E%3Cpath d=%27M5 7l5 5 5-5%27/%3E%3C/svg%3E'); background-repeat:no-repeat; background-position:right 0.85rem center; background-size:1rem 1rem; color-scheme: dark;"
                    >
                        <option value="">Tanpa kategori</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected($complaint->category_id === $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>

                    <input
                        type="text"
                        name="response_note"
                        value="{{ old('response_note', $complaint->response_note) }}"
                        placeholder="Catatan balasan (opsional)"
                        class="flex-1 min-w-[220px] bg-black/5 dark:bg-white/5 border border-black/10 dark:border-white/10 rounded-xl px-4 py-2.5 text-sm text-txprimary placeholder:text-txsecondary focus:outline-none focus:border-violet/60"
                    >

                    <button type="submit" class="bg-gradient-to-r from-violet to-teal text-bg font-medium text-sm px-5 py-2.5 rounded-xl glow-hover">
                        Simpan
                    </button>
                </form>
            </div>
        @empty
            <div class="glass rounded-none p-10 text-center text-txsecondary">
                Tidak ada komplain untuk filter ini.
            </div>
        @endforelse
    </div>

    <div class="text-txprimary">
        {{ $complaints->links() }}
    </div>
</div>
@endsection
