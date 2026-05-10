# Deploy Guide — App ke Server Dev (DB Sudah Ada)

**Target:**
- Server: `103.93.162.70`
- Domain: `pesisirfreshfish.web.id`
- OS: Ubuntu 22.04 / 24.04 LTS
- Stack: Nginx + PHP-FPM 8.2 + PostgreSQL (existing) + Let's Encrypt SSL
- Port: 80 (HTTP redirect) → 443 (HTTPS)

**Skenario:** PostgreSQL sudah ada & ter-setup di VPS yang sama. Tinggal naikkan **app Laravel** ke VPS, app connect ke DB via `127.0.0.1` (loopback, tidak lewat internet).

---

## 0. Pre-flight Checklist

SSH ke server, cek apa yang sudah ada:

```bash
ssh root@103.93.162.70   # ganti user kalau bukan root

# Cek versi PHP (butuh ≥ 8.2)
php -v

# Cek extensions PHP penting
php -m | grep -iE 'pgsql|mbstring|xml|curl|zip|gd|bcmath|intl|pdo'

# Cek PostgreSQL listen di loopback
sudo ss -tlnp | grep 5432
# Harus ada baris: 127.0.0.1:5432  ATAU  *:5432

# Cek Nginx
nginx -v

# Cek Composer
composer -V

# Cek Node.js (butuh ≥ 18 untuk Vite)
node -v
npm -v

# Cek port 80 & 443 free
sudo ss -tlnp | grep -E ':80|:443'
```

Catat mana yang **belum ada** — install di Step 1.

### 0.1 Verifikasi DB existing

Pastikan DB & credential sesuai dengan yang kamu pakai sekarang di `.env` lokal:

```bash
# Test koneksi via 127.0.0.1 (loopback)
psql -h 127.0.0.1 -U <DB_USERNAME> -d <DB_DATABASE>
# Masukkan password → harus berhasil

# Di prompt psql, cek tabel ada
\dt
\q
```

Catat:
- `DB_HOST=127.0.0.1` (untuk app yang nanti akan dipakai)
- `DB_PORT=5432`
- `DB_DATABASE=...` (nama DB existing)
- `DB_USERNAME=...`
- `DB_PASSWORD=...`

---

## 1. Install Komponen yang Kurang

> Skip langkah yang sudah ada di server (cek hasil Step 0).

### 1.1 PHP 8.2 + extensions

```bash
sudo apt update
sudo apt install -y software-properties-common
sudo add-apt-repository -y ppa:ondrej/php
sudo apt update

sudo apt install -y php8.2-fpm php8.2-cli \
    php8.2-pgsql php8.2-mbstring php8.2-xml php8.2-curl \
    php8.2-zip php8.2-gd php8.2-bcmath php8.2-intl php8.2-tokenizer

php -v
sudo systemctl enable --now php8.2-fpm
```

### 1.2 Nginx

```bash
sudo apt install -y nginx
sudo systemctl enable --now nginx
```

### 1.3 Composer

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
composer -V
```

### 1.4 Node.js 20 LTS

```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
node -v && npm -v
```

### 1.5 Git

```bash
sudo apt install -y git
```

### 1.6 Firewall (UFW)

```bash
sudo ufw allow 'Nginx Full'   # port 80 + 443
sudo ufw allow OpenSSH
sudo ufw enable
sudo ufw status
```

---

## 2. DNS — Arahkan Domain ke Server

Login ke panel registrar `pesisirfreshfish.web.id`, tambahkan A record:

| Type | Host | Value | TTL |
|---|---|---|---|
| A | `@` | `103.93.162.70` | 3600 |
| A | `www` | `103.93.162.70` | 3600 |

Tunggu propagasi (5–30 menit). Verifikasi:

```bash
dig +short pesisirfreshfish.web.id
# Harus return: 103.93.162.70
```

⚠️ **DNS harus sudah propagated SEBELUM jalankan certbot di Step 6**, karena Let's Encrypt akan verifikasi via HTTP challenge.

---

## 3. Clone Repo & Setup Aplikasi

### 3.1 Siapkan folder

```bash
sudo mkdir -p /var/www
cd /var/www
sudo git clone https://github.com/USERNAME/REPO_NAME.git pesisirfreshfish
cd pesisirfreshfish
```

> Ganti `USERNAME/REPO_NAME` dengan repo GitHub kamu.

### 3.2 Install dependencies

```bash
# PHP deps (production: skip dev packages)
composer install --no-dev --optimize-autoloader

# Node deps & build assets
npm ci
npm run build
```

### 3.3 Konfigurasi `.env`

```bash
cp .env.example .env
nano .env
```

Edit isinya:

```ini
APP_NAME="Pesisir Fresh Fish"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://pesisirfreshfish.web.id

APP_LOCALE=id
APP_TIMEZONE=Asia/Jakarta

LOG_CHANNEL=stack
LOG_LEVEL=warning

# ⬇️ Pakai 127.0.0.1 (loopback), JANGAN public IP — app & DB di server yang sama
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=fish_stock_sales
DB_USERNAME=fish_app
DB_PASSWORD=PASSWORD_DB_KAMU

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax

CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=local
```

> Sesuaikan `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` dengan credential DB existing kamu.

Generate APP_KEY:

```bash
php artisan key:generate
```

### 3.4 Test koneksi DB

```bash
php artisan tinker
> DB::select('SELECT current_database(), current_user');
# Harus return: fish_stock_sales, fish_app
> exit
```

### 3.5 Symlink storage (untuk upload gambar produk)

```bash
php artisan storage:link
```

### 3.6 Cache config & routes (production optimization)

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 3.7 Set permissions

```bash
sudo chown -R www-data:www-data /var/www/pesisirfreshfish
sudo find /var/www/pesisirfreshfish -type f -exec chmod 644 {} \;
sudo find /var/www/pesisirfreshfish -type d -exec chmod 755 {} \;

# storage & bootstrap/cache wajib writable oleh www-data
sudo chmod -R 775 /var/www/pesisirfreshfish/storage
sudo chmod -R 775 /var/www/pesisirfreshfish/bootstrap/cache
```

---

## 4. Konfigurasi Nginx

Buat file config baru:

```bash
sudo nano /etc/nginx/sites-available/pesisirfreshfish
```

Isi dengan:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name pesisirfreshfish.web.id www.pesisirfreshfish.web.id;

    root /var/www/pesisirfreshfish/public;
    index index.php index.html;

    charset utf-8;

    # Laravel-style URL rewriting
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP handling
    location ~ \.php$ {
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT   $realpath_root;
        fastcgi_buffers 16 16k;
        fastcgi_buffer_size 32k;
        fastcgi_read_timeout 60s;
    }

    # Static assets caching
    location ~* \.(?:css|js|woff2?|ttf|eot|svg|ico|png|jpg|jpeg|gif|webp)$ {
        expires 30d;
        access_log off;
        add_header Cache-Control "public, immutable";
        try_files $uri =404;
    }

    # Security: blokir akses ke file sensitif
    location ~ /\.(?!well-known).* { deny all; }
    location ~ /(?:\.env|composer\.(json|lock)|package\.json|package-lock\.json|README\.md|artisan)$ { deny all; }

    # Upload max size
    client_max_body_size 8M;
    server_tokens off;

    access_log /var/log/nginx/pesisirfreshfish.access.log;
    error_log  /var/log/nginx/pesisirfreshfish.error.log;
}
```

Aktifkan & test:

```bash
sudo ln -sf /etc/nginx/sites-available/pesisirfreshfish /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl reload nginx
```

### 4.1 Tuning PHP-FPM (opsional)

Edit `/etc/php/8.2/fpm/php.ini`:

```ini
upload_max_filesize = 8M
post_max_size       = 10M
memory_limit        = 256M
max_execution_time  = 60
date.timezone       = Asia/Jakarta
```

Reload:

```bash
sudo systemctl restart php8.2-fpm
```

---

## 5. Smoke Test (HTTP)

Buka browser → `http://pesisirfreshfish.web.id`

Yang seharusnya terjadi:
- Halaman login Laravel muncul
- Tidak ada "404 nginx" → kalau muncul, cek nginx config & DNS
- Tidak ada "500" / blank putih → cek `tail -f /var/www/pesisirfreshfish/storage/logs/laravel.log`

Login pakai user yang sudah ada di DB kamu.

---

## 6. Pasang SSL (Let's Encrypt via Certbot)

Setelah HTTP terbukti jalan, baru pasang HTTPS:

```bash
sudo apt install -y certbot python3-certbot-nginx

sudo certbot --nginx \
    -d pesisirfreshfish.web.id \
    -d www.pesisirfreshfish.web.id \
    --redirect \
    --agree-tos \
    --email YOUR_EMAIL@example.com \
    --no-eff-email
```

Certbot akan otomatis:
- Verifikasi domain via HTTP-01 challenge
- Generate certificate & key
- Update nginx config (tambah block port 443 + redirect 80→443)
- Setup auto-renewal via systemd timer

Verifikasi:

```bash
sudo certbot certificates
sudo systemctl status certbot.timer
sudo certbot renew --dry-run
```

Buka browser → `https://pesisirfreshfish.web.id` — gembok hijau muncul.

---

## 7. Tutup Port 5432 dari Publik

> ⚠️ **Penting setelah deploy.** Karena app & DB di server yang sama dan pakai `127.0.0.1`, port 5432 **tidak perlu** dibuka ke internet.

```bash
# Cek firewall
sudo ufw status

# Kalau port 5432 terbuka, tutup
sudo ufw deny 5432

# Verifikasi PostgreSQL hanya listen di loopback (lebih aman)
sudo ss -tlnp | grep 5432
# Idealnya: 127.0.0.1:5432
```

Kalau PostgreSQL masih listen di `*:5432` (semua interface), edit:

```bash
sudo nano /etc/postgresql/16/main/postgresql.conf   # ganti 16 ke versi PG kamu
```

Cari & ubah:
```
listen_addresses = 'localhost'    # tadinya '*'
```

Lalu:
```bash
sudo systemctl restart postgresql
```

> ⚠️ **Konsekuensi:** Setelah port 5432 ditutup ke publik, aplikasi lokal kamu di laptop tidak bisa lagi connect langsung ke `103.93.162.70:5432`. Untuk dev lokal yang masih butuh akses DB VPS, pakai **SSH Tunnel** (lihat Section 9).

---

## 8. Final Verification

Checklist:

- [ ] `https://pesisirfreshfish.web.id` accessible dengan SSL valid
- [ ] Login berhasil
- [ ] Halaman dashboard load tanpa error
- [ ] Bisa akses menu Produk, Stock Opening, Kartu Stok
- [ ] Bisa upload gambar produk
- [ ] Port 5432 sudah ditutup dari publik (`sudo ufw status` tidak ada 5432 ALLOW)
- [ ] `tail -f storage/logs/laravel.log` tidak spam error

---

## 9. Dev Lokal Connect ke DB VPS (Pakai SSH Tunnel)

Setelah port 5432 ditutup di Step 7, dev lokal kamu butuh tunnel untuk connect ke DB VPS.

### Di laptop kamu (Windows PowerShell)

```powershell
ssh -N -L 5433:127.0.0.1:5432 root@103.93.162.70
```

Penjelasan:
- `-N` = jangan eksekusi command, cuma forward port
- `-L 5433:127.0.0.1:5432` = port lokal `5433` di laptop → `127.0.0.1:5432` di VPS
- Pakai `5433` di lokal supaya tidak bentrok kalau ada PostgreSQL lokal

### Update `.env` lokal kamu (di laptop)

```ini
DB_HOST=127.0.0.1
DB_PORT=5433              # ⬅️ port tunnel, bukan 5432
DB_DATABASE=fish_stock_sales
DB_USERNAME=fish_app
DB_PASSWORD=...
```

Tunnel harus tetap jalan selama develop. Tutup terminal = tunnel mati = aplikasi lokal error.

### Tips: bikin tunnel auto-start

Bikin script `start-db-tunnel.ps1`:
```powershell
ssh -N -L 5433:127.0.0.1:5432 root@103.93.162.70
```
Atau pakai SSH key supaya tidak prompt password.

---

## 10. Update Deployment (Setelah Push ke GitHub)

Bikin script `~/deploy.sh` di server:

```bash
cat > ~/deploy.sh <<'EOF'
#!/bin/bash
set -e
cd /var/www/pesisirfreshfish

echo "==> Pull from GitHub"
git pull origin main

echo "==> Composer install (no-dev)"
composer install --no-dev --optimize-autoloader --no-interaction

echo "==> NPM build"
npm ci
npm run build

echo "==> Run any new SQL patches manually first via psql!"
echo "    Lihat appplandoc/*.sql yang baru ditambahkan."

echo "==> Clear & rebuild caches"
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

echo "==> Reset permissions"
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

echo "==> Reload PHP-FPM"
sudo systemctl reload php8.2-fpm

echo "==> Done!"
EOF

chmod +x ~/deploy.sh
```

Setiap kali ada update, jalankan:

```bash
~/deploy.sh
```

> ⚠️ Kalau ada SQL patch baru di `appplandoc/`, **jalankan manual** dulu via `psql` sebelum `~/deploy.sh`.

Contoh apply patch baru:
```bash
cd /var/www/pesisirfreshfish
psql -h 127.0.0.1 -U fish_app -d fish_stock_sales -f appplandoc/17_PATCH_PHASE5_NEW.sql
```

---

## 11. Catatan Production-Readiness

Sebelum benar-benar serve traffic real:

- [ ] **Ganti semua password seed** kalau seed user masih pakai `Password123!`
- [ ] **Backup database** terjadwal (cron `pg_dump` harian, simpan ke external storage)
- [ ] **Monitor logs** — minimal logrotate untuk `storage/logs/laravel.log` & `/var/log/nginx/*`
- [ ] **`APP_DEBUG=false`** (jangan lupa kalau debug sempat di-enable saat troubleshoot)
- [ ] **Firewall** — hanya buka port 22 (SSH), 80, 443. Tutup 5432 dari publik
- [ ] **Fail2ban** untuk SSH brute force protection
- [ ] **Setup queue worker** (kalau pakai job queue): `php artisan queue:work` via supervisor
- [ ] **Cron schedule** kalau ada scheduled task: tambah ke crontab
  ```
  * * * * * cd /var/www/pesisirfreshfish && php artisan schedule:run >> /dev/null 2>&1
  ```

---

## Troubleshooting Cepat

### "500 Server Error" di browser
```bash
tail -50 /var/www/pesisirfreshfish/storage/logs/laravel.log
sudo tail -50 /var/log/nginx/pesisirfreshfish.error.log
```

### Storage permission denied
```bash
sudo chown -R www-data:www-data /var/www/pesisirfreshfish/storage
sudo chmod -R 775 /var/www/pesisirfreshfish/storage
```

### "could not find driver" PostgreSQL
PHP extension `pgsql` belum terpasang:
```bash
sudo apt install -y php8.2-pgsql
sudo systemctl restart php8.2-fpm
```

### CSS/JS tidak load (404 di Network tab)
Build assets belum jalan:
```bash
cd /var/www/pesisirfreshfish
npm run build
```

### Routes 404 untuk semua URL kecuali `/`
Cache outdated:
```bash
php artisan route:clear
php artisan route:cache
sudo nginx -t && sudo systemctl reload nginx
```

### Certbot gagal "DNS problem"
DNS belum propagated:
```bash
dig +short pesisirfreshfish.web.id    # harus return 103.93.162.70
```
Tunggu 10–30 menit, coba lagi.

### "SQLSTATE[08006] Connection refused"
PostgreSQL tidak listen, atau `DB_HOST` salah:
```bash
sudo systemctl status postgresql
sudo ss -tlnp | grep 5432
psql -h 127.0.0.1 -U fish_app -d fish_stock_sales   # test manual
```

### Local laptop tidak bisa connect ke DB setelah deploy
Port 5432 sudah ditutup di Step 7. Pakai SSH Tunnel di Section 9.
