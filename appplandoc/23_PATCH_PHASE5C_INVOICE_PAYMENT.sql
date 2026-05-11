-- =====================================================================
-- PATCH: Phase 5c — Invoice + Payment
-- DESC : Permissions invoice.* dan payment.* + doc sequences INV/ & PAY/.
--        Tabel tbr_invoices, tbr_invoice_items, tbr_payments,
--        tbr_invoice_payments sudah ada di 04_DDL_POSTGRESQL.sql.
-- DEPS : Jalankan SETELAH 22_PATCH_PHASE5B_DELIVERY_ORDER.sql
-- DATE : 2026-05-11
-- =====================================================================

BEGIN;

-- ===== 1. Permissions =====
INSERT INTO tbm_permissions (name, display_name, module, description) VALUES
    -- Invoice
    ('invoice.view',   'View Invoice',   'invoice', 'Lihat daftar invoice'),
    ('invoice.create', 'Create Invoice', 'invoice', 'Generate invoice dari DO'),
    ('invoice.cancel', 'Cancel Invoice', 'invoice', 'Batalkan invoice yang belum dibayar'),
    ('invoice.print',  'Print Invoice',  'invoice', 'Cetak invoice/faktur'),
    -- Payment
    ('payment.view',   'View Payment',   'invoice', 'Lihat daftar pembayaran'),
    ('payment.create', 'Create Payment', 'invoice', 'Catat pembayaran customer'),
    ('payment.cancel', 'Cancel Payment', 'invoice', 'Batalkan pembayaran (rollback alokasi)')
ON CONFLICT (name) DO NOTHING;

-- Assign ke admin & manager (full)
INSERT INTO tbm_role_permissions (role_id, permission_id)
SELECT r.id, p.id
  FROM tbm_roles r CROSS JOIN tbm_permissions p
 WHERE r.name IN ('admin','manager')
   AND p.name IN ('invoice.view','invoice.create','invoice.cancel','invoice.print',
                  'payment.view','payment.create','payment.cancel')
ON CONFLICT DO NOTHING;

-- Cashier: full akses invoice & payment (fungsi utama mereka)
INSERT INTO tbm_role_permissions (role_id, permission_id)
SELECT r.id, p.id
  FROM tbm_roles r CROSS JOIN tbm_permissions p
 WHERE r.name = 'cashier'
   AND p.name IN ('invoice.view','invoice.create','invoice.print',
                  'payment.view','payment.create')
ON CONFLICT DO NOTHING;

-- Sales: view only invoice (untuk follow-up tagihan ke customer)
INSERT INTO tbm_role_permissions (role_id, permission_id)
SELECT r.id, p.id
  FROM tbm_roles r CROSS JOIN tbm_permissions p
 WHERE r.name = 'sales'
   AND p.name IN ('invoice.view','invoice.print','payment.view')
ON CONFLICT DO NOTHING;

-- Warehouse: tidak punya akses (skip)

-- ===== 2. Doc Sequences =====
INSERT INTO tbs_document_sequences (doc_type, prefix, reset_period) VALUES
    ('INV','INV/','yearly'),
    ('PAY','PAY/','yearly')
ON CONFLICT (doc_type) DO NOTHING;

COMMIT;

SELECT 'Phase 5c invoice-payment patches applied successfully.' AS status;
