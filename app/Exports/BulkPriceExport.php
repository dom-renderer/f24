<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Protection;

class BulkPriceExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths, WithEvents
{
    protected array $rows;
    protected array $headings;

    public function __construct(array $rows, array $headings)
    {
        $this->rows = $rows;
        $this->headings = $headings;
    }

    /**
     * Build headings and rows from categories + tiers in the same
     * logical structure as the Bulk Price Management screen.
     *
     * @param \Illuminate\Support\Collection $categories
     * @param \Illuminate\Support\Collection $pricingTiers
     * @return array{0: array, 1: array}
     */
    public static function buildData(Collection $categories, Collection $pricingTiers): array
    {
        $headings = [
            'ID (DO NOT EDIT)',    // product_id|unit_id
            'Category',
            'Product',
            'Unit',
            'Default Regular MRP',
        ];

        foreach ($pricingTiers as $tier) {
            $headings[] = $tier->name;
        }

        $rows = [];

        $walkCategories = function ($category) use (&$walkCategories, &$rows, $pricingTiers) {
            foreach ($category->products as $product) {
                foreach ($product->units as $unit) {
                    $row = [];

                    $row[] = $product->id . '|' . $unit->unit_id;
                    $row[] = $category->name;
                    $row[] = $product->name;
                    $row[] = $unit->unit->name ?? ('Unit #' . $unit->unit_id);
                    $row[] = $unit->price;

                    foreach ($pricingTiers as $tier) {
                        $existing = $product->unitPriceTiers
                            ->where('pricing_tier_id', $tier->id)
                            ->where('product_unit_id', $unit->unit_id)
                            ->first();

                        $row[] = $existing ? $existing->amount : $unit->price;
                    }

                    $rows[] = $row;
                }
            }

            foreach ($category->children as $child) {
                $walkCategories($child);
            }
        };

        foreach ($categories as $category) {
            $walkCategories($category);
        }

        return [$headings, $rows];
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('1:1')->getFont()->setBold(true);

        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 12,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Protect sheet and lock column A (IDs) while keeping other columns editable
                $sheet->getProtection()->setSheet(true);
                $sheet->getStyle('A:A')->getProtection()->setLocked(Protection::PROTECTION_PROTECTED);
                $sheet->getStyle('B:Z')->getProtection()->setLocked(Protection::PROTECTION_UNPROTECTED);
            },
        ];
    }
}

