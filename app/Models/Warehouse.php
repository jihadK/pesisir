<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;

class Warehouse extends BaseModel
{
    protected $table = 'tbm_warehouses';

    protected $fillable = [
        'code', 'name', 'address', 'type',
        'temperature_min', 'temperature_max',
        'pic_user_id', 'is_active',
    ];

    protected $casts = [
        'is_active'       => 'boolean',
        'temperature_min' => 'decimal:1',
        'temperature_max' => 'decimal:1',
    ];

    public const TYPES = [
        'cold_storage' => 'Cold Storage',
        'freezer'      => 'Freezer',
        'dry'          => 'Dry Storage',
        'retail'       => 'Retail',
    ];

    /* =========================================================
     | Relations
     | ========================================================= */
    public function picUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pic_user_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'tbm_user_warehouses',
            'warehouse_id',
            'user_id'
        )->withPivot('access_level', 'is_default');
    }

    /* =========================================================
     | Scopes
     | ========================================================= */
    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    public function scopeOfType(Builder $q, string $type): Builder
    {
        return $q->where('type', $type);
    }

    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        if (! $term) return $q;
        return $q->where(function ($qq) use ($term) {
            $qq->where('code', 'ilike', "%$term%")
               ->orWhere('name', 'ilike', "%$term%")
               ->orWhere('address', 'ilike', "%$term%");
        });
    }

    /* =========================================================
     | Helpers
     | ========================================================= */
    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function getTypeBadgeClassAttribute(): string
    {
        return match ($this->type) {
            'cold_storage' => 'badge-light-info',
            'freezer'      => 'badge-light-primary',
            'dry'          => 'badge-light-warning',
            'retail'       => 'badge-light-success',
            default        => 'badge-light',
        };
    }

    public function getTotalStockQty(): float
    {
        return (float) DB::table('tbs_stock_balances')
            ->where('warehouse_id', $this->id)
            ->sum('quantity');
    }
}
