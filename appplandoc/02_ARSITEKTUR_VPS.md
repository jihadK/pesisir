# Arsitektur Deployment VPS — Laravel + PostgreSQL

## 📊 Spesifikasi VPS
| Resource | Kapasitas |
|----------|-----------|
| vCPU | 2 Core |
| RAM | 4 GB |
| Storage | 60 GB |
| Public Access | Ya (HTTPS) |

## 🏗️ Diagram Arsitektur

```
                    🌐 INTERNET (Public Users)
                              │ HTTPS (443)
                              ▼
            ┌──────────────────────────────────────┐
            │      Cloudflare (FREE)               │
            │  • DNS / CDN / DDoS / WAF            │
            └────────────────┬─────────────────────┘
                             │ HTTPS
                             ▼
╔══════════════════════════════════════════════════════════╗
║         VPS (2 vCPU / 4 GB / 60 GB)                      ║
║         Ubuntu 22.04 LTS                                 ║
║                                                          ║
║   UFW Firewall (allow: 22, 80, 443) + Fail2ban          ║
║                       │                                  ║
║                       ▼                                  ║
║   ┌──────────────────────────────────────────────────┐  ║
║   │  Nginx (Reverse Proxy + Web Server)    ~80 MB    │  ║
║   │  vhost: app.domain.com  → /var/www/frontend      │  ║
║   │  vhost: api.domain.com  → /var/www/backend       │  ║
║   └────────────┬─────────────────────┬───────────────┘  ║
║                │                     │                   ║
║                ▼                     ▼                   ║
║   ┌──────────────────┐  ┌──────────────────┐            ║
║   │ PHP-FPM frontend │  │ PHP-FPM backend  │            ║
║   │ pool: ondemand   │  │ pool: dynamic    │            ║
║   │ ~400 MB          │  │ ~600 MB          │            ║
║   │ Laravel Frontend │  │ Laravel Backend  │            ║
║   │ (Blade views)    │  │ (REST API)       │            ║
║   └──────────────────┘  └────────┬─────────┘            ║
║                                  │                       ║
║                                  ▼                       ║
║   ┌──────────────────────────────────────────────────┐  ║
║   │ Redis 7 (cache + queue + session)      ~256 MB   │  ║
║   └──────────────────────────────────────────────────┘  ║
║   ┌──────────────────────────────────────────────────┐  ║
║   │ PostgreSQL 16 (listen 127.0.0.1)        ~1.2 GB  │  ║
║   └──────────────────────────────────────────────────┘  ║
║   ┌──────────────────────────────────────────────────┐  ║
║   │ Supervisor: queue:work + schedule:work           │  ║
║   └──────────────────────────────────────────────────┘  ║
║   ┌──────────────────────────────────────────────────┐  ║
║   │ Backup: pg_dump nightly → S3/Backblaze           │  ║
║   └──────────────────────────────────────────────────┘  ║
╚══════════════════════════════════════════════════════════╝
```

## 🧠 Alokasi RAM (Total 4 GB)
| Komponen | Alokasi |
|----------|---------|
| OS Ubuntu | ~400 MB |
| Nginx | ~80 MB |
| PHP-FPM Frontend (8 workers × 50 MB) | ~400 MB |
| PHP-FPM Backend (12 workers × 50 MB) | ~600 MB |
| PostgreSQL | ~1.2 GB |
| Redis | ~256 MB |
| Queue worker | ~150 MB |
| Monitoring | ~100 MB |
| Buffer/cache OS | ~800 MB |

**Wajib swap 2 GB** sebagai pengaman OOM.

## 🌐 Strategi Domain
- `app.domain.com` → Frontend Laravel
- `api.domain.com` → Backend Laravel (REST API)
- Komunikasi internal frontend ↔ backend via `127.0.0.1` untuk hemat overhead

## 🔒 Security Layers
1. **Network**: UFW (22/80/443 only), Fail2ban
2. **Application**: Laravel built-in (CSRF, validation, Eloquent ORM, Blade auto-escape), Sanctum/JWT auth
3. **Nginx Headers**: HSTS, X-Frame-Options, CSP, Referrer-Policy
4. **SSL**: Let's Encrypt (Certbot) atau Cloudflare Origin Cert
5. **Database**: listen localhost only, user terpisah (bukan superuser), password kuat
6. **Cookies**: `Secure; HttpOnly; SameSite=Strict` via `proxy_cookie_flags`

## 🚀 Stack Final
| Layer | Software |
|-------|----------|
| OS | Ubuntu Server 22.04 LTS |
| Reverse Proxy | Nginx 1.24+ |
| PHP | PHP-FPM 8.3 |
| Framework | Laravel 11 |
| Database | PostgreSQL 16 |
| Cache/Queue | Redis 7 |
| SSL | Certbot |
| Process Mgr | Supervisor |
| Firewall | UFW + Fail2ban |
| Monitoring | Netdata |
| Backup | pg_dump + rclone → S3 |
| CDN/DDoS | Cloudflare |

## 📈 Kapasitas
- ~50-100 concurrent users
- ~500-1000 req/menit
- DB <5 GB

**Jalur upgrade:** vertical scale → pisah DB/Redis ke managed → horizontal scale dengan load balancer.
