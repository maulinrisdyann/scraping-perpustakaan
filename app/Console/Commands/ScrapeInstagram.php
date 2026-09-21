<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\RunsPythonScript;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('scrape:instagram')]
#[Description('Scrape komentar Instagram untuk semua tracked_urls aktif')]
class ScrapeInstagram extends Command
{
    use RunsPythonScript;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        return $this->runPythonScript('instagram', 'scrape_instagram.py');
    }
}
