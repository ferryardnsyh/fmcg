-- ============================================================
-- STEP 6: RESTRUCTURE INBOUND_HEADERS + INBOUND_DETAILS
-- ============================================================

-- 6A: INBOUND_HEADERS — VARCHAR id -> INT id_inbound
CREATE TABLE _map_inbound (
  old_id VARCHAR(20) NOT NULL,
  new_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (new_id)
) ENGINE=InnoDB;

INSERT INTO _map_inbound (old_id) SELECT id FROM inbound ORDER BY id;

ALTER TABLE inbound
  ADD COLUMN id_inbound INT UNSIGNED NOT NULL DEFAULT 0 AFTER id,
  ADD COLUMN po_number VARCHAR(50) NULL AFTER reception_date,
  ADD COLUMN delivery_note_number VARCHAR(50) NULL AFTER po_number;

UPDATE inbound ih
JOIN _map_inbound m ON ih.id = m.old_id
SET ih.id_inbound = m.new_id;

-- Fix supplier_id -> id_supplier (VARCHAR -> INT)
UPDATE inbound ih
JOIN _map_suppliers m ON ih.supplier_id = m.old_id
SET ih.supplier_id = m.new_id;

ALTER TABLE inbound DROP PRIMARY KEY;
ALTER TABLE inbound DROP COLUMN id;
ALTER TABLE inbound ADD PRIMARY KEY (id_inbound);
ALTER TABLE inbound MODIFY COLUMN id_inbound INT UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE inbound CHANGE supplier_id id_supplier INT UNSIGNED NULL;
ALTER TABLE inbound ADD UNIQUE INDEX idx_reception_number (reception_number);
ALTER TABLE inbound ADD INDEX idx_reception_date (reception_date);

-- 6B: INBOUND_DETAILS — id is already INT AUTO_INCREMENT, just RENAME
--     FKs: inbound_header_id -> id_inbound, product_id -> id_product,
--          batch_id -> id_batch, rack_location_id -> id_rack
--     Add: batch_number

ALTER TABLE inbound_details RENAME COLUMN id TO id_inbound_detail;
ALTER TABLE inbound_details ADD COLUMN batch_number VARCHAR(100) NULL AFTER id_product;

UPDATE inbound_details idet
JOIN _map_inbound m ON idet.inbound_header_id = m.old_id
SET idet.inbound_header_id = m.new_id;

UPDATE inbound_details idet
JOIN _map_products m ON idet.product_id = m.old_id
SET idet.product_id = m.new_id;

UPDATE inbound_details idet
LEFT JOIN _map_batches m ON idet.batch_id = m.old_id
SET idet.batch_id = m.new_id;

ALTER TABLE inbound_details
  CHANGE inbound_header_id id_inbound INT UNSIGNED NULL,
  CHANGE product_id id_product INT UNSIGNED NULL,
  CHANGE batch_id id_batch INT UNSIGNED NULL,
  CHANGE rack_location_id id_rack INT UNSIGNED NULL;
