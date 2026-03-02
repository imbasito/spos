<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Illuminate\Support\Collection;
use App\Models\Unit;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

class DemoProductsExport implements FromCollection, WithHeadings, WithEvents
{
    /**
     * Column order matches importer rules: mandatory fields first, optional at the end.
     * Headings must match exactly — lowercase with underscores as used by LaravelExcel WithHeadingRow.
     */
    public function collection()
    {
        return collect([
            // ── mandatory: name, category, brand, unit, price, purchase_price, quantity ──
            // ── optional : sku, description, discount, discount_type, expire_date, status ──
            [
                'name'           => 'Whole Milk 1L',
                'category'       => 'Dairy',
                'brand'          => 'FreshFarm',
                'unit'           => 'pcs',
                'price'          => 1.80,
                'purchase_price' => 1.20,
                'quantity'       => 120,
                'sku'            => 'MILK-001',
                'barcode'        => '',
                'description'    => 'Full-fat whole milk, 1 litre carton.',
                'discount'       => 0,
                'discount_type'  => 'fixed',
                'expire_date'    => '2027-01-31',
                'status'         => '1',
            ],
            [
                'name'           => 'White Sandwich Bread',
                'category'       => 'Bakery',
                'brand'          => 'GoldenCrust',
                'unit'           => 'pcs',
                'price'          => 2.50,
                'purchase_price' => 1.60,
                'quantity'       => 80,
                'sku'            => 'BAKE-001',
                'barcode'        => '',
                'description'    => 'Soft white sliced sandwich bread, 700 g.',
                'discount'       => 0,
                'discount_type'  => 'fixed',
                'expire_date'    => '2027-03-15',
                'status'         => '1',
            ],
            [
                'name'           => 'Bottled Water 500ml',
                'category'       => 'Beverages',
                'brand'          => 'PureSpring',
                'unit'           => 'pcs',
                'price'          => 0.75,
                'purchase_price' => 0.40,
                'quantity'       => 300,
                'sku'            => 'BEV-001',
                'barcode'        => '',
                'description'    => 'Still mineral water, 500 ml plastic bottle.',
                'discount'       => 0,
                'discount_type'  => 'fixed',
                'expire_date'    => '2028-06-30',
                'status'         => '1',
            ],
            [
                'name'           => 'Basmati Rice 5kg',
                'category'       => 'Groceries',
                'brand'          => 'RiceKing',
                'unit'           => 'kg',
                'price'          => 12.00,
                'purchase_price' => 8.50,
                'quantity'       => 60,
                'sku'            => 'GROC-001',
                'barcode'        => '',
                'description'    => 'Long-grain aged basmati rice, 5 kg bag.',
                'discount'       => 1.00,
                'discount_type'  => 'fixed',
                'expire_date'    => '2028-12-31',
                'status'         => '1',
            ],
            [
                'name'           => 'Antibacterial Hand Soap',
                'category'       => 'Household',
                'brand'          => 'CleanGuard',
                'unit'           => 'pcs',
                'price'          => 3.25,
                'purchase_price' => 2.00,
                'quantity'       => 150,
                'sku'            => 'HH-001',
                'barcode'        => '',
                'description'    => 'Antibacterial hand soap bar, 120 g.',
                'discount'       => 10,
                'discount_type'  => 'percentage',
                'expire_date'    => '',
                'status'         => '1',
            ],
            [
                'name'           => 'AA Alkaline Batteries 4-pack',
                'category'       => 'Electronics',
                'brand'          => 'PowerCell',
                'unit'           => 'pcs',
                'price'          => 5.50,
                'purchase_price' => 3.20,
                'quantity'       => 200,
                'sku'            => 'ELEC-001',
                'barcode'        => '',
                'description'    => 'Long-life AA alkaline batteries, pack of 4.',
                'discount'       => 0,
                'discount_type'  => 'fixed',
                'expire_date'    => '',
                'status'         => '1',
            ],
        ]);
    }

    public function headings(): array
    {
        // Mandatory columns first, optional columns after — matches importer rule order
        return [
            'name', 'category', 'brand', 'unit', 'price', 'purchase_price', 'quantity',
            'sku', 'barcode', 'description', 'discount', 'discount_type', 'expire_date', 'status',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                try {
                    $units = Unit::orderBy('title')->get();
                    if ($units->isEmpty()) {
                        return;
                    }

                    $labels = $units->map(fn($u) => $u->short_name ?: $u->title)
                                   ->unique()
                                   ->values()
                                   ->toArray();

                    // Write unit values into a hidden helper sheet (avoids the 255-char inline list limit)
                    $spreadsheet = $event->sheet->getDelegate()->getParent();
                    $activeIndexBefore = $spreadsheet->getActiveSheetIndex();

                    $unitSheet = $spreadsheet->getSheetByName('_units');
                    if (!$unitSheet) {
                        $unitSheet = $spreadsheet->createSheet();
                        $unitSheet->setTitle('_units');
                    }

                    // Reset helper sheet contents on each export
                    $unitSheet->fromArray([], null, 'A1', true);
                    $unitSheet->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_HIDDEN);
                    foreach ($labels as $i => $label) {
                        $unitSheet->setCellValue('A' . ($i + 1), $label);
                    }
                    $lastRow = count($labels);

                    // Keep the template worksheet as active so import/preview reads the correct sheet
                    $spreadsheet->setActiveSheetIndex($activeIndexBefore);

                    // Apply dropdown validation to unit column (D) rows 2-1000
                    $sheet      = $event->sheet->getDelegate();
                    $validation = $sheet->getCell('D2')->getDataValidation();
                    $validation->setSqref('D2:D1000');
                    $validation->setType(DataValidation::TYPE_LIST);
                    $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                    $validation->setAllowBlank(false);
                    $validation->setShowDropDown(true);
                    $validation->setShowErrorMessage(true);
                    $validation->setErrorTitle('Invalid Unit');
                    $validation->setError('Please select a unit from the dropdown.');
                    $validation->setShowInputMessage(true);
                    $validation->setPromptTitle('Unit');
                    $validation->setPrompt('Select the unit for this product.');
                    // Reference the hidden sheet — no character length limitation
                    $validation->setFormula1('_units!$A$1:$A$' . $lastRow);
                } catch (\Throwable $e) {
                    // Do not block the download if validation setup fails
                }
            },
        ];
    }
}
