<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrackedUrl extends Model
{
    protected $fillable = [
        'source_id', 'label', 'search_query', 'url',
        'place_identifier', 'is_active', 'last_scraped_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_scraped_at' => 'datetime',
    ];

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function scrapeLogs(): HasMany
    {
        return $this->hasMany(ScrapeLog::class);
    }
}
