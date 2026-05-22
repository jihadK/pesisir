-- =====================================================================
-- PATCH 36: Kontrak Harga per Customer + Status SO 'fulfilled'
-- DESC : Order flow baru untuk customer tempo (mis. Restoran).
--        1) Tabel kontrak harga per customer × produk (override default).
--        2) Status SO 'fulfilled' = barang sudah kirim, tunggu pelunasan.
--        3) Kolom due_date di SO untuk aging piutang.
-- =====================================================================

BEGIN;

-- 1) Tabel kontrak harga per customer
CREATE TABLE IF NOT EXISTS tbm_customer_product_prices (
    id               BIGSERIAL PRIMARY KEY,
    customer_id      BIGINT NOT NULL REFERENCES tbm_customers(id) ON DELETE CASCADE,
    product_id       BIGINT NOT NULL REFERENCES tbm_products(id)  ON DELETE CASCADE,
    price            NUMERIC(14,2) NOT NULL CHECK (price >= 0),
    min_quantity     NUMERIC(14,3) NOT NULL DEFAULT 0,
    effective_from   DATE NOT NULL DEFAULT CURRENT_DATE,
    effective_to     DATE,
    notes            VARCHAR(255),
    is_active        BOOLEAN NOT NULL DEFAULT TRUE,
    created_by       BIGINT REFERENCES tbm_users(id),
    created_date     TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_date     TIMESTAMPTZ,
    CONSTRAINT chk_cpp_period CHECK (effective_to IS NULL OR effective_from <= effective_to),
    CONSTRAINT uq_cpp UNIQUE (customer_id, product_id, effective_from)
);
CREATE INDEX IF NOT EXISTS idx_cpp_lookup
    ON tbm_customer_product_prices (customer_id, product_id, effective_from DESC);

COMMENT ON TABLE  tbm_customer_product_prices IS 'Harga kontrak khusus per customer per produk. Override default_sell_price.';
COMMENT ON COLUMN tbm_customer_product_prices.effective_from IS 'Berlaku mulai tanggal ini.';
COMMENT ON COLUMN tbm_customer_product_prices.effective_to   IS 'NULL = berlaku selamanya.';

-- 2) Update CHECK constraint status SO (tambah 'fulfilled')
ALTER TABLE tbr_sales_orders DROP CONSTRAINT IF EXISTS tbr_sales_orders_status_check;
ALTER TABLE tbr_sales_orders
    ADD CONSTRAINT tbr_sales_orders_status_check
    CHECK (status IN ('draft','confirmed','partial','delivered','invoiced','fulfilled','paid','cancelled'));

-- 3) Kolom due_date di SO
ALTER TABLE tbr_sales_orders
    ADD COLUMN IF NOT EXISTS due_date DATE,
    ADD COLUMN IF NOT EXISTS fulfilled_at TIMESTAMPTZ;

COMMENT ON COLUMN tbr_sales_orders.due_date     IS 'Jatuh tempo pembayaran (order_date + payment_terms_days).';
COMMENT ON COLUMN tbr_sales_orders.fulfilled_at IS 'Timestamp saat barang dikirim & stock dideduct.';

-- 4) Permission baru
INSERT INTO tbm_permissions (name, display_name, module, description) VALUES
    ('customer_price.view',    'View Customer Contract Price',   'sales', 'Lihat kontrak harga per customer'),
    ('customer_price.create',  'Create Customer Contract Price', 'sales', 'Buat kontrak harga per customer'),
    ('customer_price.update',  'Update Customer Contract Price', 'sales', 'Edit kontrak harga per customer'),
    ('customer_price.delete',  'Delete Customer Contract Price', 'sales', 'Hapus kontrak harga per customer'),
    ('sales_order.fulfill',    'Fulfill Sales Order',            'sales', 'Tandai SO sebagai sudah dikirim (deduct stock)'),
    ('receivable.view',        'View Receivables',               'sales', 'Lihat daftar piutang')
ON CONFLICT (name) DO NOTHING;

-- 5) Grant ke role yang sudah punya sales_order.create (admin, manager, sales)
INSERT INTO tbm_role_permissions (role_id, permission_id)
SELECT DISTINCT rp.role_id, np.id
  FROM tbm_role_permissions rp
  JOIN tbm_permissions ep ON ep.id = rp.permission_id AND ep.name = 'sales_order.create'
 CROSS JOIN tbm_permissions np
 WHERE np.name IN ('customer_price.view','customer_price.create','customer_price.update','customer_price.delete','sales_order.fulfill','receivable.view')
ON CONFLICT DO NOTHING;

COMMIT;

SELECT 'Patch 36 applied: customer contract prices + SO fulfilled status.' AS status;
