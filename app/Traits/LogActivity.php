<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

trait LogActivity
{
    public static function bootLogActivity(): void
    {
        static::created(function ($model) {
            self::logAction('Created', $model);
        });

        static::updated(function ($model) {
            // Build a clean old→new diff, skip timestamp-only changes
            $changes  = $model->getChanges();
            $original = $model->getOriginal();

            unset($changes['updated_at'], $original['updated_at']);

            if (empty($changes)) {
                return; // Nothing meaningful changed
            }

            $diff = [];
            foreach ($changes as $key => $newValue) {
                $diff[$key] = [
                    'old' => $original[$key] ?? null,
                    'new' => $newValue,
                ];
            }

            self::logAction('Updated', $model, $diff);
        });

        static::deleted(function ($model) {
            self::logAction('Deleted', $model);
        });
    }

    protected static function logAction(string $action, $model, ?array $properties = null): void
    {
        try {
            ActivityLog::create([
                'user_id'      => Auth::id(),
                'action'       => $action,
                'module'       => class_basename($model),
                'description'  => "{$action} " . class_basename($model) . " #{$model->id}",
                'subject_id'   => $model->id,
                'subject_type' => get_class($model),
                'properties'   => $properties,
                'ip_address'   => request()->ip(),
            ]);

            // ── Deterministic cleanup ───────────────────────────────────────────
            // Purge logs older than 30 days once the table exceeds 10,000 rows.
            // Runs at most once per request (cached flag), so no extra queries on
            // normal pages.
            if (!app()->bound('_activity_cleanup_done')) {
                app()->instance('_activity_cleanup_done', true);
                $count = ActivityLog::count();
                if ($count > 10000) {
                    ActivityLog::where('created_at', '<', now()->subDays(30))->delete();
                }
            }

        } catch (\Exception $e) {
            // Silently fail — never break the main app flow
        }
    }
}
