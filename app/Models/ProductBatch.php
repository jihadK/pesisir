<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductBatch extends BaseModel
{
    protected $table = 'tbm_product_batches';

    protected $fillable = [
        'product_id', 'batch_number', 'supplier_id',
        'received_date', 'production_date', 'expiry_date',
        'catch_date', 'catch_location',
        'cost_price', 'initial_quantity', 'remaining_quantity',
        'quality_status', 'notes',
    ];

    protected $casts = [
        'received_date'      => 'date',
        'production_date'    => 'date',
        'expiry_date'        => 'date',
        'catch_date'         => 'date',
        'cost_price'         => 'decimal:2',
        'initial_quantity'   => 'decimal:3',
        'remaining_quantity' => 'decimal:3',
    ];

    public function product(): BelongsTo  { return $this->belongsTo(Product::class, 'product_id'); }
    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class, 'supplier_id'); }
    public function movements(): HasMany  { return $this->hasMany(StockMovement::class, 'batch_id'); }
}
