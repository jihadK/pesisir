-- ============================================================
-- PATCH 28: Tambah kolom discount_amount di PO items
-- Discount flat (Rupiah) per baris item, sebelum total.
-- Subtotal = (qty_gram * price_per_kg / 1000) - discount_amount
-- ============================================================

ALTER TABLE tbr_purchase_order_items
    ADD COLUMN IF NOT EXISTS discount_amount NUMERIC(14,2) NOT NULL DEFAULT 0
        CHECK (discount_amount >= 0);

COMMENT ON COLUMN tbr_purchase_order_items.discount_amount
    IS 'Diskon flat (Rp) per baris item; dikurangkan dari (qty*price).';
