<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Supplier extends BaseModel
{
    use SoftDeletes;

    protected $table = 'tbm_suppliers';
    const DELETED_AT = 'deleted_date';

    protected $fillable = [
        'code', 'name', 'contact_person', 'phone', 'email',
        'address', 'city', 'npwp',
        'bank_name', 'bank_account',
        'payment_terms_days', 'is_active',
    ];

    protected $casts = [
        'is_active'          => 'boolean',
        'payment_terms_days' => 'integer',
    ];

    /* ========== Scopes ========== */
    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        if (! $term) return $q;
        return $q->where(function ($qq) use ($term) {
            $qq->where('code', 'ilike', "%$term%")
               ->orWhere('name', 'ilike', "%$term%")
               ->orWhere('contact_person', 'ilike', "%$term%")
               ->orWhere('phone', 'ilike', "%$term%")
               ->orWhere('email', 'ilike', "%$term%");
        });
    }

    public function scopeOfCity(Builder $q, ?string $city): Builder
    {
        return $city ? $q->where('city', $city) : $q;
    }

    /* ========== Helpers ========== */
    public function getStatusBadgeAttribute(): string
    {
        return $this->is_active ? 'badge-light-success' : 'badge-light-danger';
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->is_active ? 'Aktif' : 'Non-aktif';
    }

    public function getTotalPurchase(): float
    {
        return (float) DB::table('tbr_purchase_orders')
            ->where('supplier_id', $this->id)
            ->whereIn('status', ['received', 'partial'])
            ->sum('total_amount');
    }

    public function getActivePOCount(): int
    {
        return (int) DB::table('tbr_purchase_orders')
            ->where('supplier_id', $this->id)
            ->whereIn('status', ['draft', 'submitted', 'partial'])
            ->count();
    }
}
