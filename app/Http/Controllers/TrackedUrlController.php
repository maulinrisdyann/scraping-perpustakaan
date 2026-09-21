<?php

namespace App\Http\Controllers;

use App\Exports\TrackedUrlReviewsExport;
use App\Models\Source;
use App\Models\TrackedUrl;
use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class TrackedUrlController extends Controller
{
    public function index(): View
    {
        $trackedUrls = TrackedUrl::with('source')
            ->withCount('reviews')
            ->orderByDesc('created_at')
            ->get();

        // Google Maps di-setup sekali lewat seeder (search-based, bukan url manual),
        // jadi form tambah url di sini cuma buat platform yang butuh input link postingan.
        $sources = Source::whereIn('name', ['instagram', 'facebook'])->orderBy('label')->get();

        return view('tracked-urls.index', [
            'trackedUrls' => $trackedUrls,
            'sources' => $sources,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'source_id' => ['required', 'exists:sources,id'],
            'label' => ['required', 'string', 'max:255'],
            'url' => ['required', 'url', 'max:2048'],
        ]);

        $trackedUrl = TrackedUrl::create([
            'source_id' => $validated['source_id'],
            'label' => $validated['label'],
            'url' => $validated['url'],
            'is_active' => true,
        ]);

        ActivityLogService::log(
            'created',
            'Sumber Pantauan',
            'Menambahkan sumber pantauan baru: ' . $trackedUrl->label,
            $trackedUrl,
            null,
            [
                'source_id' => $trackedUrl->source_id,
                'label' => $trackedUrl->label,
                'url' => $trackedUrl->url,
                'is_active' => $trackedUrl->is_active,
            ],
        );

        return redirect()->route('tracked-urls.index')->with('status', 'URL berhasil ditambahkan.');
    }

    public function export(TrackedUrl $trackedUrl)
    {
        $filename = 'ulasan_' . $trackedUrl->id . '_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new TrackedUrlReviewsExport($trackedUrl), $filename);
    }

    public function toggle(TrackedUrl $trackedUrl): RedirectResponse
    {
        $oldValues = ['is_active' => $trackedUrl->is_active];
        $newStatus = ! $trackedUrl->is_active;

        $trackedUrl->update(['is_active' => $newStatus]);

        ActivityLogService::log(
            $newStatus ? 'activated' : 'deactivated',
            'Sumber Pantauan',
            'Mengubah status sumber pantauan: ' . $trackedUrl->label,
            $trackedUrl,
            $oldValues,
            ['is_active' => $newStatus],
        );

        return redirect()->route('tracked-urls.index');
    }
}
