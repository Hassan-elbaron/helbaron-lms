<?php

namespace App\Domains\Commerce\Services;

use App\Domains\Commerce\Models\Product;
use App\Domains\Commerce\Models\ProductPrice;
use App\Shared\Services\BaseService;

/**
 * Resolves the effective (sale-aware) unit price of a product in a currency, in minor units.
 */
class PricingService extends BaseService
{
    public function priceRecord(Product $product, string $currency): ?ProductPrice
    {
        return $product->prices()->where('currency', $currency)->first()
            ?? $product->prices()->where('is_default', true)->first();
    }

    public function effectiveMinor(Product $product, string $currency): ?int
    {
        return $this->priceRecord($product, $currency)?->effectiveMinor();
    }
}
