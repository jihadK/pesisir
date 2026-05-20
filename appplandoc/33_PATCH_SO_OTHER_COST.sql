-- =====================================================================
-- PATCH 33: Tambah kolom other_cost_amount + other_cost_desc di SO header
-- DESC : "Biaya Lain-lain" — biaya tambahan flat (Rp) dengan deskripsi.
-- =====================================================================

ALTER TABLE tbr_sales_orders
    ADD COLUMN IF NOT EXISTS other_cost_amount NUMERIC(14,2) NOT NULL DEFAULT 0
        CHECK (other_cost_amount >= 0),
    ADD COLUMN IF NOT EXISTS other_cost_desc VARCHAR(255);

COMMENT ON COLUMN tbr_sales_orders.other_cost_amount IS 'Biaya lain-lain (Rp), ditambahkan ke total.';
COMMENT ON COLUMN tbr_sales_orders.other_cost_desc   IS 'Deskripsi biaya lain-lain (mis. "Es batu", "Plastik tambahan").';

SELECT 'Patch 33 applied: other_cost columns added.' AS status;
