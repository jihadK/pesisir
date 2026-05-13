<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends BaseModel
{
    protected $table = 'tbr_purchase_orders';

    public const STATUS_DRAFT     = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_PARTIAL   = 'partial';
    public const STATUS_RECEIVED  = 'received';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'po_number', 'supplier_id', 'warehouse_id',
        'po_date', 'expected_date', 'status',
        'subtotal', 'tax_amount', 'additional_cost_total', 'total_amount',
        'notes', 'created_by', 'approved_by',
    ];

    protected $casts = [
        'po_date'               => 'date',
        'expected_date'         => 'date',
        'subtotal'              => 'decimal:2',
        'tax_amount'            => 'decimal:2',
        'additional_cost_total' => 'decimal:2',
        'total_amount'          => 'decimal:2',
    ];

    public function items(): HasMany       { return $this->hasMany(PurchaseOrderItem::class, 'po_id'); }
    public function costs(): HasMany       { return $this->hasMany(PurchaseOrderCost::class, 'po_id'); }
    public function supplier(): BelongsTo  { return $this->belongsTo(Supplier::class, 'supplier_id'); }
    public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class, 'warehouse_id'); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function approvedBy(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }

    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        if (! $term) return $q;
        return $q->where(function ($qq) use ($term) {
            $qq->where('po_number', 'ilike', "%$term%")
               ->orWhereHas('supplier', fn ($s) => $s->where('name', 'ilike', "%$term%")->orWhere('code', 'ilike', "%$term%"));
        });
    }

    public function scopeOfStatus(Builder $q, ?string $status): Builder
    {
        return $status ? $q->where('status', $status) : $q;
    }

    public function scopeBetweenDates(Builder $q, ?string $from, ?string $to): Builder
    {
        if ($from) $q->whereDate('po_date', '>=', $from);
        if ($to)   $q->whereDate('po_date', '<=', $to);
        return $q;
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_DRAFT     => 'Draft',
            self::STATUS_SUBMITTED => 'Submitted',
            self::STATUS_PARTIAL   => 'Partial Received',
            self::STATUS_RECEIVED  => 'Received',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    public function getStatusLabelAttribute(): string
    {
        return self::statusLabels()[$this->status] ?? $this->status;
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT     => 'badge-light-secondary',
            self::STATUS_SUBMITTED => 'badge-light-primary',
            self::STATUS_PARTIAL   => 'badge-light-warning',
            self::STATUS_RECEIVED  => 'badge-light-success',
            self::STATUS_CANCELLED => 'badge-light-danger',
            default                => 'badge-light',
        };
    }

    public function isEditable(): bool    { return $this->status === self::STATUS_DRAFT; }
    public function isSubmittable(): bool { return $this->status === self::STATUS_DRAFT; }
    public function isCancellable(): bool { return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_SUBMITTED], true); }
    public function isReceivable(): bool  { return in_array($this->status, [self::STATUS_SUBMITTED, self::STATUS_PARTIAL], true); }
}
