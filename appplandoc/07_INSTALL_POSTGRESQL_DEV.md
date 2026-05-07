# Install PostgreSQL 16 di Ubuntu 22 — Mode Development

> **Environment:** Development (bukan production)
> **VPS:** Ubuntu 22.04
> **Tujuan:** Install PostgreSQL 16 + akses dari IP public dengan setup minimal

> ⚠️ **Catatan:** Setup ini cocok untuk dev/internal saja. Untuk production, ikuti `06_INSTALL_POSTGRESQL_PUBLIC.md` yang ada SSL, fail2ban, whitelist IP.

---

## 🚀 Langkah Cepat (10 Menit)

### STEP 1 — SSH ke VPS
```bash
ssh user@IP_VPS_ANDA
```

### STEP 2 — Update System
```bash
sudo apt update && sudo apt upgrade -y
```

### STEP 3 — Install PostgreSQL 16

**Opsi A: Pakai versi default Ubuntu (PostgreSQL 14) — paling simpel**
```bash
sudo apt install -y postgresql postgresql-contrib
```

**Opsi B: Install PostgreSQL 16 dari repo resmi**
```bash
sudo apt install -y wget gnupg2 lsb-release
sudo sh -c 'echo "deb http://apt.postgresql.org/pub/repos/apt $(lsb_release -cs)-pgdg main" > /etc/apt/sources.list.d/pgdg.list'
wget --quiet -O - https://www.postgresql.org/media/keys/ACCC4CF8.asc | sudo apt-key add -
sudo apt update
sudo apt install -y postgresql-16 postgresql-contrib-16
```

Verifikasi:
```bash
sudo systemctl status postgresql
sudo -u postgres psql -c "SELECT version();"
```

### STEP 4 — Buat Database & User Aplikasi

```bash
sudo -u postgres psql
```

Di prompt psql:
```sql
-- Set password superuser dulu
\password postgres
-- Masukkan password kuat, contoh: P@ssw0rd

-- Buat database aplikasi
CREATE DATABASE pesisir
    WITH ENCODING='UTF8'
    LC_COLLATE='en_US.UTF-8'
    LC_CTYPE='en_US.UTF-8'
    TEMPLATE=template0;

-- Buat user aplikasi
CREATE USER pesisir_app WITH ENCRYPTED PASSWORD 'pesisir!';

-- Grant privilege
GRANT ALL PRIVILEGES ON DATABASE pesisir TO pesisir_app;
\c pesisir
GRANT ALL ON SCHEMA public TO pesisir_app;
ALTER DATABASE pesisir OWNER TO pesisir_app;

\q
```

### STEP 5 — Edit `postgresql.conf` (Listen ke Public)

```bash
# Cek versi yang ke-install (14 atau 16)
ls /etc/postgresql/

# Edit (sesuaikan versi: 14 atau 16)
sudo nano /etc/postgresql/16/main/postgresql.conf
```

Cari & ubah (pakai `Ctrl+W` untuk search):
```conf
listen_addresses = '*'
port = 5432
```

Simpan: `Ctrl+O`, `Enter`, `Ctrl+X`.

### STEP 6 — Edit `pg_hba.conf` (Allow Remote Connection)

```bash
sudo nano /etc/postgresql/16/main/pg_hba.conf
```

Scroll ke bawah, tambahkan baris:
```conf
# Development — allow all IP (HANYA untuk dev!)
host    all             all             0.0.0.0/0               scram-sha-256
host    all             all             ::/0                    scram-sha-256
```

> Ini buka ke semua IP. Aman dipakai untuk development asal password kuat.

Simpan & restart:
```bash
sudo systemctl restart postgresql
sudo systemctl status postgresql
```

### STEP 7 — Buka Firewall (UFW)

```bash
# Pastikan SSH tidak ke-block dulu
sudo ufw allow 22/tcp

# Buka port PostgreSQL
sudo ufw allow 5432/tcp

# Aktifkan firewall (kalau belum)
sudo ufw enable

# Cek status
sudo ufw status
```

> **Penting:** Cek juga **firewall di provider VPS** (DigitalOcean Cloud Firewall, AWS Security Group, GCP Firewall, dll). UFW di OS hanya satu lapis — provider firewall biasanya di lapis lebih atas dan harus dibuka juga.

### STEP 8 — Verifikasi Listening Port

```bash
sudo ss -tlnp | grep 5432
```

Harus muncul:
```
LISTEN  0  244  0.0.0.0:5432  ...  users:(("postgres",...))
```

Kalau masih `127.0.0.1:5432`, berarti `listen_addresses` belum ke-update — ulangi STEP 5 dan restart.

### STEP 9 — Test Koneksi dari Laptop

#### Pakai psql:
```bash
psql -h IP_VPS_ANDA -U pesisir_app -d pesisir
# Masukkan password
```

#### Pakai DBeaver / pgAdmin:

| Field | Value |
|-------|-------|
| Host | `IP_VPS_ANDA` |
| Port | `5432` |
| Database | `pesisir` |
| Username | `pesisir_app` |
| Password | `pesisir!` |
| SSL Mode | `prefer` atau `disable` (untuk dev) |

#### Pakai Laravel `.env`:
```env
DB_CONNECTION=pgsql
DB_HOST=IP_VPS_ANDA
DB_PORT=5432
DB_DATABASE=pesisir
DB_USERNAME=pesisir_app
DB_PASSWORD=FishApp2026!Strong
```

### STEP 10 — Eksekusi DDL Aplikasi

Upload DDL dari laptop ke VPS:
```bash
scp "D:/TEMP FILE/AI/APP_STOCK/04_DDL_POSTGRESQL.sql" user@IP_VPS_ANDA:/tmp/
```

Eksekusi:
```bash
psql -h IP_VPS_ANDA -U pesisir_app -d pesisir -f /tmp/04_DDL_POSTGRESQL.sql
```

Atau langsung dari laptop tanpa upload:
```bash
psql -h IP_VPS_ANDA -U pesisir_app -d pesisir -f "D:/TEMP FILE/AI/APP_STOCK/04_DDL_POSTGRESQL.sql"
```

Verifikasi tabel terbuat:
```bash
psql -h IP_VPS_ANDA -U pesisir_app -d pesisir -c "\dt"
# Harus muncul ~35 tabel
```

---

## ✅ Checklist Singkat

- [v] PostgreSQL terinstall, service running
- [v] DB `pesisir` dibuat
- [v] User `pesisir_app` dengan password kuat
- [v] `listen_addresses = '*'`
- [v] `pg_hba.conf` allow `0.0.0.0/0` dengan `scram-sha-256`
- [v] UFW allow port 5432
- [v] Provider VPS firewall allow port 5432
- [v] `ss -tlnp` show 5432 listen di `0.0.0.0`
- [v] Test koneksi dari laptop berhasil
- [ ] DDL ter-eksekusi (35 tabel ada)

---

## 🚨 Troubleshooting Cepat

### Connection timeout / refused
1. Cek service: `sudo systemctl status postgresql`
2. Cek listen: `sudo ss -tlnp | grep 5432` — harus `0.0.0.0:5432`
3. Cek UFW: `sudo ufw status` — harus allow 5432
4. Cek **provider firewall** (Security Group / Cloud Firewall) — sering lupa!
5. Test dari VPS sendiri: `psql -h localhost -U pesisir_app -d pesisir` — kalau ini gagal, masalah di config PG

### "no pg_hba.conf entry for host..."
- Lupa edit `pg_hba.conf` atau lupa restart → ulangi STEP 6

### "password authentication failed"
- Password salah → reset di psql:
  ```bash
  sudo -u postgres psql -c "ALTER USER pesisir_app WITH PASSWORD 'PasswordBaru!';"
  ```

### "FATAL: role 'pesisir_app' is not permitted to log in"
```sql
ALTER USER pesisir_app WITH LOGIN;
```

---

## 📌 Info Koneksi (Catat di sini)

```
Host     : ___________________
Port     : 5432
Database : pesisir
Username : pesisir_app
Password : ___________________
```

---

## 🔄 Perintah Maintenance Sehari-Hari

```bash
# Restart PostgreSQL setelah ubah config
sudo systemctl restart postgresql

# Lihat log error
sudo tail -f /var/log/postgresql/postgresql-16-main.log

# Lihat aktif koneksi
sudo -u postgres psql -c "SELECT pid, usename, client_addr, state FROM pg_stat_activity;"

# Backup manual cepat
sudo -u postgres pg_dump pesisir > ~/backup_$(date +%F).sql

# Restore dari backup
psql -h IP_VPS -U pesisir_app -d pesisir < backup.sql
```

---

## 💡 Tips Development

1. **Bookmark IP VPS** di SSH config laptop:
   ```
   # ~/.ssh/config
   Host vps-dev
       HostName IP_VPS
       User user
       Port 22
   ```
   Sehingga cukup `ssh vps-dev`.

2. **Simpan password di password manager** (Bitwarden, KeePass) — jangan hardcode di banyak tempat.

3. **Test connection string dulu di DBeaver** sebelum pasang di Laravel — lebih cepat debug.

4. **Saat naik ke production**, ikuti `06_INSTALL_POSTGRESQL_PUBLIC.md` untuk:
   - Whitelist IP (tidak `0.0.0.0/0`)
   - SSL/TLS wajib
   - Fail2ban
   - Backup otomatis
   - Hardening tambahan
