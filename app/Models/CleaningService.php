<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CleaningService extends BaseModel
{
    protected $table = 'tbr_cleaning_services';

    protected $fillable = [
        'service_no', 'service_date', 'employee_id', 'category_id',
        'qty_kg', 'rate_per_kg', 'subtotal', 'notes', 'created_by',
    ];

    protected $casts = [
        'service_date' => 'date',
        'qty_kg'       => 'decimal:3',
        'rate_per_kg'  => 'decimal:2',
        'subtotal'     => 'decimal:2',
    ];

    public function employee(): BelongsTo  { return $this->belongsTo(Employee::class, 'employee_id'); }
    public function category(): BelongsTo  { return $this->belongsTo(Category::class, 'category_id'); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        if (! $term) return $q;
        return $q->where(function ($qq) use ($term) {
            $qq->where('service_no', 'ilike', "%$term%")
               ->orWhereHas('employee', fn ($e) => $e->where('name', 'ilike', "%$term%"))
               ->orWhereHas('category', fn ($c) => $c->where('name', 'ilike', "%$term%"));
        });
    }

    public function scopeBetweenDates(Builder $q, ?string $from, ?string $to): Builder
    {
        if ($from) $q->whereDate('service_date', '>=', $from);
        if ($to)   $q->whereDate('service_date', '<=', $to);
        return $q;
    }
}
