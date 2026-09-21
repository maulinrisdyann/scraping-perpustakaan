<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Review extends Model
{
    protected $fillable = [
        'source_id', 'tracked_url_id', 'external_review_id', 'author_name',
        'rating', 'review_text', 'review_relative_time', 'review_date',
        'owner_reply_text', 'owner_reply_relative_time',
        'sentiment_label', 'sentiment_score', 'scraped_at',
    ];

    protected $casts = [
        'review_date' => 'datetime',
        'scraped_at' => 'datetime',
        'sentiment_score' => 'float',
    ];

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }

    public function trackedUrl(): BelongsTo
    {
        return $this->belongsTo(TrackedUrl::class);
    }

    public function complaint(): HasOne
    {
        return $this->hasOne(Complaint::class);
    }
}
