<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\RunsPythonScript;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('scrape:facebook')]
#[Description('Scrape komentar Facebook untuk semua tracked_urls aktif')]
class ScrapeFacebook extends Command
{
    use RunsPythonScript;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        return $this->runPythonScript('facebook', 'scrape_facebook.py');
    }
}
