-- =====================================================================
-- PATCH 32: Ganti trigger trg_apply_stock_movement supaya tidak pakai
--           ON CONFLICT inference (yang kadang gagal match unique index
--           dengan COALESCE expression). Ganti dengan UPDATE-first-INSERT
--           pattern — explicit & deterministic.
-- =====================================================================

BEGIN;

CREATE OR REPLACE FUNCTION trg_apply_stock_movement()
RETURNS TRIGGER AS $$
DECLARE
    v_balance NUMERIC(14,3);
BEGIN
    -- Coba UPDATE row balance yang sudah ada (matching product+warehouse+batch).
    -- COALESCE menyamakan NULL batch_id pada kedua sisi.
    UPDATE tbs_stock_balances
       SET quantity        = quantity + NEW.quantity,
           last_updated_date = NOW()
     WHERE product_id   = NEW.product_id
       AND warehouse_id = NEW.warehouse_id
       AND COALESCE(batch_id, 0) = COALESCE(NEW.batch_id, 0)
    RETURNING quantity INTO v_balance;

    -- Tidak ada row → INSERT baru
    IF NOT FOUND THEN
        INSERT INTO tbs_stock_balances (product_id, warehouse_id, batch_id, quantity, last_updated_date)
        VALUES (NEW.product_id, NEW.warehouse_id, NEW.batch_id, NEW.quantity, NOW())
        RETURNING quantity INTO v_balance;
    END IF;

    -- Update remaining di product batches kalau movement spesifik ke batch
    IF NEW.batch_id IS NOT NULL THEN
        UPDATE tbm_product_batches
           SET remaining_quantity = remaining_quantity + NEW.quantity,
               updated_date       = NOW()
         WHERE id = NEW.batch_id;
    END IF;

    NEW.balance_after = v_balance;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

COMMIT;

SELECT 'Patch 32 applied: trigger trg_apply_stock_movement using UPDATE-first-INSERT.' AS status;
