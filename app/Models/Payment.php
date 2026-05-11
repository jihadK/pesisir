<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Payment extends BaseModel
{
    protected $table = 'tbr_payments';

    public const STATUS_PENDING   = 'pending';
    public const STATUS_CLEARED   = 'cleared';
    public const STATUS_BOUNCED   = 'bounced';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'payment_number', 'customer_id', 'payment_method_id', 'payment_date',
        'amount', 'reference_no', 'notes', 'status', 'created_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount'       => 'decimal:2',
    ];

    public function customer(): BelongsTo      { return $this->belongsTo(Customer::class, 'customer_id'); }
    public function paymentMethod(): BelongsTo { return $this->belongsTo(PaymentMethod::class, 'payment_method_id'); }
    public function createdBy(): BelongsTo     { return $this->belongsTo(User::class, 'created_by'); }

    public function invoices(): BelongsToMany
    {
        return $this->belongsToMany(Invoice::class, 'tbr_invoice_payments', 'payment_id', 'invoice_id')
            ->withPivot('allocated_amount', 'created_date');
    }

    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        if (! $term) return $q;
        return $q->where(function ($qq) use ($term) {
            $qq->where('payment_number', 'ilike', "%$term%")
               ->orWhere('reference_no', 'ilike', "%$term%")
               ->orWhereHas('customer', fn ($c) => $c->where('name', 'ilike', "%$term%"));
        });
    }

    public function scopeOfStatus(Builder $q, ?string $status): Builder
    {
        return $status ? $q->where('status', $status) : $q;
    }

    public function scopeBetweenDates(Builder $q, ?string $from, ?string $to): Builder
    {
        if ($from) $q->whereDate('payment_date', '>=', $from);
        if ($to)   $q->whereDate('payment_date', '<=', $to);
        return $q;
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_PENDING   => 'Pending',
            self::STATUS_CLEARED   => 'Cleared',
            self::STATUS_BOUNCED   => 'Bounced',
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
            self::STATUS_PENDING   => 'badge-light-warning',
            self::STATUS_CLEARED   => 'badge-light-success',
            self::STATUS_BOUNCED   => 'badge-light-danger',
            self::STATUS_CANCELLED => 'badge-light-secondary',
            default => 'badge-light',
        };
    }
}
