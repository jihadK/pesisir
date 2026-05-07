# Database Design — Aplikasi Stock & Penjualan Ikan

## 🎯 Cakupan Sistem

| Modul | Fungsi Utama |
|-------|--------------|
| **Master Data** | User/role, supplier, customer, kategori ikan, produk ikan, satuan, gudang, kemasan |
| **Inventory** | Stock per gudang, GRN, pengeluaran barang, mutasi gudang, stock opname, batch/lot tracking |
| **Penjualan** | Sales order, delivery order, retur penjualan, pricing tier |
| **Invoicing** | Invoice, payment, piutang (AR), pajak |

## 📐 ERD Overview

```
ROLES ──< USERS >── WAREHOUSES
                       │
                       ├── STOCK_BALANCES ──┐
                       │                    │
                       └── STOCK_MOVEMENTS ─┘ (trigger update saldo)

SUPPLIERS → PURCHASE_ORDERS → GRN → PRODUCT_BATCHES (auto-generate)
                                          │
                                          ▼
                                    STOCK_BALANCES

CUSTOMERS → SALES_ORDERS → DELIVERY_ORDERS → INVOICES → PAYMENTS
                                  │              │
                                  ▼              ▼
                         STOCK_MOVEMENTS    INVOICE_PAYMENTS
                            (out_sale)      (many-to-many)
```

## 📋 Daftar Tabel (35 tabel)

### Master Data (14 tabel)
- `roles` — role/jabatan
- `users` — user aplikasi
- `warehouses` — gudang/cold storage
- `units_of_measure` — satuan (KG, EKR, BOX)
- `categories` — kategori ikan (self-ref hierarchy)
- `product_grades` — grade ikan (Premium, Standard, Olahan)
- `products` — master produk ikan
- `product_uom_conversions` — konversi multi-UOM
- `price_tiers` — tier harga (Retail, Grosir, Reseller)
- `product_prices` — harga per tier per produk
- `suppliers` — supplier/pemasok
- `customers` — pelanggan
- `taxes` — pajak (PPN 11%)
- `payment_methods` — metode pembayaran (cash, transfer, giro, dll)

### Inventory (11 tabel)
- `product_batches` — batch/lot tracking (penting untuk ikan)
- `stock_balances` — saldo per produk × gudang × batch
- `stock_movements` — kartu stock (append-only ledger)
- `purchase_orders` — PO ke supplier
- `purchase_order_items`
- `goods_receipts` — GRN penerimaan barang
- `goods_receipt_items`
- `stock_transfers` — mutasi antar gudang
- `stock_transfer_items`
- `stock_opnames` — sensus stock fisik
- `stock_opname_items`

### Penjualan (6 tabel)
- `sales_orders` — SO
- `sales_order_items`
- `delivery_orders` — DO/surat jalan
- `delivery_order_items`
- `sales_returns` — retur penjualan
- `sales_return_items`

### Invoicing (4 tabel)
- `invoices`
- `invoice_items`
- `payments`
- `invoice_payments` — alokasi M:N (1 payment bisa lunasi banyak invoice)

### Sistem (2 tabel)
- `audit_logs` — JSONB old/new values
- `document_sequences` — auto-numbering PO/SO/DO/INV

## ⚙️ Bisnis Rules Penting

1. **FEFO (First Expired First Out)** untuk picking batch saat DO — wajib untuk ikan perishable
2. **Auto-update `stock_balances`** via trigger setiap `stock_movements` insert
3. **Auto-generate `product_batches`** saat GRN diterima (1 GRN item = 1 batch baru)
4. **Reserve stock** saat SO confirmed (tambah `reserved_quantity`), release saat DO shipped
5. **Auto recalc invoice paid_amount** via trigger pada `invoice_payments`
6. **Auto status invoice**: `issued` → `partial` → `paid`/`overdue` berdasarkan paid_amount + due_date
7. **Validasi credit limit** customer saat SO confirm
8. **Soft delete** untuk master data via `deleted_at`

## 📊 Views Reporting (5 views)
- `v_stock_summary` — stock total per produk lintas gudang + value HPP
- `v_stock_low` — produk di bawah min_stock_level
- `v_stock_expiring` — batch expired ≤ 7 hari
- `v_ar_aging` — aging piutang 0-30/31-60/61-90/>90 hari
- `v_sales_daily` — rekap penjualan harian

## 🔍 Index Strategy
- Pencarian: `sku`, `barcode`, `code` (UNIQUE)
- Stock card: `(product_id, warehouse_id, created_at DESC)`
- Expiry: partial index `WHERE remaining_quantity > 0`
- Invoice due: partial index `WHERE status IN ('issued','partial','overdue')`
- Fuzzy search: `gin(name gin_trgm_ops)` via pg_trgm

## 🔢 Document Numbering
Format auto-generated: `{prefix}{YYYY/MM}/{00001}` — reset yearly
- PO: `PO/2026/05/00001`
- SO: `SO/2026/05/00001`
- DO: `DO/2026/05/00001`
- GRN: `GRN/2026/05/00001`
- INV: `INV/2026/05/00001`
- PAY: `PAY/2026/05/00001`

Function: `fn_next_doc_number('SO')`
