-- ============================================================
-- STEP 2: DATA MIGRATION (before dropping tables)
-- ============================================================

-- 2A: Add brand_name, unit, and category columns to products first
ALTER TABLE products
  ADD COLUMN brand_name VARCHAR(200) NULL AFTER category_id,
  ADD COLUMN unit VARCHAR(50) NULL AFTER brand_name,
  ADD COLUMN category VARCHAR(200) NULL AFTER name;

-- 2B: Migrate brands -> products.brand_name
UPDATE products p
JOIN brands b ON p.brand_id = b.id
SET p.brand_name = b.name;

-- 2C: Migrate units -> products.unit
UPDATE products p
JOIN units u ON p.unit_id = u.id
SET p.unit = u.name;

-- 2D: Migrate categories -> products.category
UPDATE products p
JOIN categories c ON p.category_id = c.id
SET p.category = c.name;

-- 2D: Convert stock_adjustments type: IN->INCREASE, OUT->DECREASE
UPDATE stock_adjustments SET type = 'INCREASE' WHERE type = 'IN';
UPDATE stock_adjustments SET type = 'DECREASE' WHERE type = 'OUT';

-- 2E: Add quantity column and calculate from quantity_before/quantity_after
ALTER TABLE stock_adjustments ADD COLUMN quantity INT NOT NULL DEFAULT 0 AFTER type;
UPDATE stock_adjustments
SET quantity = ABS(COALESCE(quantity_after, 0) - COALESCE(quantity_before, 0));

-- 2F: Map status: CLEANED_UP/APPROVED->APPROVED, CLEANED->APPROVED, PENDING stays
UPDATE stock_adjustments SET status = 'APPROVED' WHERE status IN ('CLEANED_UP', 'CLEANED');

-- 2G: Fix the one record with NULL batch_id and product_id
-- ADJ-000001: set quantity manually since batch/product is NULL
UPDATE stock_adjustments SET quantity = 2 WHERE id = 'ADJ-000001' AND quantity = 0;
