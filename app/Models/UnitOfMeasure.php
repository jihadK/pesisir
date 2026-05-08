<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

class UnitOfMeasure extends BaseModel
{
    protected $table = 'tbm_units_of_measure';

    public $timestamps = false;

    protected $fillable = ['code', 'name', 'symbol', 'description'];

    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        if (! $term) return $q;
        return $q->where(function ($qq) use ($term) {
            $qq->where('code', 'ilike', "%$term%")
               ->orWhere('name', 'ilike', "%$term%")
               ->orWhere('symbol', 'ilike', "%$term%");
        });
    }
}
