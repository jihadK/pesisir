<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryOrder extends BaseModel
{
    protected $table = 'tbr_delivery_orders';

    public const STATUS_DRAFT     = 'draft';
    public const STATUS_SHIPPED   = 'shipped';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_RETURNED  = 'returned';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'do_number', 'so_id', 'customer_id', 'warehouse_id',
        'delivery_date', 'driver_name', 'vehicle_no',
        'status', 'delivered_at', 'received_by_name',
        'notes', 'created_by',
    ];

    protected $casts = [
        'delivery_date' => 'date',
        'delivered_at'  => 'datetime',
    ];

    public function items(): HasMany       { return $this->hasMany(DeliveryOrderItem::class, 'do_id'); }
    public function salesOrder(): BelongsTo { return $this->belongsTo(SalesOrder::class, 'so_id'); }
    public function customer(): BelongsTo  { return $this->belongsTo(Customer::class, 'customer_id'); }
    public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class, 'warehouse_id'); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        if (! $term) return $q;
        return $q->where(function ($qq) use ($term) {
            $qq->where('do_number', 'ilike', "%$term%")
               ->orWhereHas('customer', fn ($c) => $c->where('name', 'ilike', "%$term%"))
               ->orWhereHas('salesOrder', fn ($so) => $so->where('so_number', 'ilike', "%$term%"));
        });
    }

    public function scopeOfStatus(Builder $q, ?string $status): Builder
    {
        return $status ? $q->where('status', $status) : $q;
    }

    public function scopeBetweenDates(Builder $q, ?string $from, ?string $to): Builder
    {
        if ($from) $q->whereDate('delivery_date', '>=', $from);
        if ($to)   $q->whereDate('delivery_date', '<=', $to);
        return $q;
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_DRAFT     => 'Draft',
            self::STATUS_SHIPPED   => 'Shipped',
            self::STATUS_DELIVERED => 'Delivered',
            self::STATUS_RETURNED  => 'Returned',
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
            self::STATUS_SHIPPED   => 'badge-light-primary',
            self::STATUS_DELIVERED => 'badge-light-success',
            self::STATUS_RETURNED  => 'badge-light-warning',
            self::STATUS_CANCELLED => 'badge-light-danger',
            default                => 'badge-light',
        };
    }

    public function isEditable(): bool   { return $this->status === self::STATUS_DRAFT; }
    public function isShippable(): bool  { return $this->status === self::STATUS_DRAFT; }
    public function isCancellable(): bool { return $this->status === self::STATUS_DRAFT; }
}
