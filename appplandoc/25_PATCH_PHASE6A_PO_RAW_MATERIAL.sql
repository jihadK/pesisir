-- =====================================================================
-- PATCH: Phase 6a-revised — Purchase Order untuk Raw Material + Biaya Tambahan
-- DESC : Restructure PO sesuai proses bisnis aktual:
--          - Beli RAW dari nelayan (sub-kategori, qty gram, harga/kg)
--          - Biaya tambahan: jasa bersih ikan + pembelian lain-lain (plastik, box, dll)
--          - PO sekarang fokus REKAM BIAYA, stock SKU pack tetap manual via Stock Opening
--        Schema baru:
--          - DROP & RECREATE tbr_purchase_order_items (struktur raw)
--          - CREATE tbr_purchase_order_costs (biaya tambahan)
--          - CREATE tbm_employees (master karyawan)
--          - CREATE tbm_service_rates (master tarif jasa)
--          - ALTER tbr_purchase_orders: tambah additional_cost_total
-- DEPS : Jalankan SETELAH 24_PATCH_PHASE6A_PURCHASE_ORDER.sql
-- WARN : DROP TABLE tbr_purchase_order_items — pastikan belum ada data PO penting!
-- DATE : 2026-05-11
-- =====================================================================

BEGIN;

-- ===== 1. Drop & recreate tbr_purchase_order_items dengan struktur raw =====
DROP TABLE IF EXISTS tbr_purchase_order_items CASCADE;

CREATE TABLE tbr_purchase_order_items (
    id            BIGSERIAL PRIMARY KEY,
    po_id         BIGINT NOT NULL REFERENCES tbr_purchase_orders(id) ON DELETE CASCADE,
    category_id   BIGINT NOT NULL REFERENCES tbm_categories(id) ON DELETE RESTRICT,
    qty_gram      NUMERIC(14,2) NOT NULL CHECK (qty_gram > 0),
    price_per_kg  NUMERIC(14,2) NOT NULL CHECK (price_per_kg >= 0),
    subtotal      NUMERIC(14,2) NOT NULL,
    notes         VARCHAR(255)
);
CREATE INDEX idx_poi_po       ON tbr_purchase_order_items(po_id);
CREATE INDEX idx_poi_category ON tbr_purchase_order_items(category_id);

-- ===== 2. Header PO: kolom additional_cost_total =====
ALTER TABLE tbr_purchase_orders
    ADD COLUMN IF NOT EXISTS additional_cost_total NUMERIC(14,2) NOT NULL DEFAULT 0;

-- ===== 3. Master Pegawai =====
CREATE TABLE IF NOT EXISTS tbm_employees (
    id           BIGSERIAL PRIMARY KEY,
    code         VARCHAR(20) NOT NULL UNIQUE,
    name         VARCHAR(100) NOT NULL,
    position     VARCHAR(50),
    phone        VARCHAR(20),
    is_active    BOOLEAN NOT NULL DEFAULT TRUE,
    notes        VARCHAR(255),
    created_date TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_date TIMESTAMPTZ
);
CREATE INDEX IF NOT EXISTS idx_emp_active ON tbm_employees(is_active);

-- ===== 4. Master Tarif Jasa =====
-- 1 tarif bisa applies ke 1 kategori spesifik (rate_per_kg untuk Tuna = 5rb/kg)
-- atau global (category_id NULL = applies untuk semua kategori)
CREATE TABLE IF NOT EXISTS tbm_service_rates (
    id           BIGSERIAL PRIMARY KEY,
    name         VARCHAR(100) NOT NULL,
    category_id  BIGINT REFERENCES tbm_categories(id) ON DELETE SET NULL,
    rate_per_kg  NUMERIC(14,2) NOT NULL CHECK (rate_per_kg >= 0),
    is_active    BOOLEAN NOT NULL DEFAULT TRUE,
    notes        VARCHAR(255),
    created_date TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_date TIMESTAMPTZ
);
CREATE INDEX IF NOT EXISTS idx_sr_active ON tbm_service_rates(is_active);
CREATE INDEX IF NOT EXISTS idx_sr_category ON tbm_service_rates(category_id);

-- ===== 5. PO Additional Costs =====
-- Cost types: 'cleaning' (jasa bersih), 'other' (plastik/box/timba/dll)
CREATE TABLE IF NOT EXISTS tbr_purchase_order_costs (
    id           BIGSERIAL PRIMARY KEY,
    po_id        BIGINT NOT NULL REFERENCES tbr_purchase_orders(id) ON DELETE CASCADE,
    cost_type    VARCHAR(20) NOT NULL CHECK (cost_type IN ('cleaning','other')),

    -- Untuk cleaning:
    employee_id  BIGINT REFERENCES tbm_employees(id) ON DELETE SET NULL,
    po_item_id   BIGINT REFERENCES tbr_purchase_order_items(id) ON DELETE CASCADE,
    service_rate_id BIGINT REFERENCES tbm_service_rates(id) ON DELETE SET NULL,

    -- Universal:
    description  VARCHAR(255) NOT NULL,
    qty          NUMERIC(14,3) NOT NULL CHECK (qty > 0),
    unit         VARCHAR(20)   NOT NULL DEFAULT 'pcs',
    unit_price   NUMERIC(14,2) NOT NULL CHECK (unit_price >= 0),
    subtotal     NUMERIC(14,2) NOT NULL CHECK (subtotal >= 0)
);
CREATE INDEX IF NOT EXISTS idx_poc_po   ON tbr_purchase_order_costs(po_id);
CREATE INDEX IF NOT EXISTS idx_poc_type ON tbr_purchase_order_costs(cost_type);
CREATE INDEX IF NOT EXISTS idx_poc_emp  ON tbr_purchase_order_costs(employee_id);

-- ===== 6. Permissions baru =====
INSERT INTO tbm_permissions (name, display_name, module, description) VALUES
    ('employee.view',      'View Pegawai',      'master', 'Lihat daftar pegawai'),
    ('employee.create',    'Create Pegawai',    'master', 'Tambah pegawai'),
    ('employee.update',    'Update Pegawai',    'master', 'Edit pegawai'),
    ('employee.delete',    'Delete Pegawai',    'master', 'Hapus pegawai'),
    ('service_rate.view',  'View Tarif Jasa',   'master', 'Lihat daftar tarif jasa'),
    ('service_rate.create','Create Tarif Jasa', 'master', 'Tambah tarif jasa'),
    ('service_rate.update','Update Tarif Jasa', 'master', 'Edit tarif jasa'),
    ('service_rate.delete','Delete Tarif Jasa', 'master', 'Hapus tarif jasa')
ON CONFLICT (name) DO NOTHING;

-- Assign ke admin & manager
INSERT INTO tbm_role_permissions (role_id, permission_id)
SELECT r.id, p.id
  FROM tbm_roles r CROSS JOIN tbm_permissions p
 WHERE r.name IN ('admin','manager')
   AND p.name IN ('employee.view','employee.create','employee.update','employee.delete',
                  'service_rate.view','service_rate.create','service_rate.update','service_rate.delete')
ON CONFLICT DO NOTHING;

-- Warehouse: view only
INSERT INTO tbm_role_permissions (role_id, permission_id)
SELECT r.id, p.id
  FROM tbm_roles r CROSS JOIN tbm_permissions p
 WHERE r.name = 'warehouse'
   AND p.name IN ('employee.view','service_rate.view')
ON CONFLICT DO NOTHING;

COMMIT;

SELECT 'Phase 6a-revised: PO raw-material + Pegawai/Tarif Jasa applied.' AS status;
