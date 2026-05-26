-- =====================================================================
-- PATCH 40: Flag is_retail di tbm_products
-- DESC : Untuk memisahkan produk retail (tampil di customer portal) vs
--        non-retail (gelondongan / wholesale — hanya internal admin).
--        Untuk produk yang dijual di KEDUA channel, buat 2 SKU terpisah.
-- =====================================================================

BEGIN;

ALTER TABLE tbm_products
    ADD COLUMN IF NOT EXISTS is_retail BOOLEAN NOT NULL DEFAULT TRUE;

CREATE INDEX IF NOT EXISTS idx_products_retail
    ON tbm_products(is_retail) WHERE deleted_date IS NULL;

COMMENT ON COLUMN tbm_products.is_retail IS
    'TRUE = produk retail (tampil di customer portal). FALSE = non-retail / wholesale (hanya internal).';

-- Backfill: semua produk existing di-set retail = true (default sudah benar).
-- Explicit untuk dokumentasi.
UPDATE tbm_products SET is_retail = TRUE WHERE is_retail IS NULL;

COMMIT;

SELECT 'Patch 40 applied: is_retail column added.' AS status;
