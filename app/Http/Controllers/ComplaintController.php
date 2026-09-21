<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Complaint;
use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ComplaintController extends Controller
{
    public function index(Request $request): View
    {
        $query = Complaint::with(['review.source', 'review.trackedUrl', 'category']);

        if ($status = $request->string('status')->trim()->value()) {
            $query->where('status', $status);
        }

        $complaints = $query->orderByRaw("status = 'belum_dibalas' desc")
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $categories = Category::orderBy('name')->get();

        $statusCounts = Complaint::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('complaints.index', [
            'complaints' => $complaints,
            'categories' => $categories,
            'statusCounts' => $statusCounts,
        ]);
    }

    public function update(Request $request, Complaint $complaint): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:belum_dibalas,sudah_dibalas,selesai'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'response_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $wasUnanswered = $complaint->status === 'belum_dibalas';
        $oldValues = [
            'status' => $complaint->status,
            'category_id' => $complaint->category_id,
            'response_note' => $complaint->response_note,
            'responded_at' => $complaint->responded_at,
        ];

        $complaint->update([
            'status' => $validated['status'],
            'category_id' => $validated['category_id'] ?? null,
            'response_note' => $validated['response_note'] ?? null,
            'responded_at' => $wasUnanswered && $validated['status'] !== 'belum_dibalas'
                ? now()
                : $complaint->responded_at,
        ]);

        $newValues = [
            'status' => $complaint->status,
            'category_id' => $complaint->category_id,
            'response_note' => $complaint->response_note,
            'responded_at' => $complaint->responded_at,
        ];

        $action = $oldValues['status'] !== $newValues['status'] ? 'status_changed' : 'updated';

        ActivityLogService::log(
            $action,
            'Komplain',
            'Memperbarui status dan data komplain #' . $complaint->id,
            $complaint,
            $oldValues,
            $newValues,
        );

        return redirect()->route('complaints.index')->with('status', 'Komplain berhasil diperbarui.');
    }
}
