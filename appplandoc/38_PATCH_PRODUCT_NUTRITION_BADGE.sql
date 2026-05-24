-- =====================================================================
-- PATCH 38: Tambah nutrition_info (JSONB) + badge di tbm_products
-- DESC : Display nutrition tags ("Tinggi Protein", "Kaya Omega-3") &
--        badge produk (Best Seller / Recommended / New) di customer portal.
-- =====================================================================

BEGIN;

ALTER TABLE tbm_products
    ADD COLUMN IF NOT EXISTS nutrition_info JSONB,
    ADD COLUMN IF NOT EXISTS badge VARCHAR(20)
        CHECK (badge IS NULL OR badge IN ('best_seller','recommended','new'));

CREATE INDEX IF NOT EXISTS idx_products_badge
    ON tbm_products(badge) WHERE badge IS NOT NULL AND deleted_date IS NULL;

COMMENT ON COLUMN tbm_products.nutrition_info IS 'JSONB array of nutrient tags untuk display di card. Format: [{"label":"Tinggi Protein","icon":"fitness_center"}, ...]';
COMMENT ON COLUMN tbm_products.badge IS 'Badge status: best_seller | recommended | new (NULL = tanpa badge).';

COMMIT;

SELECT 'Patch 38 applied: product nutrition_info + badge columns added.' AS status;
