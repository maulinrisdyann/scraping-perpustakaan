<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ActivityLogService
{
    public static function log(
        string $action,
        string $module,
        string $description,
        ?object $subject = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $userName = null,
        ?int $userId = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): void {
        $user = Auth::user();

        $record = new ActivityLog();
        $record->user_id = $userId ?? $user?->id;
        $record->user_name = $userName ?? $user?->name ?? 'System';
        $record->action = $action;
        $record->module = $module;
        $record->subject_type = $subject ? $subject::class : null;
        $record->subject_id = $subject?->getKey();
        $record->description = $description;
        $record->old_values = self::sanitizeValues($oldValues);
        $record->new_values = self::sanitizeValues($newValues);
        $record->ip_address = $ipAddress ?? request()->ip();
        $record->user_agent = $userAgent ?? request()->header('User-Agent');
        $record->created_at = now();

        $record->save();
    }

    protected static function sanitizeValues(?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

        foreach ($values as $key => $value) {
            if (is_string($value) && Str::contains(strtolower($key), ['password', 'token', 'secret', 'api_key', 'authorization', 'cookie'])) {
                $values[$key] = '[redacted]';
                continue;
            }

            if (is_array($value)) {
                $values[$key] = self::sanitizeValues($value);
            }
        }

        return $values;
    }
}
