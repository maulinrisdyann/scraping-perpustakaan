<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk — Perpustakaan Kota Magelang</title>

    @include('partials.head-assets')
</head>

<body class="bg-bg text-txprimary min-h-screen flex items-center justify-center px-4">
    <div class="w-full max-w-sm fade-slide-in">
        <div class="flex flex-col items-center mb-8">

            <div class="w-13 h-13 flex items-center justify-center shrink-0">
                <img src="{{ asset('images/logo-kota.png') }}" alt="Logo Perpusip Magelang"
                    class="w-12 h-12 object-contain">
            </div>

            <h1 class="text-center font-display text-xl font-semibold text-txprimary">Dinas Perpustakaan & Kearsipan Kota Magelang</h1>
            <p class="text-sm text-txsecondary">Ulasan &amp; Sentimen</p>
        </div>

        <div class="glass rounded-none p-7">
            @if ($errors->any())
                <div class="mb-4 text-sm text-danger space-y-1">
                    @foreach ($errors->all() as $error)
                        <p><i class="fa-solid fa-circle-exclamation mr-1.5"></i>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('login.store') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="email" class="block text-xs text-txsecondary mb-1.5">Email</label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus
                        placeholder="admin@gmail.com"
                        class="w-full bg-black/5 dark:bg-white/5 border border-black/10 dark:border-white/10 rounded-xl px-4 py-2.5 text-sm text-txprimary placeholder:text-txsecondary focus:outline-none focus:border-violet/60">
                </div>

                <div>
                    <label for="password" class="block text-xs text-txsecondary mb-1.5">Password</label>
                    <input type="password" name="password" id="password" required placeholder="••••••••"
                        class="w-full bg-black/5 dark:bg-white/5 border border-black/10 dark:border-white/10 rounded-xl px-4 py-2.5 text-sm text-txprimary placeholder:text-txsecondary focus:outline-none focus:border-violet/60">
                </div>

                <label class="flex items-center gap-2 text-sm text-txsecondary">
                    <input type="checkbox" name="remember"
                        class="rounded border-black/20 dark:border-white/20 bg-black/5 dark:bg-white/5 text-violet focus:ring-violet/50">
                    Ingat saya
                </label>

                <div class="text-right">
                    <a href="{{ route('password.request') }}" class="text-xs text-violet hover:text-teal">
                        Lupa password?
                    </a>
                </div>

                <button type="submit"
                    class="w-full bg-gradient-to-r from-violet to-teal text-bg font-medium text-sm py-2.5 rounded-xl glow-hover">
                    Masuk
                </button>
            </form>
        </div>

        <p class="text-center text-xs text-txsecondary mt-6">
            Dinas Perpustakaan &amp; Kearsipan Kota Magelang
        </p>
    </div>
</body>

</html>
