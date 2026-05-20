-- =====================================================================
-- PATCH 31: Pastikan unique index uq_stock_balance ada & match dengan
--           ON CONFLICT (product_id, warehouse_id, COALESCE(batch_id, 0))
--           di trigger trg_apply_stock_movement.
--
-- Gejala bug: trigger membuat row tbs_stock_balances baru dengan
-- quantity negatif (chk_sb_qty_nonneg violation) padahal row dengan
-- (product, warehouse, batch_id) yang sama sudah ada → tanda ON CONFLICT
-- tidak ketemu index.
-- =====================================================================

-- 1) Diagnostik: list semua index di tbs_stock_balances
SELECT indexname, indexdef
  FROM pg_indexes
 WHERE schemaname = current_schema()
   AND tablename  = 'tbs_stock_balances';

-- 2) Drop versi lama (kalau ada nama yang salah / definisi salah)
DROP INDEX IF EXISTS uq_stock_balance;
DROP INDEX IF EXISTS tbs_stock_balances_product_warehouse_batch_uniq;

-- 3) (Optional) merge duplikat sebelum bikin unique index.
-- Kalau ada duplikat baris dengan (product, warehouse, COALESCE(batch_id,0)),
-- gabungkan: SUM quantity & reserved, hapus duplikat.
WITH dups AS (
    SELECT product_id, warehouse_id, COALESCE(batch_id, 0) AS bkey,
           MIN(id) AS keep_id,
           SUM(quantity) AS total_qty,
           SUM(reserved_quantity) AS total_res
      FROM tbs_stock_balances
     GROUP BY product_id, warehouse_id, COALESCE(batch_id, 0)
    HAVING COUNT(*) > 1
)
UPDATE tbs_stock_balances sb
   SET quantity = d.total_qty,
       reserved_quantity = d.total_res,
       last_updated_date = NOW()
  FROM dups d
 WHERE sb.id = d.keep_id;

DELETE FROM tbs_stock_balances sb
 USING (
    SELECT product_id, warehouse_id, COALESCE(batch_id, 0) AS bkey,
           MIN(id) AS keep_id
      FROM tbs_stock_balances
     GROUP BY product_id, warehouse_id, COALESCE(batch_id, 0)
    HAVING COUNT(*) > 1
 ) d
 WHERE sb.product_id = d.product_id
   AND sb.warehouse_id = d.warehouse_id
   AND COALESCE(sb.batch_id, 0) = d.bkey
   AND sb.id <> d.keep_id;

-- 4) Bersihkan row dengan quantity negatif (warisan bug)
UPDATE tbs_stock_balances
   SET quantity = 0
 WHERE quantity < 0;

-- 5) Buat ulang unique index dengan expression yang benar
CREATE UNIQUE INDEX uq_stock_balance
    ON tbs_stock_balances (product_id, warehouse_id, COALESCE(batch_id, 0));

-- 6) Verifikasi: list ulang
SELECT indexname, indexdef
  FROM pg_indexes
 WHERE schemaname = current_schema()
   AND tablename  = 'tbs_stock_balances';

SELECT 'Patch 31 applied: uq_stock_balance restored.' AS status;
