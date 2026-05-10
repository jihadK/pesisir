<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    protected $table = 'tbh_stock_movements';

    public $timestamps = false;

    public const TYPE_IN_PURCHASE    = 'in_purchase';
    public const TYPE_IN_RETURN      = 'in_return';
    public const TYPE_IN_ADJUSTMENT  = 'in_adjustment';
    public const TYPE_IN_TRANSFER    = 'in_transfer';
    public const TYPE_OUT_SALE       = 'out_sale';
    public const TYPE_OUT_RETURN     = 'out_return';
    public const TYPE_OUT_ADJUSTMENT = 'out_adjustment';
    public const TYPE_OUT_TRANSFER   = 'out_transfer';
    public const TYPE_OUT_WASTE      = 'out_waste';

    public const REF_OPENING    = 'OPENING';
    public const REF_ADJUSTMENT = 'ADJUSTMENT';

    protected $fillable = [
        'movement_number', 'product_id', 'warehouse_id', 'batch_id',
        'movement_type', 'reference_type', 'reference_id',
        'quantity', 'uom_id', 'cost_price', 'balance_after',
        'notes', 'created_by',
    ];

    protected $casts = [
        'quantity'      => 'decimal:3',
        'cost_price'    => 'decimal:2',
        'balance_after' => 'decimal:3',
        'created_date'  => 'datetime',
    ];

    public function product(): BelongsTo   { return $this->belongsTo(Product::class, 'product_id'); }
    public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class, 'warehouse_id'); }
    public function batch(): BelongsTo     { return $this->belongsTo(ProductBatch::class, 'batch_id'); }
    public function uom(): BelongsTo       { return $this->belongsTo(UnitOfMeasure::class, 'uom_id'); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    public function scopeReferenceType(Builder $q, ?string $type): Builder
    {
        return $type ? $q->where('reference_type', $type) : $q;
    }
    public function scopeOfProduct(Builder $q, $productId): Builder
    {
        return $productId ? $q->where('product_id', $productId) : $q;
    }
    public function scopeOfWarehouse(Builder $q, $warehouseId): Builder
    {
        return $warehouseId ? $q->where('warehouse_id', $warehouseId) : $q;
    }
    public function scopeBetweenDates(Builder $q, ?string $from, ?string $to): Builder
    {
        if ($from) $q->whereDate('created_date', '>=', $from);
        if ($to)   $q->whereDate('created_date', '<=', $to);
        return $q;
    }

    /** Apakah qty positif (in) */
    public function getIsInAttribute(): bool
    {
        return (float) $this->quantity > 0;
    }

    /** Label readable untuk movement_type */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->movement_type) {
            self::TYPE_IN_PURCHASE    => 'Pembelian',
            self::TYPE_IN_RETURN      => 'Retur Masuk',
            self::TYPE_IN_ADJUSTMENT  => 'Adjustment +',
            self::TYPE_IN_TRANSFER    => 'Transfer Masuk',
            self::TYPE_OUT_SALE       => 'Penjualan',
            self::TYPE_OUT_RETURN     => 'Retur Keluar',
            self::TYPE_OUT_ADJUSTMENT => 'Adjustment −',
            self::TYPE_OUT_TRANSFER   => 'Transfer Keluar',
            self::TYPE_OUT_WASTE      => 'Pemusnahan',
            default                   => $this->movement_type,
        };
    }
}
