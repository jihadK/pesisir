-- =====================================================================
-- SEED: Contoh row data untuk modul User Auth
-- DEPS: Jalankan SETELAH 04_DDL_POSTGRESQL.sql + 08_DDL_USER_AUTH.sql
-- ASUMSI: tbm_roles (admin/manager/cashier/warehouse/sales) & tbm_warehouses
--          (id 1=GUDANG PUSAT, 2=COLD STORAGE A, 3=CABANG SURABAYA)
--          sudah ada dari seed schema inti.
-- CATATAN: Semua password_hash di bawah adalah bcrypt dari "Password123!"
--          (cost 10) — GANTI sebelum production!
-- =====================================================================

BEGIN;

-- =====================================================================
-- 1. USERS — 6 user dengan beragam role & status
-- =====================================================================
-- Asumsi struktur tbm_users dari 04: id, username, email, password_hash,
-- full_name, phone, role_id, is_active, last_login_at, deleted_at, dst.

INSERT INTO tbm_users (
    username, email, password_hash, full_name, phone, role_id, is_active,
    email_verified_at, failed_login_attempts, locked_until,
    password_changed_at, must_change_password,
    two_factor_enabled, two_factor_secret, two_factor_recovery,
    registration_status, last_login_at
) VALUES
-- 1. Admin utama, 2FA aktif
('admin', 'admin@fishstock.co.id',
 '$2y$10$KIXxqf6oN4m7aOdR3hzC2OQyN8qV0Wd1L5b9mD0cBtHmPvT5zZqXK',
 'Super Admin', '081234567001', (SELECT id FROM tbm_roles WHERE name='admin'),
 TRUE, NOW() - INTERVAL '60 days', 0, NULL,
 NOW() - INTERVAL '30 days', FALSE,
 TRUE, 'JBSWY3DPEHPK3PXP', 'enc:rec1,rec2,rec3,rec4,rec5,rec6,rec7,rec8',
 'active', NOW() - INTERVAL '2 hours'),

-- 2. Manager — must change password (baru di-reset admin)
('budi.manager', 'budi@fishstock.co.id',
 '$2y$10$KIXxqf6oN4m7aOdR3hzC2OQyN8qV0Wd1L5b9mD0cBtHmPvT5zZqXK',
 'Budi Santoso', '081234567002', (SELECT id FROM tbm_roles WHERE name='manager'),
 TRUE, NOW() - INTERVAL '45 days', 0, NULL,
 NOW() - INTERVAL '1 day', TRUE,
 FALSE, NULL, NULL,
 'active', NOW() - INTERVAL '5 hours'),

-- 3. Cashier — normal aktif
('siti.cashier', 'siti@fishstock.co.id',
 '$2y$10$KIXxqf6oN4m7aOdR3hzC2OQyN8qV0Wd1L5b9mD0cBtHmPvT5zZqXK',
 'Siti Aminah', '081234567003', (SELECT id FROM tbm_roles WHERE name='cashier'),
 TRUE, NOW() - INTERVAL '40 days', 0, NULL,
 NOW() - INTERVAL '40 days', FALSE,
 FALSE, NULL, NULL,
 'active', NOW() - INTERVAL '15 minutes'),

-- 4. Warehouse staff — sedang ke-lock karena 5x salah password
('joko.gudang', 'joko@fishstock.co.id',
 '$2y$10$KIXxqf6oN4m7aOdR3hzC2OQyN8qV0Wd1L5b9mD0cBtHmPvT5zZqXK',
 'Joko Wijaya', '081234567004', (SELECT id FROM tbm_roles WHERE name='warehouse'),
 TRUE, NOW() - INTERVAL '20 days', 5, NOW() + INTERVAL '25 minutes',
 NOW() - INTERVAL '20 days', FALSE,
 FALSE, NULL, NULL,
 'active', NOW() - INTERVAL '3 days'),

-- 5. Sales — pending email verification (baru register)
('rina.sales', 'rina@fishstock.co.id',
 '$2y$10$KIXxqf6oN4m7aOdR3hzC2OQyN8qV0Wd1L5b9mD0cBtHmPvT5zZqXK',
 'Rina Pratiwi', '081234567005', (SELECT id FROM tbm_roles WHERE name='sales'),
 TRUE, NULL, 0, NULL,
 NOW() - INTERVAL '2 hours', FALSE,
 FALSE, NULL, NULL,
 'pending', NULL),

-- 6. Sales — suspended (ada pelanggaran)
('andi.sales', 'andi@fishstock.co.id',
 '$2y$10$KIXxqf6oN4m7aOdR3hzC2OQyN8qV0Wd1L5b9mD0cBtHmPvT5zZqXK',
 'Andi Kurniawan', '081234567006', (SELECT id FROM tbm_roles WHERE name='sales'),
 FALSE, NOW() - INTERVAL '90 days', 0, NULL,
 NOW() - INTERVAL '90 days', FALSE,
 FALSE, NULL, NULL,
 'suspended', NOW() - INTERVAL '14 days');

-- =====================================================================
-- 2. USER_PROFILES
-- =====================================================================
INSERT INTO tbm_user_profiles (
    user_id, avatar_url, address, city, province, postal_code,
    birth_date, gender, employee_id, department, position, join_date,
    timezone, language, preferences
) VALUES
((SELECT id FROM tbm_users WHERE username='admin'),
 '/storage/avatars/admin.png', 'Jl. Merdeka No. 1', 'Jakarta Pusat', 'DKI Jakarta', '10110',
 '1985-03-15', 'male', 'EMP-001', 'IT', 'System Administrator', '2020-01-10',
 'Asia/Jakarta', 'id',
 '{"theme":"dark","sidebar":"collapsed","notifications":{"email":true,"browser":true}}'::jsonb),

((SELECT id FROM tbm_users WHERE username='budi.manager'),
 '/storage/avatars/budi.png', 'Jl. Sudirman No. 50', 'Jakarta Selatan', 'DKI Jakarta', '12190',
 '1980-07-22', 'male', 'EMP-002', 'Operations', 'Operations Manager', '2021-03-01',
 'Asia/Jakarta', 'id',
 '{"theme":"light","dashboard_widgets":["sales_today","stock_low","ar_aging"]}'::jsonb),

((SELECT id FROM tbm_users WHERE username='siti.cashier'),
 NULL, 'Jl. Kebon Jeruk No. 12', 'Jakarta Barat', 'DKI Jakarta', '11530',
 '1992-11-08', 'female', 'EMP-003', 'Finance', 'Cashier', '2022-06-15',
 'Asia/Jakarta', 'id', '{}'::jsonb),

((SELECT id FROM tbm_users WHERE username='joko.gudang'),
 NULL, 'Jl. Cakung Cilincing', 'Jakarta Timur', 'DKI Jakarta', '13910',
 '1988-05-30', 'male', 'EMP-004', 'Warehouse', 'Warehouse Staff', '2023-02-01',
 'Asia/Jakarta', 'id', '{}'::jsonb),

((SELECT id FROM tbm_users WHERE username='rina.sales'),
 NULL, 'Jl. Pemuda No. 88', 'Surabaya', 'Jawa Timur', '60271',
 '1995-09-12', 'female', 'EMP-005', 'Sales', 'Sales Executive', '2026-05-01',
 'Asia/Jakarta', 'id', '{}'::jsonb);

-- =====================================================================
-- 3. USER_WAREHOUSES — Multi-cabang access
-- =====================================================================
-- Asumsi tbm_warehouses.id 1, 2, 3 sudah ada
INSERT INTO tbm_user_warehouses (user_id, warehouse_id, access_level, is_default, assigned_by) VALUES
-- admin: admin di semua gudang
((SELECT id FROM tbm_users WHERE username='admin'), 1, 'admin', TRUE,  (SELECT id FROM tbm_users WHERE username='admin')),
((SELECT id FROM tbm_users WHERE username='admin'), 2, 'admin', FALSE, (SELECT id FROM tbm_users WHERE username='admin')),
((SELECT id FROM tbm_users WHERE username='admin'), 3, 'admin', FALSE, (SELECT id FROM tbm_users WHERE username='admin')),
-- manager: write di gudang 1 (default) & 2
((SELECT id FROM tbm_users WHERE username='budi.manager'), 1, 'write', TRUE,  (SELECT id FROM tbm_users WHERE username='admin')),
((SELECT id FROM tbm_users WHERE username='budi.manager'), 2, 'write', FALSE, (SELECT id FROM tbm_users WHERE username='admin')),
-- cashier: read di gudang 1
((SELECT id FROM tbm_users WHERE username='siti.cashier'), 1, 'read',  TRUE,  (SELECT id FROM tbm_users WHERE username='admin')),
-- warehouse: write di gudang 2 (cold storage)
((SELECT id FROM tbm_users WHERE username='joko.gudang'), 2, 'write', TRUE,  (SELECT id FROM tbm_users WHERE username='admin')),
-- sales: read di gudang 3 (Surabaya)
((SELECT id FROM tbm_users WHERE username='rina.sales'), 3, 'read',  TRUE,  (SELECT id FROM tbm_users WHERE username='admin'));

-- =====================================================================
-- 4. USER_PERMISSIONS — Override per-user
-- =====================================================================
-- Skenario:
--   a) Sales rina dapat permission TEMP `so.approve` 7 hari (cover manager cuti)
--   b) Cashier siti DI-DENY `payment.update` (hanya boleh create, tidak edit)
--   c) Warehouse joko diberi extra `transfer.approve` permanen
INSERT INTO tbm_user_permissions (user_id, permission_id, grant_type, granted_by, expires_at, notes) VALUES
((SELECT id FROM tbm_users WHERE username='rina.sales'),
 (SELECT id FROM tbm_permissions WHERE name='so.approve'),
 'allow', (SELECT id FROM tbm_users WHERE username='admin'),
 NOW() + INTERVAL '7 days', 'Cover Budi (manager) cuti 6-13 Mei 2026'),

((SELECT id FROM tbm_users WHERE username='siti.cashier'),
 (SELECT id FROM tbm_permissions WHERE name='payment.update'),
 'deny',  (SELECT id FROM tbm_users WHERE username='admin'),
 NULL, 'Cashier hanya boleh catat payment, edit lewat manager'),

((SELECT id FROM tbm_users WHERE username='joko.gudang'),
 (SELECT id FROM tbm_permissions WHERE name='transfer.approve'),
 'allow', (SELECT id FROM tbm_users WHERE username='admin'),
 NULL, 'Joko PIC mutasi gudang antar cabang');

-- =====================================================================
-- 5. EMAIL_VERIFICATIONS
-- =====================================================================
INSERT INTO tbh_email_verifications (user_id, email, token, token_hash, expires_at, verified_at, ip_address) VALUES
-- admin: sudah verified
((SELECT id FROM tbm_users WHERE username='admin'), 'admin@fishstock.co.id',
 'ev_tok_admin_5d7f8a9b2c3e4f1a', 'sha256:9f2b6c1e0a8d4f7b3e5c1a8d4f7b3e5c1a8d4f7b3e5c1a8d4f7b3e5c1a8d4f7b',
 NOW() - INTERVAL '59 days', NOW() - INTERVAL '60 days' + INTERVAL '5 minutes', '203.0.113.10'),
-- rina: PENDING (baru register, belum klik link)
((SELECT id FROM tbm_users WHERE username='rina.sales'), 'rina@fishstock.co.id',
 'ev_tok_rina_a1b2c3d4e5f6g7h8', 'sha256:1a2b3c4d5e6f7a8b9c0d1e2f3a4b5c6d7e8f9a0b1c2d3e4f5a6b7c8d9e0f1a2b',
 NOW() + INTERVAL '22 hours', NULL, '202.62.16.45');

-- =====================================================================
-- 6. PASSWORD_RESETS
-- =====================================================================
INSERT INTO tbh_password_resets (user_id, email, token_hash, expires_at, used_at, ip_address, user_agent) VALUES
-- budi: token sudah dipakai (yang membuat must_change_password = TRUE)
((SELECT id FROM tbm_users WHERE username='budi.manager'), 'budi@fishstock.co.id',
 'sha256:bd1a2c3e4f5d6789bd1a2c3e4f5d6789bd1a2c3e4f5d6789bd1a2c3e4f5d6789',
 NOW() - INTERVAL '23 hours', NOW() - INTERVAL '23 hours' + INTERVAL '8 minutes',
 '203.0.113.10', 'Mozilla/5.0 (Windows NT 10.0) Chrome/120.0'),
-- joko: token AKTIF (belum dipakai)
((SELECT id FROM tbm_users WHERE username='joko.gudang'), 'joko@fishstock.co.id',
 'sha256:jk9a8b7c6d5e4f3a9a8b7c6d5e4f3a9a8b7c6d5e4f3a9a8b7c6d5e4f3a9a8b7c',
 NOW() + INTERVAL '50 minutes', NULL,
 '180.244.10.22', 'Mozilla/5.0 (Linux, Android 13) Chrome/120.0');

-- =====================================================================
-- 7. LOGIN_ATTEMPTS
-- =====================================================================
INSERT INTO tbh_login_attempts (user_id, email, ip_address, user_agent, success, failure_reason, attempted_at) VALUES
-- admin: sukses (login terakhir)
((SELECT id FROM tbm_users WHERE username='admin'), 'admin@fishstock.co.id',
 '203.0.113.10', 'Mozilla/5.0 (Macintosh) Chrome/120.0', TRUE, NULL, NOW() - INTERVAL '2 hours'),
-- siti: sukses
((SELECT id FROM tbm_users WHERE username='siti.cashier'), 'siti@fishstock.co.id',
 '202.62.16.45', 'Mozilla/5.0 (Windows NT 10.0) Edge/120.0', TRUE, NULL, NOW() - INTERVAL '15 minutes'),
-- joko: 5x gagal beruntun → trigger lockout
((SELECT id FROM tbm_users WHERE username='joko.gudang'), 'joko@fishstock.co.id',
 '180.244.10.22', 'Mozilla/5.0 (Linux, Android 13) Chrome/120.0', FALSE, 'invalid_password', NOW() - INTERVAL '6 minutes'),
((SELECT id FROM tbm_users WHERE username='joko.gudang'), 'joko@fishstock.co.id',
 '180.244.10.22', 'Mozilla/5.0 (Linux, Android 13) Chrome/120.0', FALSE, 'invalid_password', NOW() - INTERVAL '5 minutes'),
((SELECT id FROM tbm_users WHERE username='joko.gudang'), 'joko@fishstock.co.id',
 '180.244.10.22', 'Mozilla/5.0 (Linux, Android 13) Chrome/120.0', FALSE, 'invalid_password', NOW() - INTERVAL '4 minutes'),
((SELECT id FROM tbm_users WHERE username='joko.gudang'), 'joko@fishstock.co.id',
 '180.244.10.22', 'Mozilla/5.0 (Linux, Android 13) Chrome/120.0', FALSE, 'invalid_password', NOW() - INTERVAL '3 minutes'),
((SELECT id FROM tbm_users WHERE username='joko.gudang'), 'joko@fishstock.co.id',
 '180.244.10.22', 'Mozilla/5.0 (Linux, Android 13) Chrome/120.0', FALSE, 'account_locked', NOW() - INTERVAL '2 minutes'),
-- attempt dari email tidak terdaftar (potensi probing)
(NULL, 'random@evil.com',
 '45.142.10.99', 'curl/8.0', FALSE, 'user_not_found', NOW() - INTERVAL '1 hour'),
(NULL, 'admin@fishstock.co.id',
 '45.142.10.99', 'python-requests/2.31', FALSE, 'invalid_password', NOW() - INTERVAL '50 minutes');

-- =====================================================================
-- 8. USER_SESSIONS
-- =====================================================================
INSERT INTO tbm_user_sessions (id, user_id, ip_address, user_agent, device_type, device_name, location, payload, last_activity, expires_at) VALUES
('sess_admin_macbook_8a7b6c5d4e3f2a1b',
 (SELECT id FROM tbm_users WHERE username='admin'),
 '203.0.113.10', 'Mozilla/5.0 (Macintosh) Chrome/120.0',
 'desktop', 'MacBook Pro - Chrome', 'Jakarta, ID',
 'eyJfdG9rZW4iOiJYWVoxMjMifQ==',
 NOW() - INTERVAL '2 minutes', NOW() + INTERVAL '7 days'),

('sess_siti_chrome_b9c8d7e6f5a4b3c2',
 (SELECT id FROM tbm_users WHERE username='siti.cashier'),
 '202.62.16.45', 'Mozilla/5.0 (Windows NT 10.0) Edge/120.0',
 'desktop', 'Office PC - Edge', 'Jakarta, ID',
 'eyJfdG9rZW4iOiJBQkM0NTYifQ==',
 NOW() - INTERVAL '5 minutes', NOW() + INTERVAL '7 days'),

('sess_budi_iphone_c1d2e3f4a5b6c7d8',
 (SELECT id FROM tbm_users WHERE username='budi.manager'),
 '114.10.22.50', 'Mozilla/5.0 (iPhone, iOS 17.2)',
 'mobile', 'iPhone 14 - Safari', 'Bandung, ID',
 'eyJfdG9rZW4iOiJERjc4OSJ9',
 NOW() - INTERVAL '5 hours', NOW() + INTERVAL '6 days');

-- =====================================================================
-- 9. PERSONAL_ACCESS_TOKENS (Sanctum)
-- =====================================================================
INSERT INTO tbm_personal_access_tokens (tokenable_type, tokenable_id, name, token, abilities, last_used_at, expires_at) VALUES
('App\\Models\\User', (SELECT id FROM tbm_users WHERE username='admin'),
 'Mobile App - Admin',
 'a1b2c3d4e5f6789012345678901234567890abcdef0123456789abcdef012345',
 '["*"]',
 NOW() - INTERVAL '1 hour', NOW() + INTERVAL '30 days'),

('App\\Models\\User', (SELECT id FROM tbm_users WHERE username='budi.manager'),
 'POS Tablet',
 'b2c3d4e5f6789012345678901234567890abcdef0123456789abcdef0123456a',
 '["so.view","so.create","do.view","do.ship","inventory.view_stock"]',
 NOW() - INTERVAL '4 hours', NOW() + INTERVAL '90 days'),

('App\\Models\\User', (SELECT id FROM tbm_users WHERE username='siti.cashier'),
 'Cashier Terminal',
 'c3d4e5f6789012345678901234567890abcdef0123456789abcdef0123456ab1',
 '["invoice.view","invoice.create","payment.view","payment.create"]',
 NOW() - INTERVAL '20 minutes', NOW() + INTERVAL '7 days');

-- =====================================================================
-- 10. TWO_FACTOR_CODES
-- =====================================================================
INSERT INTO tbh_two_factor_codes (user_id, code_hash, purpose, expires_at, used_at, attempts, ip_address) VALUES
-- admin: kode login tadi sudah dipakai
((SELECT id FROM tbm_users WHERE username='admin'),
 'sha256:2fa_admin_used_a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6',
 'login', NOW() - INTERVAL '2 hours' + INTERVAL '5 minutes',
 NOW() - INTERVAL '2 hours' + INTERVAL '1 minute', 1, '203.0.113.10'),
-- admin: kode aktif (untuk approve sensitive action)
((SELECT id FROM tbm_users WHERE username='admin'),
 'sha256:2fa_admin_active_b2c3d4e5f6a7b2c3d4e5f6a7b2c3d4e5f6a7b2c3d4e5',
 'sensitive_action', NOW() + INTERVAL '4 minutes', NULL, 0, '203.0.113.10');

-- =====================================================================
-- 11. USER_ACTIVITY_LOGS
-- =====================================================================
INSERT INTO tbh_user_activity_logs (user_id, activity_type, description, metadata, ip_address, user_agent, created_date) VALUES
((SELECT id FROM tbm_users WHERE username='admin'),
 'login', 'User berhasil login dengan 2FA',
 '{"method":"password+2fa","session_id":"sess_admin_macbook_8a7b6c5d4e3f2a1b"}'::jsonb,
 '203.0.113.10', 'Mozilla/5.0 (Macintosh) Chrome/120.0', NOW() - INTERVAL '2 hours'),

((SELECT id FROM tbm_users WHERE username='admin'),
 'user.create', 'Membuat user baru: rina.sales',
 '{"target_user_id":5,"target_username":"rina.sales","role":"sales"}'::jsonb,
 '203.0.113.10', 'Mozilla/5.0 (Macintosh) Chrome/120.0', NOW() - INTERVAL '2 hours' + INTERVAL '10 minutes'),

((SELECT id FROM tbm_users WHERE username='admin'),
 'permission.grant', 'Granting temporary so.approve ke rina.sales (7 hari)',
 '{"target_user_id":5,"permission":"so.approve","expires_at":"2026-05-13","reason":"cover manager cuti"}'::jsonb,
 '203.0.113.10', 'Mozilla/5.0 (Macintosh) Chrome/120.0', NOW() - INTERVAL '1 hour'),

((SELECT id FROM tbm_users WHERE username='budi.manager'),
 'password.reset', 'Password di-reset oleh admin',
 '{"reset_by":1,"forced_change":true}'::jsonb,
 NULL, NULL, NOW() - INTERVAL '1 day'),

((SELECT id FROM tbm_users WHERE username='siti.cashier'),
 'invoice.create', 'Membuat invoice INV/2026/05/00042',
 '{"invoice_id":42,"invoice_number":"INV/2026/05/00042","customer_id":15,"total":2750000}'::jsonb,
 '202.62.16.45', 'Mozilla/5.0 (Windows NT 10.0) Edge/120.0', NOW() - INTERVAL '12 minutes'),

(NULL, 'security.suspicious_login',
 'Multiple failed login dari IP yang sama (probing)',
 '{"ip":"45.142.10.99","attempts":2,"emails":["random@evil.com","admin@fishstock.co.id"]}'::jsonb,
 '45.142.10.99', 'python-requests/2.31', NOW() - INTERVAL '50 minutes');

COMMIT;

-- =====================================================================
-- VERIFIKASI
-- =====================================================================
-- Cek permission user (admin harus TRUE untuk apapun):
--   SELECT fn_user_has_permission((SELECT id FROM tbm_users WHERE username='admin'), 'po.approve');
-- Cek override deny cashier:
--   SELECT fn_user_has_permission((SELECT id FROM tbm_users WHERE username='siti.cashier'), 'payment.update');  -- FALSE
--   SELECT fn_user_has_permission((SELECT id FROM tbm_users WHERE username='siti.cashier'), 'payment.create'); -- TRUE
-- Cek override allow temporary sales:
--   SELECT fn_user_has_permission((SELECT id FROM tbm_users WHERE username='rina.sales'), 'so.approve');       -- TRUE (sampai expires)
-- Lihat semua permission efektif user:
--   SELECT * FROM v_user_permissions WHERE user_id = (SELECT id FROM tbm_users WHERE username='siti.cashier')
--     ORDER BY module, permission_name;
-- User yang sedang ke-lock:
--   SELECT id, username, locked_until, failed_login_attempts FROM tbm_users WHERE locked_until > NOW();
-- Top IP gagal login 24 jam terakhir:
--   SELECT ip_address, COUNT(*) FROM tbh_login_attempts
--     WHERE success=FALSE AND attempted_at > NOW() - INTERVAL '24 hours'
--     GROUP BY ip_address ORDER BY 2 DESC;
