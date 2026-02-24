<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class StorePriceExport implements WithMultipleSheets
{
    use Exportable;

    protected $stores;
    protected $products;
    protected $overrideMap;

    public function __construct($stores, $products, $overrideMap)
    {
        $this->stores = $stores;
        $this->products = $products;
        $this->overrideMap = $overrideMap;
    }

    public function sheets(): array
    {
        $sheets = [];

        foreach ($this->stores as $store) {
            $sheets[] = new StorePriceSheet($store, $this->products, $this->overrideMap);
        }

        return $sheets;
    }
}
