<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceRate extends BaseModel
{
    protected $table = 'tbm_service_rates';

    protected $fillable = ['name', 'category_id', 'rate_per_kg', 'is_active', 'notes'];

    protected $casts = [
        'rate_per_kg' => 'decimal:2',
        'is_active'   => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        if (! $term) return $q;
        return $q->where(function ($qq) use ($term) {
            $qq->where('name', 'ilike', "%$term%")
               ->orWhereHas('category', fn ($c) => $c->where('name', 'ilike', "%$term%"));
        });
    }
}
