<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $table = 'tbm_payment_methods';

    public $timestamps = false;

    protected $fillable = [
        'code', 'name', 'type', 'account_no', 'account_holder', 'bank_name',
        'qris_image_url', 'display_order', 'description', 'is_active',
    ];

    protected $casts = [
        'is_active'     => 'boolean',
        'display_order' => 'integer',
    ];

    public const TYPE_CASH     = 'cash';
    public const TYPE_TRANSFER = 'transfer';
    public const TYPE_GIRO     = 'giro';
    public const TYPE_CHEQUE   = 'cheque';
    public const TYPE_EWALLET  = 'ewallet';
    public const TYPE_CARD     = 'card';

    public static function typeLabels(): array
    {
        return [
            self::TYPE_CASH     => 'Tunai / COD',
            self::TYPE_TRANSFER => 'Transfer Bank',
            self::TYPE_GIRO     => 'Giro',
            self::TYPE_CHEQUE   => 'Cheque',
            self::TYPE_EWALLET  => 'E-Wallet / QRIS',
            self::TYPE_CARD     => 'Kartu Debit/Kredit',
        ];
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    public function scopeOrdered(Builder $q): Builder
    {
        return $q->orderBy('display_order')->orderBy('name');
    }

    public function getTypeLabelAttribute(): string
    {
        return self::typeLabels()[$this->type] ?? $this->type;
    }

    public function getQrisImageDisplayUrlAttribute(): ?string
    {
        if (! $this->qris_image_url) return null;
        return str_starts_with($this->qris_image_url, 'http')
            ? $this->qris_image_url
            : asset($this->qris_image_url);
    }
}
