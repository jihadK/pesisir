<?php

namespace App\Http\Requests\CleaningService;

class UpdateCleaningServiceRequest extends StoreCleaningServiceRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('cleaning_service.update') ?? false;
    }
}
