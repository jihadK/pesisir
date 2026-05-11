<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    protected $table = 'tbr_invoice_items';

    public $timestamps = false;

    protected $fillable = [
        'invoice_id', 'do_item_id', 'product_id', 'description',
        'quantity', 'uom_id', 'unit_price', 'discount_amount', 'subtotal',
    ];

    protected $casts = [
        'quantity'        => 'decimal:3',
        'unit_price'      => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'subtotal'        => 'decimal:2',
    ];

    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class, 'invoice_id'); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class, 'product_id'); }
    public function uom(): BelongsTo     { return $this->belongsTo(UnitOfMeasure::class, 'uom_id'); }
}
