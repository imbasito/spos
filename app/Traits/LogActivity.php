<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

trait LogActivity
{
    public static function bootLogActivity()
    {
        static::created(function ($model) {
            self::logAction('Created', $model);
        });

        static::updated(function ($model) {
            self::logAction('Updated', $model, $model->getChanges());
        });

        static::deleted(function ($model) {
            self::logAction('Deleted', $model);
        });
    }

    protected static function logAction($action, $model, $changes = null)
    {
        try {
            $user_id = Auth::id() ?? null;
            $module = class_basename($model);
            
            // Skip non-critical updates if needed, but for now capture all.
            
            ActivityLog::create([
                'user_id' => $user_id,
                'action' => $action,
                'module' => $module,
                'description' => "$action $module #{$model->id}",
                'subject_id' => $model->id,
                'subject_type' => get_class($model),
                'properties' => $changes ? json_encode($changes) : null,
                'ip_address' => request()->ip(),
            ]);

            // AUTOMATED CLEANUP: 2% Chance to delete logs older than 30 Days
            if (rand(1, 100) <= 2) {
                ActivityLog::where('created_at', '<', now()->subDays(30))->delete();
            }

        } catch (\Exception $e) {
            // Silently fail to not break app flow
        }
    }
}
