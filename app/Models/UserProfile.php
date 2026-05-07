<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends BaseModel
{
    protected $table = 'tbm_user_profiles';

    public $timestamps = false;

    protected $fillable = [
        'user_id', 'avatar_url', 'address', 'city', 'province', 'postal_code',
        'country', 'birth_date', 'gender', 'employee_id', 'department',
        'position', 'join_date', 'timezone', 'language', 'preferences',
    ];

    protected $casts = [
        'birth_date'  => 'date',
        'join_date'   => 'date',
        'preferences' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
