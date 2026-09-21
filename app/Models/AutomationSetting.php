<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class AutomationSetting extends Model
{
    protected $table = 'automation_settings';

    protected $fillable = [
        'automation_enabled',
    ];

    protected $casts = [
        'automation_enabled' => 'boolean',
    ];

    public static function isEnabled(): bool
    {
        if (! Schema::hasTable((new self)->getTable())) {
            return true;
        }

        return (bool) self::query()->firstOrCreate(
            [],
            ['automation_enabled' => true],
        )->automation_enabled;
    }

    public static function toggle(): bool
    {
        if (! Schema::hasTable((new self)->getTable())) {
            return true;
        }

        $setting = self::query()->firstOrCreate(
            [],
            ['automation_enabled' => true],
        );

        $setting->automation_enabled = ! $setting->automation_enabled;
        $setting->save();

        return $setting->automation_enabled;
    }
}
