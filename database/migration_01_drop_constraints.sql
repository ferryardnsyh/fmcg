-- ============================================================
-- PSS WMS DATABASE RESTRUCTURING — MIGRATION SCRIPT
-- ============================================================
-- Date: 2026-08-23
-- Purpose: Simplify DB to 14 tables, rename PKs/FKs, clean schema
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- ============================================================
-- STEP 1: DROP ALL EXISTING FK CONSTRAINTS
-- ============================================================

-- products
ALTER TABLE products DROP FOREIGN KEY products_ibfk_1;  -- category_id → categories
ALTER TABLE products DROP FOREIGN KEY products_ibfk_2;  -- brand_id → brands
ALTER TABLE products DROP FOREIGN KEY products_ibfk_3;  -- unit_id → units
ALTER TABLE products DROP FOREIGN KEY products_ibfk_4;  -- supplier_id → suppliers

-- product_batches
ALTER TABLE product_batches DROP FOREIGN KEY product_batches_ibfk_1;  -- product_id → products
ALTER TABLE product_batches DROP FOREIGN KEY product_batches_ibfk_2;  -- warehouse_id → warehouses
ALTER TABLE product_batches DROP FOREIGN KEY product_batches_ibfk_3;  -- rack_location_id → rack_locations

-- inbound_headers
ALTER TABLE inbound_headers DROP FOREIGN KEY inbound_headers_ibfk_1;  -- supplier_id → suppliers
ALTER TABLE inbound_headers DROP FOREIGN KEY inbound_headers_ibfk_2;  -- created_by → users

-- inbound_details
ALTER TABLE inbound_details DROP FOREIGN KEY inbound_details_ibfk_1;  -- inbound_header_id → inbound_headers
ALTER TABLE inbound_details DROP FOREIGN KEY inbound_details_ibfk_2;  -- product_id → products
ALTER TABLE inbound_details DROP FOREIGN KEY inbound_details_ibfk_3;  -- batch_id → product_batches
ALTER TABLE inbound_details DROP FOREIGN KEY inbound_details_ibfk_4;  -- rack_location_id → rack_locations

-- outbound_headers
ALTER TABLE outbound_headers DROP FOREIGN KEY outbound_headers_ibfk_2;  -- created_by → users

-- outbound_details
ALTER TABLE outbound_details DROP FOREIGN KEY outbound_details_ibfk_1;  -- outbound_header_id → outbound_headers
ALTER TABLE outbound_details DROP FOREIGN KEY outbound_details_ibfk_2;  -- product_id → products
ALTER TABLE outbound_details DROP FOREIGN KEY outbound_details_ibfk_3;  -- batch_id → product_batches

-- stock_opname_sessions
ALTER TABLE stock_opname_sessions DROP FOREIGN KEY stock_opname_sessions_ibfk_1;  -- created_by → users

-- stock_opname_details
ALTER TABLE stock_opname_details DROP FOREIGN KEY stock_opname_details_ibfk_1;  -- session_id → stock_opname_sessions
ALTER TABLE stock_opname_details DROP FOREIGN KEY stock_opname_details_ibfk_2;  -- product_id → products
ALTER TABLE stock_opname_details DROP FOREIGN KEY stock_opname_details_ibfk_3;  -- batch_id → product_batches

-- stock_adjustments
ALTER TABLE stock_adjustments DROP FOREIGN KEY stock_adjustments_ibfk_1;  -- product_id → products
ALTER TABLE stock_adjustments DROP FOREIGN KEY stock_adjustments_ibfk_2;  -- batch_id → product_batches
ALTER TABLE stock_adjustments DROP FOREIGN KEY stock_adjustments_ibfk_3;  -- created_by → users
ALTER TABLE stock_adjustments DROP FOREIGN KEY stock_adjustments_ibfk_4;  -- approved_by → users

-- rack_locations
ALTER TABLE rack_locations DROP FOREIGN KEY rack_locations_ibfk_1;  -- warehouse_id → warehouses

-- disposal_details
ALTER TABLE disposal_details DROP FOREIGN KEY fk_dd_batch;
ALTER TABLE disposal_details DROP FOREIGN KEY fk_dd_header;
ALTER TABLE disposal_details DROP FOREIGN KEY fk_dd_product;

-- disposal_headers
ALTER TABLE disposal_headers DROP FOREIGN KEY k_dh_completed_by;
ALTER TABLE disposal_headers DROP FOREIGN KEY k_dh_created_by;

-- purchase_orders
ALTER TABLE purchase_orders DROP FOREIGN KEY po_ibfk_1;
ALTER TABLE purchase_orders DROP FOREIGN KEY po_ibfk_2;

-- purchase_order_items
ALTER TABLE purchase_order_items DROP FOREIGN KEY poi_ibfk_1;
ALTER TABLE purchase_order_items DROP FOREIGN KEY poi_ibfk_2;

-- delivery_orders
ALTER TABLE delivery_orders DROP FOREIGN KEY do_ibfk_1;
ALTER TABLE delivery_orders DROP FOREIGN KEY do_ibfk_2;

-- delivery_order_items
ALTER TABLE delivery_order_items DROP FOREIGN KEY doi_ibfk_1;
ALTER TABLE delivery_order_items DROP FOREIGN KEY doi_ibfk_2;
ALTER TABLE delivery_order_items DROP FOREIGN KEY doi_ibfk_3;

SET FOREIGN_KEY_CHECKS = 1;
