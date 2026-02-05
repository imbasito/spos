<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * FBR POS Integration Service
 * 
 * Handles real-time invoice reporting to FBR with Store & Forward capability.
 */
class FbrService
{
    protected $apiUrl;
    protected $apiKey;
    protected $apiSecret;
    protected $environment;
    protected $storeForward;
    protected $enabled;

    public function __construct()
    {
        $this->enabled = readConfig('fbr_integration_enabled') == 1;
        $this->environment = readConfig('fbr_environment') ?? 'sandbox';
        $this->apiUrl = readConfig('fbr_api_url') ?? 'https://gw.fbr.gov.pk/imsp/v1/api/';
        $this->apiKey = readConfig('fbr_api_key');
        $this->apiSecret = readConfig('fbr_api_secret');
        $this->storeForward = readConfig('fbr_store_forward') == 1;
    }

    /**
     * Check if FBR integration is enabled and properly configured
     */
    public function isConfigured(): bool
    {
        return $this->enabled && !empty($this->apiKey) && !empty($this->apiSecret);
    }

    /**
     * Submit invoice to FBR
     * 
     * @param array $invoiceData Invoice data to submit
     * @return array Response with success status and FBR invoice ID
     */
    public function submitInvoice(array $invoiceData): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'FBR integration not configured',
                'fbr_invoice_id' => null
            ];
        }

        try {
            // Prepare payload according to FBR specifications
            $payload = $this->preparePayload($invoiceData);

            // Try to send to FBR (3 second timeout for fast fallback)
            $response = Http::timeout(3)
                ->withBasicAuth($this->apiKey, $this->apiSecret)
                ->post($this->apiUrl . 'Live/PostData', $payload);

            if ($response->successful()) {
                $data = $response->json();
                
                Log::info('FBR Invoice Submitted', [
                    'order_id' => $invoiceData['order_id'],
                    'fbr_invoice_id' => $data['InvoiceId'] ?? null
                ]);

                return [
                    'success' => true,
                    'message' => 'Invoice submitted to FBR',
                    'fbr_invoice_id' => $data['InvoiceId'] ?? null,
                    'qr_code' => $data['QRCode'] ?? null
                ];
            } else {
                throw new \Exception('FBR API error: ' . $response->status());
            }

        } catch (\Exception $e) {
            Log::warning('FBR Submission Failed', [
                'order_id' => $invoiceData['order_id'],
                'error' => $e->getMessage()
            ]);

            // Store for later if Store & Forward is enabled
            if ($this->storeForward) {
                $this->queueForSync($invoiceData);
                
                return [
                    'success' => true,
                    'message' => 'Queued for FBR sync (offline)',
                    'fbr_invoice_id' => null,
                    'queued' => true
                ];
            }

            return [
                'success' => false,
                'message' => 'FBR submission failed: ' . $e->getMessage(),
                'fbr_invoice_id' => null
            ];
        }
    }

    /**
     * Queue invoice for later sync (Store & Forward)
     */
    protected function queueForSync(array $invoiceData): void
    {
        DB::table('fbr_pending_invoices')->insert([
            'order_id' => $invoiceData['order_id'],
            'payload' => json_encode($invoiceData),
            'attempts' => 0,
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    /**
     * Sync all pending invoices (called by scheduled job or manually)
     * 
     * @return array Summary of sync results
     */
    public function syncPendingInvoices(): array
    {
        if (!$this->isConfigured()) {
            return ['synced' => 0, 'failed' => 0, 'message' => 'FBR not configured'];
        }

        $pending = DB::table('fbr_pending_invoices')
            ->where('attempts', '<', 5)
            ->orderBy('created_at')
            ->limit(50)
            ->get();

        $synced = 0;
        $failed = 0;

        foreach ($pending as $record) {
            $invoiceData = json_decode($record->payload, true);
            
            try {
                $payload = $this->preparePayload($invoiceData);
                
                $response = Http::timeout(10)
                    ->withBasicAuth($this->apiKey, $this->apiSecret)
                    ->post($this->apiUrl . 'Live/PostData', $payload);

                if ($response->successful()) {
                    // Update order with FBR invoice ID
                    $data = $response->json();
                    DB::table('orders')
                        ->where('id', $invoiceData['order_id'])
                        ->update([
                            'fbr_invoice_id' => $data['InvoiceId'] ?? null,
                            'fbr_synced_at' => now()
                        ]);

                    // Remove from queue
                    DB::table('fbr_pending_invoices')->where('id', $record->id)->delete();
                    $synced++;
                } else {
                    throw new \Exception('FBR API error');
                }
            } catch (\Exception $e) {
                // Increment attempts
                DB::table('fbr_pending_invoices')
                    ->where('id', $record->id)
                    ->update([
                        'attempts' => $record->attempts + 1,
                        'last_error' => $e->getMessage(),
                        'updated_at' => now()
                    ]);
                $failed++;
            }
        }

        Log::info('FBR Sync Completed', ['synced' => $synced, 'failed' => $failed]);

        return [
            'synced' => $synced,
            'failed' => $failed,
            'remaining' => DB::table('fbr_pending_invoices')->where('attempts', '<', 5)->count()
        ];
    }

    /**
     * Prepare payload according to FBR POS API specifications
     */
    protected function preparePayload(array $invoiceData): array
    {
        // This structure should match FBR's API requirements
        // Actual fields may vary based on FBR documentation
        return [
            'InvoiceNumber' => $invoiceData['invoice_number'] ?? $invoiceData['order_id'],
            'POSID' => readConfig('fbr_pos_id') ?? '',
            'USIN' => uniqid('INV-'),
            'DateTime' => $invoiceData['date'] ?? now()->format('Y-m-d H:i:s'),
            'BuyerNTN' => $invoiceData['buyer_ntn'] ?? '',
            'BuyerCNIC' => $invoiceData['buyer_cnic'] ?? '',
            'BuyerName' => $invoiceData['customer_name'] ?? 'Walk-in Customer',
            'BuyerPhoneNumber' => $invoiceData['customer_phone'] ?? '',
            'TotalBillAmount' => $invoiceData['total'] ?? 0,
            'TotalQuantity' => $invoiceData['total_quantity'] ?? 0,
            'TotalSaleValue' => $invoiceData['sub_total'] ?? 0,
            'TotalTaxCharged' => $invoiceData['tax_amount'] ?? 0,
            'Discount' => $invoiceData['discount'] ?? 0,
            'FurtherTax' => 0,
            'PaymentMode' => $invoiceData['payment_method'] ?? 1, // 1=Cash, 2=Card, 3=Other
            'RefUSIN' => null,
            'InvoiceType' => 1, // 1=Sale, 2=Return
            'Items' => $this->prepareItems($invoiceData['items'] ?? [])
        ];
    }

    /**
     * Prepare line items for FBR
     */
    protected function prepareItems(array $items): array
    {
        $fbrItems = [];
        foreach ($items as $item) {
            $fbrItems[] = [
                'ItemCode' => $item['sku'] ?? $item['product_id'],
                'ItemName' => $item['name'] ?? 'Product',
                'Quantity' => $item['quantity'] ?? 1,
                'PCTCode' => $item['pct_code'] ?? '', // Pakistan Customs Tariff code
                'TaxRate' => $item['tax_rate'] ?? 17,
                'SaleValue' => $item['price'] ?? 0,
                'TotalAmount' => $item['total'] ?? 0,
                'TaxCharged' => $item['tax_amount'] ?? 0,
                'Discount' => $item['discount'] ?? 0,
                'FurtherTax' => 0,
                'InvoiceType' => 1,
                'RefUSIN' => null
            ];
        }
        return $fbrItems;
    }

    /**
     * Get count of pending invoices
     */
    public function getPendingCount(): int
    {
        try {
            return DB::table('fbr_pending_invoices')->where('attempts', '<', 5)->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Test FBR connection
     */
    public function testConnection(): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'message' => 'FBR not configured'];
        }

        try {
            $response = Http::timeout(10)
                ->withBasicAuth($this->apiKey, $this->apiSecret)
                ->get($this->apiUrl . 'GetInfo');

            return [
                'success' => $response->successful(),
                'status' => $response->status(),
                'message' => $response->successful() ? 'Connection successful' : 'Connection failed'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection error: ' . $e->getMessage()
            ];
        }
    }
}
