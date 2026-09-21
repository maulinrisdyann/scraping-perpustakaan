<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\RunsPythonScript;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('sentiment:analyze')]
#[Description('Jalankan analisis sentimen untuk review yang belum dianalisis')]
class AnalyzeSentiment extends Command
{
    use RunsPythonScript;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        return $this->runPythonScript('sentiment', 'analyze_sentiment.py', timeoutSeconds: 300);
    }
}
