<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryOrderItem extends Model
{
    protected $table = 'tbr_delivery_order_items';

    public $timestamps = false;

    protected $fillable = [
        'do_id', 'so_item_id', 'product_id', 'batch_id',
        'quantity', 'uom_id', 'unit_price',
    ];

    protected $casts = [
        'quantity'   => 'decimal:3',
        'unit_price' => 'decimal:2',
    ];

    public function deliveryOrder(): BelongsTo { return $this->belongsTo(DeliveryOrder::class, 'do_id'); }
    public function salesOrderItem(): BelongsTo { return $this->belongsTo(SalesOrderItem::class, 'so_item_id'); }
    public function product(): BelongsTo       { return $this->belongsTo(Product::class, 'product_id'); }
    public function batch(): BelongsTo         { return $this->belongsTo(ProductBatch::class, 'batch_id'); }
    public function uom(): BelongsTo           { return $this->belongsTo(UnitOfMeasure::class, 'uom_id'); }
}
