<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lupa Password - Perpusip Magelang</title>

    @include('partials.head-assets')
</head>

<body class="bg-bg text-txprimary min-h-screen flex items-center justify-center px-4">
    <div class="w-full max-w-sm fade-slide-in">
        <div class="flex flex-col items-center mb-8">
            <div class="w-13 h-13 flex items-center justify-center shrink-0">
                <img src="{{ asset('images/logo-kota.png') }}" alt="Logo Perpusip Magelang" class="w-12 h-12 object-contain">
            </div>
            <h1 class="text-center font-display text-xl font-semibold text-txprimary">Dinas Perpustakaan &amp; Kearsipan Kota Magelang</h1>
            <p class="text-sm text-txsecondary">Lupa Password</p>
        </div>

        <div class="glass rounded-none p-7">
            <p class="mb-5 text-sm text-txsecondary">Masukkan email Anda. Kami akan mengirimkan link untuk mengatur ulang password.</p>

            @if (session('status'))
                <div class="mb-4 text-sm text-teal">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="mb-4 text-sm text-danger space-y-1">
                    @foreach ($errors->all() as $error)
                        <p><i class="fa-solid fa-circle-exclamation mr-1.5"></i>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="email" class="block text-xs text-txsecondary mb-1.5">Email</label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus autocomplete="email"
                        placeholder="admin@gmail.com"
                        class="w-full bg-black/5 dark:bg-white/5 border border-black/10 dark:border-white/10 rounded-none px-4 py-2.5 text-sm text-txprimary placeholder:text-txsecondary focus:outline-none focus:border-violet/60">
                </div>
                <button type="submit" class="w-full bg-violet text-bg font-medium text-sm py-2.5 rounded-none glow-hover">
                    Kirim Link Reset Password
                </button>
            </form>
        </div>

        <p class="text-center text-sm text-txsecondary mt-6">
            <a href="{{ route('login') }}" class="text-violet hover:text-teal">Kembali ke Login</a>
        </p>
    </div>
</body>

</html>
