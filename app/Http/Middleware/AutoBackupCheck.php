<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AutoBackupCheck
{
    /**
     * Check if auto-backup should run on app access.
     * For desktop apps, we can't use cron, so we check on each request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip backup check during activation
        $currentRoute = $request->route() ? $request->route()->getName() : null;
        if ($currentRoute && in_array($currentRoute, ['license.activate.show', 'license.activate.public'])) {
            return $next($request);
        }

        // Only check once per session to avoid performance issues
        if (!session()->has('auto_backup_checked')) {
            session(['auto_backup_checked' => true]);
            $this->checkAutoBackup();
        }

        return $next($request);
    }

    protected function checkAutoBackup(): void
    {
        try {
            $autoBackup = readConfig('auto_backup') ?: 'off';
            
            if ($autoBackup === 'off') {
                return;
            }

            $lastBackup = readConfig('last_backup_date');
            $now = now();
            
            $shouldBackup = false;
            
            if (!$lastBackup) {
                $shouldBackup = true;
            } else {
                $lastBackupDate = \Carbon\Carbon::parse($lastBackup);
                
                if ($autoBackup === 'daily' && $lastBackupDate->diffInDays($now) >= 1) {
                    $shouldBackup = true;
                } else if ($autoBackup === 'weekly' && $lastBackupDate->diffInDays($now) >= 7) {
                    $shouldBackup = true;
                }
            }

            if ($shouldBackup) {
                // Trigger backup in background
                $controller = new \App\Http\Controllers\Backend\BackupController();
                $controller->createBackup(new \Illuminate\Http\Request());
            }
        } catch (\Exception $e) {
            // Silently fail - don't break app if backup fails
            \Log::error('Auto-backup failed: ' . $e->getMessage());
        }
    }
}
