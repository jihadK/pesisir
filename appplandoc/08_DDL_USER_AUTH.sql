-- =====================================================================
-- MODULE: User Authentication & Authorization
-- DESC  : Extension untuk database fish_stock_sales
--         Menambah: permission granular, multi-cabang, register/login flow,
--                   2FA, session management, audit
-- DEPS  : Jalankan SETELAH 04_DDL_POSTGRESQL.sql
-- =====================================================================

-- =====================================================================
-- 1. UPDATE TABEL `tbm_users` — Tambah kolom auth modern
-- =====================================================================

ALTER TABLE tbm_users
    ADD COLUMN email_verified_at      TIMESTAMPTZ,
    ADD COLUMN remember_token         VARCHAR(100),
    ADD COLUMN failed_login_attempts  INT NOT NULL DEFAULT 0,
    ADD COLUMN locked_until           TIMESTAMPTZ,
    ADD COLUMN password_changed_at    TIMESTAMPTZ,
    ADD COLUMN must_change_password   BOOLEAN NOT NULL DEFAULT FALSE,
    ADD COLUMN two_factor_enabled     BOOLEAN NOT NULL DEFAULT FALSE,
    ADD COLUMN two_factor_secret      TEXT,
    ADD COLUMN two_factor_recovery    TEXT,
    ADD COLUMN registration_status    VARCHAR(20) NOT NULL DEFAULT 'active'
        CHECK (registration_status IN ('pending','active','suspended','banned'));

CREATE INDEX idx_users_email_verified ON tbm_users(email_verified_at);
CREATE INDEX idx_users_locked         ON tbm_users(locked_until) WHERE locked_until IS NOT NULL;

-- =====================================================================
-- 2. PERMISSIONS — Granular access control
-- =====================================================================

CREATE TABLE tbm_permissions (
    id          BIGSERIAL PRIMARY KEY,
    name        VARCHAR(100) NOT NULL UNIQUE,
    display_name VARCHAR(150) NOT NULL,
    module      VARCHAR(50) NOT NULL,
    description VARCHAR(255),
    is_active   BOOLEAN NOT NULL DEFAULT TRUE,
    created_date  TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX idx_permissions_module ON tbm_permissions(module);

-- =====================================================================
-- 3. ROLE_PERMISSIONS
-- =====================================================================

CREATE TABLE tbm_role_permissions (
    id            BIGSERIAL PRIMARY KEY,
    role_id       BIGINT NOT NULL REFERENCES tbm_roles(id) ON DELETE CASCADE,
    permission_id BIGINT NOT NULL REFERENCES tbm_permissions(id) ON DELETE CASCADE,
    granted_at    TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    granted_by    BIGINT REFERENCES tbm_users(id) ON DELETE SET NULL,
    CONSTRAINT uq_role_perm UNIQUE (role_id, permission_id)
);
CREATE INDEX idx_rp_role ON tbm_role_permissions(role_id);
CREATE INDEX idx_rp_perm ON tbm_role_permissions(permission_id);

-- =====================================================================
-- 4. USER_PERMISSIONS — Per-user override
-- =====================================================================

CREATE TABLE tbm_user_permissions (
    id            BIGSERIAL PRIMARY KEY,
    user_id       BIGINT NOT NULL REFERENCES tbm_users(id) ON DELETE CASCADE,
    permission_id BIGINT NOT NULL REFERENCES tbm_permissions(id) ON DELETE CASCADE,
    grant_type    VARCHAR(10) NOT NULL DEFAULT 'allow'
                   CHECK (grant_type IN ('allow','deny')),
    granted_at    TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    granted_by    BIGINT REFERENCES tbm_users(id) ON DELETE SET NULL,
    expires_at    TIMESTAMPTZ,
    notes         VARCHAR(255),
    CONSTRAINT uq_user_perm UNIQUE (user_id, permission_id)
);
CREATE INDEX idx_up_user ON tbm_user_permissions(user_id);
CREATE INDEX idx_up_perm ON tbm_user_permissions(permission_id);

-- =====================================================================
-- 5. USER_WAREHOUSES — Multi-cabang access
-- =====================================================================

CREATE TABLE tbm_user_warehouses (
    id            BIGSERIAL PRIMARY KEY,
    user_id       BIGINT NOT NULL REFERENCES tbm_users(id) ON DELETE CASCADE,
    warehouse_id  BIGINT NOT NULL REFERENCES tbm_warehouses(id) ON DELETE CASCADE,
    access_level  VARCHAR(20) NOT NULL DEFAULT 'read'
                   CHECK (access_level IN ('read','write','admin')),
    is_default    BOOLEAN NOT NULL DEFAULT FALSE,
    assigned_at   TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    assigned_by   BIGINT REFERENCES tbm_users(id) ON DELETE SET NULL,
    CONSTRAINT uq_user_wh UNIQUE (user_id, warehouse_id)
);
CREATE INDEX idx_uw_user ON tbm_user_warehouses(user_id);
CREATE INDEX idx_uw_wh   ON tbm_user_warehouses(warehouse_id);
CREATE UNIQUE INDEX uq_user_default_wh
    ON tbm_user_warehouses(user_id) WHERE is_default = TRUE;

-- =====================================================================
-- 6. USER_PROFILES
-- =====================================================================

CREATE TABLE tbm_user_profiles (
    id              BIGSERIAL PRIMARY KEY,
    user_id         BIGINT NOT NULL UNIQUE REFERENCES tbm_users(id) ON DELETE CASCADE,
    avatar_url      VARCHAR(255),
    address         TEXT,
    city            VARCHAR(100),
    province        VARCHAR(100),
    postal_code     VARCHAR(10),
    country         VARCHAR(50) DEFAULT 'Indonesia',
    birth_date      DATE,
    gender          VARCHAR(10) CHECK (gender IN ('male','female','other')),
    employee_id     VARCHAR(30),
    department      VARCHAR(50),
    position        VARCHAR(100),
    join_date       DATE,
    timezone        VARCHAR(50) DEFAULT 'Asia/Jakarta',
    language        VARCHAR(10) DEFAULT 'id',
    preferences     JSONB DEFAULT '{}',
    created_date      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_date      TIMESTAMPTZ
);

CREATE TRIGGER trg_user_profiles_updated_date
    BEFORE UPDATE ON tbm_user_profiles
    FOR EACH ROW EXECUTE FUNCTION trg_set_updated_date();

-- =====================================================================
-- 7. EMAIL VERIFICATIONS
-- =====================================================================

CREATE TABLE tbh_email_verifications (
    id          BIGSERIAL PRIMARY KEY,
    user_id     BIGINT NOT NULL REFERENCES tbm_users(id) ON DELETE CASCADE,
    email       VARCHAR(100) NOT NULL,
    token       VARCHAR(100) NOT NULL UNIQUE,
    token_hash  VARCHAR(255) NOT NULL,
    expires_at  TIMESTAMPTZ NOT NULL,
    verified_at TIMESTAMPTZ,
    ip_address  INET,
    created_date  TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX idx_ev_user    ON tbh_email_verifications(user_id);
CREATE INDEX idx_ev_token   ON tbh_email_verifications(token);
CREATE INDEX idx_ev_expires ON tbh_email_verifications(expires_at) WHERE verified_at IS NULL;

-- =====================================================================
-- 8. PASSWORD RESETS
-- =====================================================================

CREATE TABLE tbh_password_resets (
    id          BIGSERIAL PRIMARY KEY,
    user_id     BIGINT REFERENCES tbm_users(id) ON DELETE CASCADE,
    email       VARCHAR(100) NOT NULL,
    token_hash  VARCHAR(255) NOT NULL,
    expires_at  TIMESTAMPTZ NOT NULL,
    used_at     TIMESTAMPTZ,
    ip_address  INET,
    user_agent  VARCHAR(255),
    created_date  TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX idx_pr_email   ON tbh_password_resets(email);
CREATE INDEX idx_pr_expires ON tbh_password_resets(expires_at) WHERE used_at IS NULL;

-- =====================================================================
-- 9. LOGIN ATTEMPTS
-- =====================================================================

CREATE TABLE tbh_login_attempts (
    id            BIGSERIAL PRIMARY KEY,
    user_id       BIGINT REFERENCES tbm_users(id) ON DELETE SET NULL,
    email         VARCHAR(100),
    ip_address    INET NOT NULL,
    user_agent    VARCHAR(255),
    success       BOOLEAN NOT NULL,
    failure_reason VARCHAR(100),
    attempted_at  TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX idx_la_user_date  ON tbh_login_attempts(user_id, attempted_at DESC);
CREATE INDEX idx_la_ip_date    ON tbh_login_attempts(ip_address, attempted_at DESC);
CREATE INDEX idx_la_email_date ON tbh_login_attempts(email, attempted_at DESC);

-- =====================================================================
-- 10. USER SESSIONS
-- =====================================================================

CREATE TABLE tbm_user_sessions (
    id              VARCHAR(100) PRIMARY KEY,
    user_id         BIGINT NOT NULL REFERENCES tbm_users(id) ON DELETE CASCADE,
    ip_address      INET,
    user_agent      VARCHAR(255),
    device_type     VARCHAR(20),
    device_name     VARCHAR(100),
    location        VARCHAR(100),
    payload         TEXT,
    last_activity   TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    created_date      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    expires_at      TIMESTAMPTZ
);
CREATE INDEX idx_us_user      ON tbm_user_sessions(user_id);
CREATE INDEX idx_us_activity  ON tbm_user_sessions(last_activity DESC);

-- =====================================================================
-- 11. PERSONAL ACCESS TOKENS (Laravel Sanctum)
-- =====================================================================

CREATE TABLE tbm_personal_access_tokens (
    id              BIGSERIAL PRIMARY KEY,
    tokenable_type  VARCHAR(100) NOT NULL DEFAULT 'App\\Models\\User',
    tokenable_id    BIGINT NOT NULL,
    name            VARCHAR(100) NOT NULL,
    token           VARCHAR(64) NOT NULL UNIQUE,
    abilities       TEXT,
    last_used_at    TIMESTAMPTZ,
    expires_at      TIMESTAMPTZ,
    created_date      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_date      TIMESTAMPTZ
);
CREATE INDEX idx_pat_tokenable ON tbm_personal_access_tokens(tokenable_type, tokenable_id);
CREATE INDEX idx_pat_token     ON tbm_personal_access_tokens(token);

-- =====================================================================
-- 12. TWO_FACTOR_CODES
-- =====================================================================

CREATE TABLE tbh_two_factor_codes (
    id          BIGSERIAL PRIMARY KEY,
    user_id     BIGINT NOT NULL REFERENCES tbm_users(id) ON DELETE CASCADE,
    code_hash   VARCHAR(255) NOT NULL,
    purpose     VARCHAR(20) NOT NULL DEFAULT 'login'
                 CHECK (purpose IN ('login','sensitive_action','email_change','password_change')),
    expires_at  TIMESTAMPTZ NOT NULL,
    used_at     TIMESTAMPTZ,
    attempts    INT NOT NULL DEFAULT 0,
    ip_address  INET,
    created_date  TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX idx_2fa_user    ON tbh_two_factor_codes(user_id, created_date DESC);
CREATE INDEX idx_2fa_expires ON tbh_two_factor_codes(expires_at) WHERE used_at IS NULL;

-- =====================================================================
-- 13. USER ACTIVITY LOGS
-- =====================================================================

CREATE TABLE tbh_user_activity_logs (
    id          BIGSERIAL PRIMARY KEY,
    user_id     BIGINT REFERENCES tbm_users(id) ON DELETE SET NULL,
    activity_type VARCHAR(50) NOT NULL,
    description VARCHAR(255),
    metadata    JSONB,
    ip_address  INET,
    user_agent  VARCHAR(255),
    created_date  TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX idx_ual_user_date ON tbh_user_activity_logs(user_id, created_date DESC);
CREATE INDEX idx_ual_type      ON tbh_user_activity_logs(activity_type);

-- =====================================================================
-- 14. SEED DATA — Permissions
-- =====================================================================

INSERT INTO tbm_permissions (name, display_name, module, description) VALUES
-- Master Data
('tbm_users.view','View Users','master','Lihat daftar user'),
('tbm_users.create','Create User','master','Tambah user baru'),
('tbm_users.update','Update User','master','Edit data user'),
('tbm_users.delete','Delete User','master','Hapus user'),
('tbm_users.manage_roles','Manage User Roles','master','Assign/ubah role user'),
('tbm_roles.view','View Roles','master','Lihat tbm_roles'),
('tbm_roles.create','Create Role','master','Buat role baru'),
('tbm_roles.update','Update Role','master','Edit role'),
('tbm_roles.delete','Delete Role','master','Hapus role'),
('tbm_roles.manage_permissions','Manage Permissions','master','Assign permission ke role'),
('tbm_products.view','View Products','master','Lihat produk'),
('tbm_products.create','Create Product','master','Tambah produk'),
('tbm_products.update','Update Product','master','Edit produk'),
('tbm_products.delete','Delete Product','master','Hapus produk'),
('tbm_suppliers.view','View Suppliers','master','Lihat supplier'),
('tbm_suppliers.create','Create Supplier','master','Tambah supplier'),
('tbm_suppliers.update','Update Supplier','master','Edit supplier'),
('tbm_suppliers.delete','Delete Supplier','master','Hapus supplier'),
('tbm_customers.view','View Customers','master','Lihat customer'),
('tbm_customers.create','Create Customer','master','Tambah customer'),
('tbm_customers.update','Update Customer','master','Edit customer'),
('tbm_customers.delete','Delete Customer','master','Hapus customer'),
('tbm_warehouses.view','View Warehouses','master','Lihat gudang'),
('tbm_warehouses.create','Create Warehouse','master','Tambah gudang'),
('tbm_warehouses.update','Update Warehouse','master','Edit gudang'),
('tbm_warehouses.delete','Delete Warehouse','master','Hapus gudang'),
-- Inventory
('inventory.view_stock','View Stock','inventory','Lihat saldo stock'),
('inventory.stock_card','View Stock Card','inventory','Lihat kartu stock'),
('po.view','View PO','inventory','Lihat Purchase Order'),
('po.create','Create PO','inventory','Buat PO'),
('po.update','Update PO','inventory','Edit PO'),
('po.approve','Approve PO','inventory','Approve PO'),
('po.cancel','Cancel PO','inventory','Batalkan PO'),
('grn.view','View GRN','inventory','Lihat penerimaan'),
('grn.create','Create GRN','inventory','Buat penerimaan'),
('grn.update','Update GRN','inventory','Edit penerimaan'),
('transfer.view','View Transfer','inventory','Lihat mutasi gudang'),
('transfer.create','Create Transfer','inventory','Buat mutasi'),
('transfer.approve','Approve Transfer','inventory','Approve mutasi'),
('opname.view','View Stock Opname','inventory','Lihat opname'),
('opname.create','Create Opname','inventory','Buat opname'),
('opname.approve','Approve Opname','inventory','Approve & adjust opname'),
-- Sales
('so.view','View Sales Order','sales','Lihat SO'),
('so.create','Create SO','sales','Buat SO'),
('so.update','Update SO','sales','Edit SO'),
('so.approve','Approve SO','sales','Approve SO'),
('so.cancel','Cancel SO','sales','Batalkan SO'),
('do.view','View Delivery Order','sales','Lihat DO'),
('do.create','Create DO','sales','Buat DO'),
('do.update','Update DO','sales','Edit DO'),
('do.ship','Ship DO','sales','Tandai DO terkirim'),
('return.view','View Sales Return','sales','Lihat retur'),
('return.create','Create Return','sales','Buat retur'),
('return.approve','Approve Return','sales','Approve retur'),
-- Invoicing
('invoice.view','View Invoice','invoice','Lihat invoice'),
('invoice.create','Create Invoice','invoice','Buat invoice'),
('invoice.update','Update Invoice','invoice','Edit invoice'),
('invoice.cancel','Cancel Invoice','invoice','Batalkan invoice'),
('invoice.void','Void Invoice','invoice','Void invoice'),
('payment.view','View Payment','invoice','Lihat pembayaran'),
('payment.create','Create Payment','invoice','Catat pembayaran'),
('payment.update','Update Payment','invoice','Edit pembayaran'),
('payment.approve','Approve Payment','invoice','Approve pembayaran'),
-- Reports
('report.stock','Stock Report','report','Laporan stock'),
('report.sales','Sales Report','report','Laporan penjualan'),
('report.ar','AR Aging Report','report','Laporan piutang'),
('report.profit','Profit Report','report','Laporan profit'),
('report.export','Export Report','report','Export laporan PDF/Excel'),
-- System
('settings.view','View Settings','system','Lihat pengaturan'),
('settings.update','Update Settings','system','Ubah pengaturan'),
('audit.view','View Audit Log','system','Lihat audit log');

-- =====================================================================
-- 15. SEED DATA — Assign Permissions ke Roles
-- =====================================================================

-- ADMIN: semua
INSERT INTO tbm_role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM tbm_roles r CROSS JOIN tbm_permissions p WHERE r.name = 'admin';

-- MANAGER: semua kecuali user/role management
INSERT INTO tbm_role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM tbm_roles r CROSS JOIN tbm_permissions p
WHERE r.name = 'manager'
  AND p.module IN ('master','inventory','sales','invoice','report')
  AND p.name NOT IN ('tbm_users.delete','tbm_roles.delete','tbm_roles.manage_permissions','tbm_users.manage_roles');

-- CASHIER: invoicing + view sales
INSERT INTO tbm_role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM tbm_roles r CROSS JOIN tbm_permissions p
WHERE r.name = 'cashier'
  AND p.name IN (
    'invoice.view','invoice.create','invoice.update',
    'payment.view','payment.create',
    'so.view','do.view','tbm_customers.view','tbm_products.view',
    'report.ar','report.sales');

-- WAREHOUSE: inventory only
INSERT INTO tbm_role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM tbm_roles r CROSS JOIN tbm_permissions p
WHERE r.name = 'warehouse'
  AND p.module = 'inventory'
  AND p.name NOT IN ('po.approve','transfer.approve','opname.approve');

INSERT INTO tbm_role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM tbm_roles r CROSS JOIN tbm_permissions p
WHERE r.name = 'warehouse'
  AND p.name IN ('tbm_products.view','tbm_warehouses.view','tbm_suppliers.view','tbm_customers.view');

-- SALES: sales + view master
INSERT INTO tbm_role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM tbm_roles r CROSS JOIN tbm_permissions p
WHERE r.name = 'sales'
  AND (p.module = 'sales'
       OR p.name IN ('tbm_products.view','tbm_customers.view','tbm_customers.create','tbm_customers.update',
                     'inventory.view_stock','report.sales'))
  AND p.name NOT IN ('so.approve','return.approve','so.cancel');

-- =====================================================================
-- 16. FUNCTION — Cek permission user
-- =====================================================================

CREATE OR REPLACE FUNCTION fn_user_has_permission(
    p_user_id    BIGINT,
    p_permission VARCHAR
) RETURNS BOOLEAN AS $$
DECLARE
    v_has BOOLEAN;
BEGIN
    -- Cek explicit deny dulu
    SELECT TRUE INTO v_has
    FROM tbm_user_permissions up
    JOIN tbm_permissions p ON p.id = up.permission_id
    WHERE up.user_id = p_user_id
      AND p.name = p_permission
      AND up.grant_type = 'deny'
      AND (up.expires_at IS NULL OR up.expires_at > NOW())
    LIMIT 1;
    IF v_has THEN RETURN FALSE; END IF;

    -- Explicit allow
    SELECT TRUE INTO v_has
    FROM tbm_user_permissions up
    JOIN tbm_permissions p ON p.id = up.permission_id
    WHERE up.user_id = p_user_id
      AND p.name = p_permission
      AND up.grant_type = 'allow'
      AND (up.expires_at IS NULL OR up.expires_at > NOW())
    LIMIT 1;
    IF v_has THEN RETURN TRUE; END IF;

    -- From role
    SELECT TRUE INTO v_has
    FROM tbm_users u
    JOIN tbm_role_permissions rp ON rp.role_id = u.role_id
    JOIN tbm_permissions p ON p.id = rp.permission_id
    WHERE u.id = p_user_id
      AND p.name = p_permission
      AND p.is_active = TRUE
      AND u.is_active = TRUE
      AND u.deleted_date IS NULL
    LIMIT 1;

    RETURN COALESCE(v_has, FALSE);
END;
$$ LANGUAGE plpgsql STABLE;

-- =====================================================================
-- 17. VIEW — User tbm_permissions effective
-- =====================================================================

CREATE OR REPLACE VIEW v_user_permissions AS
SELECT DISTINCT
    u.id AS user_id, u.username, u.full_name,
    p.id AS permission_id, p.name AS permission_name,
    p.module, p.display_name, 'role' AS source
FROM tbm_users u
JOIN tbm_role_permissions rp ON rp.role_id = u.role_id
JOIN tbm_permissions p ON p.id = rp.permission_id
WHERE u.is_active = TRUE AND u.deleted_date IS NULL AND p.is_active = TRUE
  AND NOT EXISTS (
      SELECT 1 FROM tbm_user_permissions up
      WHERE up.user_id = u.id AND up.permission_id = p.id
        AND up.grant_type = 'deny'
        AND (up.expires_at IS NULL OR up.expires_at > NOW()))
UNION
SELECT DISTINCT
    u.id, u.username, u.full_name,
    p.id, p.name, p.module, p.display_name, 'override' AS source
FROM tbm_users u
JOIN tbm_user_permissions up ON up.user_id = u.id
JOIN tbm_permissions p ON p.id = up.permission_id
WHERE up.grant_type = 'allow'
  AND (up.expires_at IS NULL OR up.expires_at > NOW())
  AND u.is_active = TRUE AND u.deleted_date IS NULL;

SELECT 'User authentication & authorization module installed successfully.' AS status;
