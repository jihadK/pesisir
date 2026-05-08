-- =====================================================================
-- PATCH: Phase 3 — Master Pendukung Products
-- DESC : Tambah kolom yang dibutuhkan UI:
--          - tbm_units_of_measure.description (text bantu untuk user)
--          - tbm_product_grades.color (hex color untuk badge auto-styling)
-- DEPS : Jalankan SETELAH 04_DDL_POSTGRESQL.sql
-- DATE : 2026-05-08
-- =====================================================================

BEGIN;

-- 1. UoM: tambah description
ALTER TABLE tbm_units_of_measure
    ADD COLUMN IF NOT EXISTS description VARCHAR(255);

-- 2. Grades: tambah color (hex format, mis. #FFD700)
ALTER TABLE tbm_product_grades
    ADD COLUMN IF NOT EXISTS color VARCHAR(20);

-- 3. Update existing 3 grades dengan default color (kalau belum diset)
UPDATE tbm_product_grades SET color = '#FFD700' WHERE code = 'A' AND color IS NULL;  -- Gold (Premium)
UPDATE tbm_product_grades SET color = '#C0C0C0' WHERE code = 'B' AND color IS NULL;  -- Silver (Standard)
UPDATE tbm_product_grades SET color = '#CD7F32' WHERE code = 'C' AND color IS NULL;  -- Bronze (Olahan)

COMMIT;

SELECT 'Phase 3 master patches applied successfully.' AS status;
