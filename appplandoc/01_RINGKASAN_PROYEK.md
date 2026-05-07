# Ringkasan Proyek — Aplikasi Stock & Penjualan Ikan

## 🎯 Tujuan
Aplikasi manajemen stock & penjualan ikan dengan cakupan modul:
- **Master Data** — user, role, supplier, customer, kategori, produk ikan, UOM, gudang, harga
- **Inventory** — stock per gudang, batch/lot tracking, GRN, mutasi gudang, stock opname
- **Penjualan** — Sales Order (SO), Delivery Order (DO), retur penjualan
- **Invoicing** — Invoice, Payment, AR (piutang), pajak

## 🏗️ Stack Teknis
| Layer | Teknologi |
|-------|-----------|
| Frontend | PHP / Laravel |
| Backend | PHP / Laravel |
| Database | PostgreSQL 14+ |
| Deployment | VPS (2 vCPU / 4 GB RAM / 60 GB) |
| Web Server | Nginx + PHP-FPM 8.3 |
| Cache/Queue | Redis 7 |
| CDN/Security | Cloudflare (free tier) |

## 📂 Struktur Folder Dokumentasi
```
D:\TEMP FILE\AI\APP_STOCK\
├── 01_RINGKASAN_PROYEK.md           ← file ini
├── 02_ARSITEKTUR_VPS.md              ← arsitektur deployment
├── 03_DATABASE_DESIGN.md             ← desain database (ERD, modul, tabel)
├── 04_DDL_POSTGRESQL.sql             ← DDL siap eksekusi
├── 05_PENTEST_FINDINGS_NOTES.md      ← catatan pentest (proyek lain, untuk referensi)
├── 06_INSTALL_POSTGRESQL_PUBLIC.md   ← panduan install PG 16 production (SSL, fail2ban, whitelist)
├── 07_INSTALL_POSTGRESQL_DEV.md      ← panduan install PG simpel untuk DEVELOPMENT ⭐
├── 08_DDL_USER_AUTH.sql              ← DDL modul user auth (12 tabel + 70 permissions)
└── 09_USER_AUTH_DESIGN.md            ← desain auth flow, permission matrix, integrasi Laravel
```

## 📌 Status Pengembangan
- [x] Arsitektur deployment VPS
- [x] Desain database relasional (~35 tabel)
- [x] DDL PostgreSQL (siap eksekusi)
- [ ] Seed data lengkap (sample produk ikan, supplier, customer)
- [ ] Migration files Laravel
- [ ] Implementasi modul Master Data
- [ ] Implementasi modul Inventory
- [ ] Implementasi modul Penjualan
- [ ] Implementasi modul Invoicing
- [ ] UI/UX design
- [ ] Reporting & dashboard
- [ ] Testing & deployment

## 🔑 Keputusan Penting
1. **Database**: PostgreSQL (bukan MySQL) — alasan: support GENERATED columns, JSONB, partial index, trigger lebih powerful.
2. **Frontend & Backend dipisah** — Laravel frontend (Blade) + Laravel backend (REST API) — pakai subdomain `app.domain.com` & `api.domain.com`.
3. **Batch/Lot tracking wajib** — karena ikan perishable, butuh FEFO (First Expired First Out).
4. **Single VPS deployment** — cukup untuk MVP / traffic awal (<100 concurrent users).
5. **Cloudflare di depan** — DDoS protection, WAF, CDN gratis.

## 🎯 Modul → Tabel
| Modul | Tabel Utama |
|-------|-------------|
| **Master Data** | `users`, `roles`, `warehouses`, `suppliers`, `customers`, `categories`, `products`, `product_grades`, `units_of_measure`, `product_uom_conversions`, `price_tiers`, `product_prices`, `taxes`, `payment_methods` |
| **Inventory** | `product_batches`, `stock_balances`, `stock_movements`, `purchase_orders`, `purchase_order_items`, `goods_receipts`, `goods_receipt_items`, `stock_transfers`, `stock_transfer_items`, `stock_opnames`, `stock_opname_items` |
| **Penjualan** | `sales_orders`, `sales_order_items`, `delivery_orders`, `delivery_order_items`, `sales_returns`, `sales_return_items` |
| **Invoicing** | `invoices`, `invoice_items`, `payments`, `invoice_payments` |
| **Sistem** | `audit_logs`, `document_sequences` |

**Total: ~35 tabel**

## 💡 Catatan untuk Sesi Berikutnya
- Saat melanjutkan, baca file ini dulu untuk context
- DDL lengkap ada di `04_DDL_POSTGRESQL.sql`
- Belum ada coding aplikasi (Laravel) — masih tahap database design
