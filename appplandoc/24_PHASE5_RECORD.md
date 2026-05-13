# Phase 5 — Sales Flow Complete (SO → DO → Invoice → Payment)

**Status:** ✅ Selesai
**Tanggal:** 2026-05-11
**Cakupan:** Phase 5a + 5b + 5c + UI/UX improvements

---

## Tujuan Phase 5

Membangun flow penjualan end-to-end:

```
Customer order (WA/datang)
        ↓
[1] Sales Order (SO)          ← Phase 5a
        ↓
   Cetak Proforma → WA customer
        ↓
[2] Delivery Order (DO)       ← Phase 5b
        ↓
   Stock auto-kurang
        ↓
[3] Invoice                   ← Phase 5c
        ↓
[4] Payment                   ← Phase 5c
        ↓
   Invoice status: Lunas
```

---

## Phase 5a — Sales Order + Payment Method

### Yang dibangun

**SQL Patches:**
- [20_PATCH_PHASE5A_SALES_ORDER.sql](20_PATCH_PHASE5A_SALES_ORDER.sql) — permissions, doc sequence SO, extend `tbm_payment_methods` (bank_name, account_holder, qris_image_url, display_order, description), seed 5 method
- [21_PATCH_PHASE5A_SO_PAYMENT_METHOD.sql](21_PATCH_PHASE5A_SO_PAYMENT_METHOD.sql) — kolom `payment_method_id` di `tbr_sales_orders`

**Master Payment Method:**
- CRUD lengkap di `/payment-methods`
- Field: code, name, type (cash/transfer/giro/cheque/ewallet/card), bank_name, account_no, account_holder, qris_image_url, display_order, description
- Type-aware form: tipe `transfer` → bank fields, tipe `ewallet` → upload QRIS

**Sales Order:**
- CRUD lengkap di `/sales-orders` dengan status: `draft → confirmed → partial → delivered → invoiced/cancelled`
- Multi-item form dengan auto-calc: subtotal, diskon, ongkir, total
- Auto-fill harga & payment terms dari master customer
- Saat **Confirm**: stock auto-`reserve` di `tbs_stock_balances.reserved_quantity` (FEFO algorithm)
- Saat **Cancel** (status confirmed): reserved stock kembali

**Cetak Proforma / WhatsApp Share:**
- PDF-printable proforma dengan logo Pesisir Fresh Fish, info pembayaran (semua bank transfer aktif + pilihan customer highlighted hijau)
- Tombol **"Kirim ke WhatsApp Customer"** → auto-format nomor HP customer + pre-fill teks ringkasan SO
- Tombol **"Download Gambar"** (PNG via html2canvas) — cocok untuk dishare via WA chat (vs PDF yang perlu Adobe Reader)

**File baru:**
- Models: `PaymentMethod`, `SalesOrder`, `SalesOrderItem`
- Service: `SalesOrderService` (createDraft, updateDraft, confirm, cancel)
- Controller: `PaymentMethodController`, `SalesOrderController` + AJAX endpoints (suggest-sku, suggest payment terms, available-stock)
- FormRequests: Store/Update untuk PaymentMethod & SalesOrder
- Views: `payment_methods/{index,create,edit,_form}`, `sales_orders/{index,create,edit,show,print,_form}`

---

## Phase 5b — Delivery Order

### Yang dibangun

**SQL Patch:**
- [22_PATCH_PHASE5B_DELIVERY_ORDER.sql](22_PATCH_PHASE5B_DELIVERY_ORDER.sql) — permissions `delivery_order.*` + doc sequence `DO/`

**Delivery Order:**
- CRUD di `/delivery-orders`, status: `draft → shipped → delivered → returned/cancelled`
- Create from SO confirmed/partial — auto-load items dengan outstanding qty
- AJAX endpoint `/delivery-orders/so-items` untuk fetch batch FEFO + outstanding qty
- Saat **Ship**:
  - Release reserved qty
  - Insert `StockMovement out_sale` (qty negatif) → trigger DB auto-kurangi stock
  - Update `tbr_sales_order_items.delivered_quantity`
  - Auto-update SO status: `partial` / `delivered`
- Surat Jalan printable dengan kotak tanda tangan 3 pihak (Driver, Bagian Gudang, Penerima)

**File baru:**
- Models: `DeliveryOrder`, `DeliveryOrderItem`
- Service: `DeliveryOrderService` (createFromSO, ship, cancel, releaseReserved)
- Controller: `DeliveryOrderController`
- FormRequest: `StoreDeliveryOrderRequest`
- Views: `delivery_orders/{index,create,show,print}`

---

## Phase 5c — Invoice + Payment

### Yang dibangun

**SQL Patch:**
- [23_PATCH_PHASE5C_INVOICE_PAYMENT.sql](23_PATCH_PHASE5C_INVOICE_PAYMENT.sql) — permissions `invoice.*` + `payment.*` + doc sequences `INV/` & `PAY/`

**Invoice:**
- Auto-generate dari DO shipped/delivered (1 DO = 1 Invoice)
- Status: `draft → issued → partial → paid → overdue → cancelled/void`
- Auto-recalc paid_amount & status saat payment masuk/cancel
- Auto-detect overdue: kalau `due_date < today && belum lunas`
- Print Invoice dengan stamp visual:
  - **✓ LUNAS** (hijau) kalau paid
  - **⚠ OVERDUE** (merah) kalau lewat due_date
- Riwayat pembayaran tampil di footer

**Payment:**
- Multi-allocation: 1 payment bisa dialokasikan ke beberapa invoice (DP + pelunasan bareng)
- Method: cash/transfer/QRIS (ambil dari master Payment Method)
- Status: `pending → cleared → bounced/cancelled`
- **Auto-Alokasi**: tombol di form Payment → alokasi ke invoice terlama dulu (FIFO by due_date)
- **Quick-Pay**: 1 klik di Invoice show → modal kecil → auto-create Payment full + alokasi penuh + invoice langsung Lunas
- Cancel payment → rollback alokasi → invoice status balik ke partial/issued

**File baru:**
- Models: `Invoice`, `InvoiceItem`, `Payment`
- Services: `InvoiceService` (createFromDO, cancel, recalcPaidStatus), `PaymentService` (record, cancel, outstandingInvoicesForCustomer)
- Controllers: `InvoiceController`, `PaymentController` + AJAX endpoint outstanding-invoices
- FormRequest: `StorePaymentRequest`
- Views: `invoices/{index,show,print}`, `payments/{index,create,show}`

---

## UI/UX Improvements (selama Phase 5)

### Format & Tampilan
- **Format angka pintar di semua tampilan**: kalau bilangan bulat → tanpa desimal (`2`), kalau pecahan → 3 desimal (`2,500`). Pemisah ribuan = titik, desimal = koma.
- **Tanda minus pakai `−`** (en-dash) untuk visibilitas, bukan hyphen `-`
- **Hapus "Rp" di kolom-kolom yang header-nya sudah jelas** (di Daftar Produk, SO show, Stock Opening list) — clutter berkurang
- **Pack info badges** di kolom Produk:
  - Badge biru `[4–5 potong]` (jumlah isi)
  - Badge kuning `[200–215 g]` (berat)
  - Tampil di semua list (Daftar Produk, SO show items, Stock Opening list)

### Estimasi Total di SO Show
Footer table SO menampilkan agregat:
```
ℹ Estimasi total: 8–10 potong · 400–430 g (0,4–0,43 kg)
```
Otomatis hitung `Σ(qty × pack_content)` & `Σ(qty × pack_weight)`. Berguna saat customer tanya "totalnya berapa kg?".

### Mobile-Responsive Tables
Form multi-line tables (Stock Opening create, Sales Order create, SO show) di mobile:
- `min-width: 760-900px` di table
- `min-width` per kolom (Qty 110px, Cost 140px, dll) → input tetap cukup lebar
- Hint message mobile-only: "Tabel bisa di-geser ke samping..."

### Form Produk Streamlined
- **Nama Produk auto-fill** dari nama sub-kategori (bisa di-edit manual untuk varian)
- **Hidden fields**: Nama Ilmiah, Asal Tangkap, Suhu Penyimpanan (default -25/-18 °C frozen storage)
- **Stock Min/Max integer-only** (`step="1"`, validasi backend `integer`)

### WhatsApp Share & Image Download
Di halaman cetak Proforma SO:
- **💬 Kirim ke WhatsApp Customer** — buka chat dengan customer pre-fill teks ringkasan SO
- **🖼️ Download Gambar** — html2canvas convert proforma jadi PNG 2x scale, auto-download
- **🖨️ Print / Save as PDF** — browser print dialog

### Sidebar Cleanup
Menu placeholder (yang belum dibangun) di-hide:
- Konfigurasi: Pajak (hide)
- Pembelian: PO + GRN (entire section hide)
- Stock: Mutasi Gudang, Stock Opname (hide)
- Sales: Retur Penjualan (hide)
- Invoicing: AR Aging (hide)
- Laporan (entire section hide)
- Sistem (entire section hide)

Sidebar sekarang hanya menampilkan **fitur yang berfungsi**. Saat phase baru selesai, menu-nya tinggal di-restore.

---

## Quick-Pay Feature (highlight)

**Masalah:** Workflow standar (Catat Pembayaran) butuh 8+ klik — pindah ke menu Payment, pilih customer, pilih invoice, alokasikan, isi method, dll.

**Solusi:** Tombol **"✓ Tandai Lunas (Quick Pay)"** di Invoice Show:
1. Klik tombol (1 klik)
2. Modal kecil muncul: tanggal bayar (default today), metode (auto-pilih dari SO.payment_method_id), ref no (opsional)
3. Klik "Tandai Lunas Sekarang"

**Hasil otomatis:**
- Payment record dibuat dengan amount = outstanding, status `cleared`
- Alokasi penuh ke invoice ini
- Invoice status → **Lunas**
- Stamp ✓ LUNAS muncul di Print
- Audit trail tetap utuh (created_by, timestamp, method)

3 klik vs 8 klik. Untuk pembayaran parsial / multi-invoice, masih tersedia tombol "Catat Pembayaran (Parsial / Lain)" yang membuka full form.

---

## Schema Database — Tabel yang Dipakai

Semua sudah ada di DDL awal ([04_DDL_POSTGRESQL.sql](04_DDL_POSTGRESQL.sql)), tinggal pakai:

| Tabel | Fungsi |
|---|---|
| `tbm_payment_methods` | Master metode pembayaran (extended di patch 20) |
| `tbr_sales_orders` | Header SO (kolom `payment_method_id` tambah di patch 21) |
| `tbr_sales_order_items` | Line items SO |
| `tbr_delivery_orders` | Header DO |
| `tbr_delivery_order_items` | Line items DO |
| `tbr_invoices` | Header Invoice (kolom `outstanding_amount` generated) |
| `tbr_invoice_items` | Line items Invoice |
| `tbr_payments` | Header Payment |
| `tbr_invoice_payments` | M:N alokasi Payment ↔ Invoice |

---

## Permissions Baru (22 total di Phase 5)

| Module | Permissions |
|---|---|
| `payment_method.*` | view, create, update, delete |
| `sales_order.*` | view, create, update, confirm, cancel, print |
| `delivery_order.*` | view, create, ship, cancel, print |
| `invoice.*` | view, create, cancel, print |
| `payment.*` | view, create, cancel |

**Role mapping:**
- **admin/manager**: full akses semuanya
- **sales**: SO + Delivery view + Invoice view + Payment view (untuk follow-up tagihan)
- **cashier**: Invoice view/create/print + Payment view/create (fungsi utama)
- **warehouse**: DO view + ship + print (mereka yang fisik kirim)

---

## Workflow End-to-End (Test Scenario)

1. **Buat SO** di menu Sales Order → New
   - Pilih customer, warehouse, tanggal
   - Tambah item produk (info pack & stock available tampil)
   - Pilih metode pembayaran
   - Simpan → status **Draft**

2. **Confirm SO**
   - Klik **Confirm** di halaman show
   - Stock auto-reserved (cek Kartu Stok → kolom "Tersedia" turun)

3. **Cetak Proforma**
   - Klik **Cetak / Proforma**
   - Klik **Download Gambar** → PNG ke-save
   - Klik **Kirim ke WhatsApp Customer** → WA terbuka pre-fill teks
   - Attach PNG → kirim

4. **Buat DO** (saat barang siap kirim)
   - Dari halaman SO show → klik **"Buat Delivery Order"**
   - Form pre-fill SO, items dengan outstanding qty + dropdown batch FEFO
   - Isi driver, kendaraan
   - Save → status **Draft**

5. **Ship DO**
   - Klik **Ship (Konfirmasi Kirim)** → modal → input "Diterima oleh" → Ship
   - Stock berkurang permanen
   - SO status → **Delivered** (atau **Partial** kalau sebagian)

6. **Generate Invoice**
   - Dari halaman DO show → klik **"Generate Invoice"**
   - Invoice auto-created dengan items dari DO, status **Issued**

7. **Catat Pembayaran**
   - Pilih A (cepat): klik **"✓ Tandai Lunas (Quick Pay)"** → modal → submit → Lunas
   - Pilih B (parsial): klik **"Catat Pembayaran (Parsial / Lain)"** → full form

8. **Cetak Invoice** (kalau perlu)
   - Stamp **✓ LUNAS** muncul kalau status paid

---

## File yang Di-deploy

### SQL Patches (urutan)
```
20_PATCH_PHASE5A_SALES_ORDER.sql
21_PATCH_PHASE5A_SO_PAYMENT_METHOD.sql
22_PATCH_PHASE5B_DELIVERY_ORDER.sql
23_PATCH_PHASE5C_INVOICE_PAYMENT.sql
```

### Code Files (summary)
- **Models**: PaymentMethod, SalesOrder, SalesOrderItem, DeliveryOrder, DeliveryOrderItem, Invoice, InvoiceItem, Payment
- **Services**: SalesOrderService, DeliveryOrderService, InvoiceService, PaymentService
- **Controllers**: PaymentMethodController, SalesOrderController, DeliveryOrderController, InvoiceController, PaymentController
- **FormRequests**: 6 file
- **Views**: 22 blade file (index/create/edit/show/print/_form untuk 5 modul)
- **Routes**: Tambahan di `routes/web.php`
- **Sidebar**: Update di `partials/sidebar.blade.php`

---

## Roadmap Phase Selanjutnya

| Phase | Fokus |
|---|---|
| **Phase 6** | Purchase Order + GRN (pembelian ke supplier) |
| Phase 7 | Stock Transfer (mutasi antar warehouse) |
| Phase 8 | Sales Return + Stock Opname |
| Phase 9 | Reports — AR Aging, Sales Report, Stock Report, Profit Report |
| Phase 10 | Manajemen User & Role UI + Audit Log viewer |

---

## Issue yang Muncul & Solusinya

### Issue 1: Eager load missing kolom pack
**Gejala:** Badge pack info tidak muncul di SO show item meski template sudah ada
**Penyebab:** `items.product:id,sku,name` di controller cuma load 3 kolom, kolom pack jadi NULL
**Fix:** Tambah kolom pack ke eager load: `items.product:id,sku,name,pack_content_type,pack_content_min,pack_content_max,pack_weight_min_g,pack_weight_max_g`

### Issue 2: Mobile tabel terlalu sempit
**Gejala:** Di HP, input Qty/Cost/Harga ke-squeeze sampai tidak bisa diketik
**Fix:** `min-width: 760-900px` di table + `min-width` per kolom → di mobile container scroll horizontal

### Issue 3: SO submit error `payment_method_id not exist`
**Gejala:** "SQLSTATE[42703]: Undefined column"
**Penyebab:** Patch 21 belum dijalankan di DB server
**Fix:** Run patch 21 manual via psql

### Issue 4: Format `2.000` ambigu (terbaca "dua ribu")
**Gejala:** Stock qty `2.000` (3 desimal) terbaca sebagai 2.000 di Indonesia (titik = pemisah ribuan)
**Fix:** Helper format pintar di semua view — bilangan bulat → tanpa desimal

---

## Catatan Production-Readiness

Sebelum live ke customer real:

- [ ] **Ganti password seed users** (`admin/Password123!`, dll)
- [ ] **Backup database harian** terjadwal
- [ ] **Logrotate** untuk Laravel & Nginx log
- [ ] **Setup queue worker** kalau ada job async (saat ini pakai `sync` driver)
- [ ] **Monitor uptime** (UptimeRobot, Better Uptime)
- [ ] **Test scenario end-to-end** dengan data real
- [ ] **Backup file storage** (logo, QRIS, gambar produk)
- [ ] **Pricing review** — pastikan rumus harga sudah benar untuk semua produk
- [ ] **Train staff** pakai Manual Book di [23_MANUAL_BOOK_PENGGUNA.md](23_MANUAL_BOOK_PENGGUNA.md)

---

## Kesimpulan

Phase 5 menyelesaikan **siklus penjualan lengkap** dari order sampai lunas. User sekarang bisa:
- Terima order via WA → cetak proforma → kirim via WA (image/PDF)
- Track delivery dengan surat jalan resmi
- Generate invoice otomatis dari DO
- Catat pembayaran dengan multi-alokasi atau quick-pay 1-klik
- Lihat outstanding piutang per customer

Phase 5 sudah deployable ke production, tinggal jalankan 4 SQL patches + deploy code seperti panduan di [22_UPDATE_DEPLOYMENT_GUIDE.md](22_UPDATE_DEPLOYMENT_GUIDE.md).
