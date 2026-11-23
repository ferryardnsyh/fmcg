-- ============================================================
-- STEP 3: DROP TABLES (dependency order: children first)
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS disposal_details;
DROP TABLE IF EXISTS disposal_headers;
DROP TABLE IF EXISTS delivery_order_items;
DROP TABLE IF EXISTS delivery_orders;
DROP TABLE IF EXISTS purchase_order_items;
DROP TABLE IF EXISTS purchase_orders;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS warehouses;
DROP TABLE IF EXISTS units;
DROP TABLE IF EXISTS brands;

SET FOREIGN_KEY_CHECKS = 1;

-- Verify: should be 14 tables now
-- SELECT COUNT(*) AS table_count FROM information_schema.tables WHERE table_schema = 'psswms_db';
