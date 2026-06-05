-- =====================================================================
-- PATCH 41: Visit Logs + Portal Leads
-- DESC : Tabel pendukung dashboard analytics:
--        - tbh_visit_logs  → 1 row per request ke halaman customer portal
--                            (di-insert oleh middleware LogPortalVisit).
--        - tbh_portal_leads → snapshot keranjang saat customer klik
--                            "Checkout via WhatsApp" di portal (sebelum
--                            window.open(wa.me)). Belum tentu jadi SO.
--        Keduanya hanya append-only, tidak punya FK ke master data.
-- =====================================================================

BEGIN;

-- =====================================================================
-- 1) tbh_visit_logs — log kunjungan halaman portal
-- =====================================================================
CREATE TABLE IF NOT EXISTS tbh_visit_logs (
    id          BIGSERIAL PRIMARY KEY,
    path        VARCHAR(255) NOT NULL,
    ip          VARCHAR(45),
    ua_hash     VARCHAR(64),
    session_id  VARCHAR(64),
    referer     VARCHAR(500),
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_visit_logs_created_at
    ON tbh_visit_logs(created_at);

CREATE INDEX IF NOT EXISTS idx_visit_logs_path_created_at
    ON tbh_visit_logs(path, created_at);

COMMENT ON TABLE  tbh_visit_logs IS
    'Log kunjungan portal publik. Append-only, di-insert middleware LogPortalVisit.';
COMMENT ON COLUMN tbh_visit_logs.ua_hash IS
    'SHA-256 dari User-Agent (64 char) — supaya bisa hitung unique visitor tanpa simpan UA mentah.';

-- =====================================================================
-- 2) tbh_portal_leads — lead intent checkout via WhatsApp
-- =====================================================================
CREATE TABLE IF NOT EXISTS tbh_portal_leads (
    id            BIGSERIAL PRIMARY KEY,
    items         JSONB NOT NULL,
    item_count    INTEGER NOT NULL DEFAULT 0,
    total_amount  NUMERIC(14,2) NOT NULL DEFAULT 0,
    ip            VARCHAR(45),
    ua_hash       VARCHAR(64),
    session_id    VARCHAR(64),
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_portal_leads_created_at
    ON tbh_portal_leads(created_at);

COMMENT ON TABLE  tbh_portal_leads IS
    'Snapshot keranjang saat customer klik checkout via WA di portal. '
    'Belum tentu jadi sales order — dipakai untuk hitung funnel di dashboard.';
COMMENT ON COLUMN tbh_portal_leads.items IS
    'Array of {name, qty, uom, price}. Disimpan as-is dari payload portal.';

COMMIT;

SELECT 'Patch 41 applied: tbh_visit_logs + tbh_portal_leads created.' AS status;
