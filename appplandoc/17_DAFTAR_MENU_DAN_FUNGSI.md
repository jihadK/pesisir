# Daftar Menu & Fungsinya

**Last updated:** 2026-05-10
**Versi:** Phase 4 (Stock Opening + Adjustment + Kartu Stok)

Legenda status:
- ✅ Live & dapat dipakai
- 🚧 Placeholder (link `#`, akan diisi di phase berikutnya)
- 🔜 Coming soon (tertulis di sidebar dengan badge `soon`)

---

## 1. Dashboard

| Item | Status | Route | Permission | Fungsi |
|---|---|---|---|---|
| Dashboard | ✅ | `dashboard` | (auth) | Halaman beranda setelah login. KPI ringkas, shortcut, dll. |

---

## 2. Master Data

### 2.1 Produk

| Item | Status | Route | Permission | Fungsi |
|---|---|---|---|---|
| **Daftar Produk** | ✅ | `products.index` | `products.view` | CRUD produk: SKU auto-generate `{GROUP}-{SUB}-{GRADE}-{NNN}` (contoh `FISH-TUNA-A-001`). Mengelola spesifikasi pack (tipe isi ekor/potong, range jumlah & berat), harga (cost/margin/sell auto-calc + bulat 1.000), level stock min/max, gambar, status aktif. |
| **Kategori** | ✅ | `categories.index` | `categories.view` | Hierarki kategori multi-level (parent→child). Kolom `code` (4-10 huruf) dipakai untuk segmen SKU. Level-1 = group (mis. `FISH`), level-2 = sub-group (mis. `TUNA`). |
| **Grade** | ✅ | `grades.index` | `grades.view` | Master grade kualitas (A/B/C dst) dengan kode singkat & warna badge. Dipakai sebagai segmen ke-3 SKU. |
| **Satuan (UoM)** | ✅ | `uoms.index` | `uom.view` | Master unit of measure (Pack, Kg, Pcs, Box, dll). Default produk = Pack. |
| **Tier Harga** | ✅ | `price_tiers.index` | `price_tiers.view` | Tingkatan harga jual (Retail, Grosir, Reseller, Restoran). Dipakai untuk pricing per segmen customer. |

### 2.2 Mitra

| Item | Status | Route | Permission | Fungsi |
|---|---|---|---|---|
| **Supplier** | ✅ | `suppliers.index` | `suppliers.view` | CRUD supplier: kode, nama, kontak, NPWP, info bank, payment terms, status aktif. Soft delete + restore. |
| **Customer** | ✅ | `customers.index` | `customers.view` | CRUD customer: tipe (individu/corporate/reseller/restoran/pasar), price tier default, kredit limit, payment terms. Soft delete + restore. |

### 2.3 Lainnya

| Item | Status | Route | Permission | Fungsi |
|---|---|---|---|---|
| **Warehouse** | ✅ | `warehouses.index` | `warehouses.view` | Master gudang: kode, nama, alamat, tipe (utama/cold-storage/cabang), status aktif. Toggle aktif/non-aktif tanpa hapus. |
| Pajak | 🚧 | — | — | Master rate pajak (PPN 11%, dll). |
| Metode Pembayaran | 🚧 | — | — | Master cara bayar (Cash, Transfer, QRIS, dll). |

---

## 3. Inventory

### 3.1 Pembelian

| Item | Status | Route | Permission | Fungsi |
|---|---|---|---|---|
| Purchase Order | 🚧 | — | `po.*` | Buat & approve PO ke supplier. |
| Goods Receipt (GRN) | 🚧 | — | `grn.*` | Terima barang dari PO; generate batch otomatis. |

### 3.2 Stock

| Item | Status | Route | Permission | Fungsi |
|---|---|---|---|---|
| **Stock Opening** | ✅ | `stock_openings.index` | `stock_opening.view` / `.create` | Input saldo awal saat go-live (sekali pakai per produk per warehouse). Multi-line: pilih warehouse → tambah baris produk + qty + cost + (opsional) production/expiry date. Untuk produk perishable yang tidak diisi tanggal, sistem auto-generate batch dengan expiry = today + shelf-life dari master. Lock setelah ada gerakan stock — koreksi selanjutnya wajib via Stock Adjustment. Generate dokumen `OPN/{YYYY}/{NNNNN}`. |
| **Stock Adjustment** | ✅ | `stock_adjustments.index` | `stock_adjustment.view` / `.create` | Koreksi stock single-line: pilih warehouse + produk + (opsional) batch spesifik → tipe Tambah(+)/Kurang(−) → alasan (Rusak/Expired/Hilang/Koreksi/Lainnya) → qty → catatan wajib (≥5 karakter, audit trail). Adjustment tipe Rusak/Expired otomatis dicatat sebagai `out_waste`. Validasi anti-negatif (qty kurang tidak boleh > saldo saat ini). Generate dokumen `ADJ/{YYYY}/{NNNNN}`. |
| **Kartu Stok** | ✅ | `stock_cards.index` | `stock_card.view` | Read-only viewer per produk. Pilih produk → halaman detail menampilkan: info produk, saldo per warehouse (Total/Tersedia), ringkasan periode (Total Masuk/Keluar/Net/Jumlah Mutasi), riwayat mutasi lengkap dengan filter warehouse + tanggal. Setiap baris mutasi menampilkan no. dokumen, tipe, qty (+/−), saldo setelahnya, catatan. |
| Mutasi Gudang | 🔜 | — | `transfer.*` | Transfer barang antar warehouse dengan workflow approve. |
| Stock Opname | 🔜 | — | `opname.*` | Stock taking fisik vs sistem; selisih auto-generate adjustment. |

---

## 4. Penjualan

### 4.1 Sales

| Item | Status | Route | Permission | Fungsi |
|---|---|---|---|---|
| Sales Order | 🚧 | — | `so.*` | Buat & approve SO ke customer. |
| Delivery Order | 🚧 | — | `do.*` | Pengiriman barang dari SO; auto kurangi stock. |
| Retur Penjualan | 🚧 | — | `return.*` | Customer return barang; opsi restock atau write-off. |

### 4.2 Invoicing

| Item | Status | Route | Permission | Fungsi |
|---|---|---|---|---|
| Invoice | 🚧 | — | `invoice.*` | Generate invoice dari SO/DO. |
| Payment | 🚧 | — | `payment.*` | Catat pembayaran customer; multi-payment per invoice. |
| AR Aging | 🚧 | — | `report.ar` | Laporan piutang berdasarkan umur. |

---

## 5. Laporan

| Item | Status | Route | Permission | Fungsi |
|---|---|---|---|---|
| Laporan Stock | 🚧 | — | `report.stock` | Saldo, valuation, slow-moving, dll. |
| Laporan Penjualan | 🚧 | — | `report.sales` | Sales by period/customer/product. |
| Laporan AR | 🚧 | — | `report.ar` | Piutang & aging. |
| Laporan Profit | 🚧 | — | `report.profit` | Margin per produk/kategori/customer. |

---

## 6. Sistem

| Item | Status | Route | Permission | Fungsi |
|---|---|---|---|---|
| Manajemen User → Users | 🚧 | — | `tbm_users.*` | CRUD user, reset password, lock/unlock. |
| Manajemen User → Roles | 🚧 | — | `tbm_roles.*` | CRUD role. |
| Manajemen User → Permissions | 🚧 | — | `tbm_roles.manage_permissions` | Assign permission ke role. |
| Audit Log | 🚧 | — | `audit.view` | Riwayat aksi user (login, CRUD penting). |

---

## Konsep Penting

### SKU Produk
Format: `{GROUP_CODE}-{SUBGROUP_CODE}-{GRADE_CODE}-{NNN}`
Contoh: `FISH-TUNA-A-001`, `FISH-TUNA-A-002`, `FISH-TUNA-B-001` (sequence reset per kombinasi group+sub+grade).
SKU di-generate via tombol "Generate" — readonly saat edit.

### Spesifikasi Pack
Setiap produk dijual per **pack** dengan:
- Tipe isi: `ekor` (ikan utuh) atau `potong` (fillet/cutting)
- Jumlah isi per pack: angka tetap atau range min–max (toggle)
- Berat per pack: angka tetap atau range min–max (toggle)

Contoh tampilan: "Tuna 1 Pack isi 4–5 potong, berat 200–215 g".

### Harga: Cost / Margin / Sell
- Input: Cost + Margin (%)
- Output: Sell auto-calc = `round(cost × (1+margin/100) / 1000) × 1000` (bulat ke kelipatan 1.000)
- Sell juga dapat di-edit manual (override) — Margin akan auto-recalc dari (sell-cost)/cost
- Field "Untung Bersih" readonly menampilkan profit per pack

### Stock Movement Engine
Semua perubahan stock (Opening, Adjustment, dan nanti PO/SO) menulis ke tabel `tbh_stock_movements`. **Trigger DB** `trg_stock_movement_apply` otomatis:
1. Update `tbs_stock_balances` (UPSERT per produk+warehouse+batch)
2. Update `tbm_product_batches.remaining_quantity` (kalau batch_id ada)
3. Set `balance_after` di record movement

Audit trail: setiap movement punya `movement_number` unik, `created_by`, `notes`, `reference_type`/`reference_id`.

### Format Angka (lokal Indonesia)
- Pemisah ribuan: titik (`.`)
- Pemisah desimal: koma (`,`)
- Bilangan bulat → tanpa desimal (`2`)
- Pecahan → 3 desimal (`2,500`)
- Tanda minus pakai en-dash (`−`) untuk visibilitas, bukan hyphen (`-`)
- Mata uang: `Rp 12.000`

### Permissions
Permission dicek via PostgreSQL function `fn_user_has_permission(user_id, permission_name)`. Format permission: `module.action` (mis. `products.view`, `stock_opening.create`).

Phase 4 menambahkan 5 permission baru:
- `stock_opening.view`, `stock_opening.create`
- `stock_adjustment.view`, `stock_adjustment.create`
- `stock_card.view`

Default-nya assigned ke role `admin`, `manager`, `warehouse`.

---

## Roadmap Phase Berikutnya

| Phase | Scope |
|---|---|
| **Phase 5** | Purchase Order + GRN (flow beli barang & terima dari supplier) |
| Phase 6 | Stock Transfer (mutasi antar warehouse) |
| Phase 7 | Stock Opname (stock taking fisik) |
| Phase 8 | Sales Order + Delivery Order |
| Phase 9 | Invoice + Payment + AR |
| Phase 10 | Reports & Analytics |
| Phase 11 | User/Role management UI + Audit Log viewer |

---

## File SQL Patches

| File | Tanggal | Deskripsi |
|---|---|---|
| `04_DDL_POSTGRESQL.sql` | initial | Schema utama database |
| `08_DDL_USER_AUTH.sql` | initial | Auth: users, roles, permissions, sessions |
| `12_PATCH_PHASE3_MASTERS.sql` | 2026-05-08 | UoM.description, Grade.color |
| `14_PATCH_PHASE3_PRODUCT_PACK.sql` | 2026-05-10 | Category.code, Product pack fields |
| `15_PATCH_PHASE3_PRODUCT_MARGIN.sql` | 2026-05-10 | Product.default_margin_percent |
| `16_PATCH_PHASE4_STOCK_OPENING_ADJUSTMENT.sql` | 2026-05-10 | Permissions + doc sequences (OPN, ADJ) |

Urutan eksekusi: 04 → 08 → 10 (seed) → 12 → 14 → 15 → 16.
