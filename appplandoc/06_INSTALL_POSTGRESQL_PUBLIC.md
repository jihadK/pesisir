# Install PostgreSQL 16 di Ubuntu 22.04 + Akses dari IP Public

> **VPS Target:** Ubuntu 22.04 LTS, 2 vCPU / 4 GB RAM / 60 GB
> **Database:** PostgreSQL 16
> **Tujuan:** Install + konfigurasi agar bisa diakses dari IP public (mis. dari laptop developer / server aplikasi terpisah)

---

## ⚠️ PERINGATAN KEAMANAN

Membuka PostgreSQL ke **public IP** sangat berisiko. PostgreSQL akan langsung terekspos ke internet → target brute-force, exploit, data theft.

### Best Practice (urutan dari paling aman):
1. ⭐ **JANGAN expose ke public** — gunakan SSH tunnel atau VPN (paling aman)
2. **Whitelist IP** via firewall — hanya IP tertentu yang boleh konek
3. **TLS/SSL wajib** untuk koneksi remote
4. **Password kuat** + auth `scram-sha-256`
5. **User aplikasi terpisah** dari superuser `postgres`
6. **Audit log** aktif

Jika tetap perlu public access, **wajib** ikuti semua langkah security di bawah.

---

## 📋 Langkah-Langkah Instalasi

### STEP 1 — Update System & Persiapan

```bash
# Login SSH ke VPS
ssh user@IP_VPS_ANDA

# Update repository & upgrade packages
sudo apt update && sudo apt upgrade -y

# Install tools dasar
sudo apt install -y wget curl gnupg2 lsb-release ca-certificates ufw fail2ban

# Set timezone (opsional, sesuaikan)
sudo timedatectl set-timezone Asia/Jakarta
```

### STEP 2 — Install PostgreSQL 16 (Repo Resmi)

Default Ubuntu 22.04 ship PostgreSQL 14. Untuk PostgreSQL 16, pakai repo PGDG:

```bash
# Tambahkan repo resmi PostgreSQL
sudo sh -c 'echo "deb http://apt.postgresql.org/pub/repos/apt $(lsb_release -cs)-pgdg main" > /etc/apt/sources.list.d/pgdg.list'

# Import GPG key
wget --quiet -O - https://www.postgresql.org/media/keys/ACCC4CF8.asc | sudo apt-key add -

# Update & install PostgreSQL 16
sudo apt update
sudo apt install -y postgresql-16 postgresql-contrib-16

# Verifikasi service jalan
sudo systemctl status postgresql
sudo systemctl enable postgresql

# Cek versi
sudo -u postgres psql -c "SELECT version();"
```

**Output yang diharapkan:**
```
PostgreSQL 16.x on x86_64-pc-linux-gnu, compiled by gcc...
```

### STEP 3 — Set Password Superuser `postgres`

```bash
# Masuk ke PostgreSQL sebagai user 'postgres'
sudo -u postgres psql

# Ganti password (di prompt psql)
\password postgres
# Masukkan password kuat (minimal 16 karakter, mix huruf/angka/simbol)

# Keluar
\q
```

### STEP 4 — Buat Database & User Aplikasi

**JANGAN gunakan user `postgres` untuk aplikasi.** Buat user khusus:

```bash
sudo -u postgres psql
```

Di prompt psql:
```sql
-- Buat database aplikasi
CREATE DATABASE fish_stock_sales
    WITH ENCODING='UTF8'
    LC_COLLATE='en_US.UTF-8'
    LC_CTYPE='en_US.UTF-8'
    TEMPLATE=template0;

-- Buat user aplikasi (ganti password!)
CREATE USER fish_app WITH ENCRYPTED PASSWORD 'GANTI_PASSWORD_KUAT_DISINI';

-- Grant privilege ke database
GRANT ALL PRIVILEGES ON DATABASE fish_stock_sales TO fish_app;

-- Connect ke database, lalu set ownership schema
\c fish_stock_sales
GRANT ALL ON SCHEMA public TO fish_app;
ALTER DATABASE fish_stock_sales OWNER TO fish_app;

-- Test
\du
\l

\q
```

### STEP 5 — Konfigurasi `postgresql.conf`

```bash
# Cari path config file
sudo -u postgres psql -c "SHOW config_file;"
# Biasanya: /etc/postgresql/16/main/postgresql.conf

# Edit
sudo nano /etc/postgresql/16/main/postgresql.conf
```

Cari & ubah baris berikut:

```conf
# ============== Listen Address ==============
# Default: listen_addresses = 'localhost'
# Untuk akses public, ubah ke '*' (semua interface)
listen_addresses = '*'

# Port default
port = 5432

# ============== Performance Tuning (4GB RAM) ==============
max_connections = 50
shared_buffers = 1GB                  # ~25% RAM
effective_cache_size = 2GB             # ~50% RAM
work_mem = 8MB
maintenance_work_mem = 128MB
wal_buffers = 16MB
random_page_cost = 1.1                 # untuk SSD
effective_io_concurrency = 200

# ============== Security & Logging ==============
ssl = on
ssl_cert_file = '/etc/ssl/certs/ssl-cert-snakeoil.pem'   # akan diganti nanti
ssl_key_file = '/etc/ssl/private/ssl-cert-snakeoil.key'
password_encryption = scram-sha-256

# Logging
log_destination = 'stderr'
logging_collector = on
log_directory = 'log'
log_filename = 'postgresql-%Y-%m-%d.log'
log_connections = on
log_disconnections = on
log_failed_connections = on
log_min_duration_statement = 1000      # log query > 1 detik
log_line_prefix = '%t [%p]: user=%u,db=%d,app=%a,client=%h '
```

Simpan: `Ctrl+O`, `Enter`, `Ctrl+X`.

### STEP 6 — Konfigurasi `pg_hba.conf` (Authentication)

Ini file yang mengatur **siapa boleh konek dari mana**.

```bash
sudo nano /etc/postgresql/16/main/pg_hba.conf
```

Tambahkan/ubah baris (tergantung skenario):

#### Skenario A — Whitelist IP tertentu (DIREKOMENDASIKAN)

```conf
# TYPE  DATABASE          USER       ADDRESS               METHOD

# Local connection (jangan dihapus!)
local   all               postgres                          peer
local   all               all                               peer

# Localhost (aplikasi di VPS yang sama)
host    all               all        127.0.0.1/32           scram-sha-256
host    all               all        ::1/128                scram-sha-256

# === REMOTE: Whitelist IP developer / server aplikasi ===
# Format: hostssl <db> <user> <ip>/<cidr> scram-sha-256
hostssl fish_stock_sales  fish_app   203.0.113.10/32        scram-sha-256
hostssl fish_stock_sales  fish_app   198.51.100.0/24        scram-sha-256
# Tambahkan IP lain sesuai kebutuhan, satu per baris

# JANGAN PERNAH pakai 0.0.0.0/0 di production!
```

#### Skenario B — Akses dari semua IP (TIDAK DIREKOMENDASIKAN)

```conf
# Hanya jika benar-benar perlu, dan WAJIB pakai SSL + password kuat
hostssl fish_stock_sales  fish_app   0.0.0.0/0              scram-sha-256
```

> ⚠️ Pakai `hostssl` (bukan `host`) untuk **memaksa** koneksi terenkripsi.

Simpan & restart:
```bash
sudo systemctl restart postgresql
sudo systemctl status postgresql
```

### STEP 7 — Setup SSL/TLS Certificate

#### Opsi 1: Self-Signed (untuk dev/internal)

```bash
sudo -u postgres bash <<'EOF'
cd /var/lib/postgresql/16/main
openssl req -new -x509 -days 365 -nodes -text \
    -out server.crt \
    -keyout server.key \
    -subj "/CN=postgres-vps"
chmod 600 server.key
EOF
```

Update `postgresql.conf`:
```conf
ssl_cert_file = '/var/lib/postgresql/16/main/server.crt'
ssl_key_file  = '/var/lib/postgresql/16/main/server.key'
```

#### Opsi 2: Let's Encrypt (kalau punya domain)

```bash
sudo apt install -y certbot
sudo certbot certonly --standalone -d db.domain.com

# Copy cert ke folder postgres
sudo cp /etc/letsencrypt/live/db.domain.com/fullchain.pem /var/lib/postgresql/16/main/server.crt
sudo cp /etc/letsencrypt/live/db.domain.com/privkey.pem   /var/lib/postgresql/16/main/server.key
sudo chown postgres:postgres /var/lib/postgresql/16/main/server.{crt,key}
sudo chmod 600 /var/lib/postgresql/16/main/server.key
```

Restart:
```bash
sudo systemctl restart postgresql
```

### STEP 8 — Konfigurasi Firewall (UFW)

```bash
# Reset & policy default
sudo ufw default deny incoming
sudo ufw default allow outgoing

# Allow SSH (PASTIKAN port SSH yang dipakai!)
sudo ufw allow 22/tcp comment 'SSH'

# Allow PostgreSQL HANYA dari IP whitelist
sudo ufw allow from 203.0.113.10 to any port 5432 proto tcp comment 'PG dev laptop'
sudo ufw allow from 198.51.100.0/24 to any port 5432 proto tcp comment 'PG app server'

# JANGAN: sudo ufw allow 5432/tcp  (ini buka ke semua, bahaya!)

# Aktifkan firewall
sudo ufw enable

# Cek status
sudo ufw status numbered
```

### STEP 9 — Setup Fail2ban untuk PostgreSQL

```bash
sudo nano /etc/fail2ban/jail.local
```

Tambahkan:
```ini
[postgresql]
enabled = true
port = 5432
filter = postgresql
logpath = /var/log/postgresql/postgresql-*.log
maxretry = 5
findtime = 600
bantime = 3600
```

Buat filter:
```bash
sudo nano /etc/fail2ban/filter.d/postgresql.conf
```
```ini
[Definition]
failregex = ^.*FATAL:\s+password authentication failed for user.*from <HOST>.*$
            ^.*FATAL:\s+no pg_hba.conf entry for host "<HOST>".*$
ignoreregex =
```

Restart:
```bash
sudo systemctl restart fail2ban
sudo fail2ban-client status postgresql
```

### STEP 10 — Cek Listening Port

```bash
# PostgreSQL harus listen di 0.0.0.0:5432
sudo ss -tlnp | grep 5432
# Output: LISTEN 0 244 0.0.0.0:5432 ... users:(("postgres",...))

# Cek port terbuka dari luar (dari laptop Anda)
nmap -p 5432 IP_VPS_ANDA
# atau
nc -zv IP_VPS_ANDA 5432
```

### STEP 11 — Test Koneksi dari IP Public

#### Dari laptop developer (Linux/Mac):

```bash
# Install psql client (kalau belum ada)
sudo apt install -y postgresql-client      # Ubuntu/Debian
brew install libpq && brew link --force libpq  # Mac

# Test koneksi
psql "host=IP_VPS_ANDA port=5432 dbname=fish_stock_sales user=fish_app sslmode=require"
# Masukkan password
```

#### Dari Windows (pgAdmin / DBeaver):

| Field | Value |
|-------|-------|
| Host | `IP_VPS_ANDA` |
| Port | `5432` |
| Database | `fish_stock_sales` |
| Username | `fish_app` |
| Password | (password yang dibuat di STEP 4) |
| SSL Mode | `require` |

#### Dari Laravel `.env`:
```env
DB_CONNECTION=pgsql
DB_HOST=IP_VPS_ANDA
DB_PORT=5432
DB_DATABASE=fish_stock_sales
DB_USERNAME=fish_app
DB_PASSWORD=GANTI_PASSWORD_KUAT_DISINI
DB_SSLMODE=require
```

### STEP 12 — Verifikasi SSL Aktif

```bash
psql "host=IP_VPS_ANDA dbname=fish_stock_sales user=fish_app sslmode=require"
```
Di prompt psql:
```sql
SELECT ssl, version, cipher
FROM pg_stat_ssl
WHERE pid = pg_backend_pid();
```
Harus muncul `ssl=t` dan info cipher (TLSv1.2 atau TLSv1.3).

### STEP 13 — Eksekusi DDL Aplikasi

```bash
# Upload DDL ke VPS (dari laptop)
scp 04_DDL_POSTGRESQL.sql user@IP_VPS_ANDA:/tmp/

# Eksekusi sebagai user fish_app
psql "host=IP_VPS_ANDA dbname=fish_stock_sales user=fish_app sslmode=require" \
     -f /tmp/04_DDL_POSTGRESQL.sql

# Verifikasi
psql "host=IP_VPS_ANDA dbname=fish_stock_sales user=fish_app sslmode=require" \
     -c "\dt"
# Harus muncul ~35 tabel
```

### STEP 14 — Setup Backup Otomatis

```bash
sudo mkdir -p /var/backups/pg
sudo chown postgres:postgres /var/backups/pg

sudo nano /etc/cron.d/pg-backup
```

Isi:
```cron
# Backup harian jam 2 pagi
0 2 * * * postgres /usr/bin/pg_dump -Fc fish_stock_sales > /var/backups/pg/fish_$(date +\%F).dump && find /var/backups/pg -name "*.dump" -mtime +7 -delete
```

Restart cron:
```bash
sudo systemctl restart cron
```

### STEP 15 — Monitoring Dasar

```bash
# Status PostgreSQL
sudo systemctl status postgresql

# Lihat aktif koneksi
sudo -u postgres psql -c "SELECT pid, usename, application_name, client_addr, state FROM pg_stat_activity;"

# Lihat log
sudo tail -f /var/log/postgresql/postgresql-16-main.log

# Cek size database
sudo -u postgres psql -c "SELECT pg_size_pretty(pg_database_size('fish_stock_sales'));"
```

---

## 🛡️ Hardening Tambahan (Wajib di Production)

### 1. Ubah Port Default 5432 → Port Custom
Di `postgresql.conf`:
```conf
port = 54321   # atau port lain
```
Update juga UFW rule.

### 2. Limit Connection per User
```sql
ALTER USER fish_app CONNECTION LIMIT 30;
```

### 3. Statement Timeout
```sql
ALTER DATABASE fish_stock_sales SET statement_timeout = '30s';
ALTER DATABASE fish_stock_sales SET idle_in_transaction_session_timeout = '60s';
```

### 4. Revoke PUBLIC Schema (Anti privilege escalation)
```sql
REVOKE ALL ON SCHEMA public FROM PUBLIC;
GRANT USAGE ON SCHEMA public TO fish_app;
```

### 5. Auto-update Security Patches
```bash
sudo apt install -y unattended-upgrades
sudo dpkg-reconfigure --priority=low unattended-upgrades
```

---

## 🔐 Alternatif Lebih Aman: SSH Tunnel (RECOMMENDED)

**JANGAN buka 5432 ke public sama sekali** — pakai SSH tunnel:

Di laptop developer:
```bash
ssh -L 5432:localhost:5432 user@IP_VPS_ANDA -N
```

Lalu konek ke `localhost:5432` di laptop, traffic akan ter-tunnel via SSH ke VPS. Database tetap `listen_addresses = 'localhost'`, port 5432 **tidak perlu** dibuka di UFW.

**Keuntungan:**
- ✅ Database tidak terekspos ke internet sama sekali
- ✅ Otomatis terenkripsi via SSH
- ✅ Auth pakai SSH key (lebih kuat dari password)
- ✅ Tidak perlu sertifikat SSL untuk PostgreSQL

---

## 📋 Checklist Lengkap

### Instalasi
- [ ] OS Ubuntu 22.04 update
- [ ] Install PostgreSQL 16 (PGDG repo)
- [ ] Service jalan & enabled
- [ ] Set password superuser `postgres`

### Database
- [ ] Buat DB `fish_stock_sales`
- [ ] Buat user aplikasi `fish_app` (bukan superuser)
- [ ] Eksekusi DDL aplikasi

### Konfigurasi
- [ ] `postgresql.conf`: listen_addresses, performance tuning, ssl=on
- [ ] `pg_hba.conf`: whitelist IP, pakai `hostssl` + `scram-sha-256`
- [ ] SSL certificate (self-signed atau Let's Encrypt)

### Security
- [ ] UFW firewall: allow 22 + whitelist IP untuk 5432
- [ ] Fail2ban untuk PostgreSQL
- [ ] Password kuat (>16 karakter)
- [ ] Connection limit per user
- [ ] Statement timeout
- [ ] Auto security updates

### Verifikasi
- [ ] `ss -tlnp` menunjukkan 5432 listen
- [ ] Test koneksi dari IP whitelist berhasil
- [ ] Test SSL aktif (`pg_stat_ssl`)
- [ ] Test koneksi dari IP non-whitelist **DITOLAK**

### Operasional
- [ ] Backup harian via cron
- [ ] Logging aktif
- [ ] Monitoring dasar

---

## 🚨 Troubleshooting

### "Connection refused"
- PostgreSQL tidak listen di public IP → cek `listen_addresses = '*'`
- Port belum dibuka di UFW → `sudo ufw status`
- Service belum restart → `sudo systemctl restart postgresql`

### "no pg_hba.conf entry"
- IP source tidak ada di whitelist → tambahkan di `pg_hba.conf`
- Salah pakai `host` vs `hostssl` → harus match dengan `sslmode` client

### "password authentication failed"
- Password salah → reset via `\password fish_app` di psql
- Method auth salah → harus `scram-sha-256` (bukan `md5` lama)

### "SSL connection required"
- Client tidak pakai SSL → tambahkan `sslmode=require` di connection string

### "FATAL: too many connections"
- `max_connections` tercapai → naikkan di `postgresql.conf` atau pakai connection pooler (PgBouncer)

---

## 📚 Referensi
- PostgreSQL 16 docs: https://www.postgresql.org/docs/16/
- pg_hba.conf reference: https://www.postgresql.org/docs/16/auth-pg-hba-conf.html
- SSL setup: https://www.postgresql.org/docs/16/ssl-tcp.html
