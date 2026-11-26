SET FOREIGN_KEY_CHECKS = 0;

ALTER TABLE products ADD CONSTRAINT fk_products_category FOREIGN KEY (id_category) REFERENCES categories(id_category) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE product_batches ADD CONSTRAINT fk_batches_product FOREIGN KEY (id_product) REFERENCES products(id_product) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE product_batches ADD CONSTRAINT fk_batches_rack FOREIGN KEY (id_rack) REFERENCES rack(id_rack) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE inbound ADD CONSTRAINT fk_inbound_supplier FOREIGN KEY (id_supplier) REFERENCES suppliers(id_supplier) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE inbound ADD CONSTRAINT fk_inbound_user FOREIGN KEY (created_by) REFERENCES users(id_user) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE inbound_details ADD CONSTRAINT fk_inbound_detail_header FOREIGN KEY (id_inbound) REFERENCES inbound(id_inbound) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE inbound_details ADD CONSTRAINT fk_inbound_detail_product FOREIGN KEY (id_product) REFERENCES products(id_product) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE inbound_details ADD CONSTRAINT fk_inbound_detail_batch FOREIGN KEY (id_batch) REFERENCES product_batches(id_batch) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE inbound_details ADD CONSTRAINT fk_inbound_detail_rack FOREIGN KEY (id_rack) REFERENCES rack(id_rack) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE outbound ADD CONSTRAINT fk_outbound_user FOREIGN KEY (created_by) REFERENCES users(id_user) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE outbound_details ADD CONSTRAINT fk_outbound_detail_header FOREIGN KEY (id_outbound) REFERENCES outbound(id_outbound) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE outbound_details ADD CONSTRAINT fk_outbound_detail_batch FOREIGN KEY (id_batch) REFERENCES product_batches(id_batch) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE stock_opname_sessions ADD CONSTRAINT fk_opname_user FOREIGN KEY (created_by) REFERENCES users(id_user) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE stock_opname_details ADD CONSTRAINT fk_opname_detail_session FOREIGN KEY (id_opname) REFERENCES stock_opname_sessions(id_opname) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE stock_opname_details ADD CONSTRAINT fk_opname_detail_batch FOREIGN KEY (id_batch) REFERENCES product_batches(id_batch) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE stock_adjustments ADD CONSTRAINT fk_adjustment_batch FOREIGN KEY (id_batch) REFERENCES product_batches(id_batch) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE stock_adjustments ADD CONSTRAINT fk_adjustment_approved_by FOREIGN KEY (approved_by) REFERENCES users(id_user) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE stock_adjustments ADD CONSTRAINT fk_adjustment_created_by FOREIGN KEY (created_by) REFERENCES users(id_user) ON DELETE SET NULL ON UPDATE CASCADE;

SET FOREIGN_KEY_CHECKS = 1;
