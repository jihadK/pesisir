<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PortalController extends Controller
{
    public function index(): View
    {
        return view('portal.home', [
            'products'  => $this->buildProductList(),
            'storeMeta' => $this->storeMeta(),
        ]);
    }

    /**
     * JSON list produk untuk konsumsi frontend portal.
     * Public — tanpa auth. Hanya tampilkan produk aktif & retail.
     */
    public function productsJson(): JsonResponse
    {
        return response()
            ->json([
                'products' => $this->buildProductList(),
                'admin_wa' => config('app.portal_admin_wa', ''),
            ])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * robots.txt dinamis — Sitemap URL otomatis absolut sesuai domain
     * yang sedang diakses (testapp.test lokal vs domain produksi).
     */
    public function robots(): Response
    {
        $sitemapUrl = route('portal.sitemap');
        $body = <<<TXT
# Pesisir Fresh Fish — Customer Portal
# Portal publik boleh di-crawl semua; area admin & endpoint API/signed link tidak.

User-agent: *
Allow: /
Disallow: /admin
Disallow: /admin/
Disallow: /portal/products.json
Disallow: /portal/lead
Disallow: /p/
Disallow: /__opcache-reset

Sitemap: {$sitemapUrl}
TXT;
        return response($body, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    /**
     * Sitemap XML untuk Google / search engine. Saat ini cuma URL home —
     * kalau nanti ada halaman detail produk per-SKU, tambah loop produk di sini.
     */
    public function sitemap(): Response
    {
        $urls = [
            [
                'loc'        => rtrim(url('/'), '/') . '/',
                'lastmod'    => now()->toDateString(),
                'changefreq' => 'daily',
                'priority'   => '1.0',
            ],
        ];

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $u) {
            $xml .= "  <url>\n";
            $xml .= '    <loc>' . htmlspecialchars($u['loc'], ENT_XML1) . "</loc>\n";
            $xml .= '    <lastmod>' . $u['lastmod'] . "</lastmod>\n";
            $xml .= '    <changefreq>' . $u['changefreq'] . "</changefreq>\n";
            $xml .= '    <priority>' . $u['priority'] . "</priority>\n";
            $xml .= "  </url>\n";
        }
        $xml .= '</urlset>' . "\n";

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    /**
     * Susun list produk untuk customer portal. Dipakai bareng oleh index()
     * (untuk SSR / SEO) dan productsJson() (untuk hydrate via fetch).
     *
     * Filter: produk aktif & is_retail = true (produk non-aktif / non-retail
     * tidak tampil). Termasuk produk stok habis — diurut ke bawah.
     */
    private function buildProductList(): Collection
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

        return Product::active()
            ->where('is_retail', true)
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
                $parentCat = $p->category?->parent?->name ?? $p->category?->name ?? '—';
                $imgUrl = $p->image_url
                    ? (str_starts_with($p->image_url, 'http') ? $p->image_url : asset($p->image_url))
                    : asset('assets/media/product/default-produk.jpg');
                $badgeOpts = Product::badgeOptions();
                $packContent = $p->pack_content_label;
                if ($p->pack_content_min == 999 || $p->pack_content_max == 999) {
                    $packContent = null;
                }
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
                    'pack_content'   => $packContent,
                    'pack_weight'    => $p->pack_weight_label,
                    'badge'          => $p->badge ? [
                        'code'  => $p->badge,
                        'label' => $badgeOpts[$p->badge]['label'] ?? $p->badge,
                        'color' => $badgeOpts[$p->badge]['color'] ?? 'primary',
                    ] : null,
                    'nutrition_info' => is_array($p->nutrition_info) ? $p->nutrition_info : [],
                ];
            })
            ->sortByDesc(fn ($p) => $p['stock'] > 0)
            ->values();
    }

    /**
     * Info toko untuk meta SEO + structured data + footer.
     * Alamat diambil dari warehouse WH-LAMONGAN, telepon dari .env (STORE_PHONE).
     */
    private function storeMeta(): array
    {
        $rawPhone = (string) config('app.store_phone', '');
        $digits = preg_replace('/\D+/', '', $rawPhone);
        $phoneDisplay = '';
        $phoneE164 = '';
        if ($digits !== '') {
            if (str_starts_with($digits, '62')) {
                $phoneDisplay = '0' . substr($digits, 2);
                $phoneE164 = '+' . $digits;
            } elseif (str_starts_with($digits, '0')) {
                $phoneDisplay = $digits;
                $phoneE164 = '+62' . substr($digits, 1);
            } else {
                $phoneDisplay = $digits;
                $phoneE164 = '+' . $digits;
            }
        }

        $address = Warehouse::where('code', 'WH-LAMONGAN')->value('address') ?: '';

        return [
            'name'          => config('app.name', 'Pesisir Fresh Fish'),
            'tagline'       => 'Ikan Segar dari Laut Pesisir',
            'description'   => 'Jual ikan laut segar berkualitas dari pesisir Lamongan. Booking langsung via WhatsApp — berbagai jenis ikan, cumi, kepiting, dan seafood retail dengan harga transparan.',
            'address'       => $address,
            'phone_display' => $phoneDisplay,
            'phone_e164'    => $phoneE164,
            'admin_wa'      => (string) config('app.portal_admin_wa', ''),
            'url'           => url('/'),
            'logo_url'      => asset('assets/media/logos/logo-pesisir-web.png'),
        ];
    }
}
