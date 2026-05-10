# Phase Deployment Record

**Tanggal deploy:** 2026-05-10
**Status:** ✅ Live di production
**URL:** https://pesisirfreshfish.web.id
**Server:** 103.93.162.70 (Biznetgio VPS)

---

## Final State Achieved

| Komponen | Versi | Status |
|---|---|---|
| OS | Ubuntu 22.04 LTS | ✅ |
| Nginx | 1.18.0 | ✅ Active |
| PHP-FPM | 8.2.31 | ✅ Active |
| PostgreSQL | 16.13 | ✅ Active (loopback only setelah port 5432 ditutup) |
| Composer | 2.9.7 | ✅ |
| Node.js | 20.20.2 | ✅ |
| npm | 10.8.2 | ✅ |
| SSL | Let's Encrypt | ✅ Valid sampai 2026-08-08, auto-renewal aktif |
| Domain | pesisirfreshfish.web.id (Biznetgio) | ✅ A record `@` & `www` → 103.93.162.70 |

---

## Konfigurasi Final

### Aplikasi
- **Path:** `/var/www/pesisirfreshfish`
- **Owner:** `www-data:www-data`
- **Repo:** `https://github.com/jihadK/pesisir.git` (public)
- **Branch:** `main`

### Database
- **Host:** `127.0.0.1` (loopback, port 5432 ditutup dari publik)
- **Database:** `pesisir`
- **User:** `pesisir_app`
- **Skema:** Sudah diapply patch 04 → 16 sebelum aplikasi dideploy

### `.env` — Setting Kunci
```ini
APP_ENV=production
APP_DEBUG=false
APP_URL=https://pesisirfreshfish.web.id
APP_LOCALE=id
APP_TIMEZONE=Asia/Jakarta

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=pesisir
DB_USERNAME=pesisir_app

SESSION_DRIVER=file        # ⬅️ NOT database (tabel sessions belum ada)
CACHE_STORE=file           # ⬅️ NOT database
QUEUE_CONNECTION=sync      # ⬅️ NOT database
SESSION_SECURE_COOKIE=true # ⬅️ Wajib true setelah SSL aktif
```

### Nginx
- **Config:** `/etc/nginx/sites-available/pesisirfreshfish`
- **Symlink:** `/etc/nginx/sites-enabled/pesisirfreshfish`
- **Default site:** Sudah dihapus (`/etc/nginx/sites-enabled/default`)
- **Listen:** 80 (auto-redirect ke 443) + 443 ssl
- **PHP socket:** `unix:/var/run/php/php8.2-fpm.sock`

### Firewall (UFW)
```
22/tcp       ALLOW    OpenSSH
80,443/tcp   ALLOW    Nginx Full
# 5432/tcp   DELETED  (DB hanya akses via loopback)
```

### SSL
- **Cert path:** `/etc/letsencrypt/live/pesisirfreshfish.web.id/`
- **Auto-renewal:** systemd timer (`certbot.timer`)
- **Domain coverage:** `pesisirfreshfish.web.id` + `www.pesisirfreshfish.web.id`
- **Email registrasi:** pesisirfreshfish@gmail.com

---

## Urutan Aksi yang Berhasil

1. **Pre-flight check** — cek komponen yang sudah ada (PHP belum, PostgreSQL ada, Nginx belum, Composer belum, Node belum, Git ada)
2. **Install komponen yang kurang:**
   - PHP 8.2 + extensions via PPA `ondrej/php`
   - Nginx via apt
   - Composer via installer resmi (bukan apt — versi apt usang)
   - Node.js 20 via NodeSource setup script
3. **Firewall UFW** — allow Nginx Full, OpenSSH, enable
4. **DNS Biznetgio** — tambah 2 A record (`@` & `www` → 103.93.162.70), tunggu propagasi ~10 menit
5. **Verifikasi DB existing** — koneksi `127.0.0.1:5432` user `pesisir_app` ke DB `pesisir` berhasil
6. **Clone repo & install dependencies:**
   - `git clone` ke `/var/www/pesisirfreshfish`
   - `composer install --no-dev --optimize-autoloader` (perlu `COMPOSER_ALLOW_SUPERUSER=1`)
   - `npm install` lalu `npm run build` (vite assets)
7. **Konfigurasi `.env`** — termasuk fix locale (jangan campur dengan timezone)
8. **`php artisan key:generate`**
9. **`php artisan storage:link`**
10. **`php artisan config:cache` + `route:cache` + `view:cache`**
11. **Permission:** `chown -R www-data:www-data` + chmod 644/755, `storage` & `bootstrap/cache` chmod 775
12. **Nginx config** — buat file di `sites-available`, symlink ke `sites-enabled`, hapus default, `nginx -t`, reload
13. **Smoke test HTTP** — error 500 → fix session driver → 419 page expired → fix SESSION_SECURE_COOKIE → login sukses
14. **Pasang SSL via `certbot --nginx`** — verify HTTP-01 challenge sukses, deploy cert ke nginx config otomatis (auto-add 443 block + redirect)
15. **Set `SESSION_SECURE_COOKIE=true`** balik
16. **Tutup port 5432** dari publik (`ufw delete allow 5432/tcp`)
17. **Final test** — HTTPS, login, dashboard, menu Stock semua jalan

---

## Issues yang Muncul & Solusinya

### Issue 1: `Invalid "Asia/Jakarta" locale`
- **Penyebab:** `APP_LOCALE=Asia/Jakarta` (salah field)
- **Fix:** `APP_LOCALE=id`, `APP_TIMEZONE=Asia/Jakarta` (terpisah)
- **Pelajaran:** Jangan campur locale (kode bahasa 2 huruf) dengan timezone (Region/City)

### Issue 2: `npm ci` gagal — package-lock.json tidak ada
- **Penyebab:** Repo tidak commit `package-lock.json`
- **Fix:** Pakai `npm install` (akan generate lock baru)
- **Saran follow-up:** Commit `package-lock.json` ke repo agar `npm ci` bisa dipakai untuk reproducible build

### Issue 3: 500 Error — `relation "sessions" does not exist`
- **Penyebab:** `SESSION_DRIVER=database` butuh tabel `sessions` di DB. Project ini pakai SQL patches manual (bukan Laravel migrations), jadi tabel internal Laravel (`sessions`, `cache`, `jobs`) tidak ter-create.
- **Fix:** Set ke `file` driver:
  ```ini
  SESSION_DRIVER=file
  CACHE_STORE=file
  QUEUE_CONNECTION=sync
  ```
  Lalu `php artisan config:cache`.
- **Saran follow-up:** Tambahkan tabel-tabel ini di SQL patch berikutnya kalau mau pakai database driver

### Issue 4: 419 Page Expired saat login
- **Penyebab:** `SESSION_SECURE_COOKIE=true` mengharuskan HTTPS, tapi smoke test masih HTTP → cookie tidak ter-set → CSRF token tidak valid
- **Fix sementara (saat HTTP):** `SESSION_SECURE_COOKIE=false`
- **Fix permanen (setelah SSL):** Balikin ke `true`

### Issue 5: 404 untuk `admin.png` & avatar lain
- **Penyebab:** Seed data `tbm_user_profiles.avatar_url` reference path `/storage/avatars/admin.png` tapi file fisik tidak ada
- **Fix:** `UPDATE tbm_user_profiles SET avatar_url = NULL` → sistem fallback ke default avatar template
- **Saran:** Untuk production, upload file avatar real ke `/var/www/pesisirfreshfish/storage/app/public/avatars/`, atau hapus reference dari seed

### Issue 6: Composer warn "do not run as root"
- **Konteks:** Karena deploy run sebagai root via `sudo su`, composer protes
- **Fix:** `export COMPOSER_ALLOW_SUPERUSER=1` sebelum jalankan composer
- **Saran:** Untuk production proper, deploy via user non-root (mis. `kamil11`) yang punya akses tulis ke `/var/www/pesisirfreshfish`

---

## Pelajaran untuk Phase Deployment Berikutnya

1. **`package-lock.json` harus di-commit** — supaya bisa pakai `npm ci` untuk reproducible build
2. **Tambah tabel Laravel internal di SQL patch** — `sessions`, `cache`, `jobs`, `failed_jobs` — supaya bisa pakai `database` driver (lebih scalable dari `file` driver)
3. **Bikin `.env.example.production`** — template `.env` yang sudah pre-set value production yang aman, supaya tinggal copy + ubah credential DB. Mengurangi typo seperti `APP_LOCALE=Asia/Jakarta`
4. **Deploy via non-root user** — bikin user `deploy` atau pakai `kamil11`, group `www-data` punya akses tulis ke folder project
5. **Auto-cleanup avatar reference di seed** — kalau tidak ada file fisik
6. **Logrotate config** untuk `storage/logs/laravel.log` & `/var/log/nginx/pesisirfreshfish.*` supaya tidak meledak

---

## Checklist Update Berikutnya (Saat Push Code Baru)

```bash
ssh root@103.93.162.70
cd /var/www/pesisirfreshfish

# 1. Pull latest code
git pull origin main

# 2. Composer (kalau ada update di composer.json)
export COMPOSER_ALLOW_SUPERUSER=1
composer install --no-dev --optimize-autoloader

# 3. Frontend rebuild (kalau ada update di JS/CSS)
npm install
npm run build

# 4. Apply SQL patches BARU (manual, sesuai dependency)
psql -h 127.0.0.1 -U pesisir_app -d pesisir -f appplandoc/<patch_baru>.sql

# 5. Re-cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Permissions (kalau ada file baru)
chown -R www-data:www-data /var/www/pesisirfreshfish
chmod -R 775 storage bootstrap/cache

# 7. Reload PHP-FPM (untuk reset opcache)
systemctl reload php8.2-fpm
```

> **Saran:** Bungkus jadi script `~/deploy.sh` (lihat Step 10 di `18_DEPLOY_GUIDE.md`).

---

## Catatan Akses Dev Lokal Setelah Port 5432 Ditutup

Port 5432 sudah tidak terbuka ke internet. Kalau dev lokal kamu (laptop) masih perlu connect ke DB VPS:

### Bikin SSH Tunnel di laptop (Windows PowerShell)
```powershell
ssh -N -L 5433:127.0.0.1:5432 root@103.93.162.70
```

### Update `.env` lokal (di laptop)
```ini
DB_HOST=127.0.0.1
DB_PORT=5433        # port tunnel, bukan 5432
DB_DATABASE=pesisir
DB_USERNAME=pesisir_app
DB_PASSWORD=...
```

Tunnel harus tetap jalan selama develop. Tutup terminal = tunnel mati.

---

## Production-Readiness Pending

Sebelum benar-benar serve real customer:

- [ ] **Ganti password seed users** — admin/manager/cashier/dst masih pakai `Password123!` (dari `10_SEED_USER_AUTH_SAMPLE.sql`)
- [ ] **Backup PostgreSQL terjadwal** — cron `pg_dump` harian, simpan ke external storage
- [ ] **Logrotate** untuk Laravel log & Nginx log
- [ ] **Fail2ban** untuk SSH brute force protection
- [ ] **Monitoring** — minimal uptime check (mis. UptimeRobot gratis), idealnya error tracking (Sentry)
- [ ] **Avatar files real** kalau memang dipakai
- [ ] **Tambah tabel Laravel internal** kalau mau migrate driver session/cache/queue ke `database`
- [ ] **CDN / asset optimization** kalau traffic mulai naik

---

## Phase Roadmap Berikutnya

| Phase | Scope |
|---|---|
| **Phase 5** | Purchase Order + GRN |
| Phase 6 | Stock Transfer (mutasi antar warehouse) |
| Phase 7 | Stock Opname |
| Phase 8 | Sales Order + Delivery Order |
| Phase 9 | Invoice + Payment + AR |
| Phase 10 | Reports & Analytics |
| Phase 11 | User/Role management UI + Audit Log viewer |
