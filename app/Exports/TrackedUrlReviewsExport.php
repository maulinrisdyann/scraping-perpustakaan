<?php

namespace App\Exports;

use App\Models\Review;
use App\Models\TrackedUrl;
use Illuminate\Support\Enumerable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TrackedUrlReviewsExport implements FromCollection, WithHeadings
{
    public function __construct(public TrackedUrl $trackedUrl)
    {
        //
    }

    public function collection(): Enumerable
    {
        return Review::with(['source', 'trackedUrl'])
            ->where('tracked_url_id', $this->trackedUrl->id)
            ->orderByDesc('scraped_at')
            ->get()
            ->map(function (Review $review) {
                return [
                    'id_ulasan' => $review->id,
                    'tracked_url_id' => $review->tracked_url_id,
                    'platform_sumber' => $review->source?->label ?? $review->source?->name ?? '—',
                    'label_sumber_pantauan' => $review->trackedUrl?->label ?? '—',
                    'url_posting' => $review->trackedUrl?->url ?? $review->trackedUrl?->search_query ?? '—',
                    'nama_pengguna' => $review->author_name ?? '—',
                    'rating' => $review->rating,
                    'ulasan' => $review->review_text ?? '—',
                    'waktu_relatif_ulasan' => $review->review_relative_time ?? '—',
                    'tanggal_ulasan' => $review->review_date ? $review->review_date->format('Y-m-d H:i:s') : null,
                    'sentimen' => $review->sentiment_label ?? '—',
                    'skor_sentimen' => $review->sentiment_score,
                    'waktu_scraping' => $review->scraped_at ? $review->scraped_at->format('Y-m-d H:i:s') : null,
                    'owner_reply' => $review->owner_reply_text ?? null,
                ];
            });
    }

    public function headings(): array
    {
        return [
            'ID Ulasan',
            'Tracked URL ID',
            'Platform/Sumber',
            'Label Sumber Pantauan',
            'URL Posting',
            'Nama Pengguna',
            'Rating',
            'Ulasan',
            'Waktu Relatif Ulasan',
            'Tanggal Ulasan',
            'Sentimen',
            'Skor Sentimen',
            'Waktu Scraping',
            'Owner Reply',
        ];
    }
}
