<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Source extends Model
{
    protected $fillable = ['name', 'label'];

    public function trackedUrls(): HasMany
    {
        return $this->hasMany(TrackedUrl::class);
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
