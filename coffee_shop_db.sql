-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 04, 2026 at 08:00 PM
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
(9, 'Add Ons', 'Customize your drink with premium add-ons', '2026-02-09 12:27:00'),
(10, 'Coffee Beans', 'Premium single-origin and blended coffee beans from top growing regions', '2026-02-28 05:47:42'),
(11, 'Milk & Creamers', 'Dairy and plant-based milk alternatives for café-quality drinks at home', '2026-02-28 05:47:42'),
(12, 'Brewing Equipment', 'Professional-grade espresso machines, grinders, and brewing tools', '2026-02-28 05:47:42');

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
-- Table structure for table `favorites`
--

CREATE TABLE `favorites` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `favorites`
--

INSERT INTO `favorites` (`id`, `user_id`, `product_id`, `created_at`) VALUES
(11, 3, 4, '2026-03-04 17:02:36');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `order_number` varchar(20) NOT NULL DEFAULT '' COMMENT 'Human-readable receipt number (e.g. PC-2026-00001)',
  `user_id` int(11) DEFAULT NULL,
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
  `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `is_kiosk` tinyint(1) NOT NULL DEFAULT 0,
  `kiosk_order_type` enum('dine_in','take_out') DEFAULT NULL,
  `customer_name` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `order_number`, `user_id`, `order_date`, `total_amount`, `status`, `payment_method`, `delivery_address`, `created_at`, `mobile_number`, `order_type`, `house_unit`, `street_name`, `barangay`, `city_municipality`, `province`, `zip_code`, `delivery_notes`, `pickup_branch`, `pickup_date`, `pickup_time`, `promo_code`, `discount_amount`, `is_kiosk`, `kiosk_order_type`, `customer_name`) VALUES
(1, 'PC-2026-00001', 3, '2026-02-21 17:51:26', 51.00, 'pending', 'GCash', 'test', '2026-02-21 09:51:26', NULL, 'delivery', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0, NULL, NULL),
(4, 'PC-2026-00004', 3, '2026-02-21 23:35:42', 210.00, 'processing', 'Cash on Delivery', 'Block 6 Lot 17 Crestview Homes, Crestview Avenue, Ula, Tugbok District, Davao City, Davao del Sur 8000', '2026-02-21 15:35:42', '09123456789', 'delivery', 'Block 6 Lot 17 Crestview Homes', 'Crestview Avenue', 'Ula, Tugbok District', 'Davao City', 'Davao del Sur', '8000', 'test', '', NULL, NULL, '', 0.00, 0, NULL, NULL),
(5, 'PC-2026-00005', 2, '2026-02-28 01:56:46', 210.00, 'completed', 'Cash on Delivery', '123, asd, asd, asd, asd 8000', '2026-02-27 17:56:46', '09603150070', 'delivery', '123', 'asd', 'asd', 'asd', 'asd', '8000', 'asdasdas', '', NULL, NULL, '', 0.00, 0, NULL, NULL),
(6, 'PC-2026-00006', NULL, '2026-02-28 01:58:47', 160.00, 'completed', 'Cash', 'Dine In', '2026-02-27 17:58:47', '09603150070', 'pickup', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 1, 'dine_in', 'Jane Smith'),
(11, 'PC-2026-00007', 3, '2026-03-01 05:23:56', 735.00, 'pending', 'Cash on Delivery', 'Pickup: Diversion Road, Matina Balusong (Main) on 2026-03-20 at 15:30', '2026-02-28 21:23:56', '09603150070', 'pickup', '', '', '', '', '', '', '', 'Diversion Road, Matina Balusong (Main)', '2026-03-20', '15:30:00', '', 0.00, 0, NULL, NULL),
(12, 'PC-2026-00008', 3, '2026-03-01 11:49:37', 735.00, 'pending', 'Cash on Delivery', 'Pickup: Diversion Road, Matina Balusong (Main) on 2026-03-25 at 14:00', '2026-03-01 03:49:37', '09603150070', 'pickup', '', '', '', '', '', '', '', 'Diversion Road, Matina Balusong (Main)', '2026-03-25', '14:00:00', '', 0.00, 0, NULL, NULL),
(13, 'PC-2026-00009', 3, '2026-03-01 11:51:53', 160.00, 'pending', 'GCash', 'Pickup: Polo Street, Obrero on 2026-03-02 at 11:00', '2026-03-01 03:51:53', '09603150070', 'pickup', '', '', '', '', '', '', '', 'Polo Street, Obrero', '2026-03-02', '11:00:00', '', 0.00, 0, NULL, NULL),
(14, 'PC-2026-00010', 3, '2026-03-01 12:20:03', 210.00, 'pending', 'Cash on Delivery', 'asd, asd, asd, asd, asd 8000', '2026-03-01 04:20:03', '09603150070', 'delivery', 'asd', 'asd', 'asd', 'asd', 'asd', '8000', 'dasdsadasdas', '', NULL, NULL, '', 0.00, 0, NULL, NULL),
(15, 'PC-2026-00011', 3, '2026-03-01 12:28:03', 185.00, 'pending', 'Cash on Delivery', 'Pickup: Diversion Road, Matina Balusong (Main) on 2026-03-25 at 12:00', '2026-03-01 04:28:03', '09603150070', 'pickup', '', '', '', '', '', '', '', 'Diversion Road, Matina Balusong (Main)', '2026-03-25', '12:00:00', '', 0.00, 0, NULL, NULL),
(16, 'PC-2026-00012', 3, '2026-03-01 12:38:20', 635.00, 'completed', 'Cash on Delivery', 'Blk 6 Lot 17, Crestview Avenue, Brgy. Ula, Tugobk District, Davao City, Davao del Sur 8000', '2026-03-01 04:38:20', '09603150070', 'delivery', 'Blk 6 Lot 17', 'Crestview Avenue', 'Brgy. Ula, Tugobk District', 'Davao City', 'Davao del Sur', '8000', 'test delivery notes', '', NULL, NULL, '', 0.00, 0, NULL, NULL),
(17, 'PC-2026-00013', 3, '2026-03-01 12:39:55', 1025.00, 'completed', 'Cash on Delivery', 'Blk 6 Lot 17, Crestview Avenue, Brgy. Ula, Tugobk District, Davao City, Davao del Sur 8000', '2026-03-01 04:39:55', '09603150070', 'delivery', 'Blk 6 Lot 17', 'Crestview Avenue', 'Brgy. Ula, Tugobk District', 'Davao City', 'Davao del Sur', '8000', 'test delivery notes', '', NULL, NULL, '', 0.00, 0, NULL, NULL),
(18, 'PC-2026-00014', 3, '2026-03-01 12:51:48', 720565.00, 'pending', 'Cash on Delivery', 'Blk 6 Lot 17, Crestview Avenue, Brgy. Ula, Tugobk District, Davao City, Davao del Sur 8000', '2026-03-01 04:51:48', '09603150070', 'delivery', 'Blk 6 Lot 17', 'Crestview Avenue', 'Brgy. Ula, Tugobk District', 'Davao City', 'Davao del Sur', '8000', 'Beside shoe rack', '', NULL, NULL, '', 0.00, 0, NULL, NULL),
(19, 'PC-2026-00015', 3, '2026-03-01 13:04:26', 250.00, 'pending', 'Cash on Delivery', 'Blk 6 Lot 17, Crestview Avenue, Brgy. Ula, Tugobk District, Davao City, Davao del Sur 8000', '2026-03-01 05:04:26', '09603150070', 'delivery', 'Blk 6 Lot 17', 'Crestview Avenue', 'Brgy. Ula, Tugobk District', 'Davao City', 'Davao del Sur', '8000', 'Beside green gate', '', NULL, NULL, '', 0.00, 0, NULL, NULL),
(20, 'PC-2026-00016', 3, '2026-03-01 13:15:48', 1725.00, 'pending', 'Cash on Delivery', 'Blk 6 Lot 17, Crestview Avenue, Brgy. Ula, Tugobk District, Davao City, Davao del Sur 8000', '2026-03-01 05:15:48', '09603150070', 'delivery', 'Blk 6 Lot 17', 'Crestview Avenue', 'Brgy. Ula, Tugobk District', 'Davao City', 'Davao del Sur', '8000', 'test', '', NULL, NULL, '', 0.00, 0, NULL, NULL),
(21, 'PC-2026-00017', 3, '2026-03-01 13:27:40', 370.00, 'pending', 'Cash on Delivery', 'Blk 6 Lot 17, Crestview Avenue, Brgy. Ula, Tugobk District, Davao City, Davao del Sur 8000', '2026-03-01 05:27:40', '09603150070', 'delivery', 'Blk 6 Lot 17', 'Crestview Avenue', 'Brgy. Ula, Tugobk District', 'Davao City', 'Davao del Sur', '8000', 'dasdasdasdasdcascdascdfaefcve fugfvchdsbvfhsvecdfevdgwdvhfvegudfvdsdhuddvhdv', '', NULL, NULL, '', 0.00, 0, NULL, NULL),
(22, 'PC-2026-00018', 3, '2026-03-01 13:51:39', 530.00, 'pending', 'Cash on Delivery', 'Blk 6 Lot 17, Crestview Avenue, Brgy. Ula, Tugobk District, Davao City, Davao del Sur 8000', '2026-03-01 05:51:39', '09603150070', 'delivery', 'Blk 6 Lot 17', 'Crestview Avenue', 'Brgy. Ula, Tugobk District', 'Davao City', 'Davao del Sur', '8000', 'Beside the shoe rack', '', NULL, NULL, '', 0.00, 0, NULL, NULL),
(23, 'PC-2026-00019', 3, '2026-03-01 13:54:21', 210.00, 'pending', 'Cash on Delivery', 'Blk 6 Lot 17, Crestview Avenue, Brgy. Ula, Tugobk District, Davao City, Davao del Sur 8000', '2026-03-01 05:54:21', '09603150070', 'delivery', 'Blk 6 Lot 17', 'Crestview Avenue', 'Brgy. Ula, Tugobk District', 'Davao City', 'Davao del Sur', '8000', '', '', NULL, NULL, '', 0.00, 0, NULL, NULL),
(24, 'PC-2026-00020', 3, '2026-03-01 13:55:50', 235.00, 'pending', 'Cash on Delivery', 'Blk 6 Lot 17, Crestview Avenue, Brgy. Ula, Tugobk District, Davao City, Davao del Sur 8000', '2026-03-01 05:55:50', '09603150070', 'delivery', 'Blk 6 Lot 17', 'Crestview Avenue', 'Brgy. Ula, Tugobk District', 'Davao City', 'Davao del Sur', '8000', '', '', NULL, NULL, '', 0.00, 0, NULL, NULL),
(25, 'PC-2026-00021', 3, '2026-03-01 13:57:04', 225.00, 'pending', 'Cash on Delivery', 'Blk 6 Lot 17, Crestview Avenue, Brgy. Ula, Tugobk District, Davao City, Davao del Sur 8000', '2026-03-01 05:57:04', '09603150070', 'delivery', 'Blk 6 Lot 17', 'Crestview Avenue', 'Brgy. Ula, Tugobk District', 'Davao City', 'Davao del Sur', '8000', '', '', NULL, NULL, '', 0.00, 0, NULL, NULL),
(26, 'PC-2026-00022', 3, '2026-03-02 05:43:36', 690.00, 'pending', 'Cash on Delivery', 'Blk 6 Lot 17, Crestview Avenue, Brgy. Ula, Tugobk District, Davao City, Davao del Sur 8000', '2026-03-01 21:43:36', '09603150070', 'delivery', 'Blk 6 Lot 17', 'Crestview Avenue', 'Brgy. Ula, Tugobk District', 'Davao City', 'Davao del Sur', '8000', 'asd', '', NULL, NULL, '', 0.00, 0, NULL, NULL),
(27, 'PC-2026-00023', 3, '2026-03-02 08:48:53', 705.00, 'pending', 'GCash', 'Blk 6 Lot 17, Crestview Avenue, Brgy. Ula, Tugobk District, Davao City, Davao del Sur 8000', '2026-03-02 00:48:53', '09603150070', 'delivery', 'Blk 6 Lot 17', 'Crestview Avenue', 'Brgy. Ula, Tugobk District', 'Davao City', 'Davao del Sur', '8000', '', '', NULL, NULL, '', 0.00, 0, NULL, NULL),
(28, 'PC-2026-00024', 3, '2026-03-03 12:53:25', 875.00, 'pending', 'Cash on Delivery', 'Blk 6 Lot 17, Crestview Avenue, Brgy. Ula, Tugobk District, Davao City, Davao del Sur 8000', '2026-03-03 04:53:25', '09603150070', 'delivery', 'Blk 6 Lot 17', 'Crestview Avenue', 'Brgy. Ula, Tugobk District', 'Davao City', 'Davao del Sur', '8000', '', '', NULL, NULL, '', 0.00, 0, NULL, NULL),
(29, 'PC-2026-00025', 3, '2026-03-04 20:44:00', 735.00, 'pending', 'GCash', 'Blk 6 Lot 17, Crestview Avenue, Brgy. Ula, Tugobk District, Davao City, Davao del Sur 8000', '2026-03-04 12:44:00', '09603150070', 'delivery', 'Blk 6 Lot 17', 'Crestview Avenue', 'Brgy. Ula, Tugobk District', 'Davao City', 'Davao del Sur', '8000', '', '', NULL, NULL, '', 0.00, 0, NULL, NULL);

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
(2, 4, 55, 1, 160.00, NULL, NULL, NULL, NULL, NULL, NULL),
(3, 5, 55, 1, 160.00, NULL, NULL, NULL, NULL, NULL, NULL),
(4, 6, 55, 1, 160.00, 'Tall', 'Iced', '50%', 'Skim', NULL, 'asdasd'),
(5, 11, 20, 1, 220.00, 'Short', 'Hot', '0%', 'Whole', '', ''),
(6, 11, 34, 1, 150.00, 'Short', 'Hot', '0%', 'Whole', '', ''),
(7, 11, 38, 1, 205.00, 'Short', 'Hot', '0%', 'Whole', '', ''),
(8, 11, 55, 1, 160.00, 'Short', 'Hot', '0%', 'Whole', '', ''),
(9, 12, 20, 1, 220.00, 'Short', 'Hot', '0%', 'Whole', '', ''),
(10, 12, 34, 1, 150.00, 'Short', 'Hot', '0%', 'Whole', '', ''),
(11, 12, 38, 1, 205.00, 'Short', 'Hot', '0%', 'Whole', '', ''),
(12, 12, 55, 1, 160.00, 'Short', 'Hot', '0%', 'Whole', '', ''),
(13, 13, 55, 1, 160.00, 'Short', 'Hot', '0%', 'Whole', '', ''),
(14, 14, 55, 1, 160.00, 'Short', 'Hot', '0%', 'Whole', '', ''),
(15, 15, 5, 1, 185.00, 'Short', 'Hot', '0%', 'Whole', '', ''),
(16, 16, 7, 3, 195.00, 'Grande', 'Iced', '50%', 'Oat', 'Vanilla Syrup', 'test instructions'),
(17, 17, 4, 5, 195.00, 'Venti', 'Blended', '25%', 'Oat', 'Whipped Cream', 'test instructions'),
(18, 18, 55, 1, 160.00, 'Short', 'Hot', '0%', 'Whole', 'Extra Espresso Shot', 'Make it drop'),
(19, 18, 72, 2, 110.00, 'Tall', 'Iced', '25%', 'Skim', 'Vanilla Syrup', 'Make me sway'),
(20, 18, 77, 3, 45.00, 'Grande', 'Blended', '50%', 'Oat', 'Whipped Cream', 'Make me twerk'),
(21, 18, 82, 4, 180000.00, 'Venti', 'Hot', '75%', 'Almond', 'Coffee Jelly', 'Make me sing'),
(22, 19, 8, 1, 200.00, 'Short', 'Hot', '0%', 'Whole', '', ''),
(23, 20, 4, 1, 195.00, 'Short', 'Hot', '0%', 'Whole', '', 'test'),
(24, 20, 62, 1, 420.00, 'Short', 'Hot', '0%', 'Whole', '', ''),
(25, 20, 66, 1, 380.00, 'Short', 'Hot', '0%', 'Whole', '', ''),
(26, 20, 68, 1, 280.00, 'Short', 'Hot', '0%', 'Whole', '', ''),
(27, 20, 69, 1, 400.00, 'Short', 'Hot', '0%', 'Whole', '', ''),
(28, 21, 55, 2, 160.00, 'Tall', 'Iced', '25%', 'Oat', 'Extra Espresso Shot, Vanilla Syrup, Whipped Cream, Coffee Jelly, Pearl (Boba)', 'dasdasdasdasdcascdascdfaefcve fugfvchdsbvfhsvecdfevdgwdvhfvegudfvdsdhuddvhdved'),
(29, 22, 55, 3, 160.00, 'Venti', 'Iced', '100%', 'Almond', 'Extra Espresso Shot, Whipped Cream', 'Add a small note'),
(30, 23, 55, 1, 160.00, 'Short', 'Hot', '0%', 'Whole', '', ''),
(31, 24, 5, 1, 185.00, 'Short', 'Hot', '0%', 'Whole', '', ''),
(32, 25, 2, 1, 175.00, 'Short', 'Hot', '0%', 'Whole', '', ''),
(33, 26, 55, 4, 160.00, 'Venti', 'Iced', '100%', 'Almond', 'Extra Espresso Shot, Whipped Cream', 'Add a small note'),
(34, 27, 2, 1, 175.00, 'Short', 'Hot', '0%', 'Whole', '', ''),
(35, 27, 55, 3, 160.00, 'Venti', 'Iced', '100%', 'Almond', 'Extra Espresso Shot, Whipped Cream', 'Add a small note'),
(36, 28, 2, 3, 175.00, 'Short', 'Hot', '0%', 'Whole', '', ''),
(37, 28, 3, 1, 140.00, 'Short', 'Hot', '0%', 'Whole', '', ''),
(38, 28, 55, 1, 160.00, 'Short', 'Hot', '0%', 'Whole', '', ''),
(39, 29, 2, 3, 175.00, 'Short', 'Hot', '0%', 'Whole', '', ''),
(40, 29, 55, 1, 160.00, 'Short', 'Hot', '0%', 'Whole', '', '');

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
  `net_content` varchar(50) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1 COMMENT '1=active, 0=inactive',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `category_id`, `name`, `description`, `price`, `image_path`, `net_content`, `status`, `created_at`) VALUES
(2, 1, 'Cappuccino', 'Perfect balance of espresso, steamed milk, and velvety foam', 175.00, NULL, NULL, 1, '2026-02-09 12:27:00'),
(3, 1, 'Espresso', 'Intense shot of pure coffee perfection, bold and concentrated', 140.00, NULL, NULL, 1, '2026-02-09 12:27:00'),
(4, 1, 'White Chocolate Mocha', 'Luxurious blend of espresso, white chocolate, and steamed milk', 195.00, NULL, NULL, 1, '2026-02-09 12:27:00'),
(5, 1, 'Hazelnut Latte', 'Smooth espresso with steamed milk and sweet hazelnut syrup', 185.00, NULL, NULL, 1, '2026-02-09 12:27:00'),
(6, 2, 'Iced Vanilla Latte', 'Chilled espresso with milk and vanilla sweetness over ice', 185.00, NULL, NULL, 1, '2026-02-09 12:27:00'),
(7, 2, 'Salted Caramel', 'Sweet and salty combination of caramel, espresso, and cold milk', 195.00, NULL, NULL, 1, '2026-02-09 12:27:00'),
(8, 2, 'Iced Caramel Macchiato', 'Layered iced coffee with vanilla, milk, espresso, and caramel drizzle', 200.00, NULL, NULL, 1, '2026-02-09 12:27:00'),
(9, 2, 'Iced Mocha', 'Refreshing chocolate coffee blend served over ice', 190.00, NULL, NULL, 1, '2026-02-09 12:27:00'),
(10, 2, 'Dirty Horchata', 'Creamy rice milk drink with a bold espresso shot', 205.00, NULL, NULL, 1, '2026-02-09 12:27:00'),
(11, 3, 'Classic Hot Chocolate', 'Rich and creamy hot chocolate topped with whipped cream', 165.00, NULL, NULL, 1, '2026-02-09 12:27:00'),
(12, 3, 'Matcha Green Tea Latte', 'Smooth Japanese matcha blended with steamed milk', 180.00, NULL, NULL, 1, '2026-02-09 12:27:00'),
(13, 3, 'Strawberry Milk', 'Sweet and refreshing strawberry flavored milk', 155.00, NULL, NULL, 1, '2026-02-09 12:27:00'),
(14, 3, 'Mango Passionfruit Refresher', 'Tropical fruit blend with a refreshing citrus kick', 175.00, NULL, NULL, 1, '2026-02-09 12:27:00'),
(15, 3, 'Spiced Chai Latte', 'Aromatic spiced tea with creamy steamed milk', 170.00, NULL, NULL, 1, '2026-02-09 12:27:00'),
(16, 4, 'Dark Chocolate Freeze', 'Rich dark chocolate blended into a creamy frozen treat', 210.00, NULL, NULL, 1, '2026-02-09 12:27:00'),
(17, 4, 'Cookies & Cream Frappe', 'Crushed cookies blended with vanilla ice cream and milk', 220.00, NULL, NULL, 1, '2026-02-09 12:27:00'),
(18, 4, 'Strawberry Cheesecake', 'Creamy cheesecake flavor with strawberry swirls', 225.00, NULL, NULL, 1, '2026-02-09 12:27:00'),
(19, 4, 'Matcha Cream Frappe', 'Green tea matcha blended with cream and ice', 215.00, NULL, NULL, 1, '2026-02-09 12:27:00'),
(20, 4, 'Toffee Nut Crunch', 'Sweet toffee and nutty flavors in a frozen delight', 220.00, NULL, NULL, 1, '2026-02-09 12:27:00'),
(21, 5, 'Earl Grey Milk Tea', 'Classic Earl Grey tea with creamy milk and sweetness', 165.00, NULL, NULL, 1, '2026-02-09 12:27:00'),
(22, 5, 'Chamomile Honey', 'Soothing chamomile tea with natural honey', 155.00, NULL, NULL, 1, '2026-02-09 12:27:00'),
(23, 5, 'Peach Iced Tea', 'Refreshing black tea infused with sweet peach flavor', 160.00, NULL, NULL, 1, '2026-02-09 12:27:00'),
(24, 5, 'Lemon Ginger Tea', 'Zesty lemon and spicy ginger tea blend', 150.00, NULL, NULL, 1, '2026-02-09 12:27:00'),
(25, 5, 'Jasmine Green Tea', 'Delicate jasmine-scented green tea', 155.00, NULL, NULL, 1, '2026-02-09 12:27:00'),
(26, 6, 'New York Cheesecake', 'Classic creamy cheesecake with graham cracker crust', 195.00, NULL, NULL, 1, '2026-02-09 12:27:00'),
(27, 6, 'Tiramisu', 'Italian dessert with coffee-soaked ladyfingers and mascarpone', 210.00, NULL, NULL, 1, '2026-02-09 12:27:00'),
(28, 6, 'Chocolate Lava Cake', 'Warm chocolate cake with molten chocolate center', 220.00, NULL, NULL, 1, '2026-02-09 12:27:00'),
(29, 6, 'Affogato', 'Vanilla gelato drowned in a shot of hot espresso', 185.00, NULL, NULL, 1, '2026-02-09 12:27:00'),
(30, 6, 'Red Velvet Cake', 'Moist red velvet cake with cream cheese frosting', 200.00, NULL, NULL, 1, '2026-02-09 12:27:00'),
(31, 7, 'Classic Croissant', 'Buttery, flaky French pastry baked to golden perfection', 145.00, NULL, NULL, 1, '2026-02-09 12:27:00'),
(32, 7, 'Blueberry Scone', 'Tender scone studded with fresh blueberries', 155.00, NULL, NULL, 1, '2026-02-09 12:27:00'),
(33, 7, 'Cinnamon Roll', 'Soft sweet dough with cinnamon swirl and cream cheese frosting', 165.00, NULL, NULL, 1, '2026-02-09 12:27:00'),
(34, 7, 'Chocolate Muffin', 'Moist chocolate crumb with rich cocoa flavor', 150.00, NULL, NULL, 1, '2026-02-09 12:27:00'),
(35, 7, 'Baguette', 'Crisp golden crust with light airy interior, freshly baked', 175.00, 'images/products/product_699c86a09c9df.jpg', NULL, 1, '2026-02-09 12:27:00'),
(36, 8, 'Tuna Melt Panini', 'Grilled sandwich with tuna salad and melted cheese', 195.00, NULL, NULL, 1, '2026-02-09 12:27:00'),
(37, 8, 'Chicken Pesto Pasta', 'Tender chicken with basil pesto and pasta', 210.00, NULL, NULL, 1, '2026-02-09 12:27:00'),
(38, 8, 'Club Sandwich', 'Triple-decker with turkey, bacon, lettuce, and tomato', 205.00, NULL, NULL, 1, '2026-02-09 12:27:00'),
(39, 8, 'Sausage Roll', 'Flaky pastry wrapped around savory sausage filling', 165.00, NULL, NULL, 1, '2026-02-09 12:27:00'),
(40, 8, 'Loaded Nachos', 'Crispy tortilla chips with cheese, salsa, and toppings', 185.00, NULL, NULL, 1, '2026-02-09 12:27:00'),
(41, 9, 'Extra Espresso Shot', 'Add an extra shot of espresso to your drink for more kick', 35.00, NULL, NULL, 1, '2026-02-09 12:27:00'),
(43, 9, 'Vanilla Syrup', 'A sweet, aromatic syrup made with natural or artificial vanilla, used to add a creamy vanilla flavor to coffee, lattes, or other beverages', 25.00, NULL, NULL, 1, '2026-02-09 12:27:00'),
(55, 1, 'Caffe Americano', 'A rich espresso diluted with hot water, creating a smooth, full-bodied coffee without the intensity of straight espresso', 160.00, NULL, NULL, 1, '2026-02-21 14:45:44'),
(56, 9, 'Whipped Cream', 'Light, airy cream topping that adds richness and a creamy texture to coffee drinks or desserts', 20.00, NULL, NULL, 1, '2026-02-21 15:05:20'),
(57, 9, 'Coffee Jelly', 'Cubes of firm, slightly sweetened coffee-flavored gelatin, perfect for mixing into cold drinks or desserts', 40.00, NULL, NULL, 1, '2026-02-21 15:05:50'),
(58, 9, 'Pearl (Boba)', 'Chewy tapioca balls often added to iced teas, coffees, or milk drinks for texture and fun', 30.00, NULL, NULL, 1, '2026-02-21 15:06:06'),
(61, 10, 'Sagada Arabica (Dark Roast)', 'A Cordillera favorite with sweet, nutty notes and a balanced finish', 450.00, NULL, '250g', 1, '2026-02-28 05:47:42'),
(62, 10, 'Benguet Arabica (Medium Roast)', 'Smooth Northern Luzon beans featuring herbal undertones and a wine-like hint', 420.00, NULL, '250g', 1, '2026-02-28 05:47:42'),
(63, 10, 'Batangas Barako (Liberica)', 'The Philippines\' famous bold coffee known for its strong, woody, and pungent aroma', 350.00, NULL, '250g', 1, '2026-02-28 05:47:42'),
(64, 10, 'Mt. Apo Arabica (Medium-Dark)', 'Award-winning Mindanao beans with a complex chocolate and caramel profile', 480.00, NULL, '250g', 1, '2026-02-28 05:47:42'),
(65, 10, 'Sultan Kudarat Robusta', 'A heavy-bodied, high-caffeine bean ideal for strong espresso bases', 300.00, NULL, '250g', 1, '2026-02-28 05:47:42'),
(66, 10, 'Cavite Excelsa', 'Unique beans with tart, fruity, and dark berry flavors for complex house blends', 380.00, NULL, '250g', 1, '2026-02-28 05:47:42'),
(67, 10, 'Colombia Supremo', 'A standard imported Arabica favored for its mild, consistent, and smooth flavor', 550.00, NULL, '250g', 1, '2026-02-28 05:47:42'),
(68, 10, 'Vietnam Robusta', 'Widely used in commercial blends to create a thick crema and punchy bitterness', 280.00, NULL, '250g', 1, '2026-02-28 05:47:42'),
(69, 10, 'House Blend Espresso', 'A 70% Arabica and 30% Robusta mix designed for daily lattes and cappuccinos', 400.00, NULL, '250g', 1, '2026-02-28 05:47:42'),
(70, 10, 'Decaf Swiss Water Process', 'Premium chemical-free decaffeinated beans for caffeine-sensitive customers', 600.00, NULL, '250g', 1, '2026-02-28 05:47:42'),
(71, 11, 'Whole Milk (Nestlé/Alaska)', 'The standard full-fat dairy milk used for rich, creamy frothing in lattes', 95.00, NULL, '1L', 1, '2026-02-28 05:47:42'),
(72, 11, 'Skim Milk', 'A fat-free dairy alternative for customers seeking a lighter, lower-calorie coffee texture', 110.00, NULL, '1L', 1, '2026-02-28 05:47:42'),
(73, 11, 'Oat Milk (Oatside/MilkLab)', 'A trending barista-grade plant milk known for its malty and creamy consistency', 185.00, NULL, '1L', 1, '2026-02-28 05:47:42'),
(74, 11, 'Almond Milk (Emborg)', 'A popular nutty, dairy-free alternative for vegan-friendly menu options', 170.00, NULL, '1L', 1, '2026-02-28 05:47:42'),
(75, 11, 'Soy Milk (Vitasoy Barista)', 'A high-protein plant-based staple that holds foam well for cappuccinos', 140.00, NULL, '1L', 1, '2026-02-28 05:47:42'),
(76, 11, 'Sweetened Condensed Milk (Jersey)', 'The essential thick creamer for Spanish Lattes and Vietnamese-style coffee', 55.00, NULL, '390g', 1, '2026-02-28 05:47:42'),
(77, 11, 'Evaporated Milk (Alaska)', 'Often paired with kapeng barako for a classic, nostalgic Filipino creamy finish', 45.00, NULL, '370ml', 1, '2026-02-28 05:47:42'),
(78, 11, 'Half and Half (Anchor)', 'A rich mixture of milk and cream used to add decadence to hot Americanos', 220.00, NULL, '1L', 1, '2026-02-28 05:47:42'),
(79, 11, 'Heavy Whipping Cream (Nestlé)', 'High-fat cream used for rich coffee toppers or specialized Dirty Coffee recipes', 160.00, NULL, '250ml', 1, '2026-02-28 05:47:42'),
(80, 11, 'Non-Dairy Creamer (Coffee-Mate)', 'A budget-friendly, shelf-stable powder used in quick-service kiosks and frappes', 180.00, NULL, '450g', 1, '2026-02-28 05:47:42'),
(81, 12, 'Gemilai CRM3200 Espresso Machine', 'A highly popular and cost-effective entry-level commercial espresso machine', 25000.00, NULL, NULL, 1, '2026-02-28 05:47:42'),
(82, 12, 'Nuova Simonelli Appia Life', 'The durable, high-volume industry standard for busy Philippine cafés', 180000.00, NULL, NULL, 1, '2026-02-28 05:47:42'),
(83, 12, 'Breville Barista Express', 'An all-in-one choice common for pop-up carts and low-volume setups', 45000.00, NULL, NULL, 1, '2026-02-28 05:47:42'),
(84, 12, 'Eureka Mignon Espresso Grinder', 'A silent and precise commercial grinder used for perfect shot extraction', 22000.00, NULL, NULL, 1, '2026-02-28 05:47:42'),
(85, 12, 'Hario V60 Dripper (Ceramic)', 'The manual brewing standard for serving single-origin pour-over coffee', 1200.00, NULL, NULL, 1, '2026-02-28 05:47:42'),
(86, 12, 'Timemore Chestnut Hand Grinder', 'A reliable tool for slow-bar setups requiring precise manual grinding', 3500.00, NULL, NULL, 1, '2026-02-28 05:47:42'),
(87, 12, 'AeroPress Coffee Maker', 'A versatile and portable brewer favored by local coffee pop-ups', 2800.00, NULL, NULL, 1, '2026-02-28 05:47:42'),
(88, 12, 'Chemex Coffeemaker (6-Cup)', 'Elegant glass equipment used for premium, clean-filtered coffee service', 3200.00, NULL, NULL, 1, '2026-02-28 05:47:42'),
(89, 12, 'Temperature Controlled Kettle', 'A gooseneck kettle essential for precise water flow in manual brewing', 6500.00, NULL, NULL, 1, '2026-02-28 05:47:42'),
(90, 12, 'French Press (Bodum)', 'A classic immersion brewer used for serving simple, full-bodied coffee', 1800.00, NULL, NULL, 1, '2026-02-28 05:47:42');

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
  `mobile_number` varchar(15) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `house_unit` varchar(100) DEFAULT NULL,
  `street_name` varchar(100) DEFAULT NULL,
  `barangay` varchar(100) DEFAULT NULL,
  `city_municipality` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `zip_code` varchar(10) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','customer') DEFAULT 'customer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `email`, `mobile_number`, `profile_image`, `house_unit`, `street_name`, `barangay`, `city_municipality`, `province`, `zip_code`, `password`, `role`, `created_at`) VALUES
(1, 'Samantha Virtudazo', 'admin@purgecoffee.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '$2a$12$F4wbKxPcOnhD6K5An76ke.4gS2.m9JQTH88Q8NZYUzGwNaYhY6YKC', 'admin', '2026-02-20 02:12:15'),
(2, 'Customer', 'customer@purgecoffee.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '$2a$12$nvPoBAKZQZpBpybEbGlsNeYwwMFn.YcvPM1SqGgM0iR.iHMw2efGW', 'customer', '2026-02-20 02:23:55'),
(3, 'John Doe', 'johndoe@gmail.com', '09603150070', 'uploads/avatars/avatar_3_1772412807.jpg', '17', 'Blk 6 Lot', '', 'Davao City', 'Davao del Sur', '8000', '$2y$10$QIGd50QI/MzcG7QnQ.j.xeHGaNP65fxatJA/KEfRiKekeye/GooB6', 'customer', '2026-02-21 07:15:51');

-- --------------------------------------------------------

--
-- Table structure for table `user_carts`
--

CREATE TABLE `user_carts` (
  `cart_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `size` varchar(20) NOT NULL DEFAULT 'Short',
  `temperature` varchar(20) NOT NULL DEFAULT 'Hot',
  `sugar_level` varchar(10) NOT NULL DEFAULT '0%',
  `milk` varchar(20) NOT NULL DEFAULT 'Whole',
  `addons` text DEFAULT NULL,
  `special_instructions` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_carts`
--

INSERT INTO `user_carts` (`cart_id`, `user_id`, `product_id`, `quantity`, `size`, `temperature`, `sugar_level`, `milk`, `addons`, `special_instructions`, `updated_at`) VALUES
(751, 2, 63, 1, 'Short', 'Hot', '0%', 'Whole', '[]', '', '2026-03-03 03:52:49'),
(763, 3, 2, 2, 'Short', 'Hot', '0%', 'Whole', '[]', '', '2026-03-04 16:55:36');

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
-- Indexes for table `favorites`
--
ALTER TABLE `favorites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user_product` (`user_id`,`product_id`),
  ADD KEY `fk_fav_user` (`user_id`),
  ADD KEY `fk_fav_product` (`product_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_kiosk` (`is_kiosk`,`status`,`order_date`);

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
-- Indexes for table `user_carts`
--
ALTER TABLE `user_carts`
  ADD PRIMARY KEY (`cart_id`),
  ADD UNIQUE KEY `unique_user_product` (`user_id`,`product_id`),
  ADD KEY `fk_uc_product` (`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `favorites`
--
ALTER TABLE `favorites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=91;

--
-- AUTO_INCREMENT for table `product_interactions`
--
ALTER TABLE `product_interactions`
  MODIFY `interaction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `user_carts`
--
ALTER TABLE `user_carts`
  MODIFY `cart_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=764;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `favorites`
--
ALTER TABLE `favorites`
  ADD CONSTRAINT `fk_fav_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_fav_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

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

--
-- Constraints for table `user_carts`
--
ALTER TABLE `user_carts`
  ADD CONSTRAINT `fk_uc_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_uc_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
