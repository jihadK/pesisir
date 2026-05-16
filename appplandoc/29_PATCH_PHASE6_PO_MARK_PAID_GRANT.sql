-- =====================================================================
-- PATCH 29: Grant purchase_order.mark_paid ke SEMUA role yang sudah
--           punya purchase_order.create. Idempotent.
-- =====================================================================

BEGIN;

-- Pastikan permission ada (jika patch 27 belum dijalankan)
INSERT INTO tbm_permissions (name, display_name, module, description)
VALUES ('purchase_order.mark_paid', 'Mark PO as Paid', 'inventory', 'Tandai PO sebagai sudah dibayar')
ON CONFLICT (name) DO NOTHING;

-- Pastikan status 'paid' diizinkan di check constraint
ALTER TABLE tbr_purchase_orders DROP CONSTRAINT IF EXISTS tbr_purchase_orders_status_check;
ALTER TABLE tbr_purchase_orders
    ADD CONSTRAINT tbr_purchase_orders_status_check
    CHECK (status IN ('draft','submitted','partial','received','paid','cancelled'));

-- Grant ke semua role yang sudah bisa create PO
INSERT INTO tbm_role_permissions (role_id, permission_id)
SELECT rp.role_id, p_paid.id
  FROM tbm_role_permissions rp
  JOIN tbm_permissions p_create ON p_create.id = rp.permission_id AND p_create.name = 'purchase_order.create'
 CROSS JOIN LATERAL (SELECT id FROM tbm_permissions WHERE name = 'purchase_order.mark_paid') p_paid
ON CONFLICT DO NOTHING;

COMMIT;

SELECT 'Patch 29 applied: purchase_order.mark_paid granted to all PO-creating roles.' AS status;
