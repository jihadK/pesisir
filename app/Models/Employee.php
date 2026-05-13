<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

class Employee extends BaseModel
{
    protected $table = 'tbm_employees';

    protected $fillable = ['code', 'name', 'position', 'phone', 'is_active', 'notes'];

    protected $casts = ['is_active' => 'boolean'];

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        if (! $term) return $q;
        return $q->where(function ($qq) use ($term) {
            $qq->where('name', 'ilike', "%$term%")
               ->orWhere('code', 'ilike', "%$term%")
               ->orWhere('position', 'ilike', "%$term%");
        });
    }
}
