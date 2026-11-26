-- ============================================================
-- STEP 4: RESTRUCTURE TABLES (INT PKs rename, VARCHAR PKs migrate)
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- 4.1: USERS — rename id -> id_user, nama -> name
-- ============================================================
ALTER TABLE users RENAME COLUMN id TO id_user;
ALTER TABLE users RENAME COLUMN nama TO name;

-- ============================================================
-- 4.2: CATEGORIES — rename id -> id_category, code -> category_code
-- ============================================================
ALTER TABLE categories RENAME COLUMN id TO id_category;
ALTER TABLE categories RENAME COLUMN code TO category_code;

-- Drop columns not needed
ALTER TABLE categories DROP COLUMN description;
ALTER TABLE categories DROP COLUMN is_active;

-- ============================================================
-- 4.3: RACK_LOCATIONS — rename id -> id_rack
-- ============================================================
ALTER TABLE rack RENAME COLUMN id TO id_rack;

-- Convert is_active (TINYINT 1/0) to status (VARCHAR 'AKTIF'/'NONAKTIF')
ALTER TABLE rack ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'AKTIF' AFTER is_active;
UPDATE rack SET status = 'AKTIF' WHERE is_active = 1;
UPDATE rack SET status = 'NONAKTIF' WHERE is_active = 0;
ALTER TABLE rack DROP COLUMN is_active;

-- Add name column (use rack_code as default)
ALTER TABLE rack ADD COLUMN name VARCHAR(100) NULL AFTER rack_code;
UPDATE rack SET name = rack_code WHERE name IS NULL;

-- Remove old columns
ALTER TABLE rack DROP COLUMN warehouse_id;
ALTER TABLE rack DROP COLUMN shelf_code;
ALTER TABLE rack DROP COLUMN zone;

-- ============================================================
-- 4.4: SUPPLIERS — VARCHAR id -> INT id_supplier
-- ============================================================

CREATE TABLE _map_suppliers (
  old_id VARCHAR(20) NOT NULL,
  new_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (new_id)
) ENGINE=InnoDB;

INSERT INTO _map_suppliers (old_id) SELECT id FROM suppliers ORDER BY id;

-- Add id_supplier as regular INT first (AUTO_INCREMENT requires it to be a key)
ALTER TABLE suppliers
  ADD COLUMN id_supplier INT UNSIGNED NOT NULL DEFAULT 0 AFTER id;

UPDATE suppliers s
JOIN _map_suppliers m ON s.id = m.old_id
SET s.id_supplier = m.new_id;

ALTER TABLE suppliers DROP PRIMARY KEY;
ALTER TABLE suppliers DROP COLUMN id;
ALTER TABLE suppliers MODIFY COLUMN id_supplier INT UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE suppliers ADD PRIMARY KEY (id_supplier);

-- Rename columns
ALTER TABLE suppliers CHANGE code supplier_code VARCHAR(50) NOT NULL;

-- Convert is_active (TINYINT 1/0) to status (VARCHAR 'AKTIF'/'NONAKTIF')
ALTER TABLE suppliers ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'AKTIF' AFTER is_active;
UPDATE suppliers SET status = 'AKTIF' WHERE is_active = 1;
UPDATE suppliers SET status = 'NONAKTIF' WHERE is_active = 0;
ALTER TABLE suppliers DROP COLUMN is_active;

-- Remove columns not needed
ALTER TABLE suppliers DROP COLUMN brand_id;
ALTER TABLE suppliers DROP COLUMN contact_person;
ALTER TABLE suppliers DROP COLUMN email;

-- ============================================================
-- 4.5: PRODUCTS — VARCHAR id -> INT id_product
-- ============================================================

CREATE TABLE _map_products (
  old_id VARCHAR(20) NOT NULL,
  new_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (new_id)
) ENGINE=InnoDB;

INSERT INTO _map_products (old_id) SELECT id FROM products ORDER BY id;

-- Add new columns (brand_name, unit, category already added by migration_02)
ALTER TABLE products
  ADD COLUMN id_product INT UNSIGNED NOT NULL AUTO_INCREMENT AFTER id,
  ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'AKTIF' AFTER minimum_stock,
  ADD COLUMN created_at DATETIME NULL AFTER status,
  ADD COLUMN updated_at DATETIME NULL AFTER created_at;

-- Populate id_product
UPDATE products p
JOIN _map_products m ON p.id = m.old_id
SET p.id_product = m.new_id;

-- Populate created_at/updated_at from old columns
UPDATE products SET created_at = created_date WHERE created_at IS NULL;
UPDATE products SET updated_at = updated_date WHERE updated_at IS NULL;

-- Drop old PK
ALTER TABLE products DROP PRIMARY KEY;
ALTER TABLE products DROP COLUMN id;
ALTER TABLE products CHANGE id_product id_product INT UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE products ADD PRIMARY KEY (id_product);

-- Rename FK columns
ALTER TABLE products CHANGE supplier_id id_supplier VARCHAR(20) NULL;
ALTER TABLE products CHANGE category_id id_category INT UNSIGNED NULL;

-- Convert is_active (TINYINT 1/0) to status (VARCHAR 'AKTIF'/'NONAKTIF')
UPDATE products SET status = 'AKTIF' WHERE is_active = 1;
UPDATE products SET status = 'NONAKTIF' WHERE is_active = 0;
ALTER TABLE products DROP COLUMN is_active;

-- Remove old columns
ALTER TABLE products DROP COLUMN barcode;
ALTER TABLE products DROP COLUMN description;
ALTER TABLE products DROP COLUMN brand_id;
ALTER TABLE products DROP COLUMN unit_id;
ALTER TABLE products DROP COLUMN reorder_point;
ALTER TABLE products DROP COLUMN created_date;
ALTER TABLE products DROP COLUMN updated_date;

SET FOREIGN_KEY_CHECKS = 1;
