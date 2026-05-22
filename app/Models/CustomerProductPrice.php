<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerProductPrice extends Model
{
    protected $table = 'tbm_customer_product_prices';

    public $timestamps = false;

    protected $fillable = [
        'customer_id', 'product_id', 'price', 'min_quantity',
        'effective_from', 'effective_to', 'notes', 'is_active', 'created_by',
    ];

    protected $casts = [
        'price'          => 'decimal:2',
        'min_quantity'   => 'decimal:3',
        'effective_from' => 'date',
        'effective_to'   => 'date',
        'is_active'      => 'boolean',
        'created_date'   => 'datetime',
        'updated_date'   => 'datetime',
    ];

    public function customer(): BelongsTo { return $this->belongsTo(Customer::class, 'customer_id'); }
    public function product(): BelongsTo  { return $this->belongsTo(Product::class, 'product_id'); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
