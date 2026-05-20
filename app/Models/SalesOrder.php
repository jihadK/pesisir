<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesOrder extends BaseModel
{
    protected $table = 'tbr_sales_orders';

    public const STATUS_DRAFT     = 'draft';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_PARTIAL   = 'partial';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_INVOICED  = 'invoiced';
    public const STATUS_PAID      = 'paid';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'so_number', 'customer_id', 'sales_user_id', 'warehouse_id',
        'order_date', 'delivery_date', 'status',
        'subtotal', 'discount_amount', 'tax_amount', 'shipping_cost', 'packing_cost',
        'other_cost_amount', 'other_cost_desc', 'total_amount',
        'payment_terms_days', 'payment_method_id', 'notes', 'created_by', 'approved_by',
    ];

    protected $casts = [
        'order_date'         => 'date',
        'delivery_date'      => 'date',
        'subtotal'           => 'decimal:2',
        'discount_amount'    => 'decimal:2',
        'tax_amount'         => 'decimal:2',
        'shipping_cost'      => 'decimal:2',
        'packing_cost'       => 'decimal:2',
        'other_cost_amount'  => 'decimal:2',
        'total_amount'       => 'decimal:2',
        'payment_terms_days' => 'integer',
    ];

    public function items(): HasMany           { return $this->hasMany(SalesOrderItem::class, 'so_id'); }
    public function customer(): BelongsTo      { return $this->belongsTo(Customer::class, 'customer_id'); }
    public function warehouse(): BelongsTo     { return $this->belongsTo(Warehouse::class, 'warehouse_id'); }
    public function salesUser(): BelongsTo     { return $this->belongsTo(User::class, 'sales_user_id'); }
    public function createdBy(): BelongsTo     { return $this->belongsTo(User::class, 'created_by'); }
    public function paymentMethod(): BelongsTo { return $this->belongsTo(PaymentMethod::class, 'payment_method_id'); }

    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        if (! $term) return $q;
        return $q->where(function ($qq) use ($term) {
            $qq->where('so_number', 'ilike', "%$term%")
               ->orWhereHas('customer', fn ($c) => $c->where('name', 'ilike', "%$term%")->orWhere('code', 'ilike', "%$term%"));
        });
    }

    public function scopeOfStatus(Builder $q, ?string $status): Builder
    {
        return $status ? $q->where('status', $status) : $q;
    }

    public function scopeBetweenDates(Builder $q, ?string $from, ?string $to): Builder
    {
        if ($from) $q->whereDate('order_date', '>=', $from);
        if ($to)   $q->whereDate('order_date', '<=', $to);
        return $q;
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_DRAFT     => 'Draft',
            self::STATUS_CONFIRMED => 'Confirmed',
            self::STATUS_PARTIAL   => 'Partial Delivered',
            self::STATUS_DELIVERED => 'Delivered',
            self::STATUS_INVOICED  => 'Invoiced',
            self::STATUS_PAID      => 'Paid',
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
            self::STATUS_CONFIRMED => 'badge-light-primary',
            self::STATUS_PARTIAL   => 'badge-light-warning',
            self::STATUS_DELIVERED => 'badge-light-info',
            self::STATUS_INVOICED  => 'badge-light-success',
            self::STATUS_PAID      => 'badge-light-success',
            self::STATUS_CANCELLED => 'badge-light-danger',
            default                => 'badge-light',
        };
    }

    public function isEditable(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isConfirmable(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isCancellable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_CONFIRMED], true);
    }

    public function isMarkPaidable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_CONFIRMED, self::STATUS_PARTIAL, self::STATUS_DELIVERED, self::STATUS_INVOICED], true);
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }
}
