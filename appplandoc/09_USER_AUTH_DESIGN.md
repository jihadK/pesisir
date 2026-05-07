# User Authentication & Authorization — Design

## 🎯 Cakupan
Modul tambahan untuk: **register, login, password reset, email verification, role-permission granular, multi-warehouse access, 2FA, session management, audit user**.

## 📋 Tabel Tambahan (12 baru + update users)

### Update `users` — 10 kolom baru
- `email_verified_at`, `remember_token`
- `failed_login_attempts`, `locked_until` — anti brute-force
- `password_changed_at`, `must_change_password`
- `two_factor_enabled`, `two_factor_secret`, `two_factor_recovery`
- `registration_status` — pending/active/suspended/banned

### Tabel Baru
| # | Tabel | Fungsi |
|---|-------|--------|
| 1 | `permissions` | Master permission granular (~70 permission seed) |
| 2 | `role_permissions` | M:N role × permission |
| 3 | `user_permissions` | Override per-user (allow/deny) |
| 4 | `user_warehouses` | Multi-cabang access (read/write/admin) |
| 5 | `user_profiles` | Avatar, alamat, dept, position, preferences (JSONB) |
| 6 | `email_verifications` | Token verifikasi email |
| 7 | `password_resets` | Token forgot password |
| 8 | `login_attempts` | Audit + rate limit login |
| 9 | `user_sessions` | Active session per device |
| 10 | `personal_access_tokens` | Laravel Sanctum compatible |
| 11 | `two_factor_codes` | OTP untuk 2FA |
| 12 | `user_activity_logs` | Audit aksi penting user |

## 🔑 Permission Naming Convention
Format: `<module>.<action>`
- `products.view`, `products.create`, `products.update`, `products.delete`
- `po.approve`, `so.approve`, `transfer.approve`
- `report.stock`, `report.sales`, `report.ar`

## 🎭 Permission Matrix (Default)

| Role | Master | Inventory | Sales | Invoice | Report | System |
|------|--------|-----------|-------|---------|--------|--------|
| **admin** | ✅ ALL | ✅ ALL | ✅ ALL | ✅ ALL | ✅ ALL | ✅ ALL |
| **manager** | ✅ (no user/role del) | ✅ ALL | ✅ ALL | ✅ ALL | ✅ ALL | ❌ |
| **cashier** | view only | ❌ | view only | ✅ create/update | sales+AR | ❌ |
| **warehouse** | view only | ✅ (no approve) | ❌ | ❌ | ❌ | ❌ |
| **sales** | view+customers | view stock | ✅ (no approve/cancel) | ❌ | sales | ❌ |

## 🔄 Auth Flow

### Register
```
1. POST /register {email, password, full_name}
2. INSERT users (registration_status='pending')
3. Generate verification token → INSERT email_verifications
4. Send email dengan link verifikasi
5. User klik link → GET /verify/{token}
6. UPDATE users.email_verified_at, registration_status='active'
7. Auto-login atau redirect ke /login
```

### Login
```
1. POST /login {email, password}
2. Cek users by email (deleted_at IS NULL)
3. Cek locked_until → kalau masih ke-lock, tolak
4. Verify password (bcrypt)
5. Kalau salah:
   - INCREMENT failed_login_attempts
   - Kalau ≥5 → SET locked_until = NOW + 30min
   - INSERT login_attempts (success=FALSE)
   - Return error
6. Kalau benar:
   - Reset failed_login_attempts = 0, locked_until = NULL
   - UPDATE last_login_at
   - INSERT login_attempts (success=TRUE)
   - INSERT user_sessions
   - (Opsional) Kalau 2FA enabled → generate OTP, kirim ke email/SMS
   - Return session cookie / API token
```

### Forgot Password
```
1. POST /password/forgot {email}
2. Cek user exist
3. Generate token, hash, save ke password_resets (expires 1 jam)
4. Send email dengan link reset
5. POST /password/reset {token, new_password}
6. Verify token (hash + expires_at + used_at IS NULL)
7. UPDATE users.password_hash, password_changed_at
8. UPDATE password_resets.used_at
9. Invalidate semua user_sessions user ini (force re-login)
```

## 🛡️ Security Best Practices

1. **Password hash**: bcrypt (Laravel default)
2. **Token hash**: SHA-256 atau bcrypt (jangan simpan plain)
3. **Email verification**: token expire 24 jam
4. **Password reset**: token expire 1 jam, sekali pakai
5. **Login lockout**: 5x gagal → lock 30 menit
6. **Session expiry**: 7 hari (atau lebih pendek untuk sensitive role)
7. **Failed attempt cleanup**: cron hapus log >90 hari
8. **2FA recovery codes**: enkripsi dengan APP_KEY

## 🧪 Contoh Query

### Cek permission
```sql
SELECT fn_user_has_permission(5, 'po.approve');
```

### Lihat semua permission user
```sql
SELECT * FROM v_user_permissions WHERE user_id = 5 ORDER BY module, permission_name;
```

### Lihat user yang ke-lock
```sql
SELECT id, username, email, locked_until, failed_login_attempts
FROM users WHERE locked_until > NOW();
```

### Force logout user dari semua device
```sql
DELETE FROM user_sessions WHERE user_id = 5;
DELETE FROM personal_access_tokens WHERE tokenable_id = 5;
```

### Top 10 IP gagal login (potensi brute-force)
```sql
SELECT ip_address, COUNT(*) AS attempts
FROM login_attempts
WHERE success = FALSE AND attempted_at > NOW() - INTERVAL '24 hours'
GROUP BY ip_address
ORDER BY attempts DESC LIMIT 10;
```

### Assign user ke gudang
```sql
INSERT INTO user_warehouses (user_id, warehouse_id, access_level, is_default)
VALUES (5, 2, 'write', TRUE);
```

### Buat permission temporary (expires)
```sql
-- Sales boleh approve SO selama 7 hari (cover manager cuti)
INSERT INTO user_permissions (user_id, permission_id, grant_type, expires_at, notes)
SELECT 5, id, 'allow', NOW() + INTERVAL '7 days', 'Cover manager cuti'
FROM permissions WHERE name = 'so.approve';
```

## 📦 Mapping ke Laravel

| Tabel DB | Laravel Component |
|----------|-------------------|
| `users` | `App\Models\User` |
| `permissions`, `role_permissions`, `user_permissions` | Spatie Permission package |
| `email_verifications` | Built-in `MustVerifyEmail` |
| `password_resets` | Built-in `Password` facade |
| `personal_access_tokens` | Laravel Sanctum |
| `user_sessions` | Custom session driver atau pakai `database` driver |
| `two_factor_codes` | Custom OTP service |

### Recommended Packages
```bash
composer require laravel/sanctum
composer require spatie/laravel-permission
composer require pragmarx/google2fa-laravel  # untuk 2FA
```

## 🔗 Integration Points dengan Aplikasi

### Middleware Permission (Laravel)
```php
Route::middleware(['auth','permission:po.approve'])->group(function () {
    Route::post('/po/{po}/approve', [POController::class, 'approve']);
});
```

### Multi-warehouse filter
```php
// Auto-filter SO berdasarkan warehouse user
$warehouseIds = auth()->user()->warehouses()->pluck('warehouse_id');
$sales = SalesOrder::whereIn('warehouse_id', $warehouseIds)->get();
```

### Dynamic role permission UI
```php
$permissions = Permission::orderBy('module')->orderBy('name')->get()->groupBy('module');
// Render checkbox tree per module di halaman role edit
```

## ✅ Migration Order

1. Run `04_DDL_POSTGRESQL.sql` (base schema 35 tabel)
2. Run `08_DDL_USER_AUTH.sql` (auth module 12 tabel + 70 permissions seed)
3. Update password admin default ke password kuat
4. Test query `SELECT fn_user_has_permission(1, 'users.view')` → harus TRUE
