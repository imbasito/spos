<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Auth;

class SystemResetController extends Controller
{
    /**
     * Executes the Factory Reset functionality.
     * Requires valid admin password and "CONFIRM" typed precisely.
     * Instantly creates a silent backup, then wipes transactional data.
     */
    public function resetSystem(Request $request)
    {
        $request->validate([
            'admin_password' => 'required',
            'confirm_text' => 'required|string'
        ]);

        if ($request->input('confirm_text') !== 'CONFIRM') {
            return response()->json([
                'success' => false, 
                'message' => 'Confirmation text must be exactly "CONFIRM" in all caps.'
            ], 403);
        }

        $user = Auth::user();
        if (!Hash::check($request->input('admin_password'), $user->password)) {
            return response()->json([
                'success' => false, 
                'message' => 'Incorrect Administrator password.'
            ], 403);
        }

        try {
            // 1. Silent Pre-Wipe Backup
            $this->createSilentBackup();

            // 2. Safely Truncate Transactional Tables
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            $tablesToWipe = [
                'pos_carts',
                'order_transactions',
                'order_products',
                'orders',
                'return_items',
                'returns',
                'purchase_items',
                'purchases',
                'activity_logs',
                'daily_closings',
                'fbr_invoices',
                'barcode_history',
                'products',
                'brands',
                'categories'
            ];

            foreach ($tablesToWipe as $table) {
                if (DB::getSchemaBuilder()->hasTable($table)) {
                    DB::table($table)->truncate();
                }
            }

            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            return response()->json([
                'success' => true,
                'message' => 'System factory reset successful. All transactional data has been securely wiped.'
            ]);

        } catch (\Exception $e) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1'); // Ensure enabled even if it fails
            return response()->json([
                'success' => false,
                'message' => 'A critical error occurred during the wipe process: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Runs a rapid mysqldump to save a state baseline before destruction.
     */
    private function createSilentBackup()
    {
        $backupPath = storage_path('app/backups');
        if (!File::exists($backupPath)) {
            File::makeDirectory($backupPath, 0755, true);
        }

        $filename = 'pre-wipe-backup-' . date('Y-m-d-H-i-s') . '.sql';
        $fullPath = $backupPath . DIRECTORY_SEPARATOR . $filename;

        // Database Config
        $dbName = config('database.connections.mysql.database');
        $dbUser = config('database.connections.mysql.username');
        $dbPass = config('database.connections.mysql.password');
        $dbHost = config('database.connections.mysql.host', '127.0.0.1');
        $dbPort = config('database.connections.mysql.port', '3306');
        
        $basePath = base_path();
        $mysqldump = $basePath . DIRECTORY_SEPARATOR . 'mysql' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'mysqldump.exe';
        
        if (!File::exists($mysqldump)) {
            $mysqldump = 'mysqldump'; 
        } else {
            $mysqldump = '"' . $mysqldump . '"';
        }

        $passwordPart = $dbPass ? "--password=\"{$dbPass}\"" : "";
        $dumpCommand = "{$mysqldump} --user=\"{$dbUser}\" {$passwordPart} --host=\"{$dbHost}\" --port=\"{$dbPort}\" --protocol=tcp --single-transaction \"{$dbName}\" > \"{$fullPath}\" 2>&1";
        
        $output = [];
        $resultCode = null;
        exec($dumpCommand, $output, $resultCode);

        if (!File::exists($fullPath) || File::size($fullPath) === 0) {
            throw new \Exception("Pre-wipe automated backup failed. Aborting wipe to prevent data loss. Details: " . implode("\n", $output));
        }
    }
}
