<?php

namespace App\Http\Requests\SuppliesPurchase;

class UpdateSuppliesPurchaseRequest extends StoreSuppliesPurchaseRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('supplies_purchase.update') ?? false;
    }
}
