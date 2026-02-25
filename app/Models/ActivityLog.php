<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action',
        'module',
        'description',
        'subject_id',
        'subject_type',
        'properties',
        'ip_address',
    ];

    protected $casts = [
        'properties' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ─── Relationships ─────────────────────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class)->withDefault(['name' => 'System']);
    }

    // ─── Scopes ────────────────────────────────────────────────────────────────

    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeByModule($query, string $module)
    {
        return $query->where('module', $module);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByDateRange($query, string $start, string $end)
    {
        return $query->whereDate('created_at', '>=', $start)
                     ->whereDate('created_at', '<=', $end);
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Returns a Tailwind/Bootstrap colour class based on the action type.
     */
    public function getActionColorAttribute(): string
    {
        return match ($this->action) {
            'Created' => 'success',
            'Updated' => 'info',
            'Deleted' => 'danger',
            'Login'   => 'primary',
            default   => 'secondary',
        };
    }

    /**
     * Returns a FontAwesome icon class for the action.
     */
    public function getActionIconAttribute(): string
    {
        return match ($this->action) {
            'Created' => 'fa-plus-circle',
            'Updated' => 'fa-edit',
            'Deleted' => 'fa-trash-alt',
            'Login'   => 'fa-sign-in-alt',
            default   => 'fa-dot-circle',
        };
    }

    // ─── Purge ─────────────────────────────────────────────────────────────────

    /**
     * Delete logs older than 30 days. Call via a scheduled command or manually.
     */
    public static function purgeOld(int $days = 30): int
    {
        return static::where('created_at', '<', now()->subDays($days))->delete();
    }
}
