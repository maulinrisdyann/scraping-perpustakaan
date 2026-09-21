<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\RunsPythonScript;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('manual-reviews:analyze {reviewId : ID manual review yang akan dianalisis}')]
#[Description('Jalankan analisis sentimen untuk satu input manual Monggo Lapor')]
class AnalyzeManualReviewSentiment extends Command
{
    use RunsPythonScript;

    public function handle(): int
    {
        return $this->runPythonScript(
            'sentiment',
            'analyze_manual_review_sentiment.py',
            timeoutSeconds: 300,
            arguments: ['--review-id', (string) $this->argument('reviewId')],
        );
    }
}
