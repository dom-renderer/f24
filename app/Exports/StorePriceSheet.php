<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StorePriceSheet implements FromArray, WithTitle, WithHeadings, ShouldAutoSize, WithStyles
{
    protected $store;
    protected $products;
    protected $overrideMap;

    public function __construct($store, $products, $overrideMap)
    {
        $this->store = $store;
        $this->products = $products;
        $this->overrideMap = $overrideMap;
    }

    public function array(): array
    {
        $data = [];
        foreach ($this->products as $product) {
            foreach ($product->units as $pUnit) {
                // Key: store_id|product_id|unit_id
                $key = $this->store->id . '|' . $product->id . '|' . $pUnit->unit_id;
                $currentPrice = $this->overrideMap[$key] ?? null;
                
                $data[] = [
                    $product->name,
                    $product->sku,
                    $pUnit->unit->name ?? 'Unknown',
                    $product->id,
                    $pUnit->unit_id,
                    $pUnit->price, // Base Price
                    $currentPrice  // Store Price
                ];
            }
        }
        return $data;
    }

    public function headings(): array
    {
        return [
            'Product Name', 
            'SKU', 
            'Unit Name', 
            'Product ID (DO NOT EDIT)', 
            'Unit ID (DO NOT EDIT)', 
            'Default Regular MRP', 
            'Store Price (Edit this)'
        ];
    }

    public function title(): string
    {
        // Excel sheet names limited to 31 chars
        return substr($this->store->name, 0, 30);
    }

    public function styles(Worksheet $sheet)
    {
        // Bold header
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
