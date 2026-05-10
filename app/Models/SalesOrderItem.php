<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesOrderItem extends Model
{
    protected $table = 'tbr_sales_order_items';

    public $timestamps = false;

    protected $fillable = [
        'so_id', 'product_id', 'uom_id',
        'quantity', 'delivered_quantity', 'unit_price',
        'discount_pct', 'discount_amount', 'subtotal', 'notes',
    ];

    protected $casts = [
        'quantity'           => 'decimal:3',
        'delivered_quantity' => 'decimal:3',
        'unit_price'         => 'decimal:2',
        'discount_pct'       => 'decimal:2',
        'discount_amount'    => 'decimal:2',
        'subtotal'           => 'decimal:2',
    ];

    public function salesOrder(): BelongsTo { return $this->belongsTo(SalesOrder::class, 'so_id'); }
    public function product(): BelongsTo    { return $this->belongsTo(Product::class, 'product_id'); }
    public function uom(): BelongsTo        { return $this->belongsTo(UnitOfMeasure::class, 'uom_id'); }

    public function getOutstandingQuantityAttribute(): float
    {
        return (float) $this->quantity - (float) $this->delivered_quantity;
    }
}
