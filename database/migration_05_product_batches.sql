-- ============================================================
-- STEP 5: RESTRUCTURE PRODUCT_BATCHES + Fix products.id_supplier
-- ============================================================

-- 5A: Fix products.id_supplier (VARCHAR -> INT) using _map_suppliers
UPDATE products p
JOIN _map_suppliers m ON p.id_supplier = m.old_id
SET p.id_supplier = m.new_id;

ALTER TABLE products MODIFY COLUMN id_supplier INT UNSIGNED NULL;

-- 5B: PRODUCT_BATCHES — VARCHAR id -> INT id_batch
CREATE TABLE _map_batches (
  old_id VARCHAR(20) NOT NULL,
  new_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (new_id)
) ENGINE=InnoDB;

INSERT INTO _map_batches (old_id) SELECT id FROM product_batches ORDER BY id;

ALTER TABLE product_batches
  ADD COLUMN id_batch INT UNSIGNED NOT NULL DEFAULT 0 AFTER id,
  ADD COLUMN id_product_new INT UNSIGNED NULL AFTER id_batch,
  ADD COLUMN id_rack INT UNSIGNED NULL AFTER expiry_date,
  ADD COLUMN created_at DATETIME NULL AFTER id_rack,
  ADD COLUMN updated_at DATETIME NULL AFTER created_at;

UPDATE product_batches pb
JOIN _map_batches m ON pb.id = m.old_id
SET pb.id_batch = m.new_id;

UPDATE product_batches pb
JOIN _map_products m ON pb.product_id = m.old_id
SET pb.id_product_new = m.new_id;

UPDATE product_batches SET id_rack = rack_location_id;
UPDATE product_batches SET created_at = created_date WHERE created_at IS NULL;
UPDATE product_batches SET updated_at = updated_date WHERE updated_at IS NULL;

ALTER TABLE product_batches DROP PRIMARY KEY;
ALTER TABLE product_batches DROP COLUMN id;
ALTER TABLE product_batches DROP COLUMN product_id;
ALTER TABLE product_batches CHANGE id_product_new id_product INT UNSIGNED NULL;
ALTER TABLE product_batches ADD PRIMARY KEY (id_batch);
ALTER TABLE product_batches MODIFY COLUMN id_batch INT UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE product_batches DROP COLUMN warehouse_id;
ALTER TABLE product_batches DROP COLUMN rack_location_id;
ALTER TABLE product_batches DROP COLUMN reserved_quantity;
ALTER TABLE product_batches DROP COLUMN available_quantity;
ALTER TABLE product_batches DROP COLUMN manufacturing_date;
ALTER TABLE product_batches DROP COLUMN destruction_date;
ALTER TABLE product_batches DROP COLUMN status;
ALTER TABLE product_batches DROP COLUMN created_date;
ALTER TABLE product_batches DROP COLUMN updated_date;

ALTER TABLE product_batches ADD UNIQUE INDEX idx_batch_product_number (id_product, batch_number);
ALTER TABLE product_batches ADD INDEX idx_batch_expiry (expiry_date);
