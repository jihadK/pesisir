-- =====================================================================
-- PATCH: Phase 5a — Sales Order + Master Payment Method
-- DESC : - Extend tbm_payment_methods (account_holder, bank_name, qris_image_url, display_order, description)
--        - Seed payment methods awal: TF-BCA, TF-BRI, TF-MANDIRI, QRIS, COD
--        - Permissions sales_order.* dan payment_method.*
--        - Doc sequence SO/ untuk Sales Order
-- DEPS : Jalankan SETELAH 16_PATCH_PHASE4_STOCK_OPENING_ADJUSTMENT.sql
-- DATE : 2026-05-10
-- =====================================================================

BEGIN;

-- ===== 1. Extend tbm_payment_methods =====
ALTER TABLE tbm_payment_methods
    ADD COLUMN IF NOT EXISTS account_holder  VARCHAR(100),
    ADD COLUMN IF NOT EXISTS bank_name       VARCHAR(50),
    ADD COLUMN IF NOT EXISTS qris_image_url  VARCHAR(255),
    ADD COLUMN IF NOT EXISTS display_order   INT NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS description     VARCHAR(255);

-- ===== 2. Seed/Update Payment Methods =====
-- Hapus seed lama (kalau ada) supaya bisa di-reset bersih
-- (gunakan ON CONFLICT untuk yang sudah ada)
INSERT INTO tbm_payment_methods (code, name, type, bank_name, account_no, account_holder, qris_image_url, display_order, description, is_active) VALUES
    ('TF-BCA',     'Transfer BCA',     'transfer', 'BCA',     '0000000001', 'Jihad Kamilullah', NULL, 10, NULL, TRUE),
    ('TF-BRI',     'Transfer BRI',     'transfer', 'BRI',     '0000000002', 'Jihad Kamilullah', NULL, 20, NULL, TRUE),
    ('TF-MANDIRI', 'Transfer Mandiri', 'transfer', 'Mandiri', '0000000003', 'Salwa Nabilah',    NULL, 30, NULL, TRUE),
    ('QRIS',       'QRIS',             'ewallet',  NULL,      NULL,         NULL,               '/storage/payment/qris.png', 40, 'Scan untuk semua e-wallet (GoPay/OVO/Dana/ShopeePay/dll)', TRUE),
    ('COD',        'Cash on Delivery', 'cash',     NULL,      NULL,         NULL,               NULL, 50, 'Bayar tunai saat barang dikirim', TRUE)
ON CONFLICT (code) DO UPDATE SET
    name           = EXCLUDED.name,
    type           = EXCLUDED.type,
    bank_name      = EXCLUDED.bank_name,
    account_no     = EXCLUDED.account_no,
    account_holder = EXCLUDED.account_holder,
    qris_image_url = EXCLUDED.qris_image_url,
    display_order  = EXCLUDED.display_order,
    description    = EXCLUDED.description,
    is_active      = EXCLUDED.is_active;

-- ===== 3. Permissions =====
INSERT INTO tbm_permissions (name, display_name, module, description) VALUES
    -- Sales Order
    ('sales_order.view',     'View Sales Order',     'sales',  'Lihat daftar sales order'),
    ('sales_order.create',   'Create Sales Order',   'sales',  'Buat sales order'),
    ('sales_order.update',   'Update Sales Order',   'sales',  'Edit sales order draft'),
    ('sales_order.confirm',  'Confirm Sales Order',  'sales',  'Confirm SO (book stock)'),
    ('sales_order.cancel',   'Cancel Sales Order',   'sales',  'Batalkan sales order'),
    ('sales_order.print',    'Print Sales Order',    'sales',  'Cetak SO sebagai Proforma'),
    -- Payment Method
    ('payment_method.view',   'View Payment Method',   'master', 'Lihat metode pembayaran'),
    ('payment_method.create', 'Create Payment Method', 'master', 'Tambah metode pembayaran'),
    ('payment_method.update', 'Update Payment Method', 'master', 'Edit metode pembayaran'),
    ('payment_method.delete', 'Delete Payment Method', 'master', 'Hapus metode pembayaran')
ON CONFLICT (name) DO NOTHING;

-- Assign ke role admin & manager
INSERT INTO tbm_role_permissions (role_id, permission_id)
SELECT r.id, p.id
  FROM tbm_roles r CROSS JOIN tbm_permissions p
 WHERE r.name IN ('admin','manager')
   AND p.name IN ('sales_order.view','sales_order.create','sales_order.update','sales_order.confirm','sales_order.cancel','sales_order.print',
                  'payment_method.view','payment_method.create','payment_method.update','payment_method.delete')
ON CONFLICT DO NOTHING;

-- Sales role: bisa CRUD SO, view payment method
INSERT INTO tbm_role_permissions (role_id, permission_id)
SELECT r.id, p.id
  FROM tbm_roles r CROSS JOIN tbm_permissions p
 WHERE r.name = 'sales'
   AND p.name IN ('sales_order.view','sales_order.create','sales_order.update','sales_order.confirm','sales_order.cancel','sales_order.print',
                  'payment_method.view')
ON CONFLICT DO NOTHING;

-- Cashier role: view SO + payment method
INSERT INTO tbm_role_permissions (role_id, permission_id)
SELECT r.id, p.id
  FROM tbm_roles r CROSS JOIN tbm_permissions p
 WHERE r.name = 'cashier'
   AND p.name IN ('sales_order.view','sales_order.print','payment_method.view')
ON CONFLICT DO NOTHING;

-- ===== 4. Doc Sequence SO =====
INSERT INTO tbs_document_sequences (doc_type, prefix, reset_period) VALUES
    ('SO','SO/','yearly')
ON CONFLICT (doc_type) DO NOTHING;

COMMIT;

SELECT 'Phase 5a sales-order patches applied successfully.' AS status;
