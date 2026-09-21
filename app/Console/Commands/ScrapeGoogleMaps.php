<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\RunsPythonScript;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('scrape:google-maps')]
#[Description('Scrape ulasan Google Maps untuk semua tracked_urls aktif')]
class ScrapeGoogleMaps extends Command
{
    use RunsPythonScript;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        return $this->runPythonScript('scraper', 'scrape_google_maps.py');
    }
}
