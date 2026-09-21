@extends('layouts.app')

@section('title', 'Pengaturan')
@section('subtitle', 'Kelola akun pengguna dashboard')

@section('content')
<div class="space-y-6">

    @if (session('status'))
        <div class="glass rounded-none px-5 py-3.5 text-sm text-success fade-slide-in">
            <i class="fa-solid fa-circle-check mr-2"></i>{{ session('status') }}
        </div>
    @endif

    @if ($actor->isSuperAdmin())
    <div class="glass rounded-none p-6">
        <h2 class="font-display font-semibold text-lg mb-1">Tambah Akun Admin</h2>
        <p class="text-xs text-txsecondary mb-4">Akun baru selalu dibuat dengan role Admin. Hanya Super Admin yang bisa menambah akun.</p>

        @if ($errors->any())
            <div class="mb-4 text-sm text-danger space-y-1">
                @foreach ($errors->all() as $error)
                    <p><i class="fa-solid fa-circle-exclamation mr-1.5"></i>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('settings.store') }}" class="flex flex-wrap gap-3 items-start">
            @csrf

            <input
                type="text"
                name="name"
                value="{{ old('name') }}"
                placeholder="Nama"
                class="bg-black/5 dark:bg-white/5 border border-black/10 dark:border-white/10 rounded-none px-4 py-2.5 text-sm text-txprimary placeholder:text-txsecondary focus:outline-none focus:border-violet/60 min-w-[180px]"
            >

            <input
                type="email"
                name="email"
                value="{{ old('email') }}"
                placeholder="Email"
                class="bg-black/5 dark:bg-white/5 border border-black/10 dark:border-white/10 rounded-none px-4 py-2.5 text-sm text-txprimary placeholder:text-txsecondary focus:outline-none focus:border-violet/60 min-w-[200px]"
            >

            <input
                type="password"
                name="password"
                placeholder="Password"
                class="bg-black/5 dark:bg-white/5 border border-black/10 dark:border-white/10 rounded-none px-4 py-2.5 text-sm text-txprimary placeholder:text-txsecondary focus:outline-none focus:border-violet/60 min-w-[160px]"
            >

            <input
                type="password"
                name="password_confirmation"
                placeholder="Konfirmasi Password"
                class="bg-black/5 dark:bg-white/5 border border-black/10 dark:border-white/10 rounded-none px-4 py-2.5 text-sm text-txprimary placeholder:text-txsecondary focus:outline-none focus:border-violet/60 min-w-[180px]"
            >

            <button type="submit" class="bg-violet text-bg font-medium text-sm px-5 py-2.5 rounded-none glow-hover">
                Tambah Akun
            </button>
        </form>
    </div>
    @endif

    <div class="glass rounded-none overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-black/10 dark:border-white/10 text-left text-txsecondary text-xs uppercase tracking-wide">
                        <th class="px-5 py-3.5 font-medium">Nama</th>
                        <th class="px-5 py-3.5 font-medium">Email</th>
                        <th class="px-5 py-3.5 font-medium">Role</th>
                        <th class="px-5 py-3.5 font-medium">Bergabung</th>
                        <th class="px-5 py-3.5 font-medium text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr class="border-b border-black/5 dark:border-white/5 last:border-0 hover:bg-black/[0.02] dark:hover:bg-white/[0.03]">
                            <td class="px-5 py-4 align-top font-medium text-txprimary">{{ $user->name }}</td>
                            <td class="px-5 py-4 align-top text-txsecondary">{{ $user->email }}</td>
                            <td class="px-5 py-4 align-top">
                                @if ($user->isSuperAdmin())
                                    <span class="text-xs px-2.5 py-1 rounded-full bg-violet text-bg font-medium whitespace-nowrap">Super Admin</span>
                                @else
                                    <span class="text-xs px-2.5 py-1 rounded-full bg-black/5 dark:bg-white/5 text-txsecondary whitespace-nowrap">Admin</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 align-top text-txsecondary whitespace-nowrap">{{ $user->created_at->diffForHumans() }}</td>
                            <td class="px-5 py-4 align-top text-right">
                                <button type="button" onclick="document.getElementById('edit-user-{{ $user->id }}').showModal()"
                                    class="bg-violet text-bg font-medium text-xs px-3 py-2 rounded-none glow-hover">
                                    Edit
                                </button>
                            </td>
                        </tr>

                        @php($hasUpdateErrors = $errors->userUpdate->any() && (int) old('editing_user') === $user->id)
                        <dialog id="edit-user-{{ $user->id }}" class="w-full max-w-lg bg-transparent p-4 backdrop:bg-black/60" @if ($hasUpdateErrors) open @endif>
                            <div class="glass rounded-none p-6 text-txprimary">
                                <div class="flex items-start justify-between gap-4 mb-5">
                                    <div>
                                        <h2 class="font-display font-semibold text-lg">Edit Akun</h2>
                                        <p class="text-xs text-txsecondary mt-1">Perbarui data {{ $user->name }}. Password kosong tidak akan diubah.</p>
                                    </div>
                                    <button type="button" onclick="this.closest('dialog').close()" class="text-txsecondary hover:text-txprimary" aria-label="Tutup">&times;</button>
                                </div>

                                @if ($hasUpdateErrors)
                                    <div class="mb-4 text-sm text-danger space-y-1">
                                        @foreach ($errors->userUpdate->all() as $error)
                                            <p><i class="fa-solid fa-circle-exclamation mr-1.5"></i>{{ $error }}</p>
                                        @endforeach
                                    </div>
                                @endif

                                <form method="POST" action="{{ route('settings.users.update', $user) }}" class="grid gap-4 md:grid-cols-2">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="editing_user" value="{{ $user->id }}">

                                    <div>
                                        <label for="name-{{ $user->id }}" class="block text-xs text-txsecondary mb-1.5">Nama</label>
                                        <input type="text" name="name" id="name-{{ $user->id }}" value="{{ $hasUpdateErrors ? old('name') : $user->name }}" required autocomplete="name"
                                            class="w-full bg-black/5 dark:bg-white/5 border border-black/10 dark:border-white/10 rounded-none px-4 py-2.5 text-sm text-txprimary focus:outline-none focus:border-violet/60">
                                    </div>

                                    <div>
                                        <label for="email-{{ $user->id }}" class="block text-xs text-txsecondary mb-1.5">Email</label>
                                        <input type="email" name="email" id="email-{{ $user->id }}" value="{{ $hasUpdateErrors ? old('email') : $user->email }}" required autocomplete="email"
                                            class="w-full bg-black/5 dark:bg-white/5 border border-black/10 dark:border-white/10 rounded-none px-4 py-2.5 text-sm text-txprimary focus:outline-none focus:border-violet/60">
                                    </div>

                                    @if ($actor->isSuperAdmin())
                                        <div>
                                            <label for="role-{{ $user->id }}" class="block text-xs text-txsecondary mb-1.5">Role</label>
                                            <select name="role" id="role-{{ $user->id }}" class="w-full bg-black/5 dark:bg-white/5 border border-black/10 dark:border-white/10 rounded-full px-4 py-2.5 text-sm text-txprimary focus:outline-none focus:border-violet/60">
                                                <option value="admin" @selected(($hasUpdateErrors ? old('role') : $user->role) === 'admin')>Admin</option>
                                                <option value="superadmin" @selected(($hasUpdateErrors ? old('role') : $user->role) === 'superadmin')>Super Admin</option>
                                            </select>
                                        </div>
                                    @endif

                                    <div>
                                        <label for="password-{{ $user->id }}" class="block text-xs text-txsecondary mb-1.5">Password Baru</label>
                                        <input type="password" name="password" id="password-{{ $user->id }}" autocomplete="new-password"
                                            class="w-full bg-black/5 dark:bg-white/5 border border-black/10 dark:border-white/10 rounded-none px-4 py-2.5 text-sm text-txprimary focus:outline-none focus:border-violet/60">
                                    </div>

                                    <div>
                                        <label for="password-confirmation-{{ $user->id }}" class="block text-xs text-txsecondary mb-1.5">Konfirmasi Password Baru</label>
                                        <input type="password" name="password_confirmation" id="password-confirmation-{{ $user->id }}" autocomplete="new-password"
                                            class="w-full bg-black/5 dark:bg-white/5 border border-black/10 dark:border-white/10 rounded-none px-4 py-2.5 text-sm text-txprimary focus:outline-none focus:border-violet/60">
                                    </div>

                                    <div class="md:col-span-2 flex justify-end gap-3">
                                        <button type="button" onclick="this.closest('dialog').close()" class="border border-black/10 dark:border-white/10 text-txsecondary text-sm px-4 py-2.5 rounded-none">Batal</button>
                                        <button type="submit" class="bg-violet text-bg font-medium text-sm px-4 py-2.5 rounded-none glow-hover">Simpan Perubahan</button>
                                    </div>
                                </form>
                            </div>
                        </dialog>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
