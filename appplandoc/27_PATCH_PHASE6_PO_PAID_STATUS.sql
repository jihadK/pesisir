-- =====================================================================
-- PATCH: Phase 6 — Add 'paid' status & mark_paid permission untuk Purchase Order
-- DESC : Sesuai UAT: status Belanja sekarang ada 'paid' untuk PO yang sudah
--        dibayar. Update CHECK constraint + tambah permission baru.
-- DEPS : Jalankan SETELAH 26_PATCH_PHASE6A_SEPARATE_COST_MENUS.sql
-- DATE : 2026-05-14
-- =====================================================================

BEGIN;

-- ===== 1. Update CHECK constraint untuk status PO =====
ALTER TABLE tbr_purchase_orders DROP CONSTRAINT IF EXISTS tbr_purchase_orders_status_check;
ALTER TABLE tbr_purchase_orders
    ADD CONSTRAINT tbr_purchase_orders_status_check
    CHECK (status IN ('draft','submitted','partial','received','paid','cancelled'));

-- ===== 2. Permission baru =====
INSERT INTO tbm_permissions (name, display_name, module, description) VALUES
    ('purchase_order.mark_paid', 'Mark PO as Paid', 'inventory', 'Tandai PO sebagai sudah dibayar')
ON CONFLICT (name) DO NOTHING;

INSERT INTO tbm_role_permissions (role_id, permission_id)
SELECT r.id, p.id
  FROM tbm_roles r CROSS JOIN tbm_permissions p
 WHERE r.name IN ('admin','manager')
   AND p.name = 'purchase_order.mark_paid'
ON CONFLICT DO NOTHING;

COMMIT;

SELECT 'Phase 6 PO paid status patch applied successfully.' AS status;
