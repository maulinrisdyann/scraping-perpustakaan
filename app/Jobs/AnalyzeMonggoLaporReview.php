<?php

namespace App\Jobs;

use App\Models\ManualReview;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use RuntimeException;

class AnalyzeMonggoLaporReview implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 360;

    public function __construct(public int $reviewId)
    {
    }

    public function handle(): void
    {
        $review = ManualReview::with('source')->find($this->reviewId);

        if (! $review || $review->source?->name !== 'monggo_lapor' || $review->sentiment_label !== null) {
            return;
        }

        $exitCode = Artisan::call('manual-reviews:analyze', ['reviewId' => $review->id]);
        $review->refresh();

        if ($exitCode !== 0 || $review->sentiment_label === null || $review->rating === null) {
            throw new RuntimeException('Analisis sentimen Monggo Lapor belum menghasilkan data lengkap.');
        }
    }
}
