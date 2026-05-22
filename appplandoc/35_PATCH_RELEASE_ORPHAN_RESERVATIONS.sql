-- =====================================================================
-- PATCH 35: Release orphan reservations di tbs_stock_balances
-- DESC : Setelah patch 30 (alur baru Draft → Paid skip Confirmed),
--        reservasi lama dari SO yang sudah Paid/Cancelled/Invoiced
--        tetap nyangkut di reserved_quantity. Patch ini menghitung ulang
--        reservasi seharusnya = SUM(qty) dari SO status Confirmed/Partial
--        saja, lalu update reserved_quantity sesuai.
-- =====================================================================

BEGIN;

-- 1) Diagnostik sebelum: tampilkan reservasi saat ini
SELECT 'Reservasi SEBELUM patch:' AS info;
SELECT product_id, warehouse_id, batch_id, quantity, reserved_quantity,
       (quantity - reserved_quantity) AS available
  FROM tbs_stock_balances
 WHERE reserved_quantity > 0
 ORDER BY product_id, batch_id;

-- 2) Reset semua reserved_quantity = 0 dulu
UPDATE tbs_stock_balances
   SET reserved_quantity = 0,
       last_updated_date = NOW()
 WHERE reserved_quantity > 0;

-- 3) Re-create reservasi dari SO yang status-nya masih Confirmed/Partial
--    (status ini yang seharusnya hold stok). Status lain (draft, paid,
--    cancelled, invoiced, delivered) tidak hold stok.
WITH active_reservations AS (
    SELECT soi.product_id,
           so.warehouse_id,
           SUM(soi.quantity) AS qty_needed
      FROM tbr_sales_orders so
      JOIN tbr_sales_order_items soi ON soi.so_id = so.id
     WHERE so.status IN ('confirmed', 'partial')
     GROUP BY soi.product_id, so.warehouse_id
)
UPDATE tbs_stock_balances sb
   SET reserved_quantity = LEAST(sb.quantity, ar.qty_needed),
       last_updated_date = NOW()
  FROM active_reservations ar
 WHERE sb.product_id   = ar.product_id
   AND sb.warehouse_id = ar.warehouse_id
   AND sb.batch_id IS NULL;  -- reservasi diakumulasikan ke baris null batch

-- 4) Verifikasi sesudah
SELECT 'Reservasi SESUDAH patch:' AS info;
SELECT product_id, warehouse_id, batch_id, quantity, reserved_quantity,
       (quantity - reserved_quantity) AS available
  FROM tbs_stock_balances
 WHERE reserved_quantity > 0 OR quantity > 0
 ORDER BY product_id, batch_id;

COMMIT;

SELECT 'Patch 35 applied: orphan reservations released.' AS status;
