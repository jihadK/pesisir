<?php

namespace App\Models;

class PriceTier extends BaseModel
{
    protected $table = 'tbm_price_tiers';

    public $timestamps = false;

    protected $fillable = ['name', 'description', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];
}
