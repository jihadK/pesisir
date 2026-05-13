<?php

namespace App\Http\Requests\PurchaseOrder;

class UpdatePurchaseOrderRequest extends StorePurchaseOrderRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('purchase_order.update') ?? false;
    }
}
