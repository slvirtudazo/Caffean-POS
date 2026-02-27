-- ============================================================
-- Purge Coffee Shop — Kiosk Feature Migration
-- Run this once to add kiosk support to the orders table.
-- ============================================================
USE `coffee_shop_db`;

-- Step 1: Drop existing FK so we can modify user_id
ALTER TABLE `orders`
DROP FOREIGN KEY `orders_ibfk_1`;

-- Step 2: Make user_id nullable (guest/kiosk orders have no user)
ALTER TABLE `orders` MODIFY `user_id` int (11) DEFAULT NULL;

-- Step 3: Re-add FK with ON DELETE SET NULL to preserve data integrity
ALTER TABLE `orders` ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

-- Step 4: Add kiosk-specific columns
ALTER TABLE `orders`
ADD COLUMN `is_kiosk` tinyint (1) NOT NULL DEFAULT 0 COMMENT '1 = walk-in kiosk order, 0 = regular online order',
ADD COLUMN `kiosk_order_type` enum ('dine_in', 'take_out') DEFAULT NULL COMMENT 'For kiosk orders: dine_in or take_out',
ADD COLUMN `customer_name` varchar(100) DEFAULT NULL COMMENT 'Guest name for kiosk orders (optional)';

-- Step 5: Index for quick lookup of pending kiosk orders (queue display)
ALTER TABLE `orders` ADD INDEX `idx_kiosk` (`is_kiosk`, `status`, `order_date`);