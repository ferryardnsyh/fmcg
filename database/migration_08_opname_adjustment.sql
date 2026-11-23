-- ============================================================
-- STEP 8: RESTRUCTURE STOCK_OPNAME + STOCK_ADJUSTMENTS
-- ============================================================

-- 8A: STOCK_OPNAME_SESSIONS — VARCHAR id -> INT id_opname
CREATE TABLE _map_opname_sessions (
  old_id VARCHAR(20) NOT NULL,
  new_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (new_id)
) ENGINE=InnoDB;

INSERT INTO _map_opname_sessions (old_id) SELECT id FROM stock_opname_sessions ORDER BY id;

ALTER TABLE stock_opname_sessions
  ADD COLUMN id_opname INT UNSIGNED NOT NULL DEFAULT 0 AFTER id;

UPDATE stock_opname_sessions sos
JOIN _map_opname_sessions m ON sos.id = m.old_id
SET sos.id_opname = m.new_id;

ALTER TABLE stock_opname_sessions DROP PRIMARY KEY;
ALTER TABLE stock_opname_sessions DROP COLUMN id;
ALTER TABLE stock_opname_sessions ADD PRIMARY KEY (id_opname);
ALTER TABLE stock_opname_sessions MODIFY COLUMN id_opname INT UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE stock_opname_sessions CHANGE session_number opname_number VARCHAR(50) NOT NULL;
ALTER TABLE stock_opname_sessions CHANGE session_date opname_date DATE NULL;
ALTER TABLE stock_opname_sessions ADD UNIQUE INDEX idx_opname_number (opname_number);

-- 8B: STOCK_OPNAME_DETAILS — id is already INT AUTO_INCREMENT, RENAME
--     FKs: session_id -> id_opname, batch_id -> id_batch
--     REMOVE product_id (redundant via batch), REMOVE notes

ALTER TABLE stock_opname_details RENAME COLUMN id TO id_opname_detail;

UPDATE stock_opname_details sod
JOIN _map_opname_sessions m ON sod.session_id = m.old_id
SET sod.session_id = m.new_id;

UPDATE stock_opname_details sod
LEFT JOIN _map_batches m ON sod.batch_id = m.old_id
SET sod.batch_id = m.new_id;

ALTER TABLE stock_opname_details
  CHANGE session_id id_opname INT UNSIGNED NULL,
  CHANGE batch_id id_batch INT UNSIGNED NULL;

ALTER TABLE stock_opname_details DROP COLUMN product_id;
ALTER TABLE stock_opname_details DROP COLUMN notes;

-- 8C: STOCK_ADJUSTMENTS — VARCHAR id -> INT id_adjustment
--     Simplify: remove adjustment_number, product_id, quantity_before,
--     quantity_after, notes, opname_id, approved_at

CREATE TABLE _map_adjustments (
  old_id VARCHAR(20) NOT NULL,
  new_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (new_id)
) ENGINE=InnoDB;

INSERT INTO _map_adjustments (old_id) SELECT id FROM stock_adjustments ORDER BY id;

ALTER TABLE stock_adjustments
  ADD COLUMN id_adjustment INT UNSIGNED NOT NULL DEFAULT 0 AFTER id;

UPDATE stock_adjustments sa
JOIN _map_adjustments m ON sa.id = m.old_id
SET sa.id_adjustment = m.new_id;

UPDATE stock_adjustments sa
LEFT JOIN _map_batches m ON sa.batch_id = m.old_id
SET sa.batch_id = m.new_id;

ALTER TABLE stock_adjustments
  CHANGE batch_id id_batch VARCHAR(20) NULL;

ALTER TABLE stock_adjustments DROP PRIMARY KEY;
ALTER TABLE stock_adjustments DROP COLUMN id;
ALTER TABLE stock_adjustments DROP COLUMN adjustment_number;
ALTER TABLE stock_adjustments DROP COLUMN product_id;
ALTER TABLE stock_adjustments DROP COLUMN quantity_before;
ALTER TABLE stock_adjustments DROP COLUMN quantity_after;
ALTER TABLE stock_adjustments DROP COLUMN notes;
ALTER TABLE stock_adjustments DROP COLUMN opname_id;
ALTER TABLE stock_adjustments DROP COLUMN approved_at;
ALTER TABLE stock_adjustments ADD PRIMARY KEY (id_adjustment);
ALTER TABLE stock_adjustments MODIFY COLUMN id_adjustment INT UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE stock_adjustments MODIFY COLUMN id_batch INT UNSIGNED NULL;
ALTER TABLE stock_adjustments ADD COLUMN updated_at DATETIME NULL AFTER created_at;
