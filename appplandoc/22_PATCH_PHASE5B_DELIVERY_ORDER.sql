-- =====================================================================
-- PATCH: Phase 5b — Delivery Order
-- DESC : Permissions delivery_order.* + doc sequence DO/.
--        Tabel tbr_delivery_orders & tbr_delivery_order_items sudah ada
--        di 04_DDL_POSTGRESQL.sql — tidak butuh ALTER schema.
-- DEPS : Jalankan SETELAH 21_PATCH_PHASE5A_SO_PAYMENT_METHOD.sql
-- DATE : 2026-05-11
-- =====================================================================

BEGIN;

-- ===== 1. Permissions =====
INSERT INTO tbm_permissions (name, display_name, module, description) VALUES
    ('delivery_order.view',   'View Delivery Order',   'sales', 'Lihat daftar DO'),
    ('delivery_order.create', 'Create Delivery Order', 'sales', 'Buat DO dari SO'),
    ('delivery_order.ship',   'Ship Delivery Order',   'sales', 'Tandai DO shipped (kurangi stock)'),
    ('delivery_order.cancel', 'Cancel Delivery Order', 'sales', 'Batalkan DO draft'),
    ('delivery_order.print',  'Print Delivery Order',  'sales', 'Cetak surat jalan')
ON CONFLICT (name) DO NOTHING;

-- Assign ke admin & manager (full akses)
INSERT INTO tbm_role_permissions (role_id, permission_id)
SELECT r.id, p.id
  FROM tbm_roles r CROSS JOIN tbm_permissions p
 WHERE r.name IN ('admin','manager')
   AND p.name IN ('delivery_order.view','delivery_order.create','delivery_order.ship','delivery_order.cancel','delivery_order.print')
ON CONFLICT DO NOTHING;

-- Sales role: create + view + print, ship butuh approval (skip ship dulu)
INSERT INTO tbm_role_permissions (role_id, permission_id)
SELECT r.id, p.id
  FROM tbm_roles r CROSS JOIN tbm_permissions p
 WHERE r.name = 'sales'
   AND p.name IN ('delivery_order.view','delivery_order.create','delivery_order.print')
ON CONFLICT DO NOTHING;

-- Warehouse staff: view + ship (mereka yang fisik kirim)
INSERT INTO tbm_role_permissions (role_id, permission_id)
SELECT r.id, p.id
  FROM tbm_roles r CROSS JOIN tbm_permissions p
 WHERE r.name = 'warehouse'
   AND p.name IN ('delivery_order.view','delivery_order.ship','delivery_order.print')
ON CONFLICT DO NOTHING;

-- Cashier: view + print only
INSERT INTO tbm_role_permissions (role_id, permission_id)
SELECT r.id, p.id
  FROM tbm_roles r CROSS JOIN tbm_permissions p
 WHERE r.name = 'cashier'
   AND p.name IN ('delivery_order.view','delivery_order.print')
ON CONFLICT DO NOTHING;

-- ===== 2. Doc Sequence DO =====
INSERT INTO tbs_document_sequences (doc_type, prefix, reset_period) VALUES
    ('DO','DO/','yearly')
ON CONFLICT (doc_type) DO NOTHING;

COMMIT;

SELECT 'Phase 5b delivery-order patches applied successfully.' AS status;
