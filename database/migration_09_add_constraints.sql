-- ============================================================
-- STEP 9: ADD ALL NEW FK, UNIQUE, AND INDEX CONSTRAINTS
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- UNIQUE CONSTRAINTS
-- ============================================================

ALTER TABLE users ADD UNIQUE INDEX idx_users_username (username);
ALTER TABLE suppliers ADD UNIQUE INDEX idx_supplier_code (supplier_code);
ALTER TABLE rack ADD UNIQUE INDEX idx_rack_code (rack_code);

-- ============================================================
-- INDEX CONSTRAINTS (for FK columns and frequently queried cols)
-- ============================================================

-- products
ALTER TABLE products ADD INDEX idx_products_id_supplier (id_supplier);
ALTER TABLE products ADD INDEX idx_products_id_category (id_category);

-- product_batches
ALTER TABLE product_batches ADD INDEX idx_batches_id_product (id_product);
ALTER TABLE product_batches ADD INDEX idx_batches_id_rack (id_rack);

-- inbound
ALTER TABLE inbound ADD INDEX idx_inbound_id_supplier (id_supplier);
ALTER TABLE inbound ADD INDEX idx_inbound_created_by (created_by);

-- inbound_details
ALTER TABLE inbound_details ADD INDEX idx_inbound_detail_id_inbound (id_inbound);
ALTER TABLE inbound_details ADD INDEX idx_inbound_detail_id_product (id_product);
ALTER TABLE inbound_details ADD INDEX idx_inbound_detail_id_batch (id_batch);
ALTER TABLE inbound_details ADD INDEX idx_inbound_detail_id_rack (id_rack);

-- outbound
ALTER TABLE outbound ADD INDEX idx_outbound_created_by (created_by);

-- outbound_details
ALTER TABLE outbound_details ADD INDEX idx_outbound_detail_id_outbound (id_outbound);
ALTER TABLE outbound_details ADD INDEX idx_outbound_detail_id_batch (id_batch);

-- stock_opname_sessions
ALTER TABLE stock_opname_sessions ADD INDEX idx_opname_created_by (created_by);

-- stock_opname_details
ALTER TABLE stock_opname_details ADD INDEX idx_opname_detail_id_opname (id_opname);
ALTER TABLE stock_opname_details ADD INDEX idx_opname_detail_id_batch (id_batch);

-- stock_adjustments
ALTER TABLE stock_adjustments ADD INDEX idx_adjustment_id_batch (id_batch);
ALTER TABLE stock_adjustments ADD INDEX idx_adjustment_approved_by (approved_by);
ALTER TABLE stock_adjustments ADD INDEX idx_adjustment_created_by (created_by);

-- ============================================================
-- FK CONSTRAINTS — new naming: fk_<child>_<parent_entity>
-- ============================================================

-- products -> suppliers (already added)
-- ALTER TABLE products
--   ADD CONSTRAINT fk_products_supplier
--   FOREIGN KEY (id_supplier) REFERENCES suppliers(id_supplier)
--   ON DELETE SET NULL ON UPDATE CASCADE;

-- products -> categories
ALTER TABLE products
  ADD CONSTRAINT fk_products_category
  FOREIGN KEY (id_category) REFERENCES categories(id_category)
  ON DELETE SET NULL ON UPDATE CASCADE;

-- product_batches -> products
ALTER TABLE product_batches
  ADD CONSTRAINT fk_batches_product
  FOREIGN KEY (id_product) REFERENCES products(id_product)
  ON DELETE SET NULL ON UPDATE CASCADE;

-- product_batches -> rack
ALTER TABLE product_batches
  ADD CONSTRAINT fk_batches_rack
  FOREIGN KEY (id_rack) REFERENCES rack(id_rack)
  ON DELETE SET NULL ON UPDATE CASCADE;

-- inbound -> suppliers
ALTER TABLE inbound
  ADD CONSTRAINT fk_inbound_supplier
  FOREIGN KEY (id_supplier) REFERENCES suppliers(id_supplier)
  ON DELETE SET NULL ON UPDATE CASCADE;

-- inbound -> users (created_by)
ALTER TABLE inbound
  ADD CONSTRAINT fk_inbound_user
  FOREIGN KEY (created_by) REFERENCES users(id_user)
  ON DELETE SET NULL ON UPDATE CASCADE;

-- inbound_details -> inbound
ALTER TABLE inbound_details
  ADD CONSTRAINT fk_inbound_detail_header
  FOREIGN KEY (id_inbound) REFERENCES inbound(id_inbound)
  ON DELETE CASCADE ON UPDATE CASCADE;

-- inbound_details -> products
ALTER TABLE inbound_details
  ADD CONSTRAINT fk_inbound_detail_product
  FOREIGN KEY (id_product) REFERENCES products(id_product)
  ON DELETE SET NULL ON UPDATE CASCADE;

-- inbound_details -> product_batches
ALTER TABLE inbound_details
  ADD CONSTRAINT fk_inbound_detail_batch
  FOREIGN KEY (id_batch) REFERENCES product_batches(id_batch)
  ON DELETE SET NULL ON UPDATE CASCADE;

-- inbound_details -> rack
ALTER TABLE inbound_details
  ADD CONSTRAINT fk_inbound_detail_rack
  FOREIGN KEY (id_rack) REFERENCES rack(id_rack)
  ON DELETE SET NULL ON UPDATE CASCADE;

-- outbound -> users (created_by)
ALTER TABLE outbound
  ADD CONSTRAINT fk_outbound_user
  FOREIGN KEY (created_by) REFERENCES users(id_user)
  ON DELETE SET NULL ON UPDATE CASCADE;

-- outbound_details -> outbound
ALTER TABLE outbound_details
  ADD CONSTRAINT fk_outbound_detail_header
  FOREIGN KEY (id_outbound) REFERENCES outbound(id_outbound)
  ON DELETE CASCADE ON UPDATE CASCADE;

-- outbound_details -> product_batches
ALTER TABLE outbound_details
  ADD CONSTRAINT fk_outbound_detail_batch
  FOREIGN KEY (id_batch) REFERENCES product_batches(id_batch)
  ON DELETE SET NULL ON UPDATE CASCADE;

-- stock_opname_sessions -> users (created_by)
ALTER TABLE stock_opname_sessions
  ADD CONSTRAINT fk_opname_user
  FOREIGN KEY (created_by) REFERENCES users(id_user)
  ON DELETE SET NULL ON UPDATE CASCADE;

-- stock_opname_details -> stock_opname_sessions
ALTER TABLE stock_opname_details
  ADD CONSTRAINT fk_opname_detail_session
  FOREIGN KEY (id_opname) REFERENCES stock_opname_sessions(id_opname)
  ON DELETE CASCADE ON UPDATE CASCADE;

-- stock_opname_details -> product_batches
ALTER TABLE stock_opname_details
  ADD CONSTRAINT fk_opname_detail_batch
  FOREIGN KEY (id_batch) REFERENCES product_batches(id_batch)
  ON DELETE SET NULL ON UPDATE CASCADE;

-- stock_adjustments -> product_batches
ALTER TABLE stock_adjustments
  ADD CONSTRAINT fk_adjustment_batch
  FOREIGN KEY (id_batch) REFERENCES product_batches(id_batch)
  ON DELETE SET NULL ON UPDATE CASCADE;

-- stock_adjustments -> users (approved_by)
ALTER TABLE stock_adjustments
  ADD CONSTRAINT fk_adjustment_approved_by
  FOREIGN KEY (approved_by) REFERENCES users(id_user)
  ON DELETE SET NULL ON UPDATE CASCADE;

-- stock_adjustments -> users (created_by)
ALTER TABLE stock_adjustments
  ADD CONSTRAINT fk_adjustment_created_by
  FOREIGN KEY (created_by) REFERENCES users(id_user)
  ON DELETE SET NULL ON UPDATE CASCADE;

SET FOREIGN_KEY_CHECKS = 1;
