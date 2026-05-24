<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PortalController extends Controller
{
    public function index(): View
    {
        return view('portal.home');
    }

    /**
     * JSON list produk untuk konsumsi frontend portal.
     * Public — tanpa auth. Hanya tampilkan produk aktif dengan stok tersedia
     * di WH-LAMONGAN (warehouse default).
     */
    public function productsJson(): JsonResponse
    {
        $defaultWh = DB::table('tbm_warehouses')->where('code', 'WH-LAMONGAN')->first();

        $stockMap = collect();
        if ($defaultWh) {
            $stockMap = DB::table('tbs_stock_balances')
                ->select('product_id', DB::raw('SUM(GREATEST(quantity - reserved_quantity, 0)) AS available_qty'))
                ->where('warehouse_id', $defaultWh->id)
                ->groupBy('product_id')
                ->pluck('available_qty', 'product_id');
        }

        $products = Product::active()
            ->with(['category:id,name,parent_id', 'category.parent:id,name', 'baseUom:id,code'])
            ->orderBy('sku')
            ->get([
                'id', 'sku', 'name', 'category_id', 'base_uom_id',
                'default_sell_price', 'image_url',
                'pack_content_type', 'pack_content_min', 'pack_content_max',
                'pack_weight_min_g', 'pack_weight_max_g',
                'badge', 'nutrition_info',
            ])
            ->map(function ($p) use ($stockMap) {
                $stock = (float) ($stockMap[$p->id] ?? 0);
                // Parent category = root category (kategori induk). Fallback ke own name kalau no parent.
                $parentCat = $p->category?->parent?->name ?? $p->category?->name ?? '—';
                // Image: cek kolom mentah image_url (accessor punya fallback ke blank-image
                // svg yang generic — kita gak mau itu, kita mau default-produk.jpg yang lebih
                // menarik). Kalau admin sudah upload → pakai URL upload, else default-produk.
                $imgUrl = $p->image_url
                    ? (str_starts_with($p->image_url, 'http') ? $p->image_url : asset($p->image_url))
                    : asset('assets/media/product/default-produk.jpg');
                $badgeOpts = Product::badgeOptions();
                return [
                    'id'             => $p->id,
                    'sku'            => $p->sku,
                    'name'           => $p->name,
                    'category'       => $p->category?->name ?? '—',
                    'parent_cat'     => $parentCat,
                    'uom'            => $p->baseUom?->code ?? 'PACK',
                    'price'          => (float) $p->default_sell_price,
                    'stock'          => $stock,
                    'image_url'      => $imgUrl,
                    'pack_content'   => $p->pack_content_label,
                    'pack_weight'    => $p->pack_weight_label,
                    'badge'          => $p->badge ? [
                        'code'  => $p->badge,
                        'label' => $badgeOpts[$p->badge]['label'] ?? $p->badge,
                        'color' => $badgeOpts[$p->badge]['color'] ?? 'primary',
                    ] : null,
                    'nutrition_info' => is_array($p->nutrition_info) ? $p->nutrition_info : [],
                ];
            })
            ->filter(fn ($p) => $p['stock'] > 0) // hanya tampilkan yang ada stok
            ->values();

        return response()->json([
            'products' => $products,
            'admin_wa' => config('app.portal_admin_wa', ''),
        ]);
    }
}
