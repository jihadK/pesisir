<?php

namespace App\Http\Requests\SalesOrder;

class UpdateSalesOrderRequest extends StoreSalesOrderRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('sales_order.update') ?? false;
    }
}
