<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuppliesPurchase extends BaseModel
{
    protected $table = 'tbr_supplies_purchases';

    protected $fillable = [
        'purchase_no', 'purchase_date', 'supplier_id',
        'description', 'qty', 'unit', 'unit_price', 'subtotal',
        'notes', 'created_by',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'qty'           => 'decimal:3',
        'unit_price'    => 'decimal:2',
        'subtotal'      => 'decimal:2',
    ];

    public function supplier(): BelongsTo  { return $this->belongsTo(Supplier::class, 'supplier_id'); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        if (! $term) return $q;
        return $q->where(function ($qq) use ($term) {
            $qq->where('purchase_no', 'ilike', "%$term%")
               ->orWhere('description', 'ilike', "%$term%")
               ->orWhereHas('supplier', fn ($s) => $s->where('name', 'ilike', "%$term%"));
        });
    }

    public function scopeBetweenDates(Builder $q, ?string $from, ?string $to): Builder
    {
        if ($from) $q->whereDate('purchase_date', '>=', $from);
        if ($to)   $q->whereDate('purchase_date', '<=', $to);
        return $q;
    }
}
