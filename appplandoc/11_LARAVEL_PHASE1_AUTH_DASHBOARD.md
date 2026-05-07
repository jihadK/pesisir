# Phase 1 — Setup Laravel + Auth + Dashboard

> **Status:** ✅ SELESAI &middot; **Tanggal:** 2026-05-06
> **Next phase:** CRUD Master Data (Products, Suppliers, Customers, Warehouses)

Dokumen ini meringkas semua yang sudah dibangun di Phase 1 supaya developer berikutnya
(atau Anda sendiri di sesi mendatang) bisa langsung melanjutkan tanpa perlu reverse-engineer.

---

## 1. Stack & Lingkungan

| Layer | Tool | Versi | Catatan |
|-------|------|-------|---------|
| OS dev | Windows 11 | — | |
| Web server lokal | Laravel Herd | — | PHP 8.3 + composer bundled |
| PHP | 8.3 | dari Herd, **bukan** Laragon | Laragon's PHP tidak punya `pgsql` extension |
| Framework | Laravel | 12.x | skeleton default |
| Database | PostgreSQL | 14+ (remote `103.93.162.70:5432`, db `pesisir`) | DDL ada di `04_*.sql` + `08_*.sql` |
| CSS/UI | Bootstrap 5 via Metronic 8.3 | — | Asset di `public/assets/` |
| Session | file driver | — | bisa pindah ke `database` atau `redis` saat scale |
| Queue | sync | — | upgrade ke `database`/`redis` saat ada job berat |

**⚠️ Wajib pakai Herd PHP** — perintah artisan harus pakai full path:
```powershell
"C:\Users\jihad\.config\herd\bin\php.bat" artisan <command>
```
Atau setting Herd PHP first di Windows PATH.

---

## 2. Struktur Folder Laravel

```
testapp/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Controller.php           ← base controller (Laravel default)
│   │   │   └── Web/                     ← controller untuk Blade web
│   │   │       ├── Auth/
│   │   │       │   └── LoginController.php
│   │   │       └── DashboardController.php
│   │   └── Middleware/
│   │       └── CheckPermission.php      ← middleware permission:xxx
│   ├── Models/
│   │   ├── BaseModel.php                ← override created_at/updated_at → _date
│   │   ├── User.php                     ← tbm_users (auth model)
│   │   ├── UserProfile.php              ← tbm_user_profiles
│   │   ├── Role.php                     ← tbm_roles
│   │   ├── Permission.php               ← tbm_permissions
│   │   └── Warehouse.php                ← tbm_warehouses
│   └── Providers/
│       └── AppServiceProvider.php       ← share $currentUser ke layout
├── bootstrap/
│   └── app.php                          ← register middleware alias + api routes
├── routes/
│   ├── web.php                          ← /login, /dashboard, /logout
│   └── api.php                          ← (placeholder utk mobile)
├── resources/
│   └── views/
│       ├── layouts/
│       │   ├── auth.blade.php           ← shell halaman login
│       │   └── app.blade.php            ← shell dashboard (page > aside + wrapper)
│       ├── partials/
│       │   ├── sidebar.blade.php        ← aside-dark dengan accordion menu
│       │   └── header.blade.php         ← topbar + user dropdown
│       ├── auth/
│       │   └── login.blade.php
│       └── dashboard.blade.php
├── public/
│   └── assets/                          ← Metronic (css/js/media/plugins) — copy dari template/
└── .env                                 ← config DB PG (jangan commit!)
```

**Folder yang BELUM dibuat tapi disiapkan untuk fase berikutnya:**
- `app/Http/Controllers/Api/` — REST controller untuk mobile (Phase mobile API)
- `app/Services/` — business logic (FEFO picker, stock reservation, credit limit checker)
- `app/Http/Resources/` — JSON response resources
- `app/Http/Requests/` — Form Request validators

---

## 3. Konvensi Penamaan

### 3.1 Tabel Database
Sudah dipakai konsisten di DDL:

| Prefix | Untuk | Contoh |
|--------|-------|--------|
| `tbm_` | Master (data referensi yang relatif statis) | `tbm_users`, `tbm_products`, `tbm_warehouses` |
| `tbr_` | Transaksi (dokumen bisnis) | `tbr_sales_orders`, `tbr_purchase_orders`, `tbr_invoices` |
| `tbh_` | History / audit / append-only | `tbh_audit_logs`, `tbh_login_attempts`, `tbh_stock_movements` |
| `tbs_` | System / state | `tbs_document_sequences`, `tbs_stock_balances` |

### 3.2 Kolom Timestamp
- **Generic lifecycle**: `created_date`, `updated_date`, `deleted_date` (dipakai di semua tabel utama)
- **Domain event**: `email_verified_at`, `expires_at`, `used_at`, `attempted_at`, `last_login_at`, `password_changed_at`, `granted_at`, `verified_at` (kolom event spesifik)

### 3.3 Eloquent Model
**Wajib extend `BaseModel`** untuk tabel yang punya `created_date`/`updated_date`:
```php
abstract class BaseModel extends Model {
    const CREATED_AT = 'created_date';
    const UPDATED_AT = 'updated_date';
}
```

User model **tidak extend BaseModel** karena harus extend `Illuminate\Foundation\Auth\User as Authenticatable`. Di-override manual:
```php
class User extends Authenticatable {
    const CREATED_AT = 'created_date';
    const UPDATED_AT = 'updated_date';
    const DELETED_AT = 'deleted_date';
}
```

Untuk tabel tanpa updated (mis. `tbm_permissions`, `tbm_user_profiles`):
```php
public $timestamps = false;
```

### 3.4 Override Password Field
Karena DDL pakai `password_hash` (bukan `password`), di model `User`:
```php
public function getAuthPassword(): string { return $this->password_hash; }
public function getAuthPasswordName(): string { return 'password_hash'; }
```

---

## 4. Auth Flow

### 4.1 Login
1. `GET /login` → render `auth.login` (Blade Metronic)
2. `POST /login` → `LoginController@login`:
   - Validasi `login` (username|email) + `password`
   - Cari `User::where('username',$x)->orWhere('email',$x)->first()`
   - Cek `$user->isLocked()` → reject + log `account_locked`
   - Cek `$user->isActive()` → reject + log `account_inactive`
   - `Hash::check($password, $user->password_hash)` → kalau salah, increment `failed_login_attempts`, set `locked_until` jika ≥5
   - Sukses: reset attempts, update `last_login_at`, log success, `Auth::login`, regenerate session
3. Semua attempt (sukses/gagal) di-insert ke `tbh_login_attempts` dengan IP + user agent + reason

### 4.2 Logout
- `POST /logout` → `Auth::logout()` + `session()->invalidate()` + `regenerateToken()` → redirect `/login`
- Tombol di dropdown header (form POST dengan CSRF)

### 4.3 Middleware Permission
- Alias `permission` terdaftar di `bootstrap/app.php`
- Pemakaian: `Route::middleware(['auth','permission:po.approve'])->...`
- Implementasi: `CheckPermission` panggil `$user->hasPermission($name)` yang mengeksekusi PG function `fn_user_has_permission(user_id, permission_name)`
- Function ini sudah handle:
  - Explicit deny override (selalu menang)
  - Explicit allow override (dengan expires_at)
  - Permission dari role

---

## 5. Layout & UI

### 5.1 Hierarchy Blade
```
layouts/auth.blade.php         layouts/app.blade.php
       ↓                              ↓
auth/login.blade.php          dashboard.blade.php
                              ├── partials/sidebar.blade.php (include)
                              └── partials/header.blade.php  (include)
```

### 5.2 Variable Global di Layout
`AppServiceProvider::boot()` register View Composer untuk `layouts.app` & `dashboard`:
```php
$view->with('currentUser', auth()->user()->load('role','profile','warehouses'));
```
Di view tinggal pakai `$currentUser->full_name`, `$currentUser->profile?->avatar_url`, dst.

### 5.3 Sidebar Menu (saat ini placeholder)
File [partials/sidebar.blade.php](../resources/views/partials/sidebar.blade.php) — pakai struktur Metronic accordion:
- Parent: `<div data-kt-menu-trigger="click" class="menu-item menu-accordion">` + `<span class="menu-arrow">`
- Child wrapper: `<div class="menu-sub menu-sub-accordion menu-active-bg">`
- Child item: pakai `<span class="menu-bullet">` (bukan icon)

Saat menambah modul baru, edit file ini → ganti `href="#"` dengan `route('xxx.index')`.

### 5.4 Page Title & Breadcrumb
Setiap view yang extend `layouts.app` bisa pakai:
```blade
@section('title', 'Daftar Produk')
@section('page_title', 'Daftar Produk')
@section('breadcrumb')
    <li class="breadcrumb-item text-muted">Master Data</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-gray-900">Produk</li>
@endsection
```

---

## 6. Setup di Mesin Baru (Replication Recipe)

```powershell
# 1. Clone repo (atau copy folder testapp/)
cd D:\path\to\testapp

# 2. Install dependencies
composer install

# 3. Copy & isi .env
copy .env.example .env       # atau pakai .env existing
# Edit DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD

# 4. Generate APP_KEY
herd php artisan key:generate

# 5. Pastikan PostgreSQL ready dan DDL sudah di-execute:
#    psql -U user -d db -f appplandoc/04_DDL_POSTGRESQL.sql
#    psql -U user -d db -f appplandoc/08_DDL_USER_AUTH.sql
#    psql -U user -d db -f appplandoc/10_SEED_USER_AUTH_SAMPLE.sql  (opsional)

# 6. Salin Metronic assets kalau belum ada
xcopy /E /I "D:\path\to\template\assets" "public\assets"

# 7. Buat password admin valid
herd php artisan tinker --execute="$u = \App\Models\User::where('username','admin')->first(); $u->password_hash = \Hash::make('Admin123!'); $u->save();"

# 8. Jalankan
herd php artisan serve
# Buka http://127.0.0.1:8000  → login admin / Admin123!
```

---

## 7. Yang Sudah Berfungsi (Phase 1 Deliverables)

✅ **Authentication**
- Login pakai username ATAU email
- Password validation pakai bcrypt (`password_hash` field)
- Lockout otomatis 30 menit setelah 5x salah password
- Cek `registration_status` (pending/active/suspended/banned)
- Logging ke `tbh_login_attempts` (sukses & gagal)
- Logout dengan session invalidate

✅ **Authorization**
- Middleware `permission:xxx` pakai PG function `fn_user_has_permission()`
- Mendukung override allow/deny per-user (dengan `expires_at`)
- Multi-warehouse access via `tbm_user_warehouses`

✅ **Dashboard**
- Welcome card dengan profil user (nama, role, avatar/inisial, dept, position, login terakhir, jumlah gudang, status 2FA)
- 4 stat card real-time dari DB:
  - Produk aktif (`tbm_products.is_active`)
  - Stock low (view `v_stock_low`)
  - Sales Order hari ini (`tbr_sales_orders.order_date`)
  - AR outstanding (`tbr_invoices.outstanding_amount`)
- Panel akses gudang user (badge color by access_level: admin=danger, write=warning, read=info)
- Tabel 8 percobaan login terakhir (dari `tbh_login_attempts`)

✅ **UI/Layout**
- Metronic 8.3 Bootstrap theme
- Sidebar accordion (klik parent menu expand child)
- Header dengan theme toggle (light/dark/system) + user dropdown
- Responsive (mobile drawer untuk sidebar)
- Breadcrumb dynamic per halaman

---

## 8. Yang BELUM Dibuat (Roadmap)

### Phase 2 — CRUD Master Data (rekomendasi berikutnya)
1. **Products** (`tbm_products`) — paling kompleks (kategori, grade, UoM, batch tracking, harga multi-tier)
2. **Suppliers** (`tbm_suppliers`)
3. **Customers** (`tbm_customers`) — credit limit
4. **Warehouses** (`tbm_warehouses`) — termasuk `pic_user_id`
5. **Categories** (`tbm_categories`) — self-referential hierarchy
6. **Product Grades, UoM, Price Tiers, Taxes, Payment Methods**
7. **Manajemen User** (CRUD `tbm_users` + assign role + assign warehouses)

### Phase 3 — Inventory
- Purchase Order (PO) → submit → approve workflow
- Goods Receipt (GRN) → auto-generate `tbm_product_batches`
- Stock Transfer antar warehouse
- Stock Opname (sensus fisik) → adjustment

### Phase 4 — Penjualan
- Sales Order (SO) → confirm → reserve stock
- Delivery Order (DO) → ship → reduce stock (FEFO picking)
- Sales Return

### Phase 5 — Invoicing
- Invoice generation dari DO
- Payment recording + alokasi M:N ke invoice
- AR Aging report

### Phase 6 — Reports & Dashboard Lanjutan
- Stock report dengan filter
- Sales report (harian/mingguan/bulanan)
- Profit report
- Export PDF/Excel

### Phase 7 — Mobile API
- `composer require laravel/sanctum`
- Routes `api/v1/*` dengan auth `auth:sanctum`
- API Resources untuk JSON response konsisten

### Phase 8 — Auth Lanjutan
- Forgot password / reset password (sudah ada DDL `tbh_password_resets`)
- Email verification (sudah ada DDL `tbh_email_verifications`)
- 2FA TOTP (sudah ada DDL `tbh_two_factor_codes`)
- Manajemen `tbm_user_sessions`

---

## 9. Known Issues / Catatan Penting

### 9.1 Encoding em-dash di SQL
File seed mengandung `—` (em-dash UTF-8). Pastikan saat edit SQL via PowerShell, baca pakai `[System.IO.File]::ReadAllText($f, [System.Text.UTF8Encoding]::new($false))` — `Get-Content` default bisa salah deteksi encoding di Windows.

### 9.2 SQL Client Gotchas
- **bcrypt hash** (`$2y$10$...`) — banyak SQL client (DBeaver, DataGrip) salah parse `$N` sebagai parameter binding. Eksekusi via `psql` atau matikan param substitution di IDE.
- **Semicolon di string literal** (mis. `'Mozilla/5.0 (iPhone; iOS)'`) — DBeaver "Execute Script" (Alt+X) split SQL pada `;` walau di dalam quote. Pakai "Execute Statement" (Ctrl+Enter) atau jalankan via psql.

### 9.3 Password Hash Placeholder
Password di seed file 04 & 10 (`$2y$10$KIX...`) adalah **placeholder bcrypt yang TIDAK valid**. Sebelum login, regenerate via tinker:
```php
$user->password_hash = \Hash::make('Admin123!');
```

### 9.4 Multiple PHP Installation
Sistem ada 2 PHP (Laragon di PATH, Herd di `C:\Users\jihad\.config\herd\bin`). Hanya **Herd PHP** yang punya extension `pdo_pgsql`. Set Herd di PATH atau pakai full path saat artisan command.

### 9.5 Avatar URL
Di seed, `avatar_url` = `/storage/avatars/admin.png` (URL dummy). Kalau file tidak ada → broken image. Solusi:
- Taruh file beneran di `public/storage/avatars/`
- ATAU set `avatar_url = NULL` → fallback ke inisial nama

### 9.6 Security Catatan
- `.env` mengandung credential PG **JANGAN commit** ke git
- PG di public IP (`103.93.162.70`) → wajib whitelist IP & SSL connection (`sslmode=require`)
- `BCRYPT_ROUNDS=10` di `.env` (default Laravel 12)

---

## 10. Resep "Cara Tambah Modul Baru"

Step-by-step untuk add modul baru (misal: Suppliers):

### A. Model
```php
// app/Models/Supplier.php
namespace App\Models;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends BaseModel {
    use SoftDeletes;
    protected $table = 'tbm_suppliers';
    const DELETED_AT = 'deleted_date';
    protected $fillable = ['code','name','contact_person','phone','email','address','city','npwp','bank_name','bank_account','payment_terms_days','is_active'];
    protected $casts = ['is_active' => 'boolean'];
}
```

### B. Form Request
```php
// app/Http/Requests/SupplierRequest.php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;

class SupplierRequest extends FormRequest {
    public function rules(): array {
        return [
            'code' => ['required','string','max:20', /* unique rule */],
            'name' => ['required','string','max:150'],
            // ...
        ];
    }
}
```

### C. Controller
```php
// app/Http/Controllers/Web/SupplierController.php
namespace App\Http\Controllers\Web;
use App\Http\Controllers\Controller;
use App\Http\Requests\SupplierRequest;
use App\Models\Supplier;

class SupplierController extends Controller {
    public function index() { return view('suppliers.index', ['suppliers' => Supplier::paginate(20)]); }
    public function create() { return view('suppliers.create'); }
    public function store(SupplierRequest $r) { Supplier::create($r->validated()); return redirect()->route('suppliers.index'); }
    public function edit(Supplier $supplier) { return view('suppliers.edit', compact('supplier')); }
    public function update(SupplierRequest $r, Supplier $supplier) { $supplier->update($r->validated()); return redirect()->route('suppliers.index'); }
    public function destroy(Supplier $supplier) { $supplier->delete(); return back(); }
}
```

### D. Routes
```php
// routes/web.php — di dalam middleware auth
Route::resource('suppliers', \App\Http\Controllers\Web\SupplierController::class)
    ->middleware('permission:suppliers.view'); // pakai middleware sesuai permission
```

### E. Views
- `resources/views/suppliers/index.blade.php` — extend `layouts.app`, pakai DataTable dari Metronic
- `resources/views/suppliers/create.blade.php`
- `resources/views/suppliers/edit.blade.php`

### F. Update Sidebar
Edit `partials/sidebar.blade.php` — ganti `href="#"` di menu Supplier dengan `href="{{ route('suppliers.index') }}"` + class `active` jika `request()->routeIs('suppliers.*')`.

---

## 11. Files Referensi

| File | Lokasi | Isi |
|------|--------|-----|
| Skema DB inti | `04_DDL_POSTGRESQL.sql` | 35 tabel master/transaksi/sistem |
| Skema DB auth | `08_DDL_USER_AUTH.sql` | 12 tabel auth + 70 permissions |
| Seed sample | `10_SEED_USER_AUTH_SAMPLE.sql` | 6 user + warehouses + sessions + activity |
| Auth design | `09_USER_AUTH_DESIGN.md` | Permission matrix, auth flow detail |
| **File ini** | `11_LARAVEL_PHASE1_AUTH_DASHBOARD.md` | Dokumentasi Phase 1 |

---

## 12. Checklist Sebelum Lanjut Phase 2

Sebelum mulai CRUD Master Data, pastikan:

- [ ] Bisa login dengan admin / Admin123! di local
- [ ] Bisa logout dan akses /dashboard ditolak (redirect ke /login)
- [ ] Sidebar accordion bisa expand/collapse
- [ ] Dashboard menampilkan data real (bukan placeholder)
- [ ] Test middleware permission jalan: bikin route dummy `/test` dengan `middleware('permission:nonexistent.permission')` → harus 403
- [ ] `tbh_login_attempts` ter-insert setiap login attempt
- [ ] Backup `.env` dan kredensial PG di tempat aman
- [ ] Pertimbangkan ganti password DB PG (`pesisir!`) — sudah ter-expose di chat history

---

**End of Phase 1 documentation.**
