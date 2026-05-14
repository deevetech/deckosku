-- DECKO Size Management — SQLite schema
-- All tables intentionally explicit. SQLite types are advisory; we still annotate
-- them for readability and forward compatibility with MySQL.

PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS users (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    name            TEXT    NOT NULL,
    email           TEXT    NOT NULL UNIQUE,
    password_hash   TEXT    NOT NULL,
    role            TEXT    NOT NULL DEFAULT 'admin',
    last_login_at   TEXT    NULL,
    created_at      TEXT    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TEXT    NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS categories (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    code            TEXT    NOT NULL UNIQUE,     -- e.g. "DD"
    name            TEXT    NOT NULL,            -- e.g. "DECKODURANTE GARAGE TILES"
    sort_order      INTEGER NOT NULL DEFAULT 0,
    created_at      TEXT    NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS skus (
    id                       INTEGER PRIMARY KEY AUTOINCREMENT,
    category_id              INTEGER NULL REFERENCES categories(id) ON DELETE SET NULL,

    -- Identification
    sku_code                 TEXT NOT NULL UNIQUE,         -- "DD-CRE-BL"
    description              TEXT NULL,                    -- "DECKO Durante Corner Ramp Edge - Black"
    variant_label            TEXT NULL,                    -- "Single - corner ramp edge"
    internal_barcode         TEXT NULL,
    gtin_barcode             TEXT NULL,                    -- EAN
    box_barcode              TEXT NULL,
    units_per_box            INTEGER NULL,

    -- Photos (paths relative to /storage/photos/)
    photo_product            TEXT NULL,
    photo_internal_barcode   TEXT NULL,
    photo_gtin_barcode       TEXT NULL,
    photo_box                TEXT NULL,
    photo_box_barcode        TEXT NULL,

    -- Unit dimensions (metric)
    unit_length_cm           REAL NULL,
    unit_width_cm            REAL NULL,
    unit_height_cm           REAL NULL,
    unit_weight_kg           REAL NULL,
    unit_volume_cm3          REAL NULL,

    -- Unit dimensions (imperial)
    unit_length_in           REAL NULL,
    unit_width_in            REAL NULL,
    unit_height_in           REAL NULL,
    unit_weight_lb           REAL NULL,
    unit_volume_in3          REAL NULL,

    -- Box dimensions (metric)
    box_length_cm            REAL NULL,
    box_width_cm             REAL NULL,
    box_height_cm            REAL NULL,
    box_weight_kg            REAL NULL,
    box_volume_cm3           REAL NULL,

    -- Box dimensions (imperial)
    box_length_in            REAL NULL,
    box_width_in             REAL NULL,
    box_height_in            REAL NULL,
    box_weight_lb            REAL NULL,
    box_volume_in3           REAL NULL,

    -- Search optimisation
    search_haystack          TEXT NULL,                    -- denormalised: sku + description + barcodes, lowercased

    created_at               TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at               TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_skus_category    ON skus(category_id);
CREATE INDEX IF NOT EXISTS idx_skus_haystack    ON skus(search_haystack);
CREATE INDEX IF NOT EXISTS idx_skus_internal    ON skus(internal_barcode);
CREATE INDEX IF NOT EXISTS idx_skus_gtin        ON skus(gtin_barcode);
CREATE INDEX IF NOT EXISTS idx_skus_box_barcode ON skus(box_barcode);

CREATE TABLE IF NOT EXISTS import_log (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    source_file   TEXT NOT NULL,
    rows_total    INTEGER NOT NULL DEFAULT 0,
    rows_imported INTEGER NOT NULL DEFAULT 0,
    rows_failed   INTEGER NOT NULL DEFAULT 0,
    photos_saved  INTEGER NOT NULL DEFAULT 0,
    duration_ms   INTEGER NOT NULL DEFAULT 0,
    notes         TEXT NULL,
    created_at    TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
