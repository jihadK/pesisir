<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrderItem extends Model
{
    protected $table = 'tbr_purchase_order_items';

    public $timestamps = false;

    protected $fillable = [
        'po_id', 'category_id',
        'qty_gram', 'price_per_kg', 'discount_amount', 'subtotal', 'notes',
    ];

    protected $casts = [
        'qty_gram'        => 'decimal:2',
        'price_per_kg'    => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'subtotal'        => 'decimal:2',
    ];

    public function purchaseOrder(): BelongsTo { return $this->belongsTo(PurchaseOrder::class, 'po_id'); }
    public function category(): BelongsTo      { return $this->belongsTo(Category::class, 'category_id'); }
    public function costs(): HasMany           { return $this->hasMany(PurchaseOrderCost::class, 'po_item_id'); }

    public function getQtyKgAttribute(): float
    {
        return (float) $this->qty_gram / 1000;
    }
}
