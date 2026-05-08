# Phase 2 & 3 — Master Data CRUD

> **Status:** ✅ SELESAI (kecuali Sub-phase 3.3 Product Prices + UoM Conversions) &middot; **Tanggal:** 2026-05-08
> **Phase sebelumnya:** Lihat [11_LARAVEL_PHASE1_AUTH_DASHBOARD.md](11_LARAVEL_PHASE1_AUTH_DASHBOARD.md)
> **Next:** Sub-phase 3.3 (Pricing) → Phase 4 (Inventory)

Dokumen ini meringkas **8 modul Master Data** yang sudah ter-build dan **pola CRUD standar** yang akan dipakai ulang di semua modul berikutnya.

---

## 1. Daftar Menu yang Sudah Dibuat

### 🗂️ Struktur Sidebar Saat Ini

```
[Sidebar]
├─ Dashboard ✅
│
├─ MASTER DATA
│  ├─ Produk (accordion)
│  │  ├─ Daftar Produk          ✅ /products
│  │  ├─ Kategori                ✅ /categories  (jstree hierarchy)
│  │  ├─ Grade                   ✅ /grades
│  │  ├─ Satuan (UoM)            ✅ /uoms
│  │  └─ Tier Harga              ✅ /price-tiers
│  ├─ Mitra (accordion)
│  │  ├─ Supplier                ✅ /suppliers
│  │  └─ Customer                ✅ /customers
│  └─ Warehouse                  ✅ /warehouses
│
├─ INVENTORY (semua placeholder ⏳ Phase 4)
│  ├─ Pembelian (PO, GRN)
│  └─ Stock (Saldo, Kartu Stock, Batch, Mutasi, Opname)
│
├─ PENJUALAN (semua placeholder ⏳ Phase 5)
│  ├─ Sales (SO, DO, Retur)
│  └─ Invoicing (Invoice, Payment, AR Aging)
│
├─ LAPORAN (placeholder ⏳ Phase 6)
│
└─ SISTEM (placeholder ⏳ Phase 7-8)
   ├─ Manajemen User
   └─ Audit Log
```

### 📋 Tabel Modul Lengkap

| # | Menu | Path | Tabel DB | Highlight Fitur |
|---|------|------|----------|-----------------|
| 1 | **Warehouse** | `/warehouses` | `tbm_warehouses` | Toggle aktif (cek stock balance), tipe enum, suhu range |
| 2 | **Supplier** | `/suppliers` | `tbm_suppliers` | Soft delete + restore, filter kota/status, info bank+TOP |
| 3 | **Customer** | `/customers` | `tbm_customers` | 5 tipe (individu/corporate/dst), credit limit dengan progress bar warna, AR aging chart, tier auto-suggest |
| 4 | **UoM** | `/uoms` | `tbm_units_of_measure` | CRUD simpel + description |
| 5 | **Product Grade** | `/grades` | `tbm_product_grades` | Color picker hex + auto contrast text + live preview badge |
| 6 | **Price Tier** | `/price-tiers` | `tbm_price_tiers` | Customer count per tier, business rule canDelete |
| 7 | **Category** | `/categories` | `tbm_categories` | **jstree hierarchy** + cycle prevention + slug auto + cascade rules |
| 8 | **Product** | `/products` | `tbm_products` | Image upload, SKU/barcode generator, 5-tab show page (stock/batch/kartu/pricing/history) |

**Total:** 8 modul, ~50 file controller/model/service/request, ~30 view Blade.

---

## 2. Pola CRUD Standar (yang konsisten di semua 8 modul)

### 2.1 File Structure per Modul

```
app/
├── Models/
│   └── XxxModel.php                 ← extend BaseModel + scopes + helpers
├── Services/
│   └── XxxService.php               ← business logic (canDelete, getStats, ...)
├── Http/
│   ├── Controllers/Web/
│   │   └── XxxController.php        ← dual-mode (web flash + JSON)
│   ├── Requests/Xxx/
│   │   ├── StoreXxxRequest.php
│   │   └── UpdateXxxRequest.php
│   └── Concerns/
│       └── ApiResponse.php          ← (shared trait — sudah ada)
└── Support/
    ├── ResponseCode.php             ← (shared — sudah ada)
    └── Flash.php                    ← (shared — sudah ada)

resources/views/xxx/
├── index.blade.php                  ← DataTable + filter + per-column search
├── _form.blade.php                  ← partial dipakai create + edit
├── create.blade.php                 ← extend layouts.app, include _form
├── edit.blade.php                   ← extend layouts.app, include _form
└── show.blade.php                   ← (kalau ada)

routes/web.php                       ← group dengan permission middleware
resources/views/partials/sidebar.blade.php  ← link active state
```

### 2.2 Konvensi yang Wajib Diikuti

**Model:**
- Extend `BaseModel` (override `created_at`/`updated_at` → `created_date`/`updated_date`)
- Tabel pakai prefix: `tbm_` (master) | `tbr_` (transaksi) | `tbh_` (history) | `tbs_` (state/system)
- Kalau soft delete → `use SoftDeletes; const DELETED_AT = 'deleted_date';`
- Kalau tabel tanpa updated_date → `public $timestamps = false;`
- Casts untuk decimal/datetime/json

**Controller:**
- Letak: `app/Http/Controllers/Web/`
- Pakai trait `ApiResponse` untuk method `ok()`/`fail*()`
- Pakai `Flash::ok()`/`Flash::err()` untuk web redirect message
- **Dual-mode:** `if ($request->expectsJson()) { return $this->ok(...); }` else redirect+flash
- Permission check pakai `$request->user()?->hasPermission('xxx.action')` di destroy/restore

**Routes:**
- Static routes (`/create`, `/suggest-sku`) **WAJIB di-declare sebelum** dynamic (`/{model}`)
- Pakai `whereNumber('xxx')` constraint pada parameter route binding numeric
- Group dengan permission middleware:
```php
Route::middleware('permission:xxx.create')->group(function () { ... });
```

**Form Request:**
- `authorize()` cek permission via `$this->user()?->hasPermission('xxx.action')`
- `prepareForValidation()` untuk normalize input (uppercase code, clean Rupiah, boolean cast)
- Field readonly setelah create (mis. `code`, `sku`) — TIDAK divalidasi di Update Request
- Custom error message dalam Bahasa Indonesia

**View:**
- DataTable client-side dengan **per-column search di filter-row** + `e.stopPropagation()` agar tidak trigger sort
- `@empty` block JANGAN dipakai dalam tbody DataTable — pakai `language.emptyTable` opsi
- Form pakai `data-control="select2"` untuk dropdown
- Konfirmasi delete pakai `data-sweet-confirm` (bukan native `confirm()`)
- Flash message ter-render otomatis via `<x-sweet-flash />` di layout

---

## 3. Cross-Cutting Features (sudah jadi, dipakai semua modul)

### 3.1 ResponseCode + Flash Helper (Phase 1.5)

| File | Fungsi |
|------|--------|
| [app/Support/ResponseCode.php](../app/Support/ResponseCode.php) | Constants: `00`=success, `01`=validation, `02`=not_found, `03`=unauthenticated, `04`=forbidden, `05`=business_rule, `06`=duplicate, `07`=dependency, `08`=rate_limit, `99`=server_error |
| [app/Support/Flash.php](../app/Support/Flash.php) | `Flash::ok($msg, $title)` → SweetAlert2 toast hijau; `Flash::err($msg, $code)` → modal merah dengan kode error |
| [app/Http/Concerns/ApiResponse.php](../app/Http/Concerns/ApiResponse.php) | Trait dengan `ok()`, `fail()`, `failValidation()`, `failNotFound()`, `failForbidden()`, `failBusinessRule()` |
| [bootstrap/app.php](../bootstrap/app.php) | Global JSON exception handler — auto-convert ValidationException/AuthException/ModelNotFound ke `{resCode, resMsg}` |

**Format JSON konsisten:**
```json
{ "resCode": "00", "resMsg": "Berhasil", "data": {...} }
```

### 3.2 SweetAlert2 Components

| Component | Fungsi |
|-----------|--------|
| `<x-sweet-flash />` | Auto-render flash dari session (success → toast 3s, error → modal dengan kode) |
| `<x-sweet-helpers />` | JS `sweetConfirm(opts)` + auto-bind `data-sweet-confirm` di form/button |
| `data-sweet-*` attributes | `data-sweet-title`, `data-sweet-text`, `data-sweet-icon`, `data-sweet-confirm-text`, `data-sweet-confirm-class`, `data-sweet-form` |

### 3.3 Inputmask (Metronic plugin)

Pakai untuk:
- **Rupiah currency:** alias `numeric`, separator titik, decimal=koma, digits=0
- **NPWP multi-format:** `99.999.999.9-999.999` (15 digit) ATAU `9999999999999999` (16 digit)
- Server-side cleaner di `prepareForValidation()`: regex strip non-digit. **PENTING:** jangan pakai `is_numeric()` short-circuit karena `is_numeric("85.000")` returns true (PHP parse "85.000" sebagai float 85.0)

### 3.4 DataTable Pattern (Metronic plugins/custom/datatables)

```js
var dt = $('#kt_table').DataTable({
    info: true, order: [[0,'asc']], pageLength: 10,
    lengthMenu: [[10,25,50,100,-1], [10,25,50,100,'Semua']],
    language: {
        search: '', lengthMenu: 'Tampil _MENU_',
        info: 'Menampilkan _START_ - _END_ dari _TOTAL_',
        paginate: {previous: 'Sebelumnya', next: 'Selanjutnya'},
        zeroRecords: 'Tidak ada data yang cocok',
        emptyTable: 'Belum ada data...'  // ← gunakan ini, JANGAN @empty di tbody
    },
    columnDefs: [
        { orderable: false, targets: [<aksi-col>] },
        { searchable: false, targets: [...] }
    ],
    initComplete: function () { $('.dataTables_filter').hide(); }
});

// Per-column search (di filter-row)
$('.filter-row input').on('click', e => e.stopPropagation())  // ← cegah trigger sort
    .on('keyup change clear', function () {
        var c = $(this).data('col');
        if (dt.column(c).search() !== this.value) dt.column(c).search(this.value).draw();
    });
$('.filter-row th').off('click.DT').on('click', e => e.stopPropagation());
```

### 3.5 Layout Components

| Komponen | File |
|----------|------|
| Layout master | [resources/views/layouts/app.blade.php](../resources/views/layouts/app.blade.php) |
| Sidebar dengan accordion menu | [resources/views/partials/sidebar.blade.php](../resources/views/partials/sidebar.blade.php) |
| Header + user dropdown | [resources/views/partials/header.blade.php](../resources/views/partials/header.blade.php) |
| `$currentUser` auto-shared (role+profile+warehouses) via `AppServiceProvider` View Composer |

---

## 4. Permission Seed yang Sudah Ada

Total **~96 permission** yang sudah di-seed ke role `admin`:

| Module | Permissions |
|--------|-------------|
| `users.*` | view/create/update/delete/manage_roles |
| `roles.*` | view/create/update/delete/manage_permissions |
| `warehouses.*` | view/create/update/delete |
| `suppliers.*` | view/create/update/delete |
| `customers.*` | view/create/update/delete |
| `uom.*` | view/create/update/delete |
| `grades.*` | view/create/update/delete |
| `price_tiers.*` | view/create/update/delete |
| `categories.*` | view/create/update/delete |
| `products.*` | view/create/update/delete |
| `inventory.*` | view_stock, stock_card |
| `po.*` | view/create/update/approve/cancel |
| `grn.*` | view/create/update |
| `transfer.*` | view/create/approve |
| `opname.*` | view/create/approve |
| `so.*` | view/create/update/approve/cancel |
| `do.*` | view/create/update/ship |
| `return.*` | view/create/approve |
| `invoice.*` | view/create/update/cancel/void |
| `payment.*` | view/create/update/approve |
| `report.*` | stock/sales/ar/profit/export |
| `settings.*` | view/update |
| `audit.*` | view |

**Untuk modul baru:** seed permission via tinker:
```php
\DB::table("tbm_permissions")->insertOrIgnore([
    ["name"=>"xxx.view","display_name"=>"View Xxx","module"=>"master",...,"created_date"=>now()],
    // ... 4 permission per modul (view/create/update/delete)
]);
$adminId = \DB::table("tbm_roles")->where("name","admin")->value("id");
foreach (\DB::table("tbm_permissions")->where("name","LIKE","xxx.%")->pluck("id") as $pid)
    \DB::table("tbm_role_permissions")->insertOrIgnore(["role_id"=>$adminId,"permission_id"=>$pid,"granted_at"=>now()]);
```

---

## 5. Patches DDL yang Sudah Diterapkan

### Patch 1: [12_PATCH_PHASE3_MASTERS.sql](12_PATCH_PHASE3_MASTERS.sql)

```sql
ALTER TABLE tbm_units_of_measure ADD COLUMN IF NOT EXISTS description VARCHAR(255);
ALTER TABLE tbm_product_grades   ADD COLUMN IF NOT EXISTS color VARCHAR(20);

UPDATE tbm_product_grades SET color = '#FFD700' WHERE code = 'A' AND color IS NULL; -- Gold
UPDATE tbm_product_grades SET color = '#C0C0C0' WHERE code = 'B' AND color IS NULL; -- Silver
UPDATE tbm_product_grades SET color = '#CD7F32' WHERE code = 'C' AND color IS NULL; -- Bronze
```

**Saat replicate fresh database:** jalankan ini setelah `04_DDL_POSTGRESQL.sql`.

---

## 6. Bug Notable yang Sudah Difix

### 6.1 `is_numeric()` short-circuit di Rupiah parser
**Root cause:** PHP menafsirkan `"85.000"` sebagai float `85.0` (titik = decimal separator).

**Fix:** hapus shortcut `if (is_numeric($val))`, selalu strip non-digit dulu via regex.

**Berlaku di:** `StoreProductRequest`, `UpdateProductRequest`, `StoreCustomerRequest`, `UpdateCustomerRequest`.

### 6.2 DataTable error "unknown parameter for row 0, column 1"
**Root cause:** `@empty` block render `<tr><td colspan="N">...</td></tr>` saat tbody kosong → DataTable bingung.

**Fix:** hapus `@empty`, pakai opsi `language.emptyTable`.

### 6.3 Per-column search trigger sort kolom
**Root cause:** click event di `<input>` di header bubble ke `<th>` → DataTable trigger sort.

**Fix:** `e.stopPropagation()` + `$('.filter-row th').off('click.DT')`.

### 6.4 Static routes vs dynamic routes ordering
**Root cause:** Laravel match `/products/create` sebagai `/{product}` dengan id="create" → bigint cast error.

**Fix:** declare static routes (create, suggest-sku, dst) **sebelum** dynamic `{model}`. Plus pakai `whereNumber()`.

### 6.5 Cycle prevention di Categories
**Root cause:** edit kategori bisa set parent ke descendant-nya = infinite loop.

**Fix:** `CategoryService::wouldCreateCycle()` walk ancestors dari new parent. Validation `withValidator()` after-callback.

### 6.6 Self-ref dropdown saat edit
**Root cause:** dropdown parent menampilkan kategori sendiri + anak-anaknya → user bisa pilih = cycle.

**Fix:** `flatTreeForDropdown($excludeId)` exclude self + descendants saat build dropdown.

---

## 7. Recipe — Cara Tambah Modul Baru (Sangat Cepat dengan Pattern Ini)

Estimasi: **30-60 menit** untuk modul simpel mengikuti pattern Warehouses/Suppliers/Customers.

### Step-by-step (mis. modul "Brands"):

**1. Seed permission via tinker:**
```php
\DB::table("tbm_permissions")->insertOrIgnore([...4 permission brands.*]);
// + assign ke admin role
```

**2. Model:**
```php
class Brand extends BaseModel {
    use SoftDeletes;
    protected $table = 'tbm_brands';
    const DELETED_AT = 'deleted_date';
    protected $fillable = [...];
    protected $casts = [...];
    public function scopeSearch(...) { ... }
}
```

**3. Service:**
```php
class BrandService {
    public function canDelete(Brand $b): array { ... }
    public function delete(Brand $b): array { ... }
}
```

**4. 2 Form Requests:** `StoreBrandRequest`, `UpdateBrandRequest`

**5. Controller** dengan dual-mode pattern (copy dari `SupplierController`)

**6. 5 Blade views:** `index`, `_form`, `create`, `edit`, `show` (opsional)

**7. Routes:**
```php
Route::prefix('brands')->name('brands.')->group(function () {
    // STATIC routes dulu
    Route::middleware('permission:brands.create')->group(function () {
        Route::get('/create', [BrandController::class, 'create'])->name('create');
        Route::post('/',      [BrandController::class, 'store'])->name('store');
    });
    Route::middleware('permission:brands.view')->group(function () {
        Route::get('/',           [BrandController::class, 'index'])->name('index');
        Route::get('/{brand}',    [BrandController::class, 'show'])->whereNumber('brand')->name('show');
    });
    // ... update + delete groups
});
```

**8. Update sidebar:** ganti `href="#"` dengan `route('brands.index')` + active class.

**Selesai!** Modul jalan lengkap dengan: SweetAlert2 notif, JSON dual-mode, permission middleware, DataTable filter+search, business rule check.

---

## 8. Roadmap Sisanya

### Sub-phase 3.3 — Pricing Per Tier + UoM Conversion (NEXT)
- **Product Prices** (`tbm_product_prices`) — 1 produk × 4 tier × period efektif
- **UoM Conversions** (`tbm_product_uom_conversions`) — 1 KG = 4 EKOR per produk

### Phase 4 — Inventory
- PO (Purchase Order) — workflow draft → submitted → approve → received
- GRN (Goods Receipt) — auto-generate batch baru saat terima barang
- Stock Transfer antar warehouse
- Stock Opname — sensus fisik + adjustment otomatis

### Phase 5 — Penjualan
- SO (Sales Order) — confirm → reserve stock + cek credit limit
- DO (Delivery Order) — ship dengan FEFO picking dari batch
- Sales Return

### Phase 6 — Invoicing
- Invoice generation dari DO
- Payment recording dengan alokasi M:N ke invoice
- AR Aging report

### Phase 7 — Reports & Dashboard real data

### Phase 8 — Mobile API (Sanctum) + Auth lanjutan (forgot pw, 2FA, email verify)

---

## 9. Yang Belum Dibuat Tapi Sudah Disiapkan Fondasinya

✅ **Sudah ready (tinggal pakai):**
- ResponseCode + Flash + ApiResponse trait
- SweetAlert2 components
- DataTable pattern
- Permission middleware + 96 permission seed
- View Composer `$currentUser`
- Image upload helper di ProductService

⏳ **Belum tapi perlu nanti:**
- Audit trail Observer (log create/update/delete ke `tbh_audit_logs`)
- Export Excel/PDF (DataTables Buttons extension sudah ada di Metronic)
- Bulk import CSV (untuk Products masal)
- Forgot password flow
- 2FA flow
- Email verification flow
- Mobile API routes + Sanctum

---

## 10. Files Reference

| File | Lokasi | Isi |
|------|--------|-----|
| 11_LARAVEL_PHASE1_AUTH_DASHBOARD.md | appplandoc/ | Phase 1 (auth + dashboard + layout) |
| 12_PATCH_PHASE3_MASTERS.sql | appplandoc/ | ALTER UoM.description + Grades.color |
| **13_LARAVEL_PHASE2_3_MASTER_DATA.md** | appplandoc/ | **File ini** |

---

## 11. Checklist Sebelum Lanjut Phase 4 (Inventory)

- [x] 8 modul Master Data CRUD lengkap
- [x] Image upload Products jalan
- [x] Show page Products 5 tab
- [ ] (Opsional) Sub-phase 3.3 Pricing + UoM Conversion
- [ ] Manual test setiap modul di browser
- [ ] Pertimbangkan Audit Trail Observer sebelum transaksi mulai (supaya semua transaksi auto-log ke `tbh_audit_logs`)

---

**End of Phase 2 & 3 documentation.**
