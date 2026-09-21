<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScrapeLog extends Model
{
    protected $fillable = [
        'source_id', 'tracked_url_id', 'status', 'new_reviews_count',
        'message', 'started_at', 'finished_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }

    public function trackedUrl(): BelongsTo
    {
        return $this->belongsTo(TrackedUrl::class);
    }
}
