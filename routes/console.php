<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('scrape:google-maps')->hourlyAt(0)->withoutOverlapping();
Schedule::command('scrape:instagram')->cron('20 */4 * * *')->withoutOverlapping();
Schedule::command('scrape:facebook')->cron('40 */4 * * *')->withoutOverlapping();
Schedule::command('sentiment:analyze')->hourlyAt(50)->withoutOverlapping();
