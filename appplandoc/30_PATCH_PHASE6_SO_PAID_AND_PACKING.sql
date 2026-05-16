-- =====================================================================
-- PATCH 30: Sales Order — add 'paid' status, packing_cost, mark_paid perm
-- DESC : UAT — SO sekarang punya status 'paid' (sudah dibayar)
--        + kolom packing_cost flat (Rp) di header SO.
-- =====================================================================

BEGIN;

-- 1) Update CHECK constraint status SO (tambah 'paid')
ALTER TABLE tbr_sales_orders DROP CONSTRAINT IF EXISTS tbr_sales_orders_status_check;
ALTER TABLE tbr_sales_orders
    ADD CONSTRAINT tbr_sales_orders_status_check
    CHECK (status IN ('draft','confirmed','partial','delivered','invoiced','paid','cancelled'));

-- 2) Kolom packing_cost
ALTER TABLE tbr_sales_orders
    ADD COLUMN IF NOT EXISTS packing_cost NUMERIC(14,2) NOT NULL DEFAULT 0
        CHECK (packing_cost >= 0);

COMMENT ON COLUMN tbr_sales_orders.packing_cost
    IS 'Biaya packing flat (Rp) per order, ditambahkan ke total.';

-- 3) Permission baru
INSERT INTO tbm_permissions (name, display_name, module, description)
VALUES ('sales_order.mark_paid', 'Mark SO as Paid', 'sales', 'Tandai SO sebagai sudah dibayar')
ON CONFLICT (name) DO NOTHING;

-- 4) Grant ke semua role yang sudah punya sales_order.create
INSERT INTO tbm_role_permissions (role_id, permission_id)
SELECT rp.role_id, p_paid.id
  FROM tbm_role_permissions rp
  JOIN tbm_permissions p_create ON p_create.id = rp.permission_id AND p_create.name = 'sales_order.create'
 CROSS JOIN LATERAL (SELECT id FROM tbm_permissions WHERE name = 'sales_order.mark_paid') p_paid
ON CONFLICT DO NOTHING;

COMMIT;

SELECT 'Patch 30 applied: SO paid status + packing_cost.' AS status;
