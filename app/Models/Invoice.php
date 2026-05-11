<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends BaseModel
{
    protected $table = 'tbr_invoices';

    public const STATUS_DRAFT     = 'draft';
    public const STATUS_ISSUED    = 'issued';
    public const STATUS_PARTIAL   = 'partial';
    public const STATUS_PAID      = 'paid';
    public const STATUS_OVERDUE   = 'overdue';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_VOID      = 'void';

    protected $fillable = [
        'invoice_number', 'so_id', 'do_id', 'customer_id',
        'invoice_date', 'due_date', 'tax_id',
        'subtotal', 'discount_amount', 'tax_amount', 'shipping_cost', 'total_amount',
        'paid_amount', 'status', 'payment_terms_days', 'currency', 'notes', 'created_by',
    ];

    protected $casts = [
        'invoice_date'       => 'date',
        'due_date'           => 'date',
        'subtotal'           => 'decimal:2',
        'discount_amount'    => 'decimal:2',
        'tax_amount'         => 'decimal:2',
        'shipping_cost'      => 'decimal:2',
        'total_amount'       => 'decimal:2',
        'paid_amount'        => 'decimal:2',
        'outstanding_amount' => 'decimal:2',
        'payment_terms_days' => 'integer',
    ];

    public function items(): HasMany           { return $this->hasMany(InvoiceItem::class, 'invoice_id'); }
    public function customer(): BelongsTo      { return $this->belongsTo(Customer::class, 'customer_id'); }
    public function salesOrder(): BelongsTo    { return $this->belongsTo(SalesOrder::class, 'so_id'); }
    public function deliveryOrder(): BelongsTo { return $this->belongsTo(DeliveryOrder::class, 'do_id'); }
    public function createdBy(): BelongsTo     { return $this->belongsTo(User::class, 'created_by'); }

    public function payments(): BelongsToMany
    {
        return $this->belongsToMany(Payment::class, 'tbr_invoice_payments', 'invoice_id', 'payment_id')
            ->withPivot('allocated_amount', 'created_date');
    }

    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        if (! $term) return $q;
        return $q->where(function ($qq) use ($term) {
            $qq->where('invoice_number', 'ilike', "%$term%")
               ->orWhereHas('customer', fn ($c) => $c->where('name', 'ilike', "%$term%"));
        });
    }

    public function scopeOfStatus(Builder $q, ?string $status): Builder
    {
        return $status ? $q->where('status', $status) : $q;
    }

    public function scopeOutstanding(Builder $q): Builder
    {
        return $q->whereIn('status', [self::STATUS_ISSUED, self::STATUS_PARTIAL, self::STATUS_OVERDUE]);
    }

    public function scopeBetweenDates(Builder $q, ?string $from, ?string $to): Builder
    {
        if ($from) $q->whereDate('invoice_date', '>=', $from);
        if ($to)   $q->whereDate('invoice_date', '<=', $to);
        return $q;
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_DRAFT     => 'Draft',
            self::STATUS_ISSUED    => 'Issued (Belum Lunas)',
            self::STATUS_PARTIAL   => 'Partial Paid',
            self::STATUS_PAID      => 'Lunas',
            self::STATUS_OVERDUE   => 'Overdue',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_VOID      => 'Void',
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
            self::STATUS_ISSUED    => 'badge-light-primary',
            self::STATUS_PARTIAL   => 'badge-light-warning',
            self::STATUS_PAID      => 'badge-light-success',
            self::STATUS_OVERDUE   => 'badge-light-danger',
            self::STATUS_CANCELLED, self::STATUS_VOID => 'badge-light-dark',
            default => 'badge-light',
        };
    }

    public function isCancellable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_ISSUED], true)
            && (float) $this->paid_amount === 0.0;
    }

    public function isReceivable(): bool
    {
        return in_array($this->status, [self::STATUS_ISSUED, self::STATUS_PARTIAL, self::STATUS_OVERDUE], true);
    }
}
