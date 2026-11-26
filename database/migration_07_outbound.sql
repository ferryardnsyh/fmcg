-- ============================================================
-- STEP 7: RESTRUCTURE OUTBOUND_HEADERS + OUTBOUND_DETAILS
-- ============================================================

-- 7A: OUTBOUND_HEADERS — VARCHAR id -> INT id_outbound
CREATE TABLE _map_outbound (
  old_id VARCHAR(20) NOT NULL,
  new_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (new_id)
) ENGINE=InnoDB;

INSERT INTO _map_outbound (old_id) SELECT id FROM outbound ORDER BY id;

ALTER TABLE outbound
  ADD COLUMN id_outbound INT UNSIGNED NOT NULL DEFAULT 0 AFTER id;

UPDATE outbound oh
JOIN _map_outbound m ON oh.id = m.old_id
SET oh.id_outbound = m.new_id;

ALTER TABLE outbound DROP PRIMARY KEY;
ALTER TABLE outbound DROP COLUMN id;
ALTER TABLE outbound ADD PRIMARY KEY (id_outbound);
ALTER TABLE outbound MODIFY COLUMN id_outbound INT UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE outbound ADD UNIQUE INDEX idx_delivery_number (delivery_number);
ALTER TABLE outbound ADD INDEX idx_delivery_date (delivery_date);

-- 7B: OUTBOUND_DETAILS — id is already INT AUTO_INCREMENT, RENAME
--     FKs: outbound_header_id -> id_outbound, batch_id -> id_batch
--     REMOVE product_id (redundant via batch)

ALTER TABLE outbound_details RENAME COLUMN id TO id_outbound_detail;

UPDATE outbound_details od
JOIN _map_outbound m ON od.outbound_header_id = m.old_id
SET od.outbound_header_id = m.new_id;

UPDATE outbound_details od
LEFT JOIN _map_batches m ON od.batch_id = m.old_id
SET od.batch_id = m.new_id;

ALTER TABLE outbound_details
  CHANGE outbound_header_id id_outbound INT UNSIGNED NULL,
  CHANGE batch_id id_batch INT UNSIGNED NULL;

ALTER TABLE outbound_details DROP COLUMN product_id;
