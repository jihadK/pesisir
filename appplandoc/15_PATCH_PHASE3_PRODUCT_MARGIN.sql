-- =====================================================================
-- PATCH: Phase 3 — Product Default Margin %
-- DESC : Tambah kolom default_margin_percent untuk simpan margin target
--         per produk. Dipakai untuk auto-calc harga jual:
--           sell = round( cost * (1 + margin/100) / 1000 ) * 1000
--        Saat cost berubah → sell tetap, margin auto-adjust (di UI).
-- DEPS : Jalankan SETELAH 14_PATCH_PHASE3_PRODUCT_PACK.sql
-- DATE : 2026-05-10
-- =====================================================================

BEGIN;

ALTER TABLE tbm_products
    ADD COLUMN IF NOT EXISTS default_margin_percent NUMERIC(5,2);

ALTER TABLE tbm_products
    DROP CONSTRAINT IF EXISTS chk_prod_margin_percent;
ALTER TABLE tbm_products
    ADD CONSTRAINT chk_prod_margin_percent
    CHECK (default_margin_percent IS NULL OR default_margin_percent >= 0);

COMMIT;

SELECT 'Phase 3 product-margin patch applied successfully.' AS status;
