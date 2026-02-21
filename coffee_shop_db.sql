-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 21, 2026 at 05:11 PM
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
(1, 'Hot Coffee', 'Freshly brewed hot coffee beverages served at the perfect temperature', '2026-02-09 12:27:00'),
(2, 'Iced Coffee', 'Refreshing cold coffee drinks perfect for any time of day', '2026-02-09 12:27:00'),
(3, 'Non-Coffee', 'Delicious beverages for non-coffee lovers', '2026-02-09 12:27:00'),
(4, 'Milkshakes', 'Creamy blended milkshakes and frappes', '2026-02-09 12:27:00'),
(5, 'Tea', 'Premium tea selections hot and cold', '2026-02-09 12:27:00'),
(6, 'Desserts', 'Decadent sweet treats and desserts', '2026-02-09 12:27:00'),
(7, 'Pastry', 'Freshly baked goods and pastries made daily', '2026-02-09 12:27:00'),
(8, 'Snacks', 'Savory snacks and light meals', '2026-02-09 12:27:00'),
(9, 'Add Ons', 'Customize your drink with premium add-ons', '2026-02-09 12:27:00');

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `message_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `mobile_number` varchar(20) DEFAULT NULL,
  `order_type` enum('delivery','pickup') NOT NULL DEFAULT 'delivery',
  `house_unit` varchar(100) DEFAULT NULL,
  `street_name` varchar(150) DEFAULT NULL,
  `barangay` varchar(100) DEFAULT NULL,
  `city_municipality` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `zip_code` varchar(10) DEFAULT NULL,
  `delivery_notes` text DEFAULT NULL,
  `pickup_branch` varchar(100) DEFAULT NULL,
  `pickup_date` date DEFAULT NULL,
  `pickup_time` time DEFAULT NULL,
  `promo_code` varchar(50) DEFAULT NULL,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `user_id`, `order_date`, `total_amount`, `status`, `payment_method`, `delivery_address`, `created_at`, `mobile_number`, `order_type`, `house_unit`, `street_name`, `barangay`, `city_municipality`, `province`, `zip_code`, `delivery_notes`, `pickup_branch`, `pickup_date`, `pickup_time`, `promo_code`, `discount_amount`) VALUES
(1, 3, '2026-02-21 17:51:26', 51.00, 'pending', 'GCash', 'test', '2026-02-21 09:51:26', NULL, 'delivery', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00),
(4, 3, '2026-02-21 23:35:42', 210.00, 'processing', 'Cash on Delivery', 'Block 6 Lot 17 Crestview Homes, Crestview Avenue, Ula, Tugbok District, Davao City, Davao del Sur 8000', '2026-02-21 15:35:42', '09123456789', 'delivery', 'Block 6 Lot 17 Crestview Homes', 'Crestview Avenue', 'Ula, Tugbok District', 'Davao City', 'Davao del Sur', '8000', 'test', '', NULL, NULL, '', 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price_at_time` decimal(10,2) NOT NULL,
  `size` varchar(20) DEFAULT NULL,
  `temperature` varchar(20) DEFAULT NULL,
  `sugar_level` varchar(10) DEFAULT NULL,
  `milk_type` varchar(30) DEFAULT NULL,
  `addons` text DEFAULT NULL,
  `special_instructions` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price_at_time`, `size`, `temperature`, `sugar_level`, `milk_type`, `addons`, `special_instructions`) VALUES
(2, 4, 55, 1, 160.00, NULL, NULL, NULL, NULL, NULL, NULL);

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
(2, 1, 'Cappuccino', 'Perfect balance of espresso, steamed milk, and velvety foam', 175.00, NULL, 1, '2026-02-09 12:27:00'),
(3, 1, 'Espresso', 'Intense shot of pure coffee perfection, bold and concentrated', 140.00, NULL, 1, '2026-02-09 12:27:00'),
(4, 1, 'White Chocolate Mocha', 'Luxurious blend of espresso, white chocolate, and steamed milk', 195.00, NULL, 1, '2026-02-09 12:27:00'),
(5, 1, 'Hazelnut Latte', 'Smooth espresso with steamed milk and sweet hazelnut syrup', 185.00, NULL, 1, '2026-02-09 12:27:00'),
(6, 2, 'Iced Vanilla Latte', 'Chilled espresso with milk and vanilla sweetness over ice', 185.00, NULL, 1, '2026-02-09 12:27:00'),
(7, 2, 'Salted Caramel', 'Sweet and salty combination of caramel, espresso, and cold milk', 195.00, NULL, 1, '2026-02-09 12:27:00'),
(8, 2, 'Iced Caramel Macchiato', 'Layered iced coffee with vanilla, milk, espresso, and caramel drizzle', 200.00, NULL, 1, '2026-02-09 12:27:00'),
(9, 2, 'Iced Mocha', 'Refreshing chocolate coffee blend served over ice', 190.00, NULL, 1, '2026-02-09 12:27:00'),
(10, 2, 'Dirty Horchata', 'Creamy rice milk drink with a bold espresso shot', 205.00, NULL, 1, '2026-02-09 12:27:00'),
(11, 3, 'Classic Hot Chocolate', 'Rich and creamy hot chocolate topped with whipped cream', 165.00, NULL, 1, '2026-02-09 12:27:00'),
(12, 3, 'Matcha Green Tea Latte', 'Smooth Japanese matcha blended with steamed milk', 180.00, NULL, 1, '2026-02-09 12:27:00'),
(13, 3, 'Strawberry Milk', 'Sweet and refreshing strawberry flavored milk', 155.00, NULL, 1, '2026-02-09 12:27:00'),
(14, 3, 'Mango Passionfruit Refresher', 'Tropical fruit blend with a refreshing citrus kick', 175.00, NULL, 1, '2026-02-09 12:27:00'),
(15, 3, 'Spiced Chai Latte', 'Aromatic spiced tea with creamy steamed milk', 170.00, NULL, 1, '2026-02-09 12:27:00'),
(16, 4, 'Dark Chocolate Freeze', 'Rich dark chocolate blended into a creamy frozen treat', 210.00, NULL, 1, '2026-02-09 12:27:00'),
(17, 4, 'Cookies & Cream Frappe', 'Crushed cookies blended with vanilla ice cream and milk', 220.00, NULL, 1, '2026-02-09 12:27:00'),
(18, 4, 'Strawberry Cheesecake', 'Creamy cheesecake flavor with strawberry swirls', 225.00, NULL, 1, '2026-02-09 12:27:00'),
(19, 4, 'Matcha Cream Frappe', 'Green tea matcha blended with cream and ice', 215.00, NULL, 1, '2026-02-09 12:27:00'),
(20, 4, 'Toffee Nut Crunch', 'Sweet toffee and nutty flavors in a frozen delight', 220.00, NULL, 1, '2026-02-09 12:27:00'),
(21, 5, 'Earl Grey Milk Tea', 'Classic Earl Grey tea with creamy milk and sweetness', 165.00, NULL, 1, '2026-02-09 12:27:00'),
(22, 5, 'Chamomile Honey', 'Soothing chamomile tea with natural honey', 155.00, NULL, 1, '2026-02-09 12:27:00'),
(23, 5, 'Peach Iced Tea', 'Refreshing black tea infused with sweet peach flavor', 160.00, NULL, 1, '2026-02-09 12:27:00'),
(24, 5, 'Lemon Ginger Tea', 'Zesty lemon and spicy ginger tea blend', 150.00, NULL, 1, '2026-02-09 12:27:00'),
(25, 5, 'Jasmine Green Tea', 'Delicate jasmine-scented green tea', 155.00, NULL, 1, '2026-02-09 12:27:00'),
(26, 6, 'New York Cheesecake', 'Classic creamy cheesecake with graham cracker crust', 195.00, NULL, 1, '2026-02-09 12:27:00'),
(27, 6, 'Tiramisu', 'Italian dessert with coffee-soaked ladyfingers and mascarpone', 210.00, NULL, 1, '2026-02-09 12:27:00'),
(28, 6, 'Chocolate Lava Cake', 'Warm chocolate cake with molten chocolate center', 220.00, NULL, 1, '2026-02-09 12:27:00'),
(29, 6, 'Affogato', 'Vanilla gelato drowned in a shot of hot espresso', 185.00, NULL, 1, '2026-02-09 12:27:00'),
(30, 6, 'Red Velvet Cake', 'Moist red velvet cake with cream cheese frosting', 200.00, NULL, 1, '2026-02-09 12:27:00'),
(31, 7, 'Classic Croissant', 'Buttery, flaky French pastry baked to golden perfection', 145.00, NULL, 1, '2026-02-09 12:27:00'),
(32, 7, 'Blueberry Scone', 'Tender scone studded with fresh blueberries', 155.00, NULL, 1, '2026-02-09 12:27:00'),
(33, 7, 'Cinnamon Roll', 'Soft sweet dough with cinnamon swirl and cream cheese frosting', 165.00, NULL, 1, '2026-02-09 12:27:00'),
(34, 7, 'Chocolate Muffin', 'Moist chocolate crumb with rich cocoa flavor', 150.00, NULL, 1, '2026-02-09 12:27:00'),
(35, 7, 'Baguette', 'Crisp golden crust with light airy interior, freshly baked', 175.00, NULL, 1, '2026-02-09 12:27:00'),
(36, 8, 'Tuna Melt Panini', 'Grilled sandwich with tuna salad and melted cheese', 195.00, NULL, 1, '2026-02-09 12:27:00'),
(37, 8, 'Chicken Pesto Pasta', 'Tender chicken with basil pesto and pasta', 210.00, NULL, 1, '2026-02-09 12:27:00'),
(38, 8, 'Club Sandwich', 'Triple-decker with turkey, bacon, lettuce, and tomato', 205.00, NULL, 1, '2026-02-09 12:27:00'),
(39, 8, 'Sausage Roll', 'Flaky pastry wrapped around savory sausage filling', 165.00, NULL, 1, '2026-02-09 12:27:00'),
(40, 8, 'Loaded Nachos', 'Crispy tortilla chips with cheese, salsa, and toppings', 185.00, NULL, 1, '2026-02-09 12:27:00'),
(41, 9, 'Extra Espresso Shot', 'Add an extra shot of espresso to your drink for more kick', 35.00, NULL, 1, '2026-02-09 12:27:00'),
(43, 9, 'Vanilla Syrup', 'A sweet, aromatic syrup made with natural or artificial vanilla, used to add a creamy vanilla flavor to coffee, lattes, or other beverages', 25.00, NULL, 1, '2026-02-09 12:27:00'),
(55, 1, 'Caffe Americano', 'A rich espresso diluted with hot water, creating a smooth, full-bodied coffee without the intensity of straight espresso', 160.00, NULL, 1, '2026-02-21 14:45:44'),
(56, 9, 'Whipped Cream', 'Light, airy cream topping that adds richness and a creamy texture to coffee drinks or desserts', 20.00, NULL, 1, '2026-02-21 15:05:20'),
(57, 9, 'Coffee Jelly', 'Cubes of firm, slightly sweetened coffee-flavored gelatin, perfect for mixing into cold drinks or desserts', 40.00, NULL, 1, '2026-02-21 15:05:50'),
(58, 9, 'Pearl (Boba)', 'Chewy tapioca balls often added to iced teas, coffees, or milk drinks for texture and fun', 30.00, NULL, 1, '2026-02-21 15:06:06');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_interactions`
--

INSERT INTO `product_interactions` (`interaction_id`, `product_id`, `interaction_type`, `interaction_count`, `last_interaction`, `created_at`) VALUES
(1, 2, 'add_to_cart', 7, '2026-02-20 09:50:41', '2026-02-18 12:31:08'),
(3, 2, 'favorite', 1, '2026-02-19 22:46:16', '2026-02-18 12:32:16'),
(4, 3, 'favorite', 1, '2026-02-19 22:24:49', '2026-02-19 12:51:04'),
(6, 3, 'add_to_cart', 3, '2026-02-19 22:18:30', '2026-02-19 14:07:25'),
(7, 5, 'add_to_cart', 2, '2026-02-19 22:18:09', '2026-02-19 14:07:28'),
(8, 8, 'add_to_cart', 2, '2026-02-19 22:18:08', '2026-02-19 14:07:30'),
(9, 5, 'favorite', 0, '2026-02-19 22:24:28', '2026-02-19 14:24:24'),
(10, 6, 'add_to_cart', 2, '2026-02-19 22:25:01', '2026-02-19 14:24:58'),
(11, 14, 'add_to_cart', 2, '2026-02-19 22:46:23', '2026-02-19 14:46:22'),
(12, 35, 'add_to_cart', 1, '2026-02-20 14:34:36', '2026-02-20 06:34:36');

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
(1, 'Admin', 'admin@purgecoffee.com', '$2a$12$F4wbKxPcOnhD6K5An76ke.4gS2.m9JQTH88Q8NZYUzGwNaYhY6YKC', 'admin', '2026-02-20 02:12:15'),
(2, 'Customer', 'customer@purgecoffee.com', '$2a$12$nvPoBAKZQZpBpybEbGlsNeYwwMFn.YcvPM1SqGgM0iR.iHMw2efGW', 'customer', '2026-02-20 02:23:55'),
(3, 'John Doe', 'johndoe@gmail.com', '$2y$10$31mqhulkg7rPDmyNSwiuj.CAQAqlJGgdknbpSJo0m22QsNKaKcLEO', 'customer', '2026-02-21 07:15:51');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `idx_is_read` (`is_read`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_status` (`status`);

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
  ADD KEY `idx_product_id` (`product_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_role` (`role`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT for table `product_interactions`
--
ALTER TABLE `product_interactions`
  MODIFY `interaction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
