<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockBalance extends Model
{
    protected $table = 'tbs_stock_balances';

    public $timestamps = false;

    protected $fillable = [
        'product_id', 'warehouse_id', 'batch_id', 'quantity', 'reserved_quantity',
    ];

    protected $casts = [
        'quantity'           => 'decimal:3',
        'reserved_quantity'  => 'decimal:3',
        'available_quantity' => 'decimal:3',
        'last_updated_date'  => 'datetime',
    ];

    public function product(): BelongsTo    { return $this->belongsTo(Product::class, 'product_id'); }
    public function warehouse(): BelongsTo  { return $this->belongsTo(Warehouse::class, 'warehouse_id'); }
    public function batch(): BelongsTo      { return $this->belongsTo(ProductBatch::class, 'batch_id'); }

    public function scopeOfProduct(Builder $q, $productId): Builder
    {
        return $productId ? $q->where('product_id', $productId) : $q;
    }
    public function scopeOfWarehouse(Builder $q, $warehouseId): Builder
    {
        return $warehouseId ? $q->where('warehouse_id', $warehouseId) : $q;
    }
}
