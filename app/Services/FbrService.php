<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * FBR POS Integration Service (Hybrid: Direct API + Legacy IMS)
 * 
 * Handles real-time invoice reporting to FBR with Store & Forward capability.
 * Supports both 2026 Digital Invoicing API and legacy localhost IMS fiscal component.
 */
class FbrService
{
    protected $integrationMode; // 'direct_api' or 'legacy_ims'
    protected $apiUrl;
    protected $imsEndpoint;
    protected $securityToken;  // For Direct API (Bearer Token)
    protected $apiKey;         // For Legacy IMS (Basic Auth)
    protected $apiSecret;      // For Legacy IMS (Basic Auth)
    protected $environment;
    protected $storeForward;
    protected $enabled;
    protected $posId;

    public function __construct()
    {
        $this->enabled = readConfig('fbr_integration_enabled') == 1;
        $this->integrationMode = readConfig('fbr_integration_mode') ?? 'direct_api';
        $this->environment = readConfig('fbr_environment') ?? 'sandbox';
        $this->posId = readConfig('fbr_pos_id');
        $this->storeForward = readConfig('fbr_store_forward') == 1;

        // Direct API config
        $this->apiUrl = readConfig('fbr_api_url') ?? 'https://gw.fbr.gov.pk/pdi/v1/api/DigitalInvoicing/PostInvoiceData_v1';
        $this->securityToken = readConfig('fbr_security_token');

        // Legacy IMS config
        $this->imsEndpoint = readConfig('fbr_ims_endpoint') ?? 'http://localhost:8585';
        $this->apiKey = readConfig('fbr_api_key');
        $this->apiSecret = readConfig('fbr_api_secret');
    }

    /**
     * Check if FBR integration is enabled and properly configured
     */
    public function isConfigured(): bool
    {
        if (!$this->enabled) return false;

        if ($this->integrationMode === 'direct_api') {
            return !empty($this->securityToken);
        } else {
            // Legacy IMS may not require auth (depending on setup)
            return !empty($this->imsEndpoint);
        }
    }

    /**
     * Submit invoice to FBR (auto-selects mode based on config)
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
            if ($this->integrationMode === 'direct_api') {
                return $this->submitViaDirectApi($invoiceData);
            } else {
                return $this->submitViaLegacyIms($invoiceData);
            }
        } catch (\Exception $e) {
            Log::warning('FBR Submission Failed', [
                'order_id' => $invoiceData['order_id'],
                'mode' => $this->integrationMode,
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
     * Submit via Direct API (Digital Invoicing - Bearer Token)
     */
    protected function submitViaDirectApi(array $invoiceData): array
    {
        $payload = $this->prepareDigitalInvoicingPayload($invoiceData);

        $response = Http::timeout(3)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->securityToken,
                'Content-Type' => 'application/json'
            ])
            ->post($this->apiUrl, $payload);

        if ($response->successful()) {
            $data = $response->json();
            
            Log::info('FBR Invoice Submitted (Direct API)', [
                'order_id' => $invoiceData['order_id'],
                'fbr_invoice_id' => $data['result'] ?? null
            ]);

            return [
                'success' => true,
                'message' => 'Invoice submitted to FBR',
                'fbr_invoice_id' => $data['result'] ?? null,
                'qr_code' => $this->generateQrCodeUrl($data['result'] ?? null)
            ];
        } else {
            throw new \Exception('FBR API error (HTTP ' . $response->status() . '): ' . $response->body());
        }
    }

    /**
     * Submit via Legacy IMS (Fiscal Component - Basic Auth to localhost)
     */
    protected function submitViaLegacyIms(array $invoiceData): array
    {
        $payload = $this->prepareLegacyPayload($invoiceData);

        $request = Http::timeout(5);

        // Add Basic Auth only if credentials are provided
        if (!empty($this->apiKey) && !empty($this->apiSecret)) {
            $request = $request->withBasicAuth($this->apiKey, $this->apiSecret);
        }

        $response = $request->post($this->imsEndpoint . '/api/Live/PostData', $payload);

        if ($response->successful()) {
            $data = $response->json();
            
            Log::info('FBR Invoice Submitted (Legacy IMS)', [
                'order_id' => $invoiceData['order_id'],
                'fbr_invoice_id' => $data['InvoiceNumber'] ?? null
            ]);

            return [
                'success' => true,
                'message' => 'Invoice submitted to FBR (IMS)',
                'fbr_invoice_id' => $data['InvoiceNumber'] ?? null,
                'qr_code' => $data['QRCode'] ?? null
            ];
        } else {
            throw new \Exception('FBR IMS error: ' . $response->status());
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
                // Use the appropriate submission method based on mode
                if ($this->integrationMode === 'direct_api') {
                    $result = $this->submitViaDirectApi($invoiceData);
                } else {
                    $result = $this->submitViaLegacyIms($invoiceData);
                }

                if ($result['success'] && !isset($result['queued'])) {
                    // Update order with FBR invoice ID
                    DB::table('orders')
                        ->where('id', $invoiceData['order_id'])
                        ->update([
                            'fbr_invoice_id' => $result['fbr_invoice_id'] ?? null,
                            'fbr_synced_at' => now()
                        ]);

                    // Remove from queue
                    DB::table('fbr_pending_invoices')->where('id', $record->id)->delete();
                    $synced++;
                } else {
                    throw new \Exception($result['message'] ?? 'Unknown error');
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
     * Prepare payload for Digital Invoicing API (2026 format)
     */
    protected function prepareDigitalInvoicingPayload(array $invoiceData): array
    {
        $items = [];
        foreach ($invoiceData['items'] ?? [] as $item) {
            $saleValue = ($item['price'] ?? 0) * ($item['quantity'] ?? 1);
            $taxRate = $item['tax_rate'] ?? 17;
            $taxCharged = $saleValue * ($taxRate / 100);
            
            $items[] = [
                'itemCode' => (string)($item['sku'] ?? $item['product_id'] ?? ''),
                'itemName' => $item['name'] ?? 'Product',
                'hsCode' => $item['hs_code'] ?? $item['hsn_code'] ?? '',
                'pctCode' => $item['pct_code'] ?? '00000000',
                'quantity' => (float)($item['quantity'] ?? 1),
                'taxRate' => (float)$taxRate,
                'saleValue' => round($saleValue, 2),
                'taxCharged' => round($taxCharged, 2),
                'discount' => (float)($item['discount'] ?? 0),
                'furtherTax' => 0.0,
                'extraTax' => 0.0
            ];
        }

        return [
            'invoiceType' => 'Sale Invoice',
            'invoiceDate' => $invoiceData['date'] ?? now()->format('Y-m-d'),
            'posId' => $this->posId ?? '',
            'sellerNTNCNIC' => readConfig('tax_ntn') ?? '',
            'sellerBusinessName' => readConfig('site_name') ?? 'POS',
            'buyerNTNCNIC' => $invoiceData['buyer_ntn'] ?? '',
            'buyerName' => $invoiceData['customer_name'] ?? 'Walk-in Customer',
            'buyerPhoneNumber' => $invoiceData['customer_phone'] ?? '',
            'totalBillAmount' => (float)($invoiceData['total'] ?? 0),
            'totalQuantity' => (float)($invoiceData['total_quantity'] ?? 0),
            'totalSaleValue' => (float)($invoiceData['sub_total'] ?? 0),
            'totalTaxCharged' => (float)($invoiceData['tax_amount'] ?? 0),
            'discount' => (float)($invoiceData['discount'] ?? 0),
            'paymentMode' => $this->mapPaymentMode($invoiceData['payment_method'] ?? 'Cash'),
            'items' => $items
        ];
    }

    /**
     * Prepare payload for Legacy IMS (Fiscal Component)
     */
    protected function prepareLegacyPayload(array $invoiceData): array
    {
        return [
            'InvoiceNumber' => $invoiceData['invoice_number'] ?? (string)$invoiceData['order_id'],
            'POSID' => $this->posId ?? '',
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
            'PaymentMode' => $invoiceData['payment_method'] ?? 1,
            'RefUSIN' => null,
            'InvoiceType' => 1,
            'Items' => $this->prepareLegacyItems($invoiceData['items'] ?? [])
        ];
    }

    /**
     * Prepare line items for Legacy IMS
     */
    protected function prepareLegacyItems(array $items): array
    {
        $fbrItems = [];
        foreach ($items as $item) {
            $fbrItems[] = [
                'ItemCode' => $item['sku'] ?? $item['product_id'],
                'ItemName' => $item['name'] ?? 'Product',
                'Quantity' => $item['quantity'] ?? 1,
                'PCTCode' => $item['pct_code'] ?? '',
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
     * Map payment method string to FBR code
     */
    protected function mapPaymentMode($method): string
    {
        $map = [
            'Cash' => 'Cash',
            'cash' => 'Cash',
            'Card' => 'Card',
            'card' => 'Card',
            'credit' => 'Card',
            'Other' => 'Other',
            'online' => 'Other'
        ];
        return $map[$method] ?? 'Cash';
    }

    /**
     * Generate QR Code verification URL
     */
    protected function generateQrCodeUrl(?string $fbrInvoiceNumber): ?string
    {
        if (empty($fbrInvoiceNumber)) {
            return null;
        }
        return 'https://e.fbr.gov.pk/esbn/Verification?InvoiceNo=' . urlencode($fbrInvoiceNumber);
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
     * Test FBR connection (mode-aware)
     */
    public function testConnection(): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'message' => 'FBR not configured. Check your ' . ($this->integrationMode === 'direct_api' ? 'Security Token' : 'IMS Endpoint') . '.'];
        }

        try {
            if ($this->integrationMode === 'direct_api') {
                // Test Direct API with a dummy ping (actual endpoint varies)
                $response = Http::timeout(10)
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $this->securityToken,
                        'Content-Type' => 'application/json'
                    ])
                    ->get(str_replace('PostInvoiceData', 'GetInfo', $this->apiUrl));

                return [
                    'success' => $response->status() < 500, // 401/403 means auth issue but connection works
                    'status' => $response->status(),
                    'mode' => 'Direct API',
                    'message' => $response->successful() ? 'Connection successful' : 'HTTP ' . $response->status()
                ];
            } else {
                // Test Legacy IMS localhost connection
                $request = Http::timeout(5);
                if (!empty($this->apiKey) && !empty($this->apiSecret)) {
                    $request = $request->withBasicAuth($this->apiKey, $this->apiSecret);
                }
                
                $response = $request->get($this->imsEndpoint . '/api/GetInfo');

                return [
                    'success' => $response->successful(),
                    'status' => $response->status(),
                    'mode' => 'Legacy IMS',
                    'message' => $response->successful() ? 'IMS Connection successful' : 'IMS Connection failed'
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'mode' => $this->integrationMode,
                'message' => 'Connection error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get current integration mode
     */
    public function getIntegrationMode(): string
    {
        return $this->integrationMode;
    }
}
