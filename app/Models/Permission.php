<?php

namespace App\Models;

class Permission extends BaseModel
{
    protected $table = 'tbm_permissions';

    public $timestamps = false;

    protected $fillable = ['name', 'display_name', 'module', 'description', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
