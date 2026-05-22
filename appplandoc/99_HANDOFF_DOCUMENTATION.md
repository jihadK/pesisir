# 📋 Handoff Documentation — Pesisir Fresh Fish

> **Tujuan dokumen ini:** Snapshot lengkap state proyek per **22 Mei 2026** sehingga developer (atau Claude di sesi berikutnya) bisa langsung melanjutkan tanpa kehilangan konteks.

---

## 1. Konteks Bisnis Singkat

**Pesisir Fresh Fish** — bisnis distribusi ikan segar di Lamongan. Aplikasi inventory + sales/purchase + invoicing. Saat ini hanya 1 warehouse aktif (**WH-LAMONGAN**), semua data sudah dikonsolidasi ke sana (patch 34).

### Customer Tipe
- **Retail** — bayar langsung, `payment_terms_days = 0`, alur Draft → Paid
- **Restoran/tempo** — bayar belakangan, `payment_terms_days > 0` (mis. 14 hari), alur Draft → Fulfilled → Paid

---

## 2. Stack & Environment

| Komponen | Detail |
|---|---|
| Backend | Laravel 12 + PHP 8.2 |
| Database | PostgreSQL 16 di VPS `103.93.162.70:5432`, database `pesisir` |
| Local dev | Windows + **Laravel Herd** + domain `testapp.test` |
| Production | `https://pesisirfreshfish.web.id` (Ubuntu 22.04, Nginx + PHP-FPM + Let's Encrypt) |
| Frontend | Metronic 8 theme (Bootstrap 5), Select2, ApexCharts, Inputmask |
| Currency | IDR, locale `id-ID` (`.` thousand separator, `,` decimal) |

### ⚠️ Caveat Penting: Opcache di Herd

Class PHP yang baru di-edit kadang tidak ke-load karena opcache. Solusi:
1. **Restart Herd** (Herd tray → Restart)
2. Atau hit URL `http://testapp.test/__opcache-reset` (sudah disediakan, lihat `routes/web.php`)
3. Atau set di `php.ini` Herd: `opcache.validate_timestamps=1` + `opcache.revalidate_freq=0`

---

## 3. Status Per Modul

### ✅ Selesai

| Modul | Catatan |
|---|---|
| **Master Data** | Products, Categories, Grades, UoM, Price Tiers, Suppliers, Customers, Warehouses, Employees, Service Rates, Payment Methods — semua CRUD lengkap |
| **Purchase Order (Phase 6)** | PO untuk raw material (per kategori, gram-based), status: Draft → Paid → Cancelled. Tombol "Tandai Terbayar". Includes diskon per item |
| **Jasa Bersih Ikan** | Menu terpisah di Pembelian |
| **Belanja Lain-lain** (`supplies_purchases`) | Plastik, box, dll. — menu terpisah |
| **Sales / Booking Order** | Status: Draft → Paid (retail) atau Draft → Fulfilled → Paid (tempo). Includes biaya packing + biaya lain-lain + diskon. Append item ke SO Confirmed. WhatsApp share dengan signed URL public (link customer view tanpa login) |
| **Kontrak Harga Customer** | NEW (Phase 7) — harga per customer × produk, override default. Pricing 2-layer (kontrak → default). AJAX auto-fill harga di form SO |
| **Piutang (Receivables)** | NEW — list SO Fulfilled + aging buckets (overdue / 7d / 14d / 30d). Filter aging |
| **Stock Card** | Histori movement per produk |
| **Stock Adjustment (Quick)** | Modal di halaman Produk, FEFO lintas batches |
| **Dashboard** | Period selector (harian/mingguan/bulanan), 3 summary cards period + lifetime, chart trend (ApexCharts area), stock low link, unpaid orders, 3 AR widgets (Total / Due 7d / Overdue) |
| **Auth + RBAC** | Login, logout, role-based permission (admin/manager/sales/cashier/warehouse). Permission DB-driven via `fn_user_has_permission` |
| **Print Document** | Proforma/Invoice dinamis (stample LUNAS otomatis kalau Paid). WhatsApp template baru dengan conditional QR/Bank/None |

### 🔒 Hidden / Disabled (sesuai UAT)

| Modul | Status di Sidebar |
|---|---|
| Delivery Order | `@if(false)` — disabled, alur sekarang skip DO (Draft → Paid langsung) |
| Invoicing (Invoice + Payment) | `@if(false)` — disabled, Daftar Invoice pakai filter `?status=paid` di Booking Order |
| Stock menu (Stock Opening + Adjustment) | `@if(false)` — disabled. Kartu Stok dipindah ke menu Produk & Stok |

**Cara restore:** hapus `@if(false)` … `@endif` di `resources/views/partials/sidebar.blade.php`.

### 🕳️ Belum Dikerjakan / Backlog

| Fitur | Prioritas | Catatan |
|---|---|---|
| Multi-warehouse | Low | Saat ini consolidated ke WH-LAMONGAN. Tabel `tbm_warehouses` masih ada, tinggal aktifkan kalau perlu |
| Stock Transfer antar warehouse | Low | Belum dibuat, hanya relevan kalau multi-warehouse |
| Recurring order template untuk Restoran | Medium | User dijawab "tidak perlu" di awal, tapi bisa jadi enhancement |
| Min order qty | Medium | Backlog |
| Bulk pricing (qty discount) | Low | Backlog |
| Stock Opening UI | Medium | Backend ada (`StockOpeningService`), menu di-hide. Buka lagi kalau perlu input awal |
| HPP akurat per-batch (FIFO actual cost) | Medium | Saat ini pakai `default_cost_price`. Lebih akurat: ambil dari `tbh_stock_movements.cost_price` per batch sold |
| Notifikasi WA otomatis | Medium | Saat ini WA manual (klik tombol). Otomasi via API butuh integrasi WhatsApp Business |
| Email invoice ke customer | Low | Belum dibuat |
| Laporan periodik (export Excel/PDF) | Medium | Belum dibuat |
| Audit log lebih detail | Low | Saat ini hanya login attempts |

---

## 4. Arsitektur Data Penting

### Skema SO Status Flow
```
Draft ──┬─→ Fulfilled (tempo) ──→ Paid
        ├─→ Paid (langsung)
        └─→ Cancelled
```

### Pricing Resolution (2-layer)
```
Customer × Product
    ↓
┌─ Ada di tbm_customer_product_prices (aktif & dalam periode)? → PAKAI kontrak
└─ Tidak → PAKAI tbm_products.default_sell_price (retail)
```

Implementasi: [`app/Services/PriceResolver.php`](../app/Services/PriceResolver.php)
AJAX endpoint: `GET /sales-orders/resolved-price?customer_id=X&product_id=Y`

### Stock Deduction
- **FEFO** (First Expired First Out) — urut `b.expiry_date ASC NULLS LAST`
- Trigger DB `trg_apply_stock_movement` (patch 32, UPDATE-first-INSERT pattern)
- Defensive guard di `StockMovementService::createMovement` — cek balance row sebelum insert movement negatif
- Quick adjust di halaman produk: ke `QuickStockUpdateService` (terpisah dari `StockAdjustmentService`)

### Stock Balance Integrity
- Unique index: `uq_stock_balance` ON `(product_id, warehouse_id, COALESCE(batch_id, 0))`
- Constraint: `quantity >= 0`, `reserved_quantity >= 0`, `reserved <= quantity`
- Patch 35: orphan reservations cleanup (release reserved dari SO yang sudah Paid/Cancelled)

---

## 5. SQL Patches History (Phase 6 onwards)

Jalankan urut kalau setup environment baru:

| # | File | Fungsi |
|---|---|---|
| 04 | `04_DDL_POSTGRESQL.sql` | Schema awal (semua tabel) |
| 20-26 | Phase 5 + Phase 6 patches | SO, DO, Invoice, Payment, PO raw material, separate cost menus |
| **27** | `27_PATCH_PHASE6_PO_PAID_STATUS.sql` | PO status `paid` + permission `purchase_order.mark_paid` |
| **28** | `28_PATCH_PHASE6_PO_ITEM_DISCOUNT.sql` | Discount per item di PO |
| **29** | `29_PATCH_PHASE6_PO_MARK_PAID_GRANT.sql` | Grant mark_paid ke semua role yg punya purchase_order.create |
| **30** | `30_PATCH_PHASE6_SO_PAID_AND_PACKING.sql` | SO status `paid` + kolom `packing_cost` + permission `sales_order.mark_paid` |
| **31** | `31_PATCH_FIX_STOCK_BALANCE_INDEX.sql` | Recreate unique index `uq_stock_balance` + merge duplikat |
| **32** | `32_PATCH_FIX_TRIGGER_STOCK_BALANCE.sql` | Trigger `trg_apply_stock_movement` UPDATE-first-INSERT (lebih robust dari ON CONFLICT inference) |
| **33** | `33_PATCH_SO_OTHER_COST.sql` | Kolom `other_cost_amount` + `other_cost_desc` di SO |
| **34** | `34_PATCH_CONSOLIDATE_TO_WH_LAMONGAN.sql` | Konsolidasi semua data ke WH-LAMONGAN, nonaktifkan warehouse lain |
| **35** | `35_PATCH_RELEASE_ORPHAN_RESERVATIONS.sql` | Reset `reserved_quantity` orphan + re-create dari SO Confirmed/Partial |
| **36** | `36_PATCH_CUSTOMER_CONTRACT_PRICES_AND_FULFILLED.sql` | **PHASE 7** — tabel kontrak harga customer + status SO `fulfilled` + `due_date` + permissions |

---

## 6. Alur Test Per Tipe Customer

### A. Retail (Bayar Langsung)
1. Customer dengan `payment_terms_days = 0`
2. Buat SO Draft di [Booking Order Baru](http://testapp.test/sales-orders/create)
3. Harga otomatis = `default_sell_price`
4. Submit → status Draft
5. Tombol di halaman detail: **Edit / Paid (Terbayar) / Cancel**
6. Klik Paid → stock terdeduct FEFO, status Paid

### B. Restoran (Tempo)
**Setup:**
1. Edit customer: set `payment_terms_days = 14` (atau sesuai kontrak)
2. Buka [Kontrak Harga](http://testapp.test/customer-prices) → Kontrak Baru
3. Pilih customer × produk → harga kontrak (mis. Rp 27.500 untuk Kakap)
4. Save

**Buat Order:**
5. [Booking Order Baru](http://testapp.test/sales-orders/create)
6. Pilih customer Restoran → `payment_terms_days` auto-fill = 14
7. Pilih produk → harga **auto-fill dari kontrak**, badge hijau "Harga Kontrak"
8. Submit → status Draft

**Fulfill:**
9. Detail SO → tombol **"Fulfill (Kirim, Tempo 14 hari)"** muncul kuning
10. Klik → stock terdeduct, status `Fulfilled`, `due_date = order_date + 14`
11. SO muncul di [Piutang](http://testapp.test/receivables)

**Lunas:**
12. Detail SO → tombol **"Tandai Lunas"** → status Paid
13. SO hilang dari Piutang, muncul di [Daftar Invoice](http://testapp.test/sales-orders?status=paid)

---

## 7. File Penting (Quick Reference)

### Controllers
| File | Fungsi |
|---|---|
| [`SalesOrderController.php`](../app/Http/Controllers/Web/SalesOrderController.php) | SO CRUD + confirm/cancel/fulfill/markPaid/appendItem/resolvedPrice (AJAX) |
| [`CustomerPriceController.php`](../app/Http/Controllers/Web/CustomerPriceController.php) | Kontrak harga customer CRUD |
| [`ReceivableController.php`](../app/Http/Controllers/Web/ReceivableController.php) | List piutang + aging |
| [`DashboardController.php`](../app/Http/Controllers/Web/DashboardController.php) | Dashboard chart + summary + AR widgets |
| [`ProductController.php`](../app/Http/Controllers/Web/ProductController.php) | Product CRUD + quick stock update (modal) |
| [`PurchaseOrderController.php`](../app/Http/Controllers/Web/PurchaseOrderController.php) | PO CRUD + mark paid |

### Services
| File | Fungsi |
|---|---|
| [`SalesOrderService.php`](../app/Services/SalesOrderService.php) | createDraft, confirm, cancel, markAsFulfilled, markAsPaid, appendItemToConfirmed, deductStockForSO (helper) |
| [`PriceResolver.php`](../app/Services/PriceResolver.php) | 2-layer price resolve (kontrak → default) |
| [`StockMovementService.php`](../app/Services/StockMovementService.php) | createMovement (dengan defensive guard), nextDocNumber, getCurrentBalance |
| [`StockAdjustmentService.php`](../app/Services/StockAdjustmentService.php) | applyAdjustment dengan FEFO + lock |
| [`QuickStockUpdateService.php`](../app/Services/QuickStockUpdateService.php) | Khusus modal di halaman Produk, FEFO lintas batches |
| [`PurchaseOrderService.php`](../app/Services/PurchaseOrderService.php) | PO CRUD + markAsPaid |

### Models
| File | Fungsi |
|---|---|
| [`SalesOrder.php`](../app/Models/SalesOrder.php) | Status constants (DRAFT/FULFILLED/PAID/dll), helper methods (isFulfillable, isOverdue) |
| [`CustomerProductPrice.php`](../app/Models/CustomerProductPrice.php) | Kontrak harga customer |
| [`Customer.php`](../app/Models/Customer.php), [`Product.php`](../app/Models/Product.php) | Standard |

### Views
| File | Fungsi |
|---|---|
| [`sales_orders/_form.blade.php`](../resources/views/sales_orders/_form.blade.php) | Form SO + AJAX auto-fill harga |
| [`sales_orders/show.blade.php`](../resources/views/sales_orders/show.blade.php) | Detail SO + tombol Fulfill/Paid/Cancel |
| [`sales_orders/print.blade.php`](../resources/views/sales_orders/print.blade.php) | Proforma/Invoice + WA template + stample LUNAS |
| [`sales_orders/index.blade.php`](../resources/views/sales_orders/index.blade.php) | List SO (+ summary cards saat `?status=paid`) |
| [`customer_prices/`](../resources/views/customer_prices/) | CRUD kontrak |
| [`receivables/index.blade.php`](../resources/views/receivables/index.blade.php) | Piutang + aging buckets |
| [`dashboard.blade.php`](../resources/views/dashboard.blade.php) | Dashboard utama |
| [`partials/sidebar.blade.php`](../resources/views/partials/sidebar.blade.php) | Sidebar navigation |
| [`layouts/app.blade.php`](../resources/views/layouts/app.blade.php) | Layout utama + FAB "Order Baru" |

---

## 8. Conventions & Gotchas

### Date Format
- DB: ISO (`YYYY-MM-DD`)
- Display: `d M Y` (mis. `22 May 2026`)
- Carbon di model `$casts` ke `'date'` atau `'datetime'`

### Currency
- DB: `NUMERIC(14,2)` (untuk harga) atau `NUMERIC(14,3)` (untuk qty)
- Display: `number_format($val, 0, ',', '.')` → `1.234.567`
- Input form: Inputmask dengan `groupSeparator:'.', radixPoint:','`
- Server-side clean: `(float) preg_replace('/[^0-9]/', '', $val)` — strip semua selain digit

### Permission Pattern
```php
// di controller / action
if (! $request->user()?->hasPermission('xxx.yyy')) {
    return back()->with('flash', Flash::err('...', ResponseCode::FORBIDDEN));
}

// di view
@if(auth()->user()?->hasPermission('xxx.yyy'))
    <button>...</button>
@endif

// di route
Route::middleware('permission:xxx.yyy')->group(function () { ... });
```

### Flash Messages
Pakai `App\Support\Flash::ok($msg, $title)` atau `Flash::err($msg, $code, $title)`.

### Foreign Key Naming
- `tbm_*` = master tables (`tbm_products`, `tbm_customers`)
- `tbr_*` = transactional records (`tbr_sales_orders`, `tbr_purchase_orders`)
- `tbh_*` = history/log (`tbh_stock_movements`, `tbh_login_attempts`)
- `tbs_*` = stock/snapshot (`tbs_stock_balances`)

### Document Sequences
- Pakai `nextDocNumber('PREFIX')` dari `StockMovementService`
- Seeded prefix: `PO, GRN, SO, DO, INV, PAY, TRF, OPN, RTN, ADJ, SM`
- Reset period: yearly (kebanyakan), monthly (`SM`)

---

## 9. Cara Lanjutkan Development di Sesi Berikutnya

**Hand-off ke developer baru / Claude session baru:**

1. **Baca dokumen ini** (`appplandoc/99_HANDOFF_DOCUMENTATION.md`)
2. **Cek state DB** — pastikan patch terakhir yang sudah dijalankan:
   ```sql
   -- Cek kolom yang ada di SO
   SELECT column_name FROM information_schema.columns
    WHERE table_name = 'tbr_sales_orders';
   -- Harus include: packing_cost, other_cost_amount, other_cost_desc, due_date, fulfilled_at

   -- Cek status SO yang allowed
   SELECT pg_get_constraintdef(oid) FROM pg_constraint
    WHERE conname = 'tbr_sales_orders_status_check';
   -- Harus include: fulfilled, paid
   ```
3. **Sinkronkan code** — pastikan branch terbaru: `git pull`
4. **Restart Herd** untuk reset opcache
5. **Cek `MEMORY.md`** di `~/.claude/projects/...` (kalau Claude session) untuk konteks user preferences

**Pola request user yang umum:**
- "tambahkan fitur X" → cek tabel, buat patch SQL, model, service, controller, view, route, sidebar
- "error Y" → minta error message + log, debug, fix
- "hide menu Z" → wrap di `@if(false)` di sidebar, kasih komentar Indonesian untuk restore

**Pola UAT user:**
- User testing → kasih feedback per menu → kita iterasi
- User suka design card dengan gradient warna pastel (lihat Daftar Invoice / Dashboard)
- User prefer komunikasi singkat dan langsung action

---

## 10. Kontak & Notes Tambahan

- **Repo lokal:** `d:\FILE\KAMIL\PROJECT\php\testapp`
- **Repo deploy:** Github (user yang punya credential)
- **VPS production:** `103.93.162.70` (user setup sendiri)
- **Domain:** `pesisirfreshfish.web.id`
- **User email:** primus.ai.2026@gmail.com

**Untuk update production setelah perubahan local:**
1. `git commit && git push`
2. SSH ke VPS → `cd /var/www/pesisirfreshfish && git pull`
3. `composer install --no-dev --optimize-autoloader` (kalau ada package change)
4. `php artisan optimize:clear`
5. Jalankan SQL patch baru (kalau ada) di DB production
6. `sudo systemctl reload php-fpm` (atau restart sesuai setup)

---

_Dokumen ini ditulis 22 Mei 2026. Update saat ada perubahan major._
