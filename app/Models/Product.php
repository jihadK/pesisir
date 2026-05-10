<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Product extends BaseModel
{
    use SoftDeletes;

    protected $table = 'tbm_products';
    const DELETED_AT = 'deleted_date';

    protected $fillable = [
        'sku', 'barcode', 'category_id', 'grade_id', 'base_uom_id',
        'name', 'scientific_name', 'origin', 'description',
        'storage_temp_min', 'storage_temp_max', 'shelf_life_days', 'is_perishable',
        'min_stock_level', 'max_stock_level',
        'default_cost_price', 'default_sell_price', 'default_margin_percent',
        'pack_content_type', 'pack_content_min', 'pack_content_max',
        'pack_weight_min_g', 'pack_weight_max_g',
        'image_url', 'is_active', 'created_by',
    ];

    protected $casts = [
        'is_perishable'      => 'boolean',
        'is_active'          => 'boolean',
        'storage_temp_min'   => 'decimal:1',
        'storage_temp_max'   => 'decimal:1',
        'shelf_life_days'    => 'integer',
        'min_stock_level'    => 'decimal:3',
        'max_stock_level'    => 'decimal:3',
        'default_cost_price'     => 'decimal:2',
        'default_sell_price'     => 'decimal:2',
        'default_margin_percent' => 'decimal:2',
        'pack_content_min'   => 'integer',
        'pack_content_max'   => 'integer',
        'pack_weight_min_g'  => 'decimal:2',
        'pack_weight_max_g'  => 'decimal:2',
    ];

    /* ========== Relations ========== */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(ProductGrade::class, 'grade_id');
    }

    public function baseUom(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'base_uom_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /* ========== Scopes ========== */
    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        if (! $term) return $q;
        return $q->where(function ($qq) use ($term) {
            $qq->where('sku', 'ilike', "%$term%")
               ->orWhere('barcode', 'ilike', "%$term%")
               ->orWhere('name', 'ilike', "%$term%")
               ->orWhere('scientific_name', 'ilike', "%$term%")
               ->orWhere('origin', 'ilike', "%$term%");
        });
    }

    public function scopeOfCategory(Builder $q, $categoryId): Builder
    {
        return $categoryId ? $q->where('category_id', $categoryId) : $q;
    }

    public function scopeOfGrade(Builder $q, $gradeId): Builder
    {
        return $gradeId ? $q->where('grade_id', $gradeId) : $q;
    }

    public function scopePerishable(Builder $q, ?bool $value): Builder
    {
        return $value === null ? $q : $q->where('is_perishable', $value);
    }

    public function scopeStockLow(Builder $q): Builder
    {
        // Join dengan stock summary view
        return $q->whereExists(function ($sub) {
            $sub->selectRaw('1')
                ->from(DB::raw('(SELECT product_id, SUM(quantity) AS total_qty FROM tbs_stock_balances GROUP BY product_id) sb'))
                ->whereColumn('sb.product_id', 'tbm_products.id')
                ->whereColumn('sb.total_qty', '<', 'tbm_products.min_stock_level');
        });
    }

    /* ========== Helpers ========== */

    /** Total qty stock di semua warehouse */
    public function getTotalStock(): float
    {
        return (float) DB::table('tbs_stock_balances')
            ->where('product_id', $this->id)
            ->sum('quantity');
    }

    /** URL gambar produk (dengan fallback) */
    public function getImageDisplayUrlAttribute(): string
    {
        if ($this->image_url) {
            return str_starts_with($this->image_url, 'http') ? $this->image_url : asset($this->image_url);
        }
        return asset('assets/media/svg/files/blank-image.svg');
    }

    /** Apakah stock di bawah minimum */
    public function getIsStockLowAttribute(): bool
    {
        if (! $this->min_stock_level) return false;
        return $this->getTotalStock() < (float) $this->min_stock_level;
    }

    /** Margin profit % dari default cost vs sell */
    public function getMarginPercentAttribute(): ?float
    {
        $cost = (float) $this->default_cost_price;
        $sell = (float) $this->default_sell_price;
        if ($cost <= 0) return null;
        return round((($sell - $cost) / $cost) * 100, 1);
    }

    /** Label pack content per pack: "4–5 potong" / "5 ekor" */
    public function getPackContentLabelAttribute(): ?string
    {
        $type = $this->pack_content_type;
        $min  = $this->pack_content_min;
        $max  = $this->pack_content_max;
        if (! $type || ! $min) return null;
        return $min == $max ? "{$min} {$type}" : "{$min}–{$max} {$type}";
    }

    /** Label berat per pack: "200–215 g" / "450 g" */
    public function getPackWeightLabelAttribute(): ?string
    {
        $min = $this->pack_weight_min_g;
        $max = $this->pack_weight_max_g;
        if ($min === null) return null;
        $minS = rtrim(rtrim(number_format((float)$min, 2, '.', ''), '0'), '.');
        $maxS = rtrim(rtrim(number_format((float)$max, 2, '.', ''), '0'), '.');
        return $minS == $maxS ? "{$minS} g" : "{$minS}–{$maxS} g";
    }
}
