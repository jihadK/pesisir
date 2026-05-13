<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderCost extends Model
{
    protected $table = 'tbr_purchase_order_costs';

    public $timestamps = false;

    public const TYPE_CLEANING = 'cleaning';
    public const TYPE_OTHER    = 'other';

    protected $fillable = [
        'po_id', 'cost_type',
        'employee_id', 'po_item_id', 'service_rate_id',
        'description', 'qty', 'unit', 'unit_price', 'subtotal',
    ];

    protected $casts = [
        'qty'        => 'decimal:3',
        'unit_price' => 'decimal:2',
        'subtotal'   => 'decimal:2',
    ];

    public function purchaseOrder(): BelongsTo  { return $this->belongsTo(PurchaseOrder::class, 'po_id'); }
    public function employee(): BelongsTo       { return $this->belongsTo(Employee::class, 'employee_id'); }
    public function poItem(): BelongsTo         { return $this->belongsTo(PurchaseOrderItem::class, 'po_item_id'); }
    public function serviceRate(): BelongsTo    { return $this->belongsTo(ServiceRate::class, 'service_rate_id'); }
}
