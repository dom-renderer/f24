<?php

namespace App\Imports;

use App\Models\OrderProductUnit;
use App\Models\UnitPriceTier;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class BulkPriceImport implements ToCollection, WithHeadingRow
{
    protected array $tierSlugToId = [];

    protected array $stats = [
        'rows_processed' => 0,
        'prices_updated' => 0,
        'prices_created' => 0,
        'prices_deleted' => 0,
        'rows_skipped_new_items' => 0,
        'rows_invalid' => 0,
    ];

    protected array $validPairs = [];

    public function __construct($pricingTiers)
    {
        foreach ($pricingTiers as $tier) {
            $slug = Str::slug($tier->name, '_');
            $this->tierSlugToId[$slug] = $tier->id;
        }

        // Preload all valid product/unit pairs for quick existence checks
        $pairs = OrderProductUnit::select('order_product_id', 'unit_id')->get();
        foreach ($pairs as $p) {
            $this->validPairs[$p->order_product_id . '|' . $p->unit_id] = true;
        }
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $this->stats['rows_processed']++;

            $idField = $row['id_do_not_edit'] ?? null;
            if (!$idField || strpos($idField, '|') === false) {
                $this->stats['rows_invalid']++;
                continue;
            }

            [$productId, $unitId] = explode('|', $idField, 2);
            $productId = (int) $productId;
            $unitId = (int) $unitId;

            $pairKey = $productId . '|' . $unitId;
            if (!isset($this->validPairs[$pairKey])) {
                // New or unknown combination – ignore but count
                $this->stats['rows_skipped_new_items']++;
                continue;
            }

            // For each tier column, see if there's a price to set/delete
            foreach ($this->tierSlugToId as $slug => $tierId) {
                if (!$row->has($slug)) {
                    // Missing column – just skip silently to avoid errors
                    continue;
                }

                $value = $row[$slug];

                $query = UnitPriceTier::where('product_id', $productId)
                    ->where('product_unit_id', $unitId)
                    ->where('pricing_tier_id', $tierId);

                if ($value === null || $value === '') {
                    // Delete existing tier price if present
                    $existing = $query->first();
                    if ($existing) {
                        $existing->delete();
                        $this->stats['prices_deleted']++;
                    }
                    continue;
                }

                if (!is_numeric($value)) {
                    // Non-numeric price – skip but count as invalid data
                    $this->stats['rows_invalid']++;
                    continue;
                }

                $amount = (float) $value;

                $existing = $query->first();
                if ($existing) {
                    if ($existing->amount != $amount) {
                        $existing->amount = $amount;
                        $existing->status = true;
                        $existing->save();
                        $this->stats['prices_updated']++;
                    }
                } else {
                    UnitPriceTier::create([
                        'pricing_tier_id' => $tierId,
                        'product_id' => $productId,
                        'product_unit_id' => $unitId,
                        'amount' => $amount,
                        'status' => true,
                    ]);
                    $this->stats['prices_created']++;
                }
            }
        }
    }

    public function getStats(): array
    {
        return $this->stats;
    }
}

