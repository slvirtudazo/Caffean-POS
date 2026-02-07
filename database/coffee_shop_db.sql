-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 07, 2026 at 10:33 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `coffee_shop_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `name`, `description`, `created_at`) VALUES
(1, 'Hot Coffee', 'Freshly brewed hot coffee beverages served at the perfect temperature', '2026-02-05 07:26:24'),
(2, 'Iced Coffee', 'Refreshing cold coffee drinks perfect for any time of day', '2026-02-05 07:26:24'),
(3, 'Pastries', 'Freshly baked goods and delicious desserts made daily', '2026-02-05 07:26:24'),
(4, 'Special Drinks', 'Signature coffee creations and seasonal favorites', '2026-02-05 07:26:24');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_date` datetime DEFAULT current_timestamp(),
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','processing','completed','cancelled') DEFAULT 'pending',
  `payment_method` varchar(50) NOT NULL,
  `delivery_address` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price_at_time` decimal(10,2) NOT NULL COMMENT 'Price when ordered'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1 COMMENT '1=active, 0=inactive',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `category_id`, `name`, `description`, `price`, `image_path`, `status`, `created_at`) VALUES
(1, 1, 'Espresso', 'Bold, concentrated coffee with a rich crema finish. Perfect for those who love intense coffee flavor.', 150.00, NULL, 1, '2026-02-05 07:26:40'),
(2, 1, 'Americano', 'Smooth espresso base with hot water added. A classic choice for coffee purists.', 160.00, NULL, 1, '2026-02-05 07:26:40'),
(3, 1, 'Cappuccino', 'Equal parts espresso, steamed milk, and light foam topping. Perfectly balanced and creamy.', 170.00, NULL, 1, '2026-02-05 07:26:40'),
(4, 1, 'Latte', 'Espresso with steamed milk, smooth and creamy. A customer favorite for its mellow taste.', 180.00, NULL, 1, '2026-02-05 07:26:40'),
(5, 1, 'Mocha', 'Rich espresso combined with chocolate and steamed milk. Indulgent and satisfying.', 190.00, NULL, 1, '2026-02-05 07:26:40'),
(6, 2, 'Iced Latte', 'Chilled espresso with cold milk poured over ice. Refreshing and energizing.', 190.00, NULL, 1, '2026-02-05 07:26:40'),
(7, 2, 'Cold Brew', 'Smooth, slow-steeped coffee served cold. Less acidic with natural sweetness.', 200.00, NULL, 1, '2026-02-05 07:26:40'),
(8, 2, 'Iced Americano', 'Espresso shots over ice with cold water. Simple, bold, and refreshing.', 170.00, NULL, 1, '2026-02-05 07:26:40'),
(9, 2, 'Iced Mocha', 'Chocolate and espresso over ice with cold milk. A cool chocolate-coffee treat.', 210.00, NULL, 1, '2026-02-05 07:26:40'),
(10, 3, 'Croissant', 'Buttery flaky layers with a golden baked exterior. Perfect with your morning coffee.', 150.00, NULL, 1, '2026-02-05 07:26:40'),
(11, 3, 'Chocolate Muffin', 'Moist chocolate crumb with rich cocoa flavor throughout. A chocolate lover\'s dream.', 160.00, NULL, 1, '2026-02-05 07:26:40'),
(12, 3, 'Cinnamon Roll', 'Soft sweet dough with warm cinnamon swirl and cream cheese frosting.', 170.00, NULL, 1, '2026-02-05 07:26:40'),
(13, 3, 'Baguette', 'Crisp golden crust with light airy interior. Freshly baked daily.', 180.00, NULL, 1, '2026-02-05 07:26:40'),
(14, 3, 'Blueberry Scone', 'Tender scone studded with fresh blueberries. Lightly sweetened and buttery.', 165.00, NULL, 1, '2026-02-05 07:26:40'),
(15, 4, 'Caramel Macchiato', 'Vanilla-flavored latte with caramel drizzle. Sweet and luxurious.', 210.00, NULL, 1, '2026-02-05 07:26:40'),
(16, 4, 'Hazelnut Latte', 'Espresso with steamed milk and hazelnut syrup. Nutty and smooth.', 195.00, NULL, 1, '2026-02-05 07:26:40'),
(17, 4, 'Vanilla Frappuccino', 'Blended ice coffee drink with vanilla. Cool and creamy.', 220.00, NULL, 1, '2026-02-05 07:26:40');

-- --------------------------------------------------------

--
-- Table structure for table `product_interactions`
--

CREATE TABLE `product_interactions` (
  `interaction_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `interaction_type` enum('favorite','add_to_cart') NOT NULL,
  `interaction_count` int(11) NOT NULL DEFAULT 0,
  `last_interaction` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tracks user interactions for Best Sellers calculation';

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','customer') DEFAULT 'customer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'Admin User', 'admin@coffeeshop.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '2026-02-05 07:26:05'),
(2, 'Samantha Virtudazo', 'samanthaavirtudazo@gmail.com', '$2y$10$lMrl9pOH4rgwglk5doAxKePcjLzmRF.XwM9OBn7TTZ44veOnBg.MK', 'customer', '2026-02-05 07:54:59');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_date` (`order_date`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `idx_order` (`order_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `idx_category` (`category_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `product_interactions`
--
ALTER TABLE `product_interactions`
  ADD PRIMARY KEY (`interaction_id`),
  ADD UNIQUE KEY `unique_product_interaction` (`product_id`,`interaction_type`),
  ADD KEY `idx_product_id` (`product_id`),
  ADD KEY `idx_interaction_type` (`interaction_type`),
  ADD KEY `idx_last_interaction` (`last_interaction`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_role` (`role`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `product_interactions`
--
ALTER TABLE `product_interactions`
  MODIFY `interaction_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON DELETE CASCADE;

--
-- Constraints for table `product_interactions`
--
ALTER TABLE `product_interactions`
  ADD CONSTRAINT `fk_interactions_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
