<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class PriceTier extends BaseModel
{
    protected $table = 'tbm_price_tiers';

    public $timestamps = false;

    protected $fillable = ['name', 'description', 'is_active'];

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
               ->orWhere('description', 'ilike', "%$term%");
        });
    }

    /** Berapa customer pakai tier ini */
    public function getCustomerCount(): int
    {
        return (int) DB::table('tbm_customers')->where('price_tier_id', $this->id)->whereNull('deleted_date')->count();
    }
}
