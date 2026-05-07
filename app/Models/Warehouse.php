<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Warehouse extends BaseModel
{
    protected $table = 'tbm_warehouses';

    protected $fillable = [
        'code', 'name', 'address', 'type',
        'temperature_min', 'temperature_max',
        'pic_user_id', 'is_active',
    ];

    protected $casts = [
        'is_active'       => 'boolean',
        'temperature_min' => 'decimal:1',
        'temperature_max' => 'decimal:1',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'tbm_user_warehouses',
            'warehouse_id',
            'user_id'
        )->withPivot('access_level', 'is_default');
    }
}
