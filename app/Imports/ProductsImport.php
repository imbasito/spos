<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Unit;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Events\AfterImport;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ProductsImport implements ToModel, WithHeadingRow, WithMapping, WithValidation, SkipsOnFailure, SkipsOnError, WithEvents, SkipsEmptyRows
{
    use Importable, SkipsFailures, SkipsErrors;

    private bool $previewOnly;
    private ?int $userId;
    private ?int $supplierId;
    private string $paymentOption;
    private int $processedRows = 0;
    private int $validRows = 0;
    private int $importedRows = 0;
    private ?Purchase $batchPurchase = null;
    private float $batchGrandTotal = 0;

    public function __construct(bool $previewOnly = false, ?int $userId = null, ?int $supplierId = null, string $paymentOption = 'due')
    {
        $this->previewOnly   = $previewOnly;
        $this->userId        = $userId;
        $this->supplierId    = $supplierId;
        $this->paymentOption = in_array($paymentOption, ['due', 'paid'], true) ? $paymentOption : 'due';
    }

    /**
     * Normalize each row before validation and model creation.
     * Handles Excel date serial numbers, Carbon objects, and string dates.
     */
    public function map($row): array
    {
        $row = (array) $row;
        $row['name'] = isset($row['name']) ? trim((string) $row['name']) : null;
        $row['category'] = isset($row['category']) ? trim((string) $row['category']) : null;
        $row['brand'] = isset($row['brand']) ? trim((string) $row['brand']) : null;
        $row['unit'] = isset($row['unit']) ? trim((string) $row['unit']) : null;
        $row['sku'] = isset($row['sku']) ? trim((string) $row['sku']) : null;
        $row['description'] = isset($row['description']) ? trim((string) $row['description']) : null;

        $row['price'] = $this->normalizeNumber($row['price'] ?? null);
        $row['purchase_price'] = $this->normalizeNumber($row['purchase_price'] ?? null);
        $row['quantity'] = $this->normalizeNumber($row['quantity'] ?? null);
        $row['discount'] = $this->normalizeNumber($row['discount'] ?? null);

        if (isset($row['discount_type'])) {
            $row['discount_type'] = strtolower(trim((string) $row['discount_type']));
        }
        if (isset($row['status'])) {
            $row['status'] = strtolower(trim((string) $row['status']));
        }

        $row['expire_date'] = $this->parseExcelDate($row['expire_date'] ?? null);
        // Normalize barcode: empty/false/0 from Excel should become null, not fail string validation
        $raw = $row['barcode'] ?? null;
        $row['barcode'] = ($raw !== null && $raw !== '' && $raw !== false && $raw !== 0)
            ? trim((string) $raw)
            : null;
        return $row;
    }

    private function normalizeNumber(mixed $value): mixed
    {
        if ($value === null || $value === '' || $value === false) {
            return null;
        }

        if (is_numeric($value)) {
            return $value;
        }

        $normalized = trim((string) $value);
        $normalized = str_replace([',', ' '], '', $normalized);
        $normalized = preg_replace('/[^0-9.\-]/', '', $normalized);

        return is_numeric($normalized) ? $normalized : $value;
    }

    public function isEmptyWhen(array $row): bool
    {
        $normalized = [
            'name' => trim((string)($row['name'] ?? '')),
            'category' => trim((string)($row['category'] ?? '')),
            'brand' => trim((string)($row['brand'] ?? '')),
            'unit' => trim((string)($row['unit'] ?? '')),
            'price' => trim((string)($row['price'] ?? '')),
            'purchase_price' => trim((string)($row['purchase_price'] ?? '')),
            'quantity' => trim((string)($row['quantity'] ?? '')),
            'sku' => trim((string)($row['sku'] ?? '')),
            'barcode' => trim((string)($row['barcode'] ?? '')),
            'description' => trim((string)($row['description'] ?? '')),
            'discount' => trim((string)($row['discount'] ?? '')),
            'discount_type' => trim((string)($row['discount_type'] ?? '')),
            'expire_date' => trim((string)($row['expire_date'] ?? '')),
            'status' => trim((string)($row['status'] ?? '')),
        ];

        foreach ($normalized as $value) {
            if ($value !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * Convert any date representation from Excel to Y-m-d string (or null).
     * Excel stores dates as float serial numbers; PhpSpreadsheet may also
     * pass Carbon/DateTime objects depending on cell format settings.
     */
    private function parseExcelDate(mixed $value): ?string
    {
        if ($value === null || $value === '' || $value === false) {
            return null;
        }

        // Carbon / DateTime (PhpSpreadsheet already converted it)
        if ($value instanceof \DateTimeInterface) {
            return (new \Carbon\Carbon($value))->format('Y-m-d');
        }

        // Numeric → Excel serial date (e.g. 46388 = 2027-01-01)
        if (is_numeric($value) && (float) $value > 1) {
            try {
                $dt = ExcelDate::excelToDateTimeObject((float) $value);
                return (new \Carbon\Carbon($dt))->format('Y-m-d');
            } catch (\Throwable $e) {
                return null;
            }
        }

        // Already a string — normalise via Carbon so any format works
        try {
            return \Carbon\Carbon::parse((string) $value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function model(array $row)
    {
        $this->processedRows++;

        if ($this->previewOnly) {
            $this->validRows++;
            return null;
        }

        // Find or create brand, category, and unit
        $brand = Brand::firstOrCreate(['name' => trim((string) $row['brand'])]);
        $category = Category::firstOrCreate(['name' => trim((string) $row['category'])]);
        $unitValue = trim((string) $row['unit']);
        $unit = Unit::where('short_name', $unitValue)
            ->orWhere('title', $unitValue)
            ->first()
            ?? Unit::create(['title' => $unitValue, 'short_name' => $unitValue]);


        // Auto-generate SKU if not provided, or ensure uniqueness
        $sku = $row['sku'] ?? null;
        if (empty($sku)) {
            // Will be auto-generated by Product model boot method
            $sku = null;
        } else {
            $originalSku = $sku;
            $counter = 1;
            // Check for SKU uniqueness if manually provided
            while (Product::where('sku', $sku)->exists()) {
                $sku = $originalSku . '-' . $counter;
                $counter++;
            }
        }
        
        // Create the product
        $product = Product::create([
            'name' => trim((string) $row['name']),
            'sku' => $sku,
            'barcode' => $row['barcode'] ?? null,
            'description' => $row['description'] ?? null,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'unit_id' => $unit->id,
            'price' => (float) $row['price'],
            'discount' => (float) ($row['discount'] ?? 0),
            'discount_type' => $this->normalizeDiscountType($row['discount_type'] ?? 'fixed'),
            'purchase_price' => (float) $row['purchase_price'],
            'quantity' => (float) $row['quantity'],
            'expire_date' => !empty($row['expire_date']) ? $row['expire_date'] : null,
            'status' => $this->normalizeStatus($row['status'] ?? 1),
        ]);

        if (!$this->supplierId) {
            throw new \InvalidArgumentException('No supplier selected for this import.');
        }

        if (!$this->batchPurchase) {
            $this->batchPurchase = Purchase::create([
                'supplier_id' => $this->supplierId,
                'user_id' => $this->userId ?? auth()->id(),
                'sub_total' => 0,
                'tax' => 0,
                'discount_value' => 0,
                'discount_type' => 'fixed',
                'shipping' => 0,
                'grand_total' => 0,
                'paid_amount' => 0,
                'payment_status' => 'unpaid',
                'status' => 1,
                'date' => now(),
            ]);
        }

        $quantity = max(1, (int) round((float) $row['quantity']));
        $purchasePrice = (float) $row['purchase_price'];
        $salePrice = (float) $row['price'];
        $lineTotal = $purchasePrice * $quantity;

        // Create purchase item record
        PurchaseItem::create([
            'purchase_id' => $this->batchPurchase->id,
            'product_id' => $product->id,
            'purchase_price' => $purchasePrice,
            'price' => $salePrice,
            'quantity' => $quantity,
        ]);

        $this->batchGrandTotal += $lineTotal;

        $this->validRows++;
        $this->importedRows++;

        return $product;
    }

    public function rules(): array
    {
        return [
            'name'           => 'required|string|max:255',
            'sku'            => 'nullable|string|max:100',
            'barcode'        => 'nullable|string|max:120',
            'description'    => 'nullable|string',
            'category'       => 'required|string|max:120',
            'brand'          => 'required|string|max:120',
            'unit'           => 'required|string|max:60',
            'price'          => 'required|numeric|min:0',
            'discount'       => 'nullable|numeric|min:0',
            'discount_type'  => 'nullable|in:fixed,percentage,percent,0,1',
            'purchase_price' => 'required|numeric|min:0',
            'quantity'       => 'required|numeric|min:0.001',
            'expire_date'    => 'nullable|date',
            'status'         => 'nullable|in:0,1,true,false,active,inactive',
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'name.required' => 'Product name is required.',
            'price.required' => 'Sale price is required.',
            'purchase_price.required' => 'Purchase price is required.',
            'quantity.required' => 'Quantity is required.',
            'discount_type.in' => 'Discount type must be fixed or percentage.',
            'status.in' => 'Status must be active/inactive or 1/0.',
        ];
    }

    public function getProcessedRows(): int
    {
        return $this->processedRows;
    }

    public function getValidRows(): int
    {
        return $this->validRows;
    }

    public function getImportedRows(): int
    {
        return $this->importedRows;
    }

    public function registerEvents(): array
    {
        return [
            AfterImport::class => function () {
                if ($this->previewOnly || !$this->batchPurchase) {
                    return;
                }

                $grandTotal = round($this->batchGrandTotal, 2);
                $isPaid = $this->paymentOption === 'paid';

                $this->batchPurchase->update([
                    'sub_total' => $grandTotal,
                    'grand_total' => $grandTotal,
                    'paid_amount' => $isPaid ? $grandTotal : 0,
                    'payment_status' => $isPaid ? 'paid' : 'unpaid',
                ]);
            },
        ];
    }

    private function normalizeDiscountType($value): string
    {
        $normalized = strtolower(trim((string) $value));

        if (in_array($normalized, ['percentage', 'percent', '1'], true)) {
            return 'percentage';
        }

        return 'fixed';
    }

    private function normalizeStatus($value): bool
    {
        $normalized = strtolower(trim((string) $value));
        return in_array($normalized, ['1', 'true', 'active'], true);
    }
}
