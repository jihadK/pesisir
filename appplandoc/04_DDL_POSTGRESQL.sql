-- =====================================================================
-- DATABASE: fish_stock_sales
-- DBMS    : PostgreSQL 14+
-- DESC    : Aplikasi Stock & Penjualan Ikan
--           Master Data | Inventory | Penjualan | Invoicing
-- =====================================================================

SET client_min_messages = WARNING;
SET timezone = 'Asia/Jakarta';

-- =====================================================================
-- EXTENSIONS
-- =====================================================================
CREATE EXTENSION IF NOT EXISTS pgcrypto;
CREATE EXTENSION IF NOT EXISTS pg_trgm;

-- =====================================================================
-- 1. MASTER DATA
-- =====================================================================

-- 1.1 ROLES
CREATE TABLE tbm_roles (
    id          BIGSERIAL PRIMARY KEY,
    name        VARCHAR(50)  NOT NULL UNIQUE,
    description VARCHAR(255),
    created_date  TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    updated_date  TIMESTAMPTZ
);

-- 1.2 USERS
CREATE TABLE tbm_users (
    id              BIGSERIAL PRIMARY KEY,
    role_id         BIGINT       NOT NULL REFERENCES tbm_roles(id) ON DELETE RESTRICT,
    username        VARCHAR(50)  NOT NULL UNIQUE,
    email           VARCHAR(100) NOT NULL UNIQUE,
    password_hash   VARCHAR(255) NOT NULL,
    full_name       VARCHAR(100) NOT NULL,
    phone           VARCHAR(20),
    is_active       BOOLEAN      NOT NULL DEFAULT TRUE,
    last_login_at   TIMESTAMPTZ,
    created_date      TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    updated_date      TIMESTAMPTZ,
    deleted_date      TIMESTAMPTZ,
    CONSTRAINT chk_users_email CHECK (email ~* '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$')
);
CREATE INDEX idx_users_active ON tbm_users(is_active) WHERE deleted_date IS NULL;
CREATE INDEX idx_users_role   ON tbm_users(role_id);

-- 1.3 WAREHOUSES
CREATE TABLE tbm_warehouses (
    id              BIGSERIAL PRIMARY KEY,
    code            VARCHAR(20)  NOT NULL UNIQUE,
    name            VARCHAR(100) NOT NULL,
    address         TEXT,
    type            VARCHAR(20)  NOT NULL DEFAULT 'cold_storage'
                    CHECK (type IN ('cold_storage','freezer','dry','retail')),
    temperature_min NUMERIC(4,1),
    temperature_max NUMERIC(4,1),
    pic_user_id     BIGINT       REFERENCES tbm_users(id) ON DELETE SET NULL,
    is_active       BOOLEAN      NOT NULL DEFAULT TRUE,
    created_date      TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    updated_date      TIMESTAMPTZ,
    CONSTRAINT chk_wh_temp CHECK (temperature_min IS NULL OR temperature_max IS NULL OR temperature_min <= temperature_max)
);

-- 1.4 UNITS OF MEASURE
CREATE TABLE tbm_units_of_measure (
    id     BIGSERIAL PRIMARY KEY,
    code   VARCHAR(10) NOT NULL UNIQUE,
    name   VARCHAR(50) NOT NULL,
    symbol VARCHAR(10)
);

-- 1.5 CATEGORIES
CREATE TABLE tbm_categories (
    id          BIGSERIAL PRIMARY KEY,
    parent_id   BIGINT REFERENCES tbm_categories(id) ON DELETE SET NULL,
    name        VARCHAR(100) NOT NULL,
    slug        VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_date  TIMESTAMPTZ  NOT NULL DEFAULT NOW()
);
CREATE INDEX idx_categories_parent ON tbm_categories(parent_id);

-- 1.6 PRODUCT GRADES
CREATE TABLE tbm_product_grades (
    id   BIGSERIAL PRIMARY KEY,
    code VARCHAR(10) NOT NULL UNIQUE,
    name VARCHAR(50) NOT NULL
);

-- 1.7 PRODUCTS
CREATE TABLE tbm_products (
    id                  BIGSERIAL PRIMARY KEY,
    sku                 VARCHAR(50)  NOT NULL UNIQUE,
    barcode             VARCHAR(50)  UNIQUE,
    category_id         BIGINT       NOT NULL REFERENCES tbm_categories(id) ON DELETE RESTRICT,
    grade_id            BIGINT       REFERENCES tbm_product_grades(id) ON DELETE SET NULL,
    base_uom_id         BIGINT       NOT NULL REFERENCES tbm_units_of_measure(id) ON DELETE RESTRICT,
    name                VARCHAR(150) NOT NULL,
    scientific_name     VARCHAR(150),
    origin              VARCHAR(100),
    description         TEXT,
    storage_temp_min    NUMERIC(4,1),
    storage_temp_max    NUMERIC(4,1),
    shelf_life_days     INT,
    is_perishable       BOOLEAN      NOT NULL DEFAULT TRUE,
    min_stock_level     NUMERIC(14,3) DEFAULT 0,
    max_stock_level     NUMERIC(14,3),
    default_cost_price  NUMERIC(14,2) DEFAULT 0,
    default_sell_price  NUMERIC(14,2) DEFAULT 0,
    image_url           VARCHAR(255),
    is_active           BOOLEAN      NOT NULL DEFAULT TRUE,
    created_by          BIGINT       REFERENCES tbm_users(id) ON DELETE SET NULL,
    created_date          TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    updated_date          TIMESTAMPTZ,
    deleted_date          TIMESTAMPTZ,
    CONSTRAINT chk_prod_stock_level CHECK (max_stock_level IS NULL OR min_stock_level <= max_stock_level),
    CONSTRAINT chk_prod_prices      CHECK (default_cost_price >= 0 AND default_sell_price >= 0)
);
CREATE INDEX idx_products_sku       ON tbm_products(sku) WHERE deleted_date IS NULL;
CREATE INDEX idx_products_category  ON tbm_products(category_id);
CREATE INDEX idx_products_active    ON tbm_products(is_active) WHERE deleted_date IS NULL;
CREATE INDEX idx_products_name_trgm ON tbm_products USING gin(name gin_trgm_ops);

-- 1.8 PRODUCT UOM CONVERSIONS
CREATE TABLE tbm_product_uom_conversions (
    id                BIGSERIAL PRIMARY KEY,
    product_id        BIGINT NOT NULL REFERENCES tbm_products(id) ON DELETE CASCADE,
    from_uom_id       BIGINT NOT NULL REFERENCES tbm_units_of_measure(id),
    to_uom_id         BIGINT NOT NULL REFERENCES tbm_units_of_measure(id),
    conversion_factor NUMERIC(14,4) NOT NULL CHECK (conversion_factor > 0),
    created_date        TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    CONSTRAINT uq_uom_conv UNIQUE (product_id, from_uom_id, to_uom_id),
    CONSTRAINT chk_uom_conv_diff CHECK (from_uom_id <> to_uom_id)
);

-- 1.9 PRICE TIERS
CREATE TABLE tbm_price_tiers (
    id          BIGSERIAL PRIMARY KEY,
    name        VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255),
    is_active   BOOLEAN     NOT NULL DEFAULT TRUE
);

-- 1.10 PRODUCT PRICES
CREATE TABLE tbm_product_prices (
    id              BIGSERIAL PRIMARY KEY,
    product_id      BIGINT NOT NULL REFERENCES tbm_products(id) ON DELETE CASCADE,
    price_tier_id   BIGINT NOT NULL REFERENCES tbm_price_tiers(id) ON DELETE CASCADE,
    price           NUMERIC(14,2) NOT NULL CHECK (price >= 0),
    min_quantity    NUMERIC(14,3) NOT NULL DEFAULT 0,
    effective_from  DATE NOT NULL DEFAULT CURRENT_DATE,
    effective_to    DATE,
    created_date      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    CONSTRAINT chk_prod_price_period CHECK (effective_to IS NULL OR effective_from <= effective_to),
    CONSTRAINT uq_prod_price UNIQUE (product_id, price_tier_id, effective_from)
);
CREATE INDEX idx_product_prices_lookup ON tbm_product_prices(product_id, price_tier_id, effective_from DESC);

-- 1.11 SUPPLIERS
CREATE TABLE tbm_suppliers (
    id                   BIGSERIAL PRIMARY KEY,
    code                 VARCHAR(20)  NOT NULL UNIQUE,
    name                 VARCHAR(150) NOT NULL,
    contact_person       VARCHAR(100),
    phone                VARCHAR(20),
    email                VARCHAR(100),
    address              TEXT,
    city                 VARCHAR(100),
    npwp                 VARCHAR(30),
    bank_name            VARCHAR(50),
    bank_account         VARCHAR(50),
    payment_terms_days   INT NOT NULL DEFAULT 0 CHECK (payment_terms_days >= 0),
    is_active            BOOLEAN NOT NULL DEFAULT TRUE,
    created_date           TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_date           TIMESTAMPTZ,
    deleted_date           TIMESTAMPTZ
);
CREATE INDEX idx_suppliers_active ON tbm_suppliers(is_active) WHERE deleted_date IS NULL;

-- 1.12 CUSTOMERS
CREATE TABLE tbm_customers (
    id                   BIGSERIAL PRIMARY KEY,
    code                 VARCHAR(20)  NOT NULL UNIQUE,
    price_tier_id        BIGINT       REFERENCES tbm_price_tiers(id) ON DELETE SET NULL,
    name                 VARCHAR(150) NOT NULL,
    customer_type        VARCHAR(20)  NOT NULL DEFAULT 'individu'
                          CHECK (customer_type IN ('individu','corporate','reseller','restoran','pasar')),
    contact_person       VARCHAR(100),
    phone                VARCHAR(20),
    email                VARCHAR(100),
    address              TEXT,
    city                 VARCHAR(100),
    npwp                 VARCHAR(30),
    credit_limit         NUMERIC(14,2) NOT NULL DEFAULT 0 CHECK (credit_limit >= 0),
    payment_terms_days   INT NOT NULL DEFAULT 0 CHECK (payment_terms_days >= 0),
    is_active            BOOLEAN NOT NULL DEFAULT TRUE,
    created_date           TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_date           TIMESTAMPTZ,
    deleted_date           TIMESTAMPTZ
);
CREATE INDEX idx_customers_active ON tbm_customers(is_active) WHERE deleted_date IS NULL;
CREATE INDEX idx_customers_tier   ON tbm_customers(price_tier_id);

-- 1.13 TAXES
CREATE TABLE tbm_taxes (
    id        BIGSERIAL PRIMARY KEY,
    code      VARCHAR(20) NOT NULL UNIQUE,
    name      VARCHAR(50) NOT NULL,
    rate      NUMERIC(5,2) NOT NULL CHECK (rate >= 0 AND rate <= 100),
    is_active BOOLEAN NOT NULL DEFAULT TRUE
);

-- 1.14 PAYMENT METHODS
CREATE TABLE tbm_payment_methods (
    id         BIGSERIAL PRIMARY KEY,
    code       VARCHAR(20) NOT NULL UNIQUE,
    name       VARCHAR(50) NOT NULL,
    type       VARCHAR(20) NOT NULL
                CHECK (type IN ('cash','transfer','giro','cheque','ewallet','card')),
    account_no VARCHAR(50),
    is_active  BOOLEAN NOT NULL DEFAULT TRUE
);

-- =====================================================================
-- 2. INVENTORY
-- =====================================================================

-- 2.1 PRODUCT BATCHES
CREATE TABLE tbm_product_batches (
    id                  BIGSERIAL PRIMARY KEY,
    product_id          BIGINT NOT NULL REFERENCES tbm_products(id) ON DELETE RESTRICT,
    batch_number        VARCHAR(50) NOT NULL,
    supplier_id         BIGINT REFERENCES tbm_suppliers(id) ON DELETE SET NULL,
    received_date       DATE NOT NULL,
    production_date     DATE,
    expiry_date         DATE,
    catch_date          DATE,
    catch_location      VARCHAR(150),
    cost_price          NUMERIC(14,2) NOT NULL DEFAULT 0 CHECK (cost_price >= 0),
    initial_quantity    NUMERIC(14,3) NOT NULL CHECK (initial_quantity >= 0),
    remaining_quantity  NUMERIC(14,3) NOT NULL CHECK (remaining_quantity >= 0),
    quality_status      VARCHAR(20) NOT NULL DEFAULT 'fresh'
                         CHECK (quality_status IN ('fresh','good','near_expiry','expired','damaged')),
    notes               TEXT,
    created_date          TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_date          TIMESTAMPTZ,
    CONSTRAINT uq_batch UNIQUE (product_id, batch_number),
    CONSTRAINT chk_batch_remain CHECK (remaining_quantity <= initial_quantity)
);
CREATE INDEX idx_batch_product   ON tbm_product_batches(product_id);
CREATE INDEX idx_batch_expiry    ON tbm_product_batches(expiry_date) WHERE remaining_quantity > 0;
CREATE INDEX idx_batch_supplier  ON tbm_product_batches(supplier_id);

-- 2.2 STOCK BALANCES
CREATE TABLE tbs_stock_balances (
    id                  BIGSERIAL PRIMARY KEY,
    product_id          BIGINT NOT NULL REFERENCES tbm_products(id) ON DELETE RESTRICT,
    warehouse_id        BIGINT NOT NULL REFERENCES tbm_warehouses(id) ON DELETE RESTRICT,
    batch_id            BIGINT REFERENCES tbm_product_batches(id) ON DELETE RESTRICT,
    quantity            NUMERIC(14,3) NOT NULL DEFAULT 0,
    reserved_quantity   NUMERIC(14,3) NOT NULL DEFAULT 0,
    available_quantity  NUMERIC(14,3) GENERATED ALWAYS AS (quantity - reserved_quantity) STORED,
    last_updated_date     TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    CONSTRAINT chk_sb_qty_nonneg     CHECK (quantity >= 0),
    CONSTRAINT chk_sb_reserved_range CHECK (reserved_quantity >= 0 AND reserved_quantity <= quantity)
);
CREATE UNIQUE INDEX uq_stock_balance
    ON tbs_stock_balances (product_id, warehouse_id, COALESCE(batch_id, 0));
CREATE INDEX idx_sb_warehouse ON tbs_stock_balances(warehouse_id);
CREATE INDEX idx_sb_product   ON tbs_stock_balances(product_id);

-- 2.3 STOCK MOVEMENTS
CREATE TABLE tbh_stock_movements (
    id              BIGSERIAL PRIMARY KEY,
    movement_number VARCHAR(30) NOT NULL UNIQUE,
    product_id      BIGINT NOT NULL REFERENCES tbm_products(id) ON DELETE RESTRICT,
    warehouse_id    BIGINT NOT NULL REFERENCES tbm_warehouses(id) ON DELETE RESTRICT,
    batch_id        BIGINT REFERENCES tbm_product_batches(id) ON DELETE RESTRICT,
    movement_type   VARCHAR(20) NOT NULL
                     CHECK (movement_type IN (
                         'in_purchase','in_return','in_adjustment','in_transfer',
                         'out_sale','out_return','out_adjustment','out_transfer','out_waste'
                     )),
    reference_type  VARCHAR(30),
    reference_id    BIGINT,
    quantity        NUMERIC(14,3) NOT NULL CHECK (quantity <> 0),
    uom_id          BIGINT NOT NULL REFERENCES tbm_units_of_measure(id),
    cost_price      NUMERIC(14,2) DEFAULT 0,
    balance_after   NUMERIC(14,3),
    notes           TEXT,
    created_by      BIGINT REFERENCES tbm_users(id) ON DELETE SET NULL,
    created_date      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX idx_sm_card     ON tbh_stock_movements(product_id, warehouse_id, created_date DESC);
CREATE INDEX idx_sm_batch    ON tbh_stock_movements(batch_id);
CREATE INDEX idx_sm_ref      ON tbh_stock_movements(reference_type, reference_id);
CREATE INDEX idx_sm_date     ON tbh_stock_movements(created_date DESC);

-- 2.4 PURCHASE ORDERS
CREATE TABLE tbr_purchase_orders (
    id            BIGSERIAL PRIMARY KEY,
    po_number     VARCHAR(30) NOT NULL UNIQUE,
    supplier_id   BIGINT NOT NULL REFERENCES tbm_suppliers(id) ON DELETE RESTRICT,
    warehouse_id  BIGINT NOT NULL REFERENCES tbm_warehouses(id) ON DELETE RESTRICT,
    po_date       DATE NOT NULL,
    expected_date DATE,
    status        VARCHAR(20) NOT NULL DEFAULT 'draft'
                   CHECK (status IN ('draft','submitted','partial','received','cancelled')),
    subtotal      NUMERIC(14,2) NOT NULL DEFAULT 0,
    tax_amount    NUMERIC(14,2) NOT NULL DEFAULT 0,
    total_amount  NUMERIC(14,2) NOT NULL DEFAULT 0,
    notes         TEXT,
    created_by    BIGINT REFERENCES tbm_users(id) ON DELETE SET NULL,
    approved_by   BIGINT REFERENCES tbm_users(id) ON DELETE SET NULL,
    created_date    TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_date    TIMESTAMPTZ
);
CREATE INDEX idx_po_supplier ON tbr_purchase_orders(supplier_id);
CREATE INDEX idx_po_status   ON tbr_purchase_orders(status);
CREATE INDEX idx_po_date     ON tbr_purchase_orders(po_date DESC);

-- 2.5 PURCHASE ORDER ITEMS
CREATE TABLE tbr_purchase_order_items (
    id                BIGSERIAL PRIMARY KEY,
    po_id             BIGINT NOT NULL REFERENCES tbr_purchase_orders(id) ON DELETE CASCADE,
    product_id        BIGINT NOT NULL REFERENCES tbm_products(id) ON DELETE RESTRICT,
    uom_id            BIGINT NOT NULL REFERENCES tbm_units_of_measure(id),
    quantity          NUMERIC(14,3) NOT NULL CHECK (quantity > 0),
    unit_price        NUMERIC(14,2) NOT NULL CHECK (unit_price >= 0),
    received_quantity NUMERIC(14,3) NOT NULL DEFAULT 0 CHECK (received_quantity >= 0),
    subtotal          NUMERIC(14,2) NOT NULL,
    CONSTRAINT chk_poi_received_le_qty CHECK (received_quantity <= quantity)
);
CREATE INDEX idx_poi_po      ON tbr_purchase_order_items(po_id);
CREATE INDEX idx_poi_product ON tbr_purchase_order_items(product_id);

-- 2.6 GOODS RECEIPTS
CREATE TABLE tbr_goods_receipts (
    id                  BIGSERIAL PRIMARY KEY,
    grn_number          VARCHAR(30) NOT NULL UNIQUE,
    po_id               BIGINT REFERENCES tbr_purchase_orders(id) ON DELETE SET NULL,
    supplier_id         BIGINT NOT NULL REFERENCES tbm_suppliers(id) ON DELETE RESTRICT,
    warehouse_id        BIGINT NOT NULL REFERENCES tbm_warehouses(id) ON DELETE RESTRICT,
    receipt_date        DATE NOT NULL,
    supplier_invoice_no VARCHAR(50),
    status              VARCHAR(20) NOT NULL DEFAULT 'draft'
                         CHECK (status IN ('draft','received','rejected')),
    notes               TEXT,
    received_by         BIGINT REFERENCES tbm_users(id) ON DELETE SET NULL,
    created_date          TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_date          TIMESTAMPTZ
);
CREATE INDEX idx_grn_po       ON tbr_goods_receipts(po_id);
CREATE INDEX idx_grn_supplier ON tbr_goods_receipts(supplier_id);
CREATE INDEX idx_grn_date     ON tbr_goods_receipts(receipt_date DESC);

-- 2.7 GOODS RECEIPT ITEMS
CREATE TABLE tbr_goods_receipt_items (
    id            BIGSERIAL PRIMARY KEY,
    grn_id        BIGINT NOT NULL REFERENCES tbr_goods_receipts(id) ON DELETE CASCADE,
    po_item_id    BIGINT REFERENCES tbr_purchase_order_items(id) ON DELETE SET NULL,
    product_id    BIGINT NOT NULL REFERENCES tbm_products(id) ON DELETE RESTRICT,
    batch_id      BIGINT REFERENCES tbm_product_batches(id) ON DELETE RESTRICT,
    quantity      NUMERIC(14,3) NOT NULL CHECK (quantity > 0),
    uom_id        BIGINT NOT NULL REFERENCES tbm_units_of_measure(id),
    cost_price    NUMERIC(14,2) NOT NULL CHECK (cost_price >= 0),
    quality_check VARCHAR(20) NOT NULL DEFAULT 'passed'
                   CHECK (quality_check IN ('passed','failed','partial')),
    notes         TEXT
);
CREATE INDEX idx_gri_grn     ON tbr_goods_receipt_items(grn_id);
CREATE INDEX idx_gri_product ON tbr_goods_receipt_items(product_id);

-- 2.8 STOCK TRANSFERS
CREATE TABLE tbr_stock_transfers (
    id                 BIGSERIAL PRIMARY KEY,
    transfer_number    VARCHAR(30) NOT NULL UNIQUE,
    from_warehouse_id  BIGINT NOT NULL REFERENCES tbm_warehouses(id) ON DELETE RESTRICT,
    to_warehouse_id    BIGINT NOT NULL REFERENCES tbm_warehouses(id) ON DELETE RESTRICT,
    transfer_date      DATE NOT NULL,
    status             VARCHAR(20) NOT NULL DEFAULT 'draft'
                        CHECK (status IN ('draft','in_transit','received','cancelled')),
    notes              TEXT,
    created_by         BIGINT REFERENCES tbm_users(id) ON DELETE SET NULL,
    received_by        BIGINT REFERENCES tbm_users(id) ON DELETE SET NULL,
    created_date         TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_date         TIMESTAMPTZ,
    CONSTRAINT chk_st_diff_wh CHECK (from_warehouse_id <> to_warehouse_id)
);

-- 2.9 STOCK TRANSFER ITEMS
CREATE TABLE tbr_stock_transfer_items (
    id           BIGSERIAL PRIMARY KEY,
    transfer_id  BIGINT NOT NULL REFERENCES tbr_stock_transfers(id) ON DELETE CASCADE,
    product_id   BIGINT NOT NULL REFERENCES tbm_products(id) ON DELETE RESTRICT,
    batch_id     BIGINT REFERENCES tbm_product_batches(id) ON DELETE RESTRICT,
    quantity     NUMERIC(14,3) NOT NULL CHECK (quantity > 0),
    uom_id       BIGINT NOT NULL REFERENCES tbm_units_of_measure(id),
    notes        VARCHAR(255)
);

-- 2.10 STOCK OPNAME
CREATE TABLE tbr_stock_opnames (
    id            BIGSERIAL PRIMARY KEY,
    opname_number VARCHAR(30) NOT NULL UNIQUE,
    warehouse_id  BIGINT NOT NULL REFERENCES tbm_warehouses(id) ON DELETE RESTRICT,
    opname_date   DATE NOT NULL,
    status        VARCHAR(20) NOT NULL DEFAULT 'draft'
                   CHECK (status IN ('draft','counted','adjusted','closed')),
    notes         TEXT,
    created_by    BIGINT REFERENCES tbm_users(id) ON DELETE SET NULL,
    approved_by   BIGINT REFERENCES tbm_users(id) ON DELETE SET NULL,
    created_date    TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_date    TIMESTAMPTZ
);

-- 2.11 STOCK OPNAME ITEMS
CREATE TABLE tbr_stock_opname_items (
    id                BIGSERIAL PRIMARY KEY,
    opname_id         BIGINT NOT NULL REFERENCES tbr_stock_opnames(id) ON DELETE CASCADE,
    product_id        BIGINT NOT NULL REFERENCES tbm_products(id) ON DELETE RESTRICT,
    batch_id          BIGINT REFERENCES tbm_product_batches(id) ON DELETE RESTRICT,
    system_quantity   NUMERIC(14,3) NOT NULL DEFAULT 0,
    physical_quantity NUMERIC(14,3) NOT NULL DEFAULT 0,
    variance_quantity NUMERIC(14,3) GENERATED ALWAYS AS (physical_quantity - system_quantity) STORED,
    variance_value    NUMERIC(14,2) DEFAULT 0,
    reason            VARCHAR(255)
);

-- =====================================================================
-- 3. PENJUALAN
-- =====================================================================

-- 3.1 SALES ORDERS
CREATE TABLE tbr_sales_orders (
    id                  BIGSERIAL PRIMARY KEY,
    so_number           VARCHAR(30) NOT NULL UNIQUE,
    customer_id         BIGINT NOT NULL REFERENCES tbm_customers(id) ON DELETE RESTRICT,
    sales_user_id       BIGINT REFERENCES tbm_users(id) ON DELETE SET NULL,
    warehouse_id        BIGINT NOT NULL REFERENCES tbm_warehouses(id) ON DELETE RESTRICT,
    order_date          DATE NOT NULL,
    delivery_date       DATE,
    status              VARCHAR(20) NOT NULL DEFAULT 'draft'
                         CHECK (status IN ('draft','confirmed','partial','delivered','invoiced','cancelled')),
    subtotal            NUMERIC(14,2) NOT NULL DEFAULT 0,
    discount_amount     NUMERIC(14,2) NOT NULL DEFAULT 0,
    tax_amount          NUMERIC(14,2) NOT NULL DEFAULT 0,
    shipping_cost       NUMERIC(14,2) NOT NULL DEFAULT 0,
    total_amount        NUMERIC(14,2) NOT NULL DEFAULT 0,
    payment_terms_days  INT NOT NULL DEFAULT 0,
    notes               TEXT,
    created_by          BIGINT REFERENCES tbm_users(id) ON DELETE SET NULL,
    approved_by         BIGINT REFERENCES tbm_users(id) ON DELETE SET NULL,
    created_date          TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_date          TIMESTAMPTZ
);
CREATE INDEX idx_so_customer ON tbr_sales_orders(customer_id);
CREATE INDEX idx_so_status   ON tbr_sales_orders(status);
CREATE INDEX idx_so_date     ON tbr_sales_orders(order_date DESC);

-- 3.2 SALES ORDER ITEMS
CREATE TABLE tbr_sales_order_items (
    id                  BIGSERIAL PRIMARY KEY,
    so_id               BIGINT NOT NULL REFERENCES tbr_sales_orders(id) ON DELETE CASCADE,
    product_id          BIGINT NOT NULL REFERENCES tbm_products(id) ON DELETE RESTRICT,
    uom_id              BIGINT NOT NULL REFERENCES tbm_units_of_measure(id),
    quantity            NUMERIC(14,3) NOT NULL CHECK (quantity > 0),
    delivered_quantity  NUMERIC(14,3) NOT NULL DEFAULT 0 CHECK (delivered_quantity >= 0),
    unit_price          NUMERIC(14,2) NOT NULL CHECK (unit_price >= 0),
    discount_pct        NUMERIC(5,2)  NOT NULL DEFAULT 0 CHECK (discount_pct BETWEEN 0 AND 100),
    discount_amount     NUMERIC(14,2) NOT NULL DEFAULT 0 CHECK (discount_amount >= 0),
    subtotal            NUMERIC(14,2) NOT NULL,
    notes               VARCHAR(255),
    CONSTRAINT chk_soi_delivered_le_qty CHECK (delivered_quantity <= quantity)
);
CREATE INDEX idx_soi_so      ON tbr_sales_order_items(so_id);
CREATE INDEX idx_soi_product ON tbr_sales_order_items(product_id);

-- 3.3 DELIVERY ORDERS
CREATE TABLE tbr_delivery_orders (
    id                BIGSERIAL PRIMARY KEY,
    do_number         VARCHAR(30) NOT NULL UNIQUE,
    so_id             BIGINT REFERENCES tbr_sales_orders(id) ON DELETE SET NULL,
    customer_id       BIGINT NOT NULL REFERENCES tbm_customers(id) ON DELETE RESTRICT,
    warehouse_id      BIGINT NOT NULL REFERENCES tbm_warehouses(id) ON DELETE RESTRICT,
    delivery_date     DATE NOT NULL,
    driver_name       VARCHAR(100),
    vehicle_no        VARCHAR(20),
    status            VARCHAR(20) NOT NULL DEFAULT 'draft'
                       CHECK (status IN ('draft','shipped','delivered','returned','cancelled')),
    delivered_at      TIMESTAMPTZ,
    received_by_name  VARCHAR(100),
    notes             TEXT,
    created_by        BIGINT REFERENCES tbm_users(id) ON DELETE SET NULL,
    created_date        TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_date        TIMESTAMPTZ
);
CREATE INDEX idx_do_so       ON tbr_delivery_orders(so_id);
CREATE INDEX idx_do_customer ON tbr_delivery_orders(customer_id);
CREATE INDEX idx_do_status   ON tbr_delivery_orders(status);
CREATE INDEX idx_do_date     ON tbr_delivery_orders(delivery_date DESC);

-- 3.4 DELIVERY ORDER ITEMS
CREATE TABLE tbr_delivery_order_items (
    id           BIGSERIAL PRIMARY KEY,
    do_id        BIGINT NOT NULL REFERENCES tbr_delivery_orders(id) ON DELETE CASCADE,
    so_item_id   BIGINT REFERENCES tbr_sales_order_items(id) ON DELETE SET NULL,
    product_id   BIGINT NOT NULL REFERENCES tbm_products(id) ON DELETE RESTRICT,
    batch_id     BIGINT REFERENCES tbm_product_batches(id) ON DELETE RESTRICT,
    quantity     NUMERIC(14,3) NOT NULL CHECK (quantity > 0),
    uom_id       BIGINT NOT NULL REFERENCES tbm_units_of_measure(id),
    unit_price   NUMERIC(14,2) NOT NULL DEFAULT 0
);
CREATE INDEX idx_doi_do      ON tbr_delivery_order_items(do_id);
CREATE INDEX idx_doi_product ON tbr_delivery_order_items(product_id);

-- 3.5 SALES RETURNS
CREATE TABLE tbr_sales_returns (
    id            BIGSERIAL PRIMARY KEY,
    return_number VARCHAR(30) NOT NULL UNIQUE,
    do_id         BIGINT REFERENCES tbr_delivery_orders(id) ON DELETE SET NULL,
    customer_id   BIGINT NOT NULL REFERENCES tbm_customers(id) ON DELETE RESTRICT,
    warehouse_id  BIGINT NOT NULL REFERENCES tbm_warehouses(id) ON DELETE RESTRICT,
    return_date   DATE NOT NULL,
    reason        VARCHAR(255),
    status        VARCHAR(20) NOT NULL DEFAULT 'draft'
                   CHECK (status IN ('draft','approved','rejected')),
    total_amount  NUMERIC(14,2) NOT NULL DEFAULT 0,
    created_by    BIGINT REFERENCES tbm_users(id) ON DELETE SET NULL,
    approved_by   BIGINT REFERENCES tbm_users(id) ON DELETE SET NULL,
    created_date    TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_date    TIMESTAMPTZ
);
CREATE INDEX idx_sr_do       ON tbr_sales_returns(do_id);
CREATE INDEX idx_sr_customer ON tbr_sales_returns(customer_id);
CREATE INDEX idx_sr_date     ON tbr_sales_returns(return_date DESC);

-- 3.6 SALES RETURN ITEMS
CREATE TABLE tbr_sales_return_items (
    id          BIGSERIAL PRIMARY KEY,
    return_id   BIGINT NOT NULL REFERENCES tbr_sales_returns(id) ON DELETE CASCADE,
    product_id  BIGINT NOT NULL REFERENCES tbm_products(id) ON DELETE RESTRICT,
    batch_id    BIGINT REFERENCES tbm_product_batches(id) ON DELETE RESTRICT,
    quantity    NUMERIC(14,3) NOT NULL CHECK (quantity > 0),
    uom_id      BIGINT NOT NULL REFERENCES tbm_units_of_measure(id),
    unit_price  NUMERIC(14,2) NOT NULL DEFAULT 0,
    condition   VARCHAR(20) NOT NULL DEFAULT 'good'
                 CHECK (condition IN ('good','damaged','expired')),
    restock     BOOLEAN NOT NULL DEFAULT FALSE
);

-- =====================================================================
-- 4. INVOICING & PAYMENT
-- =====================================================================

-- 4.1 INVOICES
CREATE TABLE tbr_invoices (
    id                  BIGSERIAL PRIMARY KEY,
    invoice_number      VARCHAR(30) NOT NULL UNIQUE,
    so_id               BIGINT REFERENCES tbr_sales_orders(id) ON DELETE SET NULL,
    do_id               BIGINT REFERENCES tbr_delivery_orders(id) ON DELETE SET NULL,
    customer_id         BIGINT NOT NULL REFERENCES tbm_customers(id) ON DELETE RESTRICT,
    invoice_date        DATE NOT NULL,
    due_date            DATE NOT NULL,
    tax_id              BIGINT REFERENCES tbm_taxes(id) ON DELETE SET NULL,
    subtotal            NUMERIC(14,2) NOT NULL CHECK (subtotal >= 0),
    discount_amount     NUMERIC(14,2) NOT NULL DEFAULT 0 CHECK (discount_amount >= 0),
    tax_amount          NUMERIC(14,2) NOT NULL DEFAULT 0 CHECK (tax_amount >= 0),
    shipping_cost       NUMERIC(14,2) NOT NULL DEFAULT 0 CHECK (shipping_cost >= 0),
    total_amount        NUMERIC(14,2) NOT NULL CHECK (total_amount >= 0),
    paid_amount         NUMERIC(14,2) NOT NULL DEFAULT 0 CHECK (paid_amount >= 0),
    outstanding_amount  NUMERIC(14,2) GENERATED ALWAYS AS (total_amount - paid_amount) STORED,
    status              VARCHAR(20) NOT NULL DEFAULT 'draft'
                         CHECK (status IN ('draft','issued','partial','paid','overdue','cancelled','void')),
    payment_terms_days  INT NOT NULL DEFAULT 0,
    currency            CHAR(3) NOT NULL DEFAULT 'IDR',
    notes               TEXT,
    created_by          BIGINT REFERENCES tbm_users(id) ON DELETE SET NULL,
    created_date          TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_date          TIMESTAMPTZ,
    CONSTRAINT chk_inv_dates CHECK (invoice_date <= due_date),
    CONSTRAINT chk_inv_paid_le_total CHECK (paid_amount <= total_amount)
);
CREATE INDEX idx_inv_customer ON tbr_invoices(customer_id);
CREATE INDEX idx_inv_status   ON tbr_invoices(status);
CREATE INDEX idx_inv_due      ON tbr_invoices(due_date) WHERE status IN ('issued','partial','overdue');
CREATE INDEX idx_inv_date     ON tbr_invoices(invoice_date DESC);

-- 4.2 INVOICE ITEMS
CREATE TABLE tbr_invoice_items (
    id              BIGSERIAL PRIMARY KEY,
    invoice_id      BIGINT NOT NULL REFERENCES tbr_invoices(id) ON DELETE CASCADE,
    do_item_id      BIGINT REFERENCES tbr_delivery_order_items(id) ON DELETE SET NULL,
    product_id      BIGINT NOT NULL REFERENCES tbm_products(id) ON DELETE RESTRICT,
    description     VARCHAR(255),
    quantity        NUMERIC(14,3) NOT NULL CHECK (quantity > 0),
    uom_id          BIGINT NOT NULL REFERENCES tbm_units_of_measure(id),
    unit_price      NUMERIC(14,2) NOT NULL CHECK (unit_price >= 0),
    discount_amount NUMERIC(14,2) NOT NULL DEFAULT 0 CHECK (discount_amount >= 0),
    subtotal        NUMERIC(14,2) NOT NULL CHECK (subtotal >= 0)
);
CREATE INDEX idx_ii_invoice ON tbr_invoice_items(invoice_id);
CREATE INDEX idx_ii_product ON tbr_invoice_items(product_id);

-- 4.3 PAYMENTS
CREATE TABLE tbr_payments (
    id                BIGSERIAL PRIMARY KEY,
    payment_number    VARCHAR(30) NOT NULL UNIQUE,
    customer_id       BIGINT NOT NULL REFERENCES tbm_customers(id) ON DELETE RESTRICT,
    payment_method_id BIGINT NOT NULL REFERENCES tbm_payment_methods(id) ON DELETE RESTRICT,
    payment_date      DATE NOT NULL,
    amount            NUMERIC(14,2) NOT NULL CHECK (amount > 0),
    reference_no      VARCHAR(50),
    notes             TEXT,
    status            VARCHAR(20) NOT NULL DEFAULT 'pending'
                       CHECK (status IN ('pending','cleared','bounced','cancelled')),
    created_by        BIGINT REFERENCES tbm_users(id) ON DELETE SET NULL,
    created_date        TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_date        TIMESTAMPTZ
);
CREATE INDEX idx_pay_customer ON tbr_payments(customer_id);
CREATE INDEX idx_pay_status   ON tbr_payments(status);
CREATE INDEX idx_pay_date     ON tbr_payments(payment_date DESC);

-- 4.4 INVOICE PAYMENTS (M:N)
CREATE TABLE tbr_invoice_payments (
    id                BIGSERIAL PRIMARY KEY,
    invoice_id        BIGINT NOT NULL REFERENCES tbr_invoices(id) ON DELETE CASCADE,
    payment_id        BIGINT NOT NULL REFERENCES tbr_payments(id) ON DELETE CASCADE,
    allocated_amount  NUMERIC(14,2) NOT NULL CHECK (allocated_amount > 0),
    created_date        TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    CONSTRAINT uq_inv_pay UNIQUE (invoice_id, payment_id)
);
CREATE INDEX idx_ip_invoice ON tbr_invoice_payments(invoice_id);
CREATE INDEX idx_ip_payment ON tbr_invoice_payments(payment_id);

-- =====================================================================
-- 5. SISTEM
-- =====================================================================

CREATE TABLE tbh_audit_logs (
    id          BIGSERIAL PRIMARY KEY,
    user_id     BIGINT REFERENCES tbm_users(id) ON DELETE SET NULL,
    table_name  VARCHAR(50) NOT NULL,
    record_id   BIGINT NOT NULL,
    action      VARCHAR(20) NOT NULL CHECK (action IN ('create','update','delete')),
    old_values  JSONB,
    new_values  JSONB,
    ip_address  INET,
    user_agent  VARCHAR(255),
    created_date  TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX idx_audit_record    ON tbh_audit_logs(table_name, record_id);
CREATE INDEX idx_audit_user_date ON tbh_audit_logs(user_id, created_date DESC);

CREATE TABLE tbs_document_sequences (
    id              BIGSERIAL PRIMARY KEY,
    doc_type        VARCHAR(20) NOT NULL UNIQUE,
    prefix          VARCHAR(20) NOT NULL,
    current_number  INT NOT NULL DEFAULT 0,
    reset_period    VARCHAR(10) NOT NULL DEFAULT 'yearly'
                     CHECK (reset_period IN ('never','yearly','monthly')),
    last_reset_at   DATE,
    updated_date      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- =====================================================================
-- 6. TRIGGERS
-- =====================================================================

-- 6.1 Generic updated_date
CREATE OR REPLACE FUNCTION trg_set_updated_date()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_date = NOW();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DO $$
DECLARE
    t TEXT;
    tables TEXT[] := ARRAY[
        'tbm_users','tbm_warehouses','tbm_products','tbm_suppliers','tbm_customers','tbm_product_batches',
        'tbr_purchase_orders','tbr_goods_receipts','tbr_stock_transfers','tbr_stock_opnames',
        'tbr_sales_orders','tbr_delivery_orders','tbr_sales_returns','tbr_invoices','tbr_payments'
    ];
BEGIN
    FOREACH t IN ARRAY tables LOOP
        EXECUTE format(
            'CREATE TRIGGER trg_%s_updated_date BEFORE UPDATE ON %I
             FOR EACH ROW EXECUTE FUNCTION trg_set_updated_date();', t, t);
    END LOOP;
END$$;

-- 6.2 Auto-update tbs_stock_balances from tbh_stock_movements
CREATE OR REPLACE FUNCTION trg_apply_stock_movement()
RETURNS TRIGGER AS $$
DECLARE
    v_balance NUMERIC(14,3);
BEGIN
    INSERT INTO tbs_stock_balances (product_id, warehouse_id, batch_id, quantity, last_updated_date)
    VALUES (NEW.product_id, NEW.warehouse_id, NEW.batch_id, NEW.quantity, NOW())
    ON CONFLICT (product_id, warehouse_id, COALESCE(batch_id, 0))
    DO UPDATE SET
        quantity        = tbs_stock_balances.quantity + EXCLUDED.quantity,
        last_updated_date = NOW()
    RETURNING quantity INTO v_balance;

    IF NEW.batch_id IS NOT NULL THEN
        UPDATE tbm_product_batches
        SET remaining_quantity = remaining_quantity + NEW.quantity,
            updated_date = NOW()
        WHERE id = NEW.batch_id;
    END IF;

    NEW.balance_after = v_balance;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_stock_movement_apply
BEFORE INSERT ON tbh_stock_movements
FOR EACH ROW EXECUTE FUNCTION trg_apply_stock_movement();

-- 6.3 Recalc invoice paid_amount
CREATE OR REPLACE FUNCTION trg_recalc_invoice_paid()
RETURNS TRIGGER AS $$
DECLARE
    v_inv_id   BIGINT;
    v_total    NUMERIC(14,2);
    v_paid     NUMERIC(14,2);
    v_status   VARCHAR(20);
    v_due      DATE;
BEGIN
    v_inv_id := COALESCE(NEW.invoice_id, OLD.invoice_id);

    SELECT COALESCE(SUM(allocated_amount), 0)
      INTO v_paid
      FROM tbr_invoice_payments
     WHERE invoice_id = v_inv_id;

    SELECT total_amount, due_date INTO v_total, v_due
      FROM tbr_invoices WHERE id = v_inv_id;

    IF v_paid = 0 THEN
        v_status := CASE WHEN v_due < CURRENT_DATE THEN 'overdue' ELSE 'issued' END;
    ELSIF v_paid >= v_total THEN
        v_status := 'paid';
    ELSE
        v_status := CASE WHEN v_due < CURRENT_DATE THEN 'overdue' ELSE 'partial' END;
    END IF;

    UPDATE tbr_invoices
       SET paid_amount = v_paid,
           status      = CASE WHEN status IN ('cancelled','void','draft') THEN status ELSE v_status END,
           updated_date  = NOW()
     WHERE id = v_inv_id;

    RETURN COALESCE(NEW, OLD);
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_inv_pay_recalc
AFTER INSERT OR UPDATE OR DELETE ON tbr_invoice_payments
FOR EACH ROW EXECUTE FUNCTION trg_recalc_invoice_paid();

-- =====================================================================
-- 7. VIEWS
-- =====================================================================

CREATE OR REPLACE VIEW v_stock_summary AS
SELECT
    p.id              AS product_id,
    p.sku,
    p.name,
    c.name            AS category,
    u.code            AS uom,
    SUM(sb.quantity)            AS total_qty,
    SUM(sb.reserved_quantity)   AS total_reserved,
    SUM(sb.available_quantity)  AS total_available,
    SUM(sb.quantity * COALESCE(p.default_cost_price,0)) AS total_value
FROM tbm_products p
LEFT JOIN tbs_stock_balances sb  ON sb.product_id  = p.id
LEFT JOIN tbm_categories c       ON c.id           = p.category_id
LEFT JOIN tbm_units_of_measure u ON u.id           = p.base_uom_id
WHERE p.deleted_date IS NULL
GROUP BY p.id, p.sku, p.name, c.name, u.code;

CREATE OR REPLACE VIEW v_stock_low AS
SELECT v.product_id, v.sku, v.name, v.total_qty, p.min_stock_level
FROM v_stock_summary v
JOIN tbm_products p ON p.id = v.product_id
WHERE v.total_qty < p.min_stock_level;

CREATE OR REPLACE VIEW v_stock_expiring AS
SELECT
    pb.id          AS batch_id,
    pb.batch_number,
    p.sku, p.name,
    pb.expiry_date,
    (pb.expiry_date - CURRENT_DATE) AS days_to_expiry,
    pb.remaining_quantity
FROM tbm_product_batches pb
JOIN tbm_products p ON p.id = pb.product_id
WHERE pb.remaining_quantity > 0
  AND pb.expiry_date IS NOT NULL
  AND pb.expiry_date <= CURRENT_DATE + INTERVAL '7 days'
ORDER BY pb.expiry_date ASC;

CREATE OR REPLACE VIEW v_ar_aging AS
SELECT
    c.id   AS customer_id,
    c.code AS customer_code,
    c.name AS customer_name,
    SUM(CASE WHEN CURRENT_DATE - i.due_date <=  0                                        THEN i.outstanding_amount ELSE 0 END) AS not_due,
    SUM(CASE WHEN CURRENT_DATE - i.due_date BETWEEN  1 AND 30                            THEN i.outstanding_amount ELSE 0 END) AS d_1_30,
    SUM(CASE WHEN CURRENT_DATE - i.due_date BETWEEN 31 AND 60                            THEN i.outstanding_amount ELSE 0 END) AS d_31_60,
    SUM(CASE WHEN CURRENT_DATE - i.due_date BETWEEN 61 AND 90                            THEN i.outstanding_amount ELSE 0 END) AS d_61_90,
    SUM(CASE WHEN CURRENT_DATE - i.due_date >  90                                        THEN i.outstanding_amount ELSE 0 END) AS d_over_90,
    SUM(i.outstanding_amount)                                                                                                  AS total_outstanding
FROM tbr_invoices i
JOIN tbm_customers c ON c.id = i.customer_id
WHERE i.status IN ('issued','partial','overdue')
GROUP BY c.id, c.code, c.name;

CREATE OR REPLACE VIEW v_sales_daily AS
SELECT
    DATE(so.order_date) AS sales_date,
    COUNT(*)            AS total_orders,
    SUM(so.total_amount) AS total_revenue,
    SUM(so.tax_amount)   AS total_tax,
    SUM(so.discount_amount) AS total_discount
FROM tbr_sales_orders so
WHERE so.status NOT IN ('draft','cancelled')
GROUP BY DATE(so.order_date);

-- =====================================================================
-- 8. FUNCTION — Generate document number
-- =====================================================================
CREATE OR REPLACE FUNCTION fn_next_doc_number(p_doc_type VARCHAR)
RETURNS VARCHAR AS $$
DECLARE
    v_seq         tbs_document_sequences%ROWTYPE;
    v_should_reset BOOLEAN := FALSE;
    v_new_number  VARCHAR;
BEGIN
    SELECT * INTO v_seq FROM tbs_document_sequences WHERE doc_type = p_doc_type FOR UPDATE;
    IF NOT FOUND THEN
        RAISE EXCEPTION 'doc_type % not found in tbs_document_sequences', p_doc_type;
    END IF;

    IF v_seq.reset_period = 'yearly' AND
       (v_seq.last_reset_at IS NULL OR EXTRACT(YEAR FROM v_seq.last_reset_at) <> EXTRACT(YEAR FROM CURRENT_DATE))
    THEN
        v_should_reset := TRUE;
    ELSIF v_seq.reset_period = 'monthly' AND
       (v_seq.last_reset_at IS NULL OR DATE_TRUNC('month',v_seq.last_reset_at) <> DATE_TRUNC('month',CURRENT_DATE))
    THEN
        v_should_reset := TRUE;
    END IF;

    IF v_should_reset THEN
        UPDATE tbs_document_sequences
           SET current_number = 1,
               last_reset_at  = CURRENT_DATE,
               updated_date     = NOW()
         WHERE doc_type = p_doc_type
        RETURNING * INTO v_seq;
    ELSE
        UPDATE tbs_document_sequences
           SET current_number = current_number + 1,
               updated_date     = NOW()
         WHERE doc_type = p_doc_type
        RETURNING * INTO v_seq;
    END IF;

    v_new_number := v_seq.prefix
                 || TO_CHAR(CURRENT_DATE,'YYYY/MM') || '/'
                 || LPAD(v_seq.current_number::TEXT, 5, '0');
    RETURN v_new_number;
END;
$$ LANGUAGE plpgsql;

-- =====================================================================
-- 9. SEED DATA
-- =====================================================================

INSERT INTO tbm_roles (name, description) VALUES
    ('admin','Administrator sistem'),
    ('manager','Manajer operasional'),
    ('cashier','Kasir / pembuat invoice'),
    ('warehouse','Staff gudang'),
    ('sales','Sales person');

INSERT INTO tbm_units_of_measure (code,name,symbol) VALUES
    ('KG','Kilogram','kg'),
    ('GR','Gram','gr'),
    ('TON','Ton','ton'),
    ('EKR','Ekor','ekr'),
    ('BOX','Box','box'),
    ('PCS','Pieces','pcs');

INSERT INTO tbm_product_grades (code,name) VALUES
    ('A','Premium / Sashimi Grade'),
    ('B','Standard'),
    ('C','Olahan / Industri');

INSERT INTO tbm_categories (name,slug,description) VALUES
    ('Ikan Laut','ikan-laut','Hasil tangkapan laut'),
    ('Ikan Air Tawar','ikan-air-tawar','Budidaya air tawar'),
    ('Ikan Olahan','ikan-olahan','Produk olahan ikan'),
    ('Seafood Lain','seafood','Cumi, udang, kerang dll');

INSERT INTO tbm_price_tiers (name,description) VALUES
    ('Retail','Harga eceran'),
    ('Grosir','Harga grosir'),
    ('Reseller','Harga reseller'),
    ('Restoran','Harga kontrak restoran');

INSERT INTO tbm_taxes (code,name,rate) VALUES
    ('PPN11','PPN 11%',11.00),
    ('NONTAX','Non Pajak',0.00);

INSERT INTO tbm_payment_methods (code,name,type) VALUES
    ('CASH','Tunai','cash'),
    ('TF-BCA','Transfer BCA','transfer'),
    ('TF-MANDIRI','Transfer Mandiri','transfer'),
    ('GIRO','Giro','giro'),
    ('QRIS','QRIS','ewallet');

INSERT INTO tbs_document_sequences (doc_type,prefix,reset_period) VALUES
    ('PO','PO/','yearly'),
    ('GRN','GRN/','yearly'),
    ('SO','SO/','yearly'),
    ('DO','DO/','yearly'),
    ('INV','INV/','yearly'),
    ('PAY','PAY/','yearly'),
    ('TRF','TRF/','yearly'),
    ('OPN','OPN/','yearly'),
    ('RTN','RTN/','yearly'),
    ('SM','SM/','monthly');

INSERT INTO tbm_users (role_id, username, email, password_hash, full_name)
SELECT id, 'admin', 'admin@example.com',
       '$2y$10$E3XN4mZxK9F7LZpVQpQpHeR1ZCQy7w3W3QvRzS4MxQyK7r2W3Q4ee',
       'System Administrator'
FROM tbm_roles WHERE name = 'admin';

SELECT 'DDL fish_stock_sales installed successfully.' AS status;
