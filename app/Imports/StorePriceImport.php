<?php

namespace App\Imports;

use App\Models\OrderProductPriceManagement;
use App\Models\Store;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\BeforeSheet;

class StorePriceImport implements ToCollection, WithHeadingRow, WithEvents
{
    protected $currentStoreId;

    public function collection(Collection $rows)
    {
        if (!$this->currentStoreId) {
            return; // Skip if store not found
        }

        foreach ($rows as $row) {
            // Header: product_id_do_not_edit, unit_id_do_not_edit, store_price_edit_this
            // Slugified headers
            
            $productId = $row['product_id_do_not_edit'] ?? null;
            $unitId = $row['unit_id_do_not_edit'] ?? null;
            $price = $row['store_price_edit_this'] ?? null;

            if ($productId && $unitId) {
                // If price is numeric (allows 0), update/create. 
                // If null/empty string, delete override.
                
                if (is_numeric($price)) {
                    OrderProductPriceManagement::updateOrCreate(
                        [
                            'order_product_id' => $productId,
                            'unit_id' => $unitId,
                            'store_id' => $this->currentStoreId
                        ],
                        [
                            'price' => $price
                        ]
                    );
                } elseif ($price === null || trim($price) === '') {
                    // Remove override
                    OrderProductPriceManagement::where('order_product_id', $productId)
                        ->where('unit_id', $unitId)
                        ->where('store_id', $this->currentStoreId)
                        ->delete();
                }
            }
        }
    }

    public function registerEvents(): array
    {
        return [
            BeforeSheet::class => function(BeforeSheet $event) {
                // Get Sheet Name
                $sheetName = $event->getSheet()->getTitle();
                
                // Find store where name matches sheet title (accounting for truncation)
                $store = Store::all()->filter(function($s) use ($sheetName) {
                    // Excel sheet limits: 31 chars, but we truncated to 30 in Export
                    return substr($s->name, 0, 30) === $sheetName;
                })->first();

                $this->currentStoreId = $store ? $store->id : null;
            },
        ];
    }
}
