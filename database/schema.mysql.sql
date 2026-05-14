-- DECKO Size Management — MySQL 5.7+/8.x schema
-- Mirror of database/schema.sql, translated to MySQL dialect.
-- Apply via `php bin/migrate.php` when DB_DRIVER=mysql in .env.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS users (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name            VARCHAR(190) NOT NULL,
    email           VARCHAR(190) NOT NULL,
    password_hash   VARCHAR(255) NOT NULL,
    role            VARCHAR(32)  NOT NULL DEFAULT 'admin',
    last_login_at   DATETIME     NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS categories (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    code            VARCHAR(16)  NOT NULL,
    name            VARCHAR(190) NOT NULL,
    sort_order      INT          NOT NULL DEFAULT 0,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_categories_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS skus (
    id                       INT UNSIGNED NOT NULL AUTO_INCREMENT,
    category_id              INT UNSIGNED NULL,

    sku_code                 VARCHAR(64)  NOT NULL,
    description              VARCHAR(500) NULL,
    variant_label            VARCHAR(255) NULL,
    internal_barcode         VARCHAR(64)  NULL,
    gtin_barcode             VARCHAR(64)  NULL,
    box_barcode              VARCHAR(64)  NULL,
    units_per_box            INT          NULL,

    photo_product            VARCHAR(255) NULL,
    photo_internal_barcode   VARCHAR(255) NULL,
    photo_gtin_barcode       VARCHAR(255) NULL,
    photo_box                VARCHAR(255) NULL,
    photo_box_barcode        VARCHAR(255) NULL,

    unit_length_cm           DOUBLE NULL,
    unit_width_cm            DOUBLE NULL,
    unit_height_cm           DOUBLE NULL,
    unit_weight_kg           DOUBLE NULL,
    unit_volume_cm3          DOUBLE NULL,

    unit_length_in           DOUBLE NULL,
    unit_width_in            DOUBLE NULL,
    unit_height_in           DOUBLE NULL,
    unit_weight_lb           DOUBLE NULL,
    unit_volume_in3          DOUBLE NULL,

    box_length_cm            DOUBLE NULL,
    box_width_cm             DOUBLE NULL,
    box_height_cm            DOUBLE NULL,
    box_weight_kg            DOUBLE NULL,
    box_volume_cm3           DOUBLE NULL,

    box_length_in            DOUBLE NULL,
    box_width_in             DOUBLE NULL,
    box_height_in            DOUBLE NULL,
    box_weight_lb            DOUBLE NULL,
    box_volume_in3           DOUBLE NULL,

    search_haystack          TEXT NULL,

    created_at               DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at               DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_skus_code (sku_code),
    KEY idx_skus_category    (category_id),
    KEY idx_skus_haystack    (search_haystack(191)),
    KEY idx_skus_internal    (internal_barcode),
    KEY idx_skus_gtin        (gtin_barcode),
    KEY idx_skus_box_barcode (box_barcode),
    CONSTRAINT fk_skus_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS import_log (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    source_file   VARCHAR(500) NOT NULL,
    rows_total    INT NOT NULL DEFAULT 0,
    rows_imported INT NOT NULL DEFAULT 0,
    rows_failed   INT NOT NULL DEFAULT 0,
    photos_saved  INT NOT NULL DEFAULT 0,
    duration_ms   INT NOT NULL DEFAULT 0,
    notes         TEXT NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
