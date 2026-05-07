<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Customer extends BaseModel
{
    use SoftDeletes;

    protected $table = 'tbm_customers';
    const DELETED_AT = 'deleted_date';

    protected $fillable = [
        'code', 'price_tier_id', 'name', 'customer_type',
        'contact_person', 'phone', 'email', 'address', 'city', 'npwp',
        'credit_limit', 'payment_terms_days', 'is_active',
    ];

    protected $casts = [
        'is_active'          => 'boolean',
        'credit_limit'       => 'decimal:2',
        'payment_terms_days' => 'integer',
    ];

    /** Tipe customer */
    public const TYPES = [
        'individu'  => 'Individu',
        'corporate' => 'Corporate',
        'reseller'  => 'Reseller',
        'restoran'  => 'Restoran',
        'pasar'     => 'Pasar',
    ];

    /** Auto-suggestion price_tier per customer_type (boleh di-override user) */
    public const TYPE_TO_TIER = [
        'individu'  => 'Retail',
        'corporate' => 'Grosir',
        'reseller'  => 'Reseller',
        'restoran'  => 'Restoran',
        'pasar'     => 'Retail',
    ];

    /* ========== Relations ========== */
    public function priceTier(): BelongsTo
    {
        return $this->belongsTo(\App\Models\PriceTier::class, 'price_tier_id');
    }

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

    public function scopeOfType(Builder $q, ?string $type): Builder
    {
        return $type ? $q->where('customer_type', $type) : $q;
    }

    public function scopeOfTier(Builder $q, $tierId): Builder
    {
        return $tierId ? $q->where('price_tier_id', $tierId) : $q;
    }

    public function scopeOfCity(Builder $q, ?string $city): Builder
    {
        return $city ? $q->where('city', $city) : $q;
    }

    /* ========== Helpers ========== */
    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->customer_type] ?? $this->customer_type;
    }

    public function getTypeBadgeClassAttribute(): string
    {
        return match ($this->customer_type) {
            'corporate' => 'badge-light-primary',
            'reseller'  => 'badge-light-info',
            'restoran'  => 'badge-light-warning',
            'pasar'     => 'badge-light-success',
            'individu'  => 'badge-light-secondary',
            default     => 'badge-light',
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return $this->is_active ? 'badge-light-success' : 'badge-light-danger';
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->is_active ? 'Aktif' : 'Non-aktif';
    }

    /** Outstanding piutang sekarang (sum dari invoice issued/partial/overdue) */
    public function getOutstandingAR(): float
    {
        return (float) DB::table('tbr_invoices')
            ->where('customer_id', $this->id)
            ->whereIn('status', ['issued', 'partial', 'overdue'])
            ->sum('outstanding_amount');
    }

    /** Persentase pemakaian kredit (0-100). Kalau credit_limit=0 → return null */
    public function getCreditUtilization(): ?float
    {
        $limit = (float) $this->credit_limit;
        if ($limit <= 0) return null;
        $outstanding = $this->getOutstandingAR();
        return round(($outstanding / $limit) * 100, 1);
    }

    /** Class warna untuk progress bar credit utilization */
    public function getCreditColorClass(): string
    {
        $util = $this->getCreditUtilization();
        if ($util === null) return 'bg-secondary';
        if ($util < 70)  return 'bg-success';
        if ($util < 90)  return 'bg-warning';
        return 'bg-danger';
    }
}
