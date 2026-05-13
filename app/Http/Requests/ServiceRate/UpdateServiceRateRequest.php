<?php

namespace App\Http\Requests\ServiceRate;

class UpdateServiceRateRequest extends StoreServiceRateRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('service_rate.update') ?? false;
    }
}
