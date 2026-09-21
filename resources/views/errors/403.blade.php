<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Akses Ditolak — Perpustakaan Kota Magelang</title>

    @include('partials.head-assets')
</head>
<body class="bg-bg text-txprimary min-h-screen flex items-center justify-center px-4">
    <div class="w-full max-w-sm text-center fade-slide-in">
        <div class="w-14 h-14 rounded-2xl bg-danger/10 flex items-center justify-center mx-auto mb-5">
            <i class="fa-solid fa-lock text-danger text-xl"></i>
        </div>
        <h1 class="font-display text-2xl font-semibold text-txprimary mb-2">Akses Ditolak</h1>
        <p class="text-sm text-txsecondary mb-8">Halaman ini khusus untuk Super Admin. Hubungi administrator kalau kamu butuh akses.</p>
        <a href="{{ route('dashboard.overview') }}" class="inline-block bg-gradient-to-r from-violet to-teal text-bg font-medium text-sm px-6 py-2.5 rounded-xl glow-hover">
            Kembali ke Dashboard
        </a>
    </div>
</body>
</html>
