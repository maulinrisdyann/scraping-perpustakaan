<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $query = ActivityLog::query()->with('user')->orderByDesc('created_at');

        if ($userId = $request->integer('user_id')) {
            $query->where('user_id', $userId);
        }

        if ($module = $request->string('module')->trim()->value()) {
            $query->where('module', 'like', "%{$module}%");
        }

        if ($action = $request->string('action')->trim()->value()) {
            $query->where('action', $action);
        }

        if ($search = $request->string('q')->trim()->value()) {
            $query->where('description', 'like', "%{$search}%");
        }

        $logs = $query->paginate(20)->withQueryString();

        $admins = \App\Models\User::orderBy('name')->get();

        return view('activity-logs.index', [
            'logs' => $logs,
            'admins' => $admins,
        ]);
    }
}
