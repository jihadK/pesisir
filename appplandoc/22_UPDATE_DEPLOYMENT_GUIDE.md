# Update Deployment Guide — Dev Server

**Target server:** `103.93.162.70` (Ubuntu 22.04)
**App path:** `/var/www/pesisirfreshfish`
**URL:** `https://pesisirfreshfish.web.id`
**Last updated:** 2026-05-11

Panduan ini untuk **update incremental** setelah initial deploy ([18_DEPLOY_GUIDE.md](18_DEPLOY_GUIDE.md)) sudah selesai.

---

## TL;DR — Quick Update (Code-only)

Update yang hanya mengubah code PHP/Blade (tanpa SQL/asset baru):

```bash
ssh root@103.93.162.70
cd /var/www/pesisirfreshfish

git pull origin main

php artisan config:cache
php artisan route:cache
php artisan view:cache

systemctl reload php8.2-fpm
```

Selesai dalam <30 detik. Tidak perlu langkah berikutnya kalau **tidak ada**:
- SQL patch baru
- File JS/CSS yang berubah
- Composer dependency baru
- File static (logo/gambar) baru

---

## Update Lengkap (Phase / Major Update)

Saat ada perubahan besar (phase baru, SQL patch, asset baru), ikuti langkah ini berurutan.

### Step 1 — Push dari Laptop ke GitHub

Di laptop kamu (PowerShell):

```powershell
cd D:\FILE\KAMIL\PROJECT\php\testapp

# Cek apa yang berubah
git status

# Stage semua
git add .

# Commit dengan pesan jelas
git commit -m "Phase 5a: Sales Order + Payment Method + WhatsApp share"

# Push ke GitHub
git push origin main
```

> **Catatan file binary:** Logo, QRIS, dan gambar produk biasanya **tidak** di-commit ke git (`.gitignore` pada `public/storage/*`). Upload terpisah di Step 4.

### Step 2 — Pull di Server & Update Dependencies

```bash
ssh root@103.93.162.70
cd /var/www/pesisirfreshfish

# Pull code terbaru
git pull origin main

# Composer (kalau composer.json/composer.lock berubah)
export COMPOSER_ALLOW_SUPERUSER=1
composer install --no-dev --optimize-autoloader

# Frontend rebuild (kalau ada file JS/CSS berubah)
npm install
npm run build
```

> **Sering kena masalah:** `npm run build` error `vite: Permission denied` karena `chmod -R 644` yang ada di langkah permission menghilangkan executable bit dari `node_modules/.bin/*`. Fix: `chmod +x node_modules/.bin/*` lalu ulang `npm run build`. Atau pakai `npx vite build` sebagai workaround.

### Step 3 — Apply SQL Patch Baru (kalau ada)

**Jalankan manual, jangan auto-script.** SQL patch perlu pengawasan supaya tahu kalau ada error.

Cek SQL patch apa saja yang baru di repo:
```bash
ls -lt appplandoc/*.sql | head -10
```

Bandingkan dengan yang sudah ke-apply:
```bash
psql -h 127.0.0.1 -U pesisir_app -d pesisir -c "SELECT name FROM tbm_permissions WHERE name LIKE 'sales_order%';"
# Kalau hasil kosong → patch 20 belum jalan
```

Apply patch baru (urutkan sesuai nomor file):
```bash
psql -h 127.0.0.1 -U pesisir_app -d pesisir -f appplandoc/<patch_baru>.sql
```

Contoh untuk Phase 5a:
```bash
psql -h 127.0.0.1 -U pesisir_app -d pesisir -f appplandoc/20_PATCH_PHASE5A_SALES_ORDER.sql
psql -h 127.0.0.1 -U pesisir_app -d pesisir -f appplandoc/21_PATCH_PHASE5A_SO_PAYMENT_METHOD.sql
```

**Verifikasi setelah patch jalan** — output akhir harus tampil:
```
status
─────────────────────────────────────────────
 Phase 5a sales-order patches applied successfully.
```

Kalau muncul error `current transaction is aborted`, jalankan `ROLLBACK;` dulu lalu run ulang seluruh file (Ctrl+A → Ctrl+Enter di SQL editor).

### Step 4 — Upload File Static (Logo, QRIS, Gambar)

File binary (logo, QRIS, gambar produk) tidak di-track git. Upload via salah satu cara:

**Cara 1 — SCP dari laptop (kalau punya SSH key)**
```powershell
scp "D:\FILE\KAMIL\PROJECT\php\testapp\public\storage\logo\logo-pesisir-web.png" kamil11@103.93.162.70:/home/kamil11/deployment-file/
scp "D:\FILE\KAMIL\PROJECT\php\testapp\public\storage\payment\qris.png" kamil11@103.93.162.70:/home/kamil11/deployment-file/
```

**Cara 2 — WinSCP / Filezilla GUI** — drag & drop ke `/home/kamil11/deployment-file/`

Di server (sebagai root), pindahkan ke folder target & set ownership:
```bash
mkdir -p /var/www/pesisirfreshfish/storage/app/public/logo
mkdir -p /var/www/pesisirfreshfish/storage/app/public/payment

mv /home/kamil11/deployment-file/logo-pesisir-web.png /var/www/pesisirfreshfish/storage/app/public/logo/
mv /home/kamil11/deployment-file/qris.png /var/www/pesisirfreshfish/storage/app/public/payment/

chown -R www-data:www-data /var/www/pesisirfreshfish/storage/app/public/logo /var/www/pesisirfreshfish/storage/app/public/payment
chmod 644 /var/www/pesisirfreshfish/storage/app/public/logo/*.png /var/www/pesisirfreshfish/storage/app/public/payment/*.png

# Test akses publik
curl -I https://pesisirfreshfish.web.id/storage/logo/logo-pesisir-web.png
# Harus return: HTTP/2 200
```

### Step 5 — Clear & Rebuild Cache Laravel

**Wajib** kalau ada route/config/view baru. Tanpa ini, Laravel masih pakai cache lama → route baru 404.

```bash
cd /var/www/pesisirfreshfish

php artisan config:clear && php artisan config:cache
php artisan route:clear && php artisan route:cache
php artisan view:clear && php artisan view:cache
```

### Step 6 — Reset Permissions

```bash
chown -R www-data:www-data /var/www/pesisirfreshfish
chmod -R 775 storage bootstrap/cache

# Re-grant executable bit untuk node_modules binaries (kalau habis chmod -R)
chmod +x node_modules/.bin/* 2>/dev/null || true
```

### Step 7 — Reload PHP-FPM (reset opcache)

```bash
systemctl reload php8.2-fpm
```

### Step 8 — Verifikasi di Browser

1. **Logout** & **login ulang** (refresh permission session)
2. **Hard refresh** browser: `Ctrl+Shift+R` (atau `Ctrl+F5`)
3. Cek menu baru muncul di sidebar
4. Test fitur baru (create record, klik tombol baru, dll)
5. Cek error log:
   ```bash
   tail -30 /var/www/pesisirfreshfish/storage/logs/laravel.log
   tail -30 /var/log/nginx/pesisirfreshfish.error.log
   ```

---

## Script Otomasi `~/deploy.sh`

Bikin sekali, pakai untuk update code-only berikutnya:

```bash
cat > ~/deploy.sh <<'EOF'
#!/bin/bash
set -e
cd /var/www/pesisirfreshfish

echo "==> Pulling from GitHub..."
git pull origin main

echo "==> Composer install (no-dev)..."
export COMPOSER_ALLOW_SUPERUSER=1
composer install --no-dev --optimize-autoloader --no-interaction

echo "==> NPM build..."
chmod +x node_modules/.bin/* 2>/dev/null || true
npm install
npm run build

echo ""
echo "⚠️  REMINDER: Kalau ada SQL patch baru di appplandoc/*.sql,"
echo "    jalankan manual dengan: psql -h 127.0.0.1 -U pesisir_app -d pesisir -f appplandoc/<file>.sql"
echo ""

echo "==> Clearing & caching..."
php artisan config:clear && php artisan config:cache
php artisan route:clear  && php artisan route:cache
php artisan view:clear   && php artisan view:cache

echo "==> Permissions..."
chown -R www-data:www-data /var/www/pesisirfreshfish
chmod -R 775 storage bootstrap/cache
chmod +x node_modules/.bin/* 2>/dev/null || true

echo "==> Reload PHP-FPM..."
systemctl reload php8.2-fpm

echo "==> Done! Test di https://pesisirfreshfish.web.id"
EOF

chmod +x ~/deploy.sh
```

Pakai:
```bash
~/deploy.sh
```

---

## Troubleshooting

### `fatal: detected dubious ownership in repository`
Git keberatan karena folder owned `www-data` tapi di-run dari root. Fix:
```bash
git config --global --add safe.directory /var/www/pesisirfreshfish
```

### `vite: Permission denied` saat `npm run build`
Permission file di `node_modules/.bin/` ke-strip jadi 644. Fix:
```bash
chmod +x node_modules/.bin/*
npm run build
```

### Menu baru tidak muncul setelah deploy
1. Logout & login ulang (permission cache session)
2. `php artisan route:clear && php artisan route:cache` (route belum re-cache)
3. Hard refresh browser (`Ctrl+Shift+R`)
4. Cek permission user di DB:
   ```sql
   SELECT p.name FROM tbm_role_permissions rp
   JOIN tbm_permissions p ON p.id = rp.permission_id
   JOIN tbm_users u ON u.role_id = rp.role_id
   WHERE u.username = 'admin' AND p.name LIKE 'sales_%';
   ```

### Error 500 setelah deploy
```bash
tail -50 /var/www/pesisirfreshfish/storage/logs/laravel.log
```
Lihat baris error utama. Penyebab umum:
- **Undefined column** → SQL patch belum jalan. Apply patch.
- **Class not found** → composer install belum jalan, atau autoloader perlu dump: `composer dump-autoload`.
- **Permission denied** di storage/cache → `chmod -R 775 storage bootstrap/cache && chown -R www-data:www-data storage bootstrap/cache`.

### Header masih nama lama padahal `.env` sudah diupdate
Config cache belum di-rebuild:
```bash
php artisan config:clear
php artisan config:cache
```
Lalu hard refresh browser.

### CSS/JS broken setelah deploy
```bash
cd /var/www/pesisirfreshfish
npm run build
chown -R www-data:www-data public/build
```

### "current transaction is aborted" di psql
Transaksi sebelumnya nyangkut:
```sql
ROLLBACK;
-- Lalu run patch SQL ulang dengan select all + execute
```

---

## Checklist Sebelum Push ke Server

Sebelum `git push` dari laptop:

- [ ] Code sudah ditest di local (login, navigasi menu baru, fitur baru)
- [ ] File `.env` lokal **tidak ke-commit** (cek `git status`)
- [ ] File binary besar (gambar, PDF) tidak ke-commit
- [ ] SQL patch baru ada di `appplandoc/` dengan nomor urut benar
- [ ] Commit message jelas (apa yang ditambah/diubah)

## Checklist Setelah Deploy

- [ ] Login berhasil di production
- [ ] Menu baru muncul di sidebar
- [ ] Fitur utama jalan (create record, view detail, dll)
- [ ] Tidak ada error 500 saat klik menu
- [ ] `tail /var/www/pesisirfreshfish/storage/logs/laravel.log` tidak spam error
- [ ] Akses static file (logo, QRIS) return 200

---

## Catatan untuk Phase Berikutnya

Saat add Phase baru, urutan file SQL patch jangan loncat — pakai nomor urut konsisten:

| File | Phase | Status |
|---|---|---|
| `04_DDL_POSTGRESQL.sql` | Initial DDL | ✅ Applied |
| `08_DDL_USER_AUTH.sql` | Auth schema | ✅ Applied |
| `12_PATCH_PHASE3_MASTERS.sql` | UoM/Grade tweaks | ✅ Applied |
| `14_PATCH_PHASE3_PRODUCT_PACK.sql` | Category code + product pack | ✅ Applied |
| `15_PATCH_PHASE3_PRODUCT_MARGIN.sql` | Product margin | ✅ Applied |
| `16_PATCH_PHASE4_STOCK_OPENING_ADJUSTMENT.sql` | Stock permissions + sequences | ✅ Applied |
| `20_PATCH_PHASE5A_SALES_ORDER.sql` | SO + payment method seed | ✅ Applied |
| `21_PATCH_PHASE5A_SO_PAYMENT_METHOD.sql` | SO payment_method_id col | ✅ Applied |

Untuk Phase 5b (DO + Invoice + Payment) nanti pakai nomor `22_*.sql`, `23_*.sql`, dst.
