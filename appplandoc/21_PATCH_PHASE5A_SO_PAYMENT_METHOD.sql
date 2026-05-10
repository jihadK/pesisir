-- =====================================================================
-- PATCH: Phase 5a — Sales Order Payment Method
-- DESC : Tambah kolom payment_method_id di tbr_sales_orders.
--        Customer bisa pilih metode pembayaran saat order, dan bisa
--        diupdate kalau customer minta ganti metode (misal awalnya
--        TF-BCA, lalu request pakai QRIS).
-- DEPS : Jalankan SETELAH 20_PATCH_PHASE5A_SALES_ORDER.sql
-- DATE : 2026-05-10
-- =====================================================================

BEGIN;

ALTER TABLE tbr_sales_orders
    ADD COLUMN IF NOT EXISTS payment_method_id BIGINT REFERENCES tbm_payment_methods(id) ON DELETE SET NULL;

CREATE INDEX IF NOT EXISTS idx_so_payment_method ON tbr_sales_orders(payment_method_id);

COMMIT;

SELECT 'Phase 5a SO payment_method_id patch applied.' AS status;
