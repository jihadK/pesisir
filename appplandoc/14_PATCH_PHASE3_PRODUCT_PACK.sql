-- =====================================================================
-- PATCH: Phase 3 — Update Product Menu (Pack Concept + SKU Generator)
-- DESC : Update menu Produk:
--         - Produk dijual per PACK dengan range berat & isi (ekor/potong)
--         - SKU pattern: {GROUP_CODE}-{SUBGROUP_CODE}-{GRADE_CODE}-{NNN}
--           contoh: FISH-TUNA-A-001
--         - Kategori dapat kolom `code` (untuk segmen SKU)
--         - Kategori produk WAJIB level-2 (subgroup), group otomatis dari parent
--         - Sequence 001 reset per kombinasi group+subgroup+grade
-- DEPS : Jalankan SETELAH 12_PATCH_PHASE3_MASTERS.sql
-- DATE : 2026-05-10
-- =====================================================================

BEGIN;

-- 1. tbm_categories: tambah kolom code (4-10 huruf, untuk segmen SKU)
ALTER TABLE tbm_categories
    ADD COLUMN IF NOT EXISTS code VARCHAR(10);

-- Unique constraint: code harus unik per parent (tidak ada 2 child dengan code sama dlm 1 parent)
-- NULL parent = root group, jadi pakai expression index pada COALESCE
CREATE UNIQUE INDEX IF NOT EXISTS uq_categories_code_per_parent
    ON tbm_categories (code, COALESCE(parent_id, 0))
    WHERE code IS NOT NULL;

-- 2. tbm_products: tambah kolom pack info
ALTER TABLE tbm_products
    ADD COLUMN IF NOT EXISTS pack_content_type   VARCHAR(10),                       -- 'ekor' | 'potong'
    ADD COLUMN IF NOT EXISTS pack_content_min    SMALLINT,
    ADD COLUMN IF NOT EXISTS pack_content_max    SMALLINT,
    ADD COLUMN IF NOT EXISTS pack_weight_min_g   NUMERIC(8,2),
    ADD COLUMN IF NOT EXISTS pack_weight_max_g   NUMERIC(8,2);

-- Constraint: tipe isi harus salah satu dari enum
ALTER TABLE tbm_products
    DROP CONSTRAINT IF EXISTS chk_prod_pack_content_type;
ALTER TABLE tbm_products
    ADD CONSTRAINT chk_prod_pack_content_type
    CHECK (pack_content_type IS NULL OR pack_content_type IN ('ekor','potong'));

-- Constraint: min ≤ max (kalau dua-duanya terisi)
ALTER TABLE tbm_products
    DROP CONSTRAINT IF EXISTS chk_prod_pack_content_range;
ALTER TABLE tbm_products
    ADD CONSTRAINT chk_prod_pack_content_range
    CHECK (
        pack_content_min IS NULL OR pack_content_max IS NULL
        OR pack_content_min <= pack_content_max
    );

ALTER TABLE tbm_products
    DROP CONSTRAINT IF EXISTS chk_prod_pack_weight_range;
ALTER TABLE tbm_products
    ADD CONSTRAINT chk_prod_pack_weight_range
    CHECK (
        pack_weight_min_g IS NULL OR pack_weight_max_g IS NULL
        OR pack_weight_min_g <= pack_weight_max_g
    );

-- 3. Backfill code untuk seed kategori existing (kalau ada).
--    Strategy:
--      a) Buang prefix "Ikan " supaya "Ikan Laut" → "Laut" (bukan "Ikan")
--      b) Ambil 4 huruf alfabet pertama, uppercase
--      c) Kalau masih bentrok dalam 1 parent, suffix dengan ROW_NUMBER (max 3 huruf + digit)
WITH base AS (
    SELECT
        id,
        parent_id,
        UPPER(LEFT(REGEXP_REPLACE(
            CASE WHEN name ILIKE 'Ikan %' THEN SUBSTRING(name FROM 6) ELSE name END,
            '[^A-Za-z]', '', 'g'
        ), 4)) AS base_code
    FROM tbm_categories
    WHERE code IS NULL
),
numbered AS (
    SELECT
        id,
        base_code,
        ROW_NUMBER() OVER (PARTITION BY parent_id, base_code ORDER BY id) AS rn
    FROM base
)
UPDATE tbm_categories c
   SET code = CASE
        WHEN n.rn = 1 THEN n.base_code
        ELSE LEFT(n.base_code, 3) || (n.rn::text)
   END
  FROM numbered n
 WHERE c.id = n.id;

COMMIT;

SELECT 'Phase 3 product-pack patches applied successfully.' AS status;
