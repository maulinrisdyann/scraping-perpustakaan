@extends('layouts.app')

@section('title', 'History Log')
@section('subtitle', 'Riwayat aktivitas admin yang melakukan perubahan data')

@section('content')
<div class="space-y-5">
    <form method="GET" action="{{ route('activity-logs.index') }}" class="glass rounded-none p-4 flex flex-wrap gap-3 items-center">
        <div class="relative flex-1 min-w-[220px]">
            <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-txsecondary text-sm"></i>
            <input
                type="text"
                name="q"
                value="{{ request('q') }}"
                placeholder="Cari deskripsi aktivitas..."
                class="w-full bg-black/5 dark:bg-white/5 border border-black/10 dark:border-white/10 rounded-none pl-10 pr-4 py-2.5 text-sm text-txprimary placeholder:text-txsecondary focus:outline-none focus:border-violet/60"
            >
        </div>

        <select name="user_id" class="bg-black/5 dark:bg-white/5 border border-black/10 dark:border-white/10 rounded-none px-3.5 py-2.5 text-sm text-txprimary focus:outline-none focus:border-violet/60">
            <option value="">Semua Admin</option>
            @foreach ($admins as $admin)
                <option value="{{ $admin->id }}" @selected(request('user_id') == $admin->id)>{{ $admin->name }}</option>
            @endforeach
        </select>

        <select name="module" class="bg-black/5 dark:bg-white/5 border border-black/10 dark:border-white/10 rounded-none px-3.5 py-2.5 text-sm text-txprimary focus:outline-none focus:border-violet/60">
            <option value="">Semua Modul</option>
            @foreach (['Review', 'Komplain', 'Sumber Pantauan', 'Kategori', 'User', 'Pengaturan'] as $module)
                <option value="{{ $module }}" @selected(request('module') == $module)>{{ $module }}</option>
            @endforeach
        </select>

        <select name="action" class="bg-black/5 dark:bg-white/5 border border-black/10 dark:border-white/10 rounded-none px-3.5 py-2.5 text-sm text-txprimary focus:outline-none focus:border-violet/60">
            <option value="">Semua Aktivitas</option>
            @foreach (['created', 'updated', 'deleted', 'status_changed', 'activated', 'deactivated'] as $action)
                <option value="{{ $action }}" @selected(request('action') == $action)>{{ $action }}</option>
            @endforeach
        </select>

        <button type="submit" class="bg-gradient-to-r from-violet to-teal text-bg font-medium text-sm px-5 py-2.5 rounded-xl glow-hover">
            Filter
        </button>

        @if (request()->anyFilled(['q', 'user_id', 'module', 'action']))
            <a href="{{ route('activity-logs.index') }}" class="text-sm text-txsecondary hover:text-txprimary px-2">Reset</a>
        @endif
    </form>

    <div class="glass rounded-none overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-black/10 dark:border-white/10 text-left text-txsecondary text-xs uppercase tracking-wide">
                        <th class="px-5 py-3.5 font-medium">Waktu</th>
                        <th class="px-5 py-3.5 font-medium">Admin</th>
                        <th class="px-5 py-3.5 font-medium">Aktivitas</th>
                        <th class="px-5 py-3.5 font-medium">Modul</th>
                        <th class="px-5 py-3.5 font-medium">Deskripsi</th>
                        <th class="px-5 py-3.5 font-medium">Data</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr class="border-b border-black/5 dark:border-white/5 last:border-0 hover:bg-black/[0.02] dark:hover:bg-white/[0.03] align-top">
                            <td class="px-5 py-4 align-top whitespace-nowrap text-txsecondary">
                                {{ $log->created_at?->translatedFormat('d M Y H:i') ?? '-' }}
                            </td>
                            <td class="px-5 py-4 align-top text-txprimary">
                                {{ $log->user_name ?? ($log->user?->name ?? 'System') }}
                            </td>
                            <td class="px-5 py-4 align-top">
                                <span class="text-xs px-2.5 py-1 rounded-full bg-black/5 dark:bg-white/5 text-txsecondary whitespace-nowrap">
                                    {{ $log->action }}
                                </span>
                            </td>
                            <td class="px-5 py-4 align-top text-txprimary">
                                {{ $log->module }}
                            </td>
                            <td class="px-5 py-4 align-top max-w-md text-txprimary/90">
                                {{ $log->description }}
                            </td>
                            <td class="px-5 py-4 align-top text-xs text-txsecondary">
                                @if ($log->old_values || $log->new_values)
                                    @if ($log->old_values)
                                        <div class="mb-1">
                                            <span class="font-medium text-txprimary">Sebelum:</span>
                                            <pre class="whitespace-pre-wrap break-words">{{ json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                        </div>
                                    @endif
                                    @if ($log->new_values)
                                        <div>
                                            <span class="font-medium text-txprimary">Sesudah:</span>
                                            <pre class="whitespace-pre-wrap break-words">{{ json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                        </div>
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center text-txsecondary">Belum ada riwayat aktivitas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="text-txprimary">
        {{ $logs->links() }}
    </div>
</div>
@endsection
