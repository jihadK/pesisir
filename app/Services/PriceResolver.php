<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Resolusi harga jual untuk pasangan customer × product.
 *
 * Strategi 2-layer:
 *   1) Kontrak per customer (tbm_customer_product_prices) — aktif & masih dalam periode
 *   2) Fallback: default_sell_price dari master produk
 */
class PriceResolver
{
    /**
     * @return array{price: float, source: 'contract'|'default', contract_id: ?int}
     */
    public function resolve(int $customerId, int $productId, ?string $onDate = null): array
    {
        $date = $onDate ? Carbon::parse($onDate)->toDateString() : Carbon::now()->toDateString();

        $contract = DB::table('tbm_customer_product_prices')
            ->where('customer_id', $customerId)
            ->where('product_id', $productId)
            ->where('is_active', true)
            ->where('effective_from', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', $date);
            })
            ->orderByDesc('effective_from')
            ->first();

        if ($contract) {
            return [
                'price'       => (float) $contract->price,
                'source'      => 'contract',
                'contract_id' => (int) $contract->id,
            ];
        }

        $defaultPrice = (float) (Product::where('id', $productId)->value('default_sell_price') ?? 0);
        return [
            'price'       => $defaultPrice,
            'source'      => 'default',
            'contract_id' => null,
        ];
    }
}
