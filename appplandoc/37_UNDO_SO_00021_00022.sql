-- =====================================================================
-- PATCH 37 (ONE-TIME): Undo SO/2026/00021 & SO/2026/00022 dari Paid → Draft
-- DESC : Order salah, perlu di-revert. Stock yang sudah ke-deduct akan
--        dikembalikan via movement reversal (type=in_return).
-- SAFE : Wrapped dalam transaction + validasi status sebelum action.
--        Idempotent: kalau di-run 2x, validasi gagal di run kedua.
-- =====================================================================

BEGIN;

DO $$
DECLARE
    v_so_ids       BIGINT[];
    v_so_id        BIGINT;
    v_so_number    VARCHAR;
    v_mv           RECORD;
    v_doc_number   VARCHAR;
    v_next_number  INT;
    v_seq_year     INT := EXTRACT(YEAR FROM NOW());
    v_undo_count   INT := 0;
BEGIN
    -- 1) Ambil ID SO yang mau di-undo & validasi statusnya = 'paid'
    SELECT array_agg(id) INTO v_so_ids
      FROM tbr_sales_orders
     WHERE so_number IN ('SO/2026/00021', 'SO/2026/00022')
       AND status = 'paid';

    IF v_so_ids IS NULL OR array_length(v_so_ids, 1) IS NULL THEN
        RAISE EXCEPTION 'Tidak ada SO/2026/00021 atau SO/2026/00022 dengan status paid. Patch dibatalkan.';
    END IF;

    RAISE NOTICE 'Akan undo % SO: %', array_length(v_so_ids, 1), v_so_ids;

    -- 2) Iterate per SO
    FOREACH v_so_id IN ARRAY v_so_ids
    LOOP
        SELECT so_number INTO v_so_number FROM tbr_sales_orders WHERE id = v_so_id;
        RAISE NOTICE '  Processing % (id=%)', v_so_number, v_so_id;

        -- 2a) Ambil semua movement out_sale untuk SO ini
        FOR v_mv IN
            SELECT id, product_id, warehouse_id, batch_id, quantity, uom_id, cost_price
              FROM tbh_stock_movements
             WHERE reference_type = 'SALES_ORDER'
               AND reference_id   = v_so_id
               AND movement_type  = 'out_sale'
        LOOP
            -- 2b) Generate doc number baru (pakai SM prefix monthly)
            -- Ambil counter dengan FOR UPDATE supaya safe concurrent
            SELECT current_number + 1 INTO v_next_number
              FROM tbs_document_sequences
             WHERE doc_type = 'SM'
             FOR UPDATE;

            UPDATE tbs_document_sequences
               SET current_number = v_next_number,
                   updated_date   = NOW()
             WHERE doc_type = 'SM';

            v_doc_number := 'SM/' || v_seq_year || '/' || LPAD(v_next_number::TEXT, 5, '0');

            -- 2c) Insert movement reversal: kuantitas dibalik jadi positif
            --     Trigger akan otomatis update tbs_stock_balances (qty + reverse)
            INSERT INTO tbh_stock_movements
                (movement_number, product_id, warehouse_id, batch_id,
                 movement_type, reference_type, reference_id,
                 quantity, uom_id, cost_price, notes, created_by)
            VALUES (
                v_doc_number,
                v_mv.product_id, v_mv.warehouse_id, v_mv.batch_id,
                'in_return', 'SALES_ORDER', v_so_id,
                ABS(v_mv.quantity), -- balik tanda
                v_mv.uom_id,
                v_mv.cost_price,
                'UNDO: ' || v_so_number || ' di-revert ke Draft (manual correction)',
                1  -- created_by admin
            );

            v_undo_count := v_undo_count + 1;
        END LOOP;

        -- 2d) Reset status SO ke draft + clear field tempo
        UPDATE tbr_sales_orders
           SET status       = 'draft',
               approved_by  = NULL,
               fulfilled_at = NULL,
               due_date     = NULL,
               updated_date = NOW()
         WHERE id = v_so_id;

        RAISE NOTICE '  ✓ % di-reset ke Draft.', v_so_number;
    END LOOP;

    RAISE NOTICE 'Total movement reversal: %', v_undo_count;
END $$;

-- 3) Verifikasi: status SO setelah undo
SELECT 'Status SO sesudah undo:' AS info;
SELECT so_number, status, total_amount, due_date, fulfilled_at
  FROM tbr_sales_orders
 WHERE so_number IN ('SO/2026/00021', 'SO/2026/00022');

-- 4) Verifikasi: movement reversal yang dibuat
SELECT 'Movement reversal (in_return):' AS info;
SELECT sm.movement_number, sm.movement_type, sm.product_id, sm.batch_id,
       sm.quantity, sm.notes, sm.created_date
  FROM tbh_stock_movements sm
  JOIN tbr_sales_orders so ON so.id = sm.reference_id
 WHERE so.so_number IN ('SO/2026/00021', 'SO/2026/00022')
   AND sm.movement_type = 'in_return'
 ORDER BY sm.created_date DESC, sm.id DESC;

COMMIT;

SELECT 'Patch 37 applied: SO/2026/00021 & SO/2026/00022 di-revert ke Draft.' AS status;
