<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Password - Perpusip Magelang</title>

    @include('partials.head-assets')
</head>

<body class="bg-bg text-txprimary min-h-screen flex items-center justify-center px-4">
    <div class="w-full max-w-sm fade-slide-in">
        <div class="flex flex-col items-center mb-8">
            <div class="w-13 h-13 flex items-center justify-center shrink-0">
                <img src="{{ asset('images/logo-kota.png') }}" alt="Logo Perpusip Magelang" class="w-12 h-12 object-contain">
            </div>
            <h1 class="text-center font-display text-xl font-semibold text-txprimary">Dinas Perpustakaan &amp; Kearsipan Kota Magelang</h1>
            <p class="text-sm text-txsecondary">Reset Password</p>
        </div>

        <div class="glass rounded-none p-7">
            @if ($errors->any())
                <div class="mb-4 text-sm text-danger space-y-1">
                    @foreach ($errors->all() as $error)
                        <p><i class="fa-solid fa-circle-exclamation mr-1.5"></i>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div>
                    <label for="email" class="block text-xs text-txsecondary mb-1.5">Email</label>
                    <input type="email" name="email" id="email" value="{{ old('email', $email) }}" required autofocus autocomplete="email"
                        class="w-full bg-black/5 dark:bg-white/5 border border-black/10 dark:border-white/10 rounded-none px-4 py-2.5 text-sm text-txprimary focus:outline-none focus:border-violet/60">
                </div>

                <div>
                    <label for="password" class="block text-xs text-txsecondary mb-1.5">Password Baru</label>
                    <input type="password" name="password" id="password" required autocomplete="new-password"
                        class="w-full bg-black/5 dark:bg-white/5 border border-black/10 dark:border-white/10 rounded-none px-4 py-2.5 text-sm text-txprimary focus:outline-none focus:border-violet/60">
                </div>

                <div>
                    <label for="password_confirmation" class="block text-xs text-txsecondary mb-1.5">Konfirmasi Password</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" required autocomplete="new-password"
                        class="w-full bg-black/5 dark:bg-white/5 border border-black/10 dark:border-white/10 rounded-none px-4 py-2.5 text-sm text-txprimary focus:outline-none focus:border-violet/60">
                </div>

                <button type="submit" class="w-full bg-violet text-bg font-medium text-sm py-2.5 rounded-none glow-hover">
                    Reset Password
                </button>
            </form>
        </div>
    </div>
</body>

</html>
