<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(Request $request): View
    {
        $actor = $request->user();

        $users = User::query()
            ->when(! $actor->isSuperAdmin(), fn ($query) => $query->whereKey($actor->id))
            ->orderByDesc('created_at')
            ->get();

        return view('settings.index', [
            'actor' => $actor,
            'users' => $users,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => 'admin', // hanya superadmin bawaan yang bisa punya role ini, tidak bisa dibuat lewat form
        ]);

        ActivityLogService::log(
            'created',
            'Pengaturan',
            'Membuat akun admin baru: ' . $user->name,
            $user,
            null,
            [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
        );

        return redirect()->route('settings.index')->with('status', 'Akun admin berhasil dibuat.');
    }

    public function updateUser(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();

        abort_unless($actor->isSuperAdmin() || $actor->is($user), 403);

        $validated = $request->validateWithBag('userUpdate', [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'confirmed', Password::min(8)],
            'role' => $actor->isSuperAdmin()
                ? ['required', Rule::in($actor->is($user) ? ['superadmin'] : ['admin', 'superadmin'])]
                : ['prohibited'],
        ]);

        $oldValues = $user->only(['name', 'email', 'role']);

        $user->name = $validated['name'];
        $user->email = $validated['email'];

        if ($actor->isSuperAdmin()) {
            $user->role = $validated['role'];
        }

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        ActivityLogService::log(
            'updated',
            'Akun',
            $actor->name . ' mengubah akun ' . $user->name . '.',
            $user,
            $oldValues,
            $user->only(['name', 'email', 'role']),
        );

        return redirect()->route('settings.index')->with('status', 'Data akun berhasil diperbarui.');
    }
}
