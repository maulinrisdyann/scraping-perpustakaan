<?php

use App\Models\AutomationSetting;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

if (AutomationSetting::isEnabled()) {
    // Jadwal scraping otomatis. Interval IG & FB sengaja lebih longgar (tiap 4
    // jam) dibanding GMaps (tiap 1 jam) karena akun dummy-nya masih baru --
    // digenjot terlalu sering berisiko kena checkpoint/rate-limit lagi seperti
    // yang terjadi saat development (lihat catatan Fase 4 & 5). Jadwal
    // masing-masing di-stagger (menit berbeda) biar 3 browser Chrome tidak
    // coba jalan bersamaan.
    //
    // PENTING: scraper GMaps & Facebook butuh browser window nyata
    // (headless=False) -- lolos deteksi anti-bot butuh ini. Jadi jadwal ini
    // hanya akan berjalan mulus selama Mac ini menyala & user sedang login
    // desktop (bukan lewat SSH headless). Instagram (instaloader, murni API)
    // tidak butuh browser sama sekali.
    Schedule::command('scrape:google-maps')->hourlyAt(0)->withoutOverlapping();
    Schedule::command('scrape:instagram')->cron('20 */4 * * *')->withoutOverlapping();
    Schedule::command('scrape:facebook')->cron('40 */4 * * *')->withoutOverlapping();

    // Jalan tiap jam setelah siklus scraping, cukup 50 menit dari :00 supaya
    // scraper di atas sempat selesai duluan. Idempotent & murah (cuma proses
    // review yang sentiment_label-nya masih kosong), aman di-jadwalkan sering.
    Schedule::command('sentiment:analyze')->hourlyAt(50)->withoutOverlapping();
}
