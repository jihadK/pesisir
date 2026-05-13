-- =====================================================================
-- PATCH: Phase 6a — Pisahkan Jasa Bersih & Pembelian Lain-lain ke Menu Sendiri
-- DESC : Biaya tambahan keluar dari form PO, jadi menu terpisah:
--          - tbr_cleaning_services: catat jasa bersih ikan (per transaksi)
--          - tbr_supplies_purchases: catat pembelian plastik/box/timba/dll
--        Tabel tbr_purchase_order_costs dari patch 25 dibiarkan (di-deprecate),
--        tidak dipakai lagi tapi tidak di-drop untuk safety.
-- DEPS : Jalankan SETELAH 25_PATCH_PHASE6A_PO_RAW_MATERIAL.sql
-- DATE : 2026-05-11
-- =====================================================================

BEGIN;

-- ===== 1. Cleaning Services (Jasa Bersih Ikan) =====
CREATE TABLE IF NOT EXISTS tbr_cleaning_services (
    id            BIGSERIAL PRIMARY KEY,
    service_no    VARCHAR(30) NOT NULL UNIQUE,
    service_date  DATE NOT NULL,
    employee_id   BIGINT NOT NULL REFERENCES tbm_employees(id) ON DELETE RESTRICT,
    category_id   BIGINT NOT NULL REFERENCES tbm_categories(id) ON DELETE RESTRICT,
    qty_kg        NUMERIC(14,3) NOT NULL CHECK (qty_kg > 0),
    rate_per_kg   NUMERIC(14,2) NOT NULL CHECK (rate_per_kg >= 0),
    subtotal      NUMERIC(14,2) NOT NULL CHECK (subtotal >= 0),
    notes         TEXT,
    created_by    BIGINT REFERENCES tbm_users(id) ON DELETE SET NULL,
    created_date  TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_date  TIMESTAMPTZ
);
CREATE INDEX IF NOT EXISTS idx_cs_date     ON tbr_cleaning_services(service_date DESC);
CREATE INDEX IF NOT EXISTS idx_cs_employee ON tbr_cleaning_services(employee_id);
CREATE INDEX IF NOT EXISTS idx_cs_category ON tbr_cleaning_services(category_id);

-- ===== 2. Supplies Purchases (Pembelian Lain-lain: Plastik, Box, dll) =====
CREATE TABLE IF NOT EXISTS tbr_supplies_purchases (
    id             BIGSERIAL PRIMARY KEY,
    purchase_no    VARCHAR(30) NOT NULL UNIQUE,
    purchase_date  DATE NOT NULL,
    supplier_id    BIGINT REFERENCES tbm_suppliers(id) ON DELETE SET NULL,
    description    VARCHAR(255) NOT NULL,
    qty            NUMERIC(14,3) NOT NULL CHECK (qty > 0),
    unit           VARCHAR(20)   NOT NULL DEFAULT 'pcs',
    unit_price     NUMERIC(14,2) NOT NULL CHECK (unit_price >= 0),
    subtotal       NUMERIC(14,2) NOT NULL CHECK (subtotal >= 0),
    notes          TEXT,
    created_by     BIGINT REFERENCES tbm_users(id) ON DELETE SET NULL,
    created_date   TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_date   TIMESTAMPTZ
);
CREATE INDEX IF NOT EXISTS idx_sp_date     ON tbr_supplies_purchases(purchase_date DESC);
CREATE INDEX IF NOT EXISTS idx_sp_supplier ON tbr_supplies_purchases(supplier_id);

-- ===== 3. Doc Sequences =====
INSERT INTO tbs_document_sequences (doc_type, prefix, reset_period) VALUES
    ('CS','CS/','yearly'),
    ('SP','SP/','yearly')
ON CONFLICT (doc_type) DO NOTHING;

-- ===== 4. Permissions =====
INSERT INTO tbm_permissions (name, display_name, module, description) VALUES
    ('cleaning_service.view',   'View Jasa Bersih',   'inventory', 'Lihat daftar jasa bersih'),
    ('cleaning_service.create', 'Create Jasa Bersih', 'inventory', 'Catat jasa bersih ikan'),
    ('cleaning_service.update', 'Update Jasa Bersih', 'inventory', 'Edit catatan jasa bersih'),
    ('cleaning_service.delete', 'Delete Jasa Bersih', 'inventory', 'Hapus catatan jasa bersih'),
    ('supplies_purchase.view',   'View Pembelian Lain-lain',   'inventory', 'Lihat daftar pembelian lain-lain'),
    ('supplies_purchase.create', 'Create Pembelian Lain-lain', 'inventory', 'Catat pembelian lain-lain'),
    ('supplies_purchase.update', 'Update Pembelian Lain-lain', 'inventory', 'Edit catatan pembelian'),
    ('supplies_purchase.delete', 'Delete Pembelian Lain-lain', 'inventory', 'Hapus catatan pembelian')
ON CONFLICT (name) DO NOTHING;

-- Assign ke admin & manager (full)
INSERT INTO tbm_role_permissions (role_id, permission_id)
SELECT r.id, p.id
  FROM tbm_roles r CROSS JOIN tbm_permissions p
 WHERE r.name IN ('admin','manager')
   AND p.name IN ('cleaning_service.view','cleaning_service.create','cleaning_service.update','cleaning_service.delete',
                  'supplies_purchase.view','supplies_purchase.create','supplies_purchase.update','supplies_purchase.delete')
ON CONFLICT DO NOTHING;

-- Warehouse: view only
INSERT INTO tbm_role_permissions (role_id, permission_id)
SELECT r.id, p.id
  FROM tbm_roles r CROSS JOIN tbm_permissions p
 WHERE r.name = 'warehouse'
   AND p.name IN ('cleaning_service.view','supplies_purchase.view')
ON CONFLICT DO NOTHING;

COMMIT;

SELECT 'Phase 6a separate-cost-menus applied successfully.' AS status;
