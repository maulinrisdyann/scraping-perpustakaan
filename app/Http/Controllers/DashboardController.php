<?php

namespace App\Http\Controllers;

use App\Models\AutomationSetting;
use App\Models\Complaint;
use App\Models\Review;
use App\Models\Source;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function overview()
    {
        $automationEnabled = AutomationSetting::isEnabled();
        $totalReviews = Review::count();
        $avgRating = Review::whereNotNull('rating')->avg('rating');
        $pendingComplaints = Complaint::where('status', 'belum_dibalas')->count();
        $unanalyzedCount = Review::whereNull('sentiment_label')->count();

        $sentimentCounts = Review::select(
                DB::raw("COALESCE(sentiment_label, 'belum_dianalisis') as label"),
                DB::raw('count(*) as total')
            )
            ->groupBy('label')
            ->pluck('total', 'label');

        $bySource = Source::withCount('reviews')->orderByDesc('reviews_count')->get();

        $recentReviews = Review::with(['source', 'trackedUrl'])
            ->orderByDesc('scraped_at')
            ->limit(5)
            ->get();

        return view('dashboard.overview', [
            'automationEnabled' => $automationEnabled,
            'totalReviews' => $totalReviews,
            'avgRating' => $avgRating,
            'pendingComplaints' => $pendingComplaints,
            'unanalyzedCount' => $unanalyzedCount,
            'sentimentCounts' => $sentimentCounts,
            'bySource' => $bySource,
            'recentReviews' => $recentReviews,
        ]);
    }

    public function toggleAutomation(Request $request): RedirectResponse
    {
        abort_unless($request->user(), 403);

        $automationEnabled = AutomationSetting::toggle();

        return redirect()->route('dashboard.overview')->with(
            'status',
            $automationEnabled
                ? 'Automatisasi scraping berhasil diaktifkan.'
                : 'Automatisasi scraping berhasil dinonaktifkan.'
        );
    }
}
