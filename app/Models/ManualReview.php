<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManualReview extends Model
{
    protected $fillable = ['source_id', 'review_text', 'review_date', 'sentiment_label', 'sentiment_score', 'rating'];

    protected $casts = [
        'review_date' => 'datetime',
        'sentiment_score' => 'float',
    ];

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }
}
