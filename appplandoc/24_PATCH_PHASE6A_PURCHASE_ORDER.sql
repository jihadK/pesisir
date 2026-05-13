-- =====================================================================
-- PATCH: Phase 6a — Purchase Order
-- DESC : Permissions purchase_order.* + doc sequence PO/.
--        Tabel tbr_purchase_orders & tbr_purchase_order_items sudah ada
--        di 04_DDL_POSTGRESQL.sql.
-- DEPS : Jalankan SETELAH 23_PATCH_PHASE5C_INVOICE_PAYMENT.sql
-- DATE : 2026-05-11
-- =====================================================================

BEGIN;

-- ===== 1. Permissions =====
INSERT INTO tbm_permissions (name, display_name, module, description) VALUES
    ('purchase_order.view',    'View Purchase Order',   'inventory', 'Lihat daftar PO'),
    ('purchase_order.create',  'Create Purchase Order', 'inventory', 'Buat PO ke supplier'),
    ('purchase_order.update',  'Update Purchase Order', 'inventory', 'Edit PO draft'),
    ('purchase_order.submit',  'Submit Purchase Order', 'inventory', 'Submit PO (kunci edit, kirim ke supplier)'),
    ('purchase_order.cancel',  'Cancel Purchase Order', 'inventory', 'Batalkan PO'),
    ('purchase_order.print',   'Print Purchase Order',  'inventory', 'Cetak PO untuk supplier')
ON CONFLICT (name) DO NOTHING;

-- Assign ke admin & manager (full)
INSERT INTO tbm_role_permissions (role_id, permission_id)
SELECT r.id, p.id
  FROM tbm_roles r CROSS JOIN tbm_permissions p
 WHERE r.name IN ('admin','manager')
   AND p.name IN ('purchase_order.view','purchase_order.create','purchase_order.update','purchase_order.submit','purchase_order.cancel','purchase_order.print')
ON CONFLICT DO NOTHING;

-- Warehouse staff: view + print (mereka yang terima barang, butuh referensi PO)
INSERT INTO tbm_role_permissions (role_id, permission_id)
SELECT r.id, p.id
  FROM tbm_roles r CROSS JOIN tbm_permissions p
 WHERE r.name = 'warehouse'
   AND p.name IN ('purchase_order.view','purchase_order.print')
ON CONFLICT DO NOTHING;

-- ===== 2. Doc Sequence PO =====
INSERT INTO tbs_document_sequences (doc_type, prefix, reset_period) VALUES
    ('PO','PO/','yearly')
ON CONFLICT (doc_type) DO NOTHING;

COMMIT;

SELECT 'Phase 6a purchase-order patches applied successfully.' AS status;
