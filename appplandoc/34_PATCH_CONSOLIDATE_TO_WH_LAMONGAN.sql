-- =====================================================================
-- PATCH 34: Konsolidasi semua data warehouse ke WH-LAMONGAN
-- DESC : Saat ini hanya ada 1 warehouse aktif (WH-LAMONGAN).
--        Pindahkan / merge semua tbs_stock_balances, tbh_stock_movements,
--        dan reference dari warehouse lain ke WH-LAMONGAN.
-- =====================================================================

BEGIN;

DO $$
DECLARE
    v_target_wh_id BIGINT;
    v_consolidated INT;
BEGIN
    -- 1) Cari ID WH-LAMONGAN (target)
    SELECT id INTO v_target_wh_id
      FROM tbm_warehouses
     WHERE code = 'WH-LAMONGAN'
     LIMIT 1;

    IF v_target_wh_id IS NULL THEN
        RAISE EXCEPTION 'WH-LAMONGAN tidak ditemukan di tbm_warehouses. Patch dibatalkan.';
    END IF;

    RAISE NOTICE 'Target warehouse: WH-LAMONGAN (id=%)', v_target_wh_id;

    -- 2) MERGE tbs_stock_balances:
    --    Untuk row di warehouse lain, kalau row sejenis sudah ada di target,
    --    tambahkan quantity & reserved ke target lalu hapus source.
    --    Kalau row sejenis BELUM ada di target, langsung UPDATE warehouse_id.

    -- 2a) Tambahkan qty & reserved ke row target yang sudah ada
    UPDATE tbs_stock_balances tgt
       SET quantity          = tgt.quantity + src.quantity,
           reserved_quantity = tgt.reserved_quantity + src.reserved_quantity,
           last_updated_date = NOW()
      FROM tbs_stock_balances src
     WHERE src.warehouse_id <> v_target_wh_id
       AND tgt.warehouse_id  = v_target_wh_id
       AND tgt.product_id    = src.product_id
       AND COALESCE(tgt.batch_id, 0) = COALESCE(src.batch_id, 0);

    -- 2b) Hapus source yang sudah di-merge ke target
    DELETE FROM tbs_stock_balances src
     WHERE src.warehouse_id <> v_target_wh_id
       AND EXISTS (
           SELECT 1
             FROM tbs_stock_balances tgt
            WHERE tgt.warehouse_id = v_target_wh_id
              AND tgt.product_id   = src.product_id
              AND COALESCE(tgt.batch_id, 0) = COALESCE(src.batch_id, 0)
       );

    -- 2c) Sisa row di warehouse lain (yang belum ada padanan di target) → pindahkan
    UPDATE tbs_stock_balances
       SET warehouse_id = v_target_wh_id,
           last_updated_date = NOW()
     WHERE warehouse_id <> v_target_wh_id;

    GET DIAGNOSTICS v_consolidated = ROW_COUNT;
    RAISE NOTICE 'tbs_stock_balances: % row dipindahkan ke WH-LAMONGAN.', v_consolidated;

    -- 3) tbh_stock_movements: update warehouse_id ke target (untuk history konsistensi)
    UPDATE tbh_stock_movements
       SET warehouse_id = v_target_wh_id
     WHERE warehouse_id <> v_target_wh_id;
    GET DIAGNOSTICS v_consolidated = ROW_COUNT;
    RAISE NOTICE 'tbh_stock_movements: % row warehouse_id diupdate.', v_consolidated;

    -- 4) tbr_purchase_orders
    UPDATE tbr_purchase_orders
       SET warehouse_id = v_target_wh_id
     WHERE warehouse_id <> v_target_wh_id;
    GET DIAGNOSTICS v_consolidated = ROW_COUNT;
    RAISE NOTICE 'tbr_purchase_orders: % row warehouse_id diupdate.', v_consolidated;

    -- 5) tbr_sales_orders
    UPDATE tbr_sales_orders
       SET warehouse_id = v_target_wh_id
     WHERE warehouse_id <> v_target_wh_id;
    GET DIAGNOSTICS v_consolidated = ROW_COUNT;
    RAISE NOTICE 'tbr_sales_orders: % row warehouse_id diupdate.', v_consolidated;

    -- 6) Cek table opsional lain yang mungkin reference warehouse
    --    (skip kalau table belum ada — bungkus dalam DO EXCEPTION)
    BEGIN
        EXECUTE format('UPDATE tbr_delivery_orders SET warehouse_id = %L WHERE warehouse_id <> %L', v_target_wh_id, v_target_wh_id);
        RAISE NOTICE 'tbr_delivery_orders updated.';
    EXCEPTION WHEN undefined_table THEN
        RAISE NOTICE 'tbr_delivery_orders tidak ada — skip.';
    END;

    BEGIN
        EXECUTE format('UPDATE tbr_grn_receipts SET warehouse_id = %L WHERE warehouse_id <> %L', v_target_wh_id, v_target_wh_id);
        RAISE NOTICE 'tbr_grn_receipts updated.';
    EXCEPTION WHEN undefined_table THEN
        RAISE NOTICE 'tbr_grn_receipts tidak ada — skip.';
    END;

    BEGIN
        EXECUTE format('UPDATE tbr_stock_openings SET warehouse_id = %L WHERE warehouse_id <> %L', v_target_wh_id, v_target_wh_id);
        RAISE NOTICE 'tbr_stock_openings updated.';
    EXCEPTION WHEN undefined_table THEN
        RAISE NOTICE 'tbr_stock_openings tidak ada — skip.';
    END;

    -- 7) Bersihkan: nonaktifkan warehouse selain WH-LAMONGAN
    UPDATE tbm_warehouses
       SET is_active = false
     WHERE id <> v_target_wh_id
       AND is_active = true;
    GET DIAGNOSTICS v_consolidated = ROW_COUNT;
    RAISE NOTICE 'tbm_warehouses: % warehouse lain dinonaktifkan.', v_consolidated;

END $$;

-- 8) Verifikasi: pastikan tidak ada lagi data di warehouse lain
SELECT 'Stock balances per warehouse:' AS info;
SELECT w.code, w.name, COUNT(*) AS rows, SUM(sb.quantity) AS total_qty
  FROM tbs_stock_balances sb
  JOIN tbm_warehouses w ON w.id = sb.warehouse_id
 GROUP BY w.code, w.name
 ORDER BY w.code;

COMMIT;

SELECT 'Patch 34 applied: konsolidasi ke WH-LAMONGAN selesai.' AS status;
