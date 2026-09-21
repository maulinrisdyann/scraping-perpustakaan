<?php

namespace App\Http\Controllers;

use App\Jobs\AnalyzeMonggoLaporReview;
use App\Models\ManualReview;
use App\Models\Review;
use App\Models\Source;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $query = Review::with(['source', 'trackedUrl']);

        if ($search = $request->string('q')->trim()->value()) {
            $query->where(function ($q) use ($search) {
                $q->where('author_name', 'like', "%{$search}%")
                    ->orWhere('review_text', 'like', "%{$search}%");
            });
        }

        if ($sourceId = $request->integer('source_id')) {
            $query->where('source_id', $sourceId);
        }

        if ($rating = $request->integer('rating')) {
            $query->where('rating', $rating);
        }

        $reviews = $query->orderByDesc('scraped_at')
            ->paginate(15)
            ->withQueryString();

        $sources = Source::orderBy('label')->get();
        $monggoLaporSourceId = $sources->firstWhere('name', 'monggo_lapor')?->id;
        $manualReviews = ManualReview::with('source')
            ->when($sourceId, fn ($query) => $query->where('source_id', $sourceId))
            ->orderByDesc('review_date')
            ->limit(15)
            ->get();

        return view('reviews.index', [
            'reviews' => $reviews,
            'sources' => $sources,
            'manualReviews' => $manualReviews,
            'showManualReviews' => ! $sourceId || $sourceId === $monggoLaporSourceId,
            'manualReview' => $request->session()->has('manual_review_id')
                ? ManualReview::with('source')->find($request->session()->get('manual_review_id'))
                : null,
        ]);
    }

    public function storeMonggoLapor(Request $request)
    {
        $validated = $request->validate([
            'review_text' => ['required', 'string'],
        ]);

        $source = Source::firstOrCreate(
            ['name' => 'monggo_lapor'],
            ['label' => 'Monggo Lapor'],
        );

        $review = ManualReview::create([
            'source_id' => $source->id,
            'review_text' => $validated['review_text'],
            'review_date' => now(),
        ]);

        ActivityLogService::log(
            'created',
            'Review / Monggo Lapor',
            'Menambahkan komentar manual dari Monggo Lapor.',
            $review,
            null,
            ['source' => 'Monggo Lapor'],
        );

        try {
            AnalyzeMonggoLaporReview::dispatch($review->id);
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()->route('reviews.index')->with([
                'warning' => 'Komentar berhasil ditambahkan, tetapi antrean analisis belum dapat dibuat. Data tetap tersimpan untuk diproses ulang.',
                'manual_review_id' => $review->id,
            ]);
        }

        return redirect()->route('reviews.index')->with([
            'status' => 'Komentar berhasil ditambahkan dan sedang diproses sentimennya.',
            'manual_review_id' => $review->id,
        ]);
    }
}
