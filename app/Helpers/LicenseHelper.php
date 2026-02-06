<?php

namespace App\Helpers;

class LicenseHelper
{
    // Secret key - MUST match the generator
    private static $secretKey = 'MITHAI_POS_2026_QTECH_SECRET';
    
    /**
     * Validate a license key
     */
    public static function validate($licenseKey)
    {
        try {
            // Check format: MPOS-[data]-[checksum]
            if (!preg_match('/^MPOS-(.+)-([A-F0-9]{8})$/i', $licenseKey, $matches)) {
                return [
                    'valid' => false,
                    'error' => 'Invalid license format'
                ];
            }

            $encodedData = $matches[1];
            $providedChecksum = strtoupper($matches[2]);

            // Decode the license data
            $jsonData = base64_decode($encodedData);
            if (!$jsonData) {
                return [
                    'valid' => false,
                    'error' => 'Invalid license data'
                ];
            }

            $licenseData = json_decode($jsonData, true);
            if (!$licenseData || !isset($licenseData['shop']) || !isset($licenseData['expiry'])) {
                return [
                    'valid' => false,
                    'error' => 'Corrupted license data'
                ];
            }

            // Verify checksum
            $dataString = "{$licenseData['shop']}|{$licenseData['expiry']}|{$licenseData['machine']}|" . self::$secretKey;
            $expectedChecksum = strtoupper(substr(self::simpleHash($dataString), 0, 8));

            if ($providedChecksum !== $expectedChecksum) {
                \Log::error('License Checksum Mismatch', [
                    'encoded_data' => $encodedData,
                    'json_data' => $jsonData,
                    'data_string' => $dataString,
                    'provided_checksum' => $providedChecksum,
                    'expected_checksum' => $expectedChecksum,
                ]);
                return [
                    'valid' => false,
                    'error' => 'Invalid license key'
                ];
            }

            // Check expiry
            $expiryDate = strtotime($licenseData['expiry']);
            if ($expiryDate < time()) {
                return [
                    'valid' => false,
                    'error' => 'License expired on ' . $licenseData['expiry'],
                    'expired' => true,
                    'shop' => $licenseData['shop']
                ];
            }

            // Check machine ID if specified
            if ($licenseData['machine'] !== 'ANY') {
                $currentMachineId = self::getMachineId();
                if ($licenseData['machine'] !== $currentMachineId) {
                    return [
                        'valid' => false,
                        'error' => 'License not valid for this computer'
                    ];
                }
            }

            // License is valid!
            return [
                'valid' => true,
                'shop' => $licenseData['shop'],
                'expiry' => $licenseData['expiry'],
                'lifetime' => strtotime($licenseData['expiry']) > strtotime('2090-01-01')
            ];

        } catch (\Exception $e) {
            return [
                'valid' => false,
                'error' => 'License validation error'
            ];
        }
    }

    /**
     * Simple hash function matching the JavaScript version
     */
    private static function simpleHash($str)
    {
        $hash = 0;
        for ($i = 0; $i < strlen($str); $i++) {
            $char = ord($str[$i]);
            $hash = (($hash << 5) - $hash) + $char;
            $hash = $hash & 0xFFFFFFFF; // Convert to 32bit integer
            if ($hash > 0x7FFFFFFF) {
                $hash -= 0x100000000;
            }
        }
        return strtoupper(str_pad(dechex(abs($hash)), 8, '0', STR_PAD_LEFT));
    }

    /**
     * Get current machine ID
     */
    public static function getMachineId()
    {
        // Use computer name + volume serial as simple machine ID
        $computerName = gethostname();
        return strtoupper(substr(md5($computerName), 0, 12));
    }

    /**
     * Check if license is activated
     */
    public static function isActivated()
    {
        // First check system state file (preserved during updates)
        $stateFilePath = storage_path('app/system_state.json');
        if (file_exists($stateFilePath)) {
            try {
                $state = json_decode(file_get_contents($stateFilePath), true);
                if (isset($state['activated']) && $state['activated'] === true) {
                    // Verify the license from state is still valid
                    if (!empty($state['license_key'])) {
                        $result = self::validate($state['license_key']);
                        if ($result['valid']) {
                            // Sync config if needed
                            if (readConfig('license_key') !== $state['license_key']) {
                                writeConfig('license_key', $state['license_key']);
                                writeConfig('licensed_to', $state['licensed_to'] ?? $result['shop']);
                            }
                            return true;
                        }
                    }
                }
            } catch (\Exception $e) {
                \Log::warning('Failed to read system state for activation check', ['error' => $e->getMessage()]);
            }
        }

        // Fallback to config-based check
        $licenseKey = readConfig('license_key');
        if (empty($licenseKey)) {
            return false;
        }

        $result = self::validate($licenseKey);
        return $result['valid'];
    }

    /**
     * Get license info
     */
    public static function getInfo()
    {
        $licenseKey = readConfig('license_key');
        if (empty($licenseKey)) {
            return null;
        }

        return self::validate($licenseKey);
    }

    /**
     * Activate license
     */
    public static function activate($licenseKey)
    {
        $result = self::validate($licenseKey);
        
        if ($result['valid']) {
            writeConfig('license_key', $licenseKey);
            writeConfig('licensed_to', $result['shop']);
            
            // Mark as activated and remove first-run flag
            file_put_contents(storage_path('app/activated_at'), now()->toDateTimeString());
            @unlink(storage_path('app/first_run_pending'));
            
            // Save to system state for persistence across updates
            try {
                $versionService = app(\App\Services\VersionService::class);
                $versionService->markActivated($licenseKey, $result['shop']);
            } catch (\Exception $e) {
                \Log::warning('Failed to save activation to system state', ['error' => $e->getMessage()]);
            }
            
            return $result;
        }
        
        return $result;
    }
}
