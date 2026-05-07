<?php

namespace App\Models;

use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable implements AuthenticatableContract
{
    use Notifiable, SoftDeletes;

    protected $table = 'tbm_users';

    const CREATED_AT = 'created_date';
    const UPDATED_AT = 'updated_date';
    const DELETED_AT = 'deleted_date';

    protected $fillable = [
        'role_id', 'username', 'email', 'password_hash', 'full_name', 'phone',
        'is_active', 'registration_status', 'must_change_password',
        'two_factor_enabled', 'email_verified_at',
    ];

    protected $hidden = [
        'password_hash', 'remember_token',
        'two_factor_secret', 'two_factor_recovery',
    ];

    protected $casts = [
        'email_verified_at'    => 'datetime',
        'last_login_at'        => 'datetime',
        'locked_until'         => 'datetime',
        'password_changed_at'  => 'datetime',
        'is_active'            => 'boolean',
        'must_change_password' => 'boolean',
        'two_factor_enabled'   => 'boolean',
    ];

    /**
     * Override Laravel default — kolom kita namanya `password_hash`.
     */
    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    public function getAuthPasswordName(): string
    {
        return 'password_hash';
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class, 'user_id');
    }

    public function warehouses(): BelongsToMany
    {
        return $this->belongsToMany(
            Warehouse::class,
            'tbm_user_warehouses',
            'user_id',
            'warehouse_id'
        )->withPivot('access_level', 'is_default');
    }

    public function defaultWarehouse(): ?Warehouse
    {
        return $this->warehouses()->wherePivot('is_default', true)->first();
    }

    /**
     * Check permission via PostgreSQL function fn_user_has_permission().
     */
    public function hasPermission(string $permission): bool
    {
        $result = DB::selectOne(
            'SELECT fn_user_has_permission(?, ?) AS has_perm',
            [$this->id, $permission]
        );

        return (bool) ($result->has_perm ?? false);
    }

    public function isLocked(): bool
    {
        return $this->locked_until && $this->locked_until->isFuture();
    }

    public function isActive(): bool
    {
        return $this->is_active
            && $this->registration_status === 'active'
            && is_null($this->deleted_date);
    }
}
