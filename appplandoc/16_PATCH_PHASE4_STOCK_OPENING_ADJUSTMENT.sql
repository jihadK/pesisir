-- =====================================================================
-- PATCH: Phase 4 — Stock Opening, Stock Adjustment & Stock Card
-- DESC : Tambah permissions, document sequences, dan role mapping untuk
--         menu Inventory Phase 4 (Opening, Adjustment, Card).
--         Tidak ada perubahan schema tabel — semua data akan ditulis ke
--         tabel existing: tbm_product_batches, tbs_stock_balances,
--         tbh_stock_movements (memanfaatkan trigger trg_stock_movement_apply).
-- DEPS : Jalankan SETELAH 15_PATCH_PHASE3_PRODUCT_MARGIN.sql
-- DATE : 2026-05-10
-- =====================================================================

BEGIN;

-- ===== 1. Permissions =====
INSERT INTO tbm_permissions (name, display_name, module, description) VALUES
    ('stock_opening.view',     'View Stock Opening',     'inventory', 'Lihat riwayat stock opening'),
    ('stock_opening.create',   'Create Stock Opening',   'inventory', 'Input stock awal saat go-live'),
    ('stock_adjustment.view',  'View Stock Adjustment',  'inventory', 'Lihat riwayat adjustment'),
    ('stock_adjustment.create','Create Stock Adjustment','inventory', 'Buat penyesuaian stock'),
    ('stock_card.view',        'View Stock Card',        'inventory', 'Lihat kartu stok produk')
ON CONFLICT (name) DO NOTHING;

-- ===== 2. Assign ke role admin =====
INSERT INTO tbm_role_permissions (role_id, permission_id)
SELECT r.id, p.id
  FROM tbm_roles r CROSS JOIN tbm_permissions p
 WHERE r.name = 'admin'
   AND p.name IN ('stock_opening.view','stock_opening.create',
                  'stock_adjustment.view','stock_adjustment.create',
                  'stock_card.view')
ON CONFLICT DO NOTHING;

-- ===== 3. Assign ke role manager (semua kecuali setting) =====
INSERT INTO tbm_role_permissions (role_id, permission_id)
SELECT r.id, p.id
  FROM tbm_roles r CROSS JOIN tbm_permissions p
 WHERE r.name = 'manager'
   AND p.name IN ('stock_opening.view','stock_opening.create',
                  'stock_adjustment.view','stock_adjustment.create',
                  'stock_card.view')
ON CONFLICT DO NOTHING;

-- ===== 4. Assign ke role warehouse (operasional) =====
INSERT INTO tbm_role_permissions (role_id, permission_id)
SELECT r.id, p.id
  FROM tbm_roles r CROSS JOIN tbm_permissions p
 WHERE r.name = 'warehouse'
   AND p.name IN ('stock_opening.view','stock_opening.create',
                  'stock_adjustment.view','stock_adjustment.create',
                  'stock_card.view')
ON CONFLICT DO NOTHING;

-- ===== 5. Document number sequences =====
INSERT INTO tbs_document_sequences (doc_type, prefix, reset_period) VALUES
    ('OPN','OPN/','yearly'),
    ('ADJ','ADJ/','yearly')
ON CONFLICT (doc_type) DO NOTHING;

COMMIT;

SELECT 'Phase 4 stock-ops permissions & sequences applied.' AS status;
