-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 08, 2026 at 07:20 AM
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
(66, 3, 2, '2026-03-08 06:12:53'),
(67, 3, 3, '2026-03-08 06:12:54');

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
  `is_kiosk` tinyint(1) NOT NULL DEFAULT 0,
  `kiosk_order_type` enum('dine_in','take_out') DEFAULT NULL,
  `customer_name` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `order_number`, `user_id`, `order_date`, `total_amount`, `status`, `payment_method`, `delivery_address`, `created_at`, `mobile_number`, `order_type`, `house_unit`, `street_name`, `barangay`, `city_municipality`, `province`, `zip_code`, `delivery_notes`, `pickup_branch`, `pickup_date`, `pickup_time`, `is_kiosk`, `kiosk_order_type`, `customer_name`) VALUES
(1, 'PC-2026-00001', 3, '2026-02-21 17:51:26', 51.00, 'pending', 'GCash', 'test', '2026-02-21 09:51:26', NULL, 'delivery', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(4, 'PC-2026-00004', 3, '2026-02-21 23:35:42', 210.00, 'processing', 'Cash on Delivery', 'Block 6 Lot 17 Crestview Homes, Crestview Avenue, Ula, Tugbok District, Davao City, Davao del Sur 8000', '2026-02-21 15:35:42', '09123456789', 'delivery', 'Block 6 Lot 17 Crestview Homes', 'Crestview Avenue', 'Ula, Tugbok District', 'Davao City', 'Davao del Sur', '8000', 'test', '', NULL, NULL, 0, NULL, NULL),
(5, 'PC-2026-00005', 2, '2026-02-28 01:56:46', 210.00, 'completed', 'Cash on Delivery', '123, asd, asd, asd, asd 8000', '2026-02-27 17:56:46', '09603150070', 'delivery', '123', 'asd', 'asd', 'asd', 'asd', '8000', 'asdasdas', '', NULL, NULL, 0, NULL, NULL),
(6, 'PC-2026-00006', NULL, '2026-02-28 01:58:47', 160.00, 'completed', 'Cash', 'Dine In', '2026-02-27 17:58:47', '09603150070', 'pickup', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'dine_in', 'Jane Smith'),
(11, 'PC-2026-00007', 3, '2026-03-01 05:23:56', 735.00, 'pending', 'Cash on Delivery', 'Pickup: Diversion Road, Matina Balusong (Main) on 2026-03-20 at 15:30', '2026-02-28 21:23:56', '09603150070', 'pickup', '', '', '', '', '', '', '', 'Diversion Road, Matina Balusong (Main)', '2026-03-20', '15:30:00', 0, NULL, NULL),
(12, 'PC-2026-00008', 3, '2026-03-01 11:49:37', 735.00, 'pending', 'Cash on Delivery', 'Pickup: Diversion Road, Matina Balusong (Main) on 2026-03-25 at 14:00', '2026-03-01 03:49:37', '09603150070', 'pickup', '', '', '', '', '', '', '', 'Diversion Road, Matina Balusong (Main)', '2026-03-25', '14:00:00', 0, NULL, NULL),
(13, 'PC-2026-00009', 3, '2026-03-01 11:51:53', 160.00, 'pending', 'GCash', 'Pickup: Polo Street, Obrero on 2026-03-02 at 11:00', '2026-03-01 03:51:53', '09603150070', 'pickup', '', '', '', '', '', '', '', 'Polo Street, Obrero', '2026-03-02', '11:00:00', 0, NULL, NULL),
(14, 'PC-2026-00010', 3, '2026-03-01 12:20:03', 210.00, 'pending', 'Cash on Delivery', 'asd, asd, asd, asd, asd 8000', '2026-03-01 04:20:03', '09603150070', 'delivery', 'asd', 'asd', 'asd', 'asd', 'asd', '8000', 'dasdsadasdas', '', NULL, NULL, 0, NULL, NULL),
(15, 'PC-2026-00011', 3, '2026-03-01 12:28:03', 185.00, 'pending', 'Cash on Delivery', 'Pickup: Diversion Road, Matina Balusong (Main) on 2026-03-25 at 12:00', '2026-03-01 04:28:03', '09603150070', 'pickup', '', '', '', '', '', '', '', 'Diversion Road, Matina Balusong (Main)', '2026-03-25', '12:00:00', 0, NULL, NULL),
(16, 'PC-2026-00012', 3, '2026-03-01 12:38:20', 635.00, 'completed', 'Cash on Delivery', 'Blk 6 Lot 17, Crestview Avenue, Brgy. Ula, Tugobk District, Davao City, Davao del Sur 8000', '2026-03-01 04:38:20', '09603150070', 'delivery', 'Blk 6 Lot 17', 'Crestview Avenue', 'Brgy. Ula, Tugobk District', 'Davao City', 'Davao del Sur', '8000', 'test delivery notes', '', NULL, NULL, 0, NULL, NULL),
(17, 'PC-2026-00013', 3, '2026-03-01 12:39:55', 1025.00, 'completed', 'Cash on Delivery', 'Blk 6 Lot 17, Crestview Avenue, Brgy. Ula, Tugobk District, Davao City, Davao del Sur 8000', '2026-03-01 04:39:55', '09603150070', 'delivery', 'Blk 6 Lot 17', 'Crestview Avenue', 'Brgy. Ula, Tugobk District', 'Davao City', 'Davao del Sur', '8000', 'test delivery notes', '', NULL, NULL, 0, NULL, NULL),
(18, 'PC-2026-00014', 3, '2026-03-01 12:51:48', 720565.00, 'pending', 'Cash on Delivery', 'Blk 6 Lot 17, Crestview Avenue, Brgy. Ula, Tugobk District, Davao City, Davao del Sur 8000', '2026-03-01 04:51:48', '09603150070', 'delivery', 'Blk 6 Lot 17', 'Crestview Avenue', 'Brgy. Ula, Tugobk District', 'Davao City', 'Davao del Sur', '8000', 'Beside shoe rack', '', NULL, NULL, 0, NULL, NULL),
(19, 'PC-2026-00015', 3, '2026-03-01 13:04:26', 250.00, 'pending', 'Cash on Delivery', 'Blk 6 Lot 17, Crestview Avenue, Brgy. Ula, Tugobk District, Davao City, Davao del Sur 8000', '2026-03-01 05:04:26', '09603150070', 'delivery', 'Blk 6 Lot 17', 'Crestview Avenue', 'Brgy. Ula, Tugobk District', 'Davao City', 'Davao del Sur', '8000', 'Beside green gate', '', NULL, NULL, 0, NULL, NULL),
(20, 'PC-2026-00016', 3, '2026-03-01 13:15:48', 1725.00, 'pending', 'Cash on Delivery', 'Blk 6 Lot 17, Crestview Avenue, Brgy. Ula, Tugobk District, Davao City, Davao del Sur 8000', '2026-03-01 05:15:48', '09603150070', 'delivery', 'Blk 6 Lot 17', 'Crestview Avenue', 'Brgy. Ula, Tugobk District', 'Davao City', 'Davao del Sur', '8000', 'test', '', NULL, NULL, 0, NULL, NULL),
(21, 'PC-2026-00017', 3, '2026-03-01 13:27:40', 370.00, 'pending', 'Cash on Delivery', 'Blk 6 Lot 17, Crestview Avenue, Brgy. Ula, Tugobk District, Davao City, Davao del Sur 8000', '2026-03-01 05:27:40', '09603150070', 'delivery', 'Blk 6 Lot 17', 'Crestview Avenue', 'Brgy. Ula, Tugobk District', 'Davao City', 'Davao del Sur', '8000', 'dasdasdasdasdcascdascdfaefcve fugfvchdsbvfhsvecdfevdgwdvhfvegudfvdsdhuddvhdv', '', NULL, NULL, 0, NULL, NULL),
(22, 'PC-2026-00018', 3, '2026-03-01 13:51:39', 530.00, 'pending', 'Cash on Delivery', 'Blk 6 Lot 17, Crestview Avenue, Brgy. Ula, Tugobk District, Davao City, Davao del Sur 8000', '2026-03-01 05:51:39', '09603150070', 'delivery', 'Blk 6 Lot 17', 'Crestview Avenue', 'Brgy. Ula, Tugobk District', 'Davao City', 'Davao del Sur', '8000', 'Beside the shoe rack', '', NULL, NULL, 0, NULL, NULL),
(23, 'PC-2026-00019', 3, '2026-03-01 13:54:21', 210.00, 'pending', 'Cash on Delivery', 'Blk 6 Lot 17, Crestview Avenue, Brgy. Ula, Tugobk District, Davao City, Davao del Sur 8000', '2026-03-01 05:54:21', '09603150070', 'delivery', 'Blk 6 Lot 17', 'Crestview Avenue', 'Brgy. Ula, Tugobk District', 'Davao City', 'Davao del Sur', '8000', '', '', NULL, NULL, 0, NULL, NULL),
(24, 'PC-2026-00020', 3, '2026-03-01 13:55:50', 235.00, 'pending', 'Cash on Delivery', 'Blk 6 Lot 17, Crestview Avenue, Brgy. Ula, Tugobk District, Davao City, Davao del Sur 8000', '2026-03-01 05:55:50', '09603150070', 'delivery', 'Blk 6 Lot 17', 'Crestview Avenue', 'Brgy. Ula, Tugobk District', 'Davao City', 'Davao del Sur', '8000', '', '', NULL, NULL, 0, NULL, NULL),
(25, 'PC-2026-00021', 3, '2026-03-01 13:57:04', 225.00, 'pending', 'Cash on Delivery', 'Blk 6 Lot 17, Crestview Avenue, Brgy. Ula, Tugobk District, Davao City, Davao del Sur 8000', '2026-03-01 05:57:04', '09603150070', 'delivery', 'Blk 6 Lot 17', 'Crestview Avenue', 'Brgy. Ula, Tugobk District', 'Davao City', 'Davao del Sur', '8000', '', '', NULL, NULL, 0, NULL, NULL),
(26, 'PC-2026-00022', 3, '2026-03-02 05:43:36', 690.00, 'pending', 'Cash on Delivery', 'Blk 6 Lot 17, Crestview Avenue, Brgy. Ula, Tugobk District, Davao City, Davao del Sur 8000', '2026-03-01 21:43:36', '09603150070', 'delivery', 'Blk 6 Lot 17', 'Crestview Avenue', 'Brgy. Ula, Tugobk District', 'Davao City', 'Davao del Sur', '8000', 'asd', '', NULL, NULL, 0, NULL, NULL),
(27, 'PC-2026-00023', 3, '2026-03-02 08:48:53', 705.00, 'pending', 'GCash', 'Blk 6 Lot 17, Crestview Avenue, Brgy. Ula, Tugobk District, Davao City, Davao del Sur 8000', '2026-03-02 00:48:53', '09603150070', 'delivery', 'Blk 6 Lot 17', 'Crestview Avenue', 'Brgy. Ula, Tugobk District', 'Davao City', 'Davao del Sur', '8000', '', '', NULL, NULL, 0, NULL, NULL),
(28, 'PC-2026-00024', 3, '2026-03-03 12:53:25', 875.00, 'pending', 'Cash on Delivery', 'Blk 6 Lot 17, Crestview Avenue, Brgy. Ula, Tugobk District, Davao City, Davao del Sur 8000', '2026-03-03 04:53:25', '09603150070', 'delivery', 'Blk 6 Lot 17', 'Crestview Avenue', 'Brgy. Ula, Tugobk District', 'Davao City', 'Davao del Sur', '8000', '', '', NULL, NULL, 0, NULL, NULL),
(29, 'PC-2026-00025', 3, '2026-03-04 20:44:00', 735.00, 'pending', 'GCash', 'Blk 6 Lot 17, Crestview Avenue, Brgy. Ula, Tugobk District, Davao City, Davao del Sur 8000', '2026-03-04 12:44:00', '09603150070', 'delivery', 'Blk 6 Lot 17', 'Crestview Avenue', 'Brgy. Ula, Tugobk District', 'Davao City', 'Davao del Sur', '8000', '', '', NULL, NULL, 0, NULL, NULL),
(30, 'PC-2026-00026', 3, '2026-03-07 13:36:23', 385.00, 'pending', 'Cash on Delivery', 'Blk 6 Lot 17, Crestview Avenue, Brgy. Ula, Tugobk District, Davao City, Davao del Sur 8000', '2026-03-07 05:36:23', '09603150070', 'delivery', 'Blk 6 Lot 17', 'Crestview Avenue', 'Brgy. Ula, Tugobk District', 'Davao City', 'Davao del Sur', '8000', 'test', '', NULL, NULL, 0, NULL, NULL),
(31, 'PC-2026-00027', 3, '2026-03-07 13:39:18', 190.00, 'pending', 'Bank Transfer', 'Blk 6 Lot 17, Crestview Avenue, Brgy. Ula, Tugobk District, Davao City, Davao del Sur 8000', '2026-03-07 05:39:18', '09603150070', 'delivery', 'Blk 6 Lot 17', 'Crestview Avenue', 'Brgy. Ula, Tugobk District', 'Davao City', 'Davao del Sur', '8000', 'asdas', '', NULL, NULL, 0, NULL, NULL),
(32, 'PC-2026-00028', NULL, '2026-03-07 20:21:46', 520.00, 'pending', 'Pay at the counter (Cash)', 'Dine In - Table 1', '2026-03-07 12:21:46', '', 'pickup', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'dine_in', 'Guest'),
(33, 'PC-2026-00029', NULL, '2026-03-07 20:23:20', 175.00, 'pending', 'Pay at the counter (Cash)', 'Dine In - Table 1', '2026-03-07 12:23:20', '', 'pickup', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'dine_in', 'Guest'),
(34, 'PC-2026-00030', NULL, '2026-03-07 20:24:37', 140.00, 'pending', 'Pay at the counter (Cash)', 'Dine In - Table 45', '2026-03-07 12:24:37', '', 'pickup', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'dine_in', 'Guest'),
(35, 'PC-2026-00031', NULL, '2026-03-07 20:27:38', 520.00, 'pending', 'Pay at the counter (Cash)', 'Dine In - Table 31', '2026-03-07 12:27:38', '', 'pickup', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'dine_in', 'Guest'),
(36, 'PC-2026-00032', NULL, '2026-03-07 20:30:42', 475.00, 'pending', 'Pay at the counter (Cash)', 'Dine In - Table 31', '2026-03-07 12:30:42', '', 'pickup', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'dine_in', 'Guest'),
(37, 'PC-2026-00033', NULL, '2026-03-07 20:32:17', 140.00, 'pending', 'Pay at the counter (Cash)', 'Take Out', '2026-03-07 12:32:17', '', 'pickup', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'take_out', 'Guest'),
(38, 'PC-2026-00034', NULL, '2026-03-07 20:32:35', 185.00, 'pending', 'GCash', 'Take Out', '2026-03-07 12:32:35', '', 'pickup', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'take_out', 'Guest'),
(39, 'PC-2026-00035', NULL, '2026-03-07 20:36:10', 325.00, 'pending', 'GCash', 'Dine In - Table 4', '2026-03-07 12:36:10', '', 'pickup', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'dine_in', 'Guest'),
(40, 'PC-2026-00036', NULL, '2026-03-07 20:36:54', 380.00, 'pending', 'Pay at the counter (Cash)', 'Dine In - Table 31', '2026-03-07 12:36:54', '', 'pickup', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'dine_in', 'Guest'),
(41, 'PC-2026-00037', NULL, '2026-03-07 20:37:28', 855.00, 'pending', 'Maya', 'Take Out', '2026-03-07 12:37:28', '', 'pickup', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'take_out', 'Guest'),
(42, 'PC-2026-00038', NULL, '2026-03-07 20:39:50', 140.00, 'pending', 'Pay at the counter (Cash)', 'Take Out', '2026-03-07 12:39:50', '', 'pickup', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'take_out', 'Guest'),
(43, 'PC-2026-00039', NULL, '2026-03-07 20:41:29', 140.00, 'pending', 'GCash', 'Take Out', '2026-03-07 12:41:29', '', 'pickup', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'take_out', 'Guest'),
(44, 'PC-2026-00040', NULL, '2026-03-07 20:43:36', 335.00, 'pending', 'Pay at the counter (Cash)', 'Dine In - Table 35', '2026-03-07 12:43:36', '', 'pickup', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'dine_in', 'Guest'),
(45, 'PC-2026-00041', NULL, '2026-03-07 20:44:12', 325.00, 'pending', 'GCash', 'Take Out', '2026-03-07 12:44:12', '', 'pickup', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'take_out', 'Guest'),
(46, 'PC-2026-00042', NULL, '2026-03-07 20:48:02', 325.00, 'pending', 'Pay at the counter (Cash)', 'Take Out', '2026-03-07 12:48:02', '', 'pickup', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'take_out', 'Guest'),
(47, 'PC-2026-00043', NULL, '2026-03-07 20:48:13', 315.00, 'pending', 'GCash', 'Dine In - Table 7', '2026-03-07 12:48:13', '', 'pickup', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'dine_in', 'Guest'),
(48, 'PC-2026-00044', NULL, '2026-03-07 20:48:32', 345.00, 'pending', 'Pay at the counter (Cash)', 'Dine In - Table 22', '2026-03-07 12:48:32', '', 'pickup', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'dine_in', 'Guest'),
(49, 'PC-2026-00045', NULL, '2026-03-07 20:48:40', 335.00, 'pending', 'GoTyme', 'Take Out', '2026-03-07 12:48:40', '', 'pickup', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'take_out', 'Guest'),
(50, 'PC-2026-00046', 3, '2026-03-07 20:50:36', 1345.00, 'pending', 'GCash', 'Pickup: Diversion Road, Matina Balusong (Main) on 2026-03-16 at 16:30', '2026-03-07 12:50:36', '09603150070', 'pickup', '', '', '', '', '', '', '', 'Diversion Road, Matina Balusong (Main)', '2026-03-16', '16:30:00', 0, NULL, NULL),
(51, 'PC-2026-00047', 3, '2026-03-07 20:51:33', 375.00, 'pending', 'Cash on Delivery', 'Blk 6 Lot 17, Crestview Avenue, Brgy. Ula, Tugobk District, Davao City, Davao del Sur 8000', '2026-03-07 12:51:33', '09603150070', 'delivery', 'Blk 6 Lot 17', 'Crestview Avenue', 'Brgy. Ula, Tugobk District', 'Davao City', 'Davao del Sur', '8000', '', '', NULL, NULL, 0, NULL, NULL),
(52, 'PC-2026-00048', 3, '2026-03-07 21:12:40', 190.00, 'processing', 'GoTyme', 'BLK 6 LOT 17, Crestview Avenue, Ula, Tugbok, Davao City, Davao del Sur 8000', '2026-03-07 13:12:40', '09603150070', 'delivery', 'BLK 6 LOT 17', 'Crestview Avenue', 'Ula, Tugbok', 'Davao City', 'Davao del Sur', '8000', '', '', NULL, NULL, 0, NULL, NULL),
(53, 'PC-2026-00049', NULL, '2026-03-07 21:22:48', 140.00, 'pending', 'GoTyme', 'Dine In - Counter Pickup', '2026-03-07 13:22:48', '', 'pickup', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'dine_in', 'Guest'),
(54, 'PC-2026-00050', NULL, '2026-03-07 22:53:37', 140.00, 'pending', 'Pay at the counter (Cash)', 'Take Out', '2026-03-07 14:53:37', '', 'pickup', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'take_out', 'Guest'),
(55, 'PC-2026-00051', 3, '2026-03-07 22:58:44', 1260.00, 'cancelled', 'Cash on Delivery', 'Pickup: Quimpo Boulevard, Ecoland on 2026-03-08 at 12:00', '2026-03-07 14:58:44', '09603150070', 'pickup', '', '', '', '', '', '', '', 'Quimpo Boulevard, Ecoland', '2026-03-08', '12:00:00', 0, NULL, NULL),
(56, 'PC-2026-00052', 3, '2026-03-07 23:01:40', 210.00, 'pending', 'Maya', 'BLK 6 LOT 17, Crestview Avenue, Ula, Tugbok, Davao City, Davao del Sur 8000', '2026-03-07 15:01:40', '09603150070', 'delivery', 'BLK 6 LOT 17', 'Crestview Avenue', 'Ula, Tugbok', 'Davao City', 'Davao del Sur', '8000', '', '', NULL, NULL, 0, NULL, NULL),
(57, 'PC-2026-00053', 3, '2026-03-07 23:03:08', 365.00, 'pending', 'Cash on Delivery', 'BLK 6 LOT 17, Crestview Avenue, Ula, Tugbok, Davao City, Davao del Sur 8000', '2026-03-07 15:03:08', '09603150070', 'delivery', 'BLK 6 LOT 17', 'Crestview Avenue', 'Ula, Tugbok', 'Davao City', 'Davao del Sur', '8000', '', '', NULL, NULL, 0, NULL, NULL),
(58, 'PC-2026-00054', 3, '2026-03-07 23:03:41', 375.00, 'completed', 'GCash', 'BLK 6 LOT 17, Crestview Avenue, Ula, Tugbok, Davao City, Davao del Sur 8000', '2026-03-07 15:03:41', '09603150070', 'delivery', 'BLK 6 LOT 17', 'Crestview Avenue', 'Ula, Tugbok', 'Davao City', 'Davao del Sur', '8000', '', '', NULL, NULL, 0, NULL, NULL),
(59, 'PC-2026-00055', NULL, '2026-03-07 23:04:38', 315.00, 'pending', 'Maya', 'Dine In - Table 31', '2026-03-07 15:04:38', '', 'pickup', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'dine_in', 'Guest'),
(60, 'PC-2026-00056', NULL, '2026-03-07 23:10:38', 335.00, 'pending', 'Pay at the counter (Cash)', 'Dine In - Table 14', '2026-03-07 15:10:38', '', 'pickup', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'dine_in', 'Guest'),
(61, 'PC-2026-00057', NULL, '2026-03-07 23:23:54', 160.00, 'processing', 'Pay at the counter (Cash)', 'Dine In - Table 20', '2026-03-07 15:23:54', '', 'pickup', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'dine_in', 'Guest'),
(62, 'PC-2026-00058', NULL, '2026-03-07 23:25:39', 140.00, 'pending', 'GCash', 'Dine In - Table 15', '2026-03-07 15:25:39', '', 'pickup', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'dine_in', 'Guest'),
(63, 'PC-2026-00059', NULL, '2026-03-07 23:31:22', 140.00, 'processing', 'Pay at the counter (Cash)', 'Take Out', '2026-03-07 15:31:22', '', 'pickup', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'take_out', 'Guest'),
(64, 'PC-2026-00060', NULL, '2026-03-07 23:32:31', 140.00, 'pending', 'GoTyme', 'Take Out', '2026-03-07 15:32:31', '', 'pickup', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'take_out', 'Guest'),
(65, 'PC-2026-00061', NULL, '2026-03-07 23:57:20', 160.00, 'processing', 'Pay at the counter (Cash)', 'Dine In - Table 18', '2026-03-07 15:57:20', '', 'pickup', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'dine_in', 'Guest'),
(66, 'PC-2026-00062', 3, '2026-03-08 00:43:30', 210.00, 'completed', 'Cash on Delivery', 'BLK 6 LOT 17, Crestview Avenue, Ula, Tugbok, Davao City, Davao del Sur 8000', '2026-03-07 16:43:30', '09603150070', 'delivery', 'BLK 6 LOT 17', 'Crestview Avenue', 'Ula, Tugbok', 'Davao City', 'Davao del Sur', '8000', '', '', NULL, NULL, 0, NULL, NULL),
(67, 'PC-2026-00063', 3, '2026-03-08 00:45:44', 210.00, 'cancelled', 'GCash', 'BLK 6 LOT 17, Crestview Avenue, Ula, Tugbok, Davao City, Davao del Sur 8000', '2026-03-07 16:45:44', '09603150070', 'delivery', 'BLK 6 LOT 17', 'Crestview Avenue', 'Ula, Tugbok', 'Davao City', 'Davao del Sur', '8000', '', '', NULL, NULL, 0, NULL, NULL),
(68, 'PC-2026-00064', NULL, '2026-03-08 00:47:16', 175.00, 'processing', 'Pay at the counter (Cash)', 'Dine In - Table 49', '2026-03-07 16:47:16', '', 'pickup', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'dine_in', 'Guest'),
(69, 'PC-2026-00065', NULL, '2026-03-08 00:49:16', 175.00, 'pending', 'GCash', 'Dine In - Table 48', '2026-03-07 16:49:16', '', 'pickup', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'dine_in', 'Guest'),
(70, 'PC-2026-00066', 3, '2026-03-08 01:21:16', 1860.00, 'completed', 'Cash on Delivery', 'BLK 6 LOT 17, Crestview Avenue, Ula, Tugbok, Davao City, Davao del Sur 8000', '2026-03-07 17:21:16', '09603150070', 'delivery', 'BLK 6 LOT 17', 'Crestview Avenue', 'Ula, Tugbok', 'Davao City', 'Davao del Sur', '8000', '', '', NULL, NULL, 0, NULL, NULL),
(71, 'PC-2026-00067', 3, '2026-03-08 01:22:59', 225.00, 'completed', 'GCash', 'BLK 6 LOT 17, Crestview Avenue, Ula, Tugbok, Davao City, Davao del Sur 8000', '2026-03-07 17:22:59', '09603150070', 'delivery', 'BLK 6 LOT 17', 'Crestview Avenue', 'Ula, Tugbok', 'Davao City', 'Davao del Sur', '8000', '', '', NULL, NULL, 0, NULL, NULL),
(72, 'PC-2026-00068', NULL, '2026-03-08 01:24:36', 175.00, 'processing', 'Pay at the counter (Cash)', 'Dine In - Table 22', '2026-03-07 17:24:36', '', 'pickup', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'dine_in', 'Guest'),
(73, 'PC-2026-00069', 3, '2026-03-08 01:31:02', 380.00, 'processing', 'Pay at the counter (Cash)', 'Take Out', '2026-03-07 17:31:02', '', 'pickup', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'take_out', 'Guest'),
(74, 'PC-2026-00070', 3, '2026-03-08 01:32:19', 195.00, 'completed', 'Maya', 'Take Out', '2026-03-07 17:32:19', '', 'pickup', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'take_out', 'Guest'),
(75, 'PC-2026-00071', 3, '2026-03-08 04:48:48', 225.00, 'pending', 'Cash on Delivery', 'BLK 6 LOT 17, Crestview Avenue, Ula, Tugbok, Davao City, Davao del Sur 8000', '2026-03-07 20:48:48', '09603150070', 'delivery', 'BLK 6 LOT 17', 'Crestview Avenue', 'Ula, Tugbok', 'Davao City', 'Davao del Sur', '8000', '', '', NULL, NULL, 0, NULL, NULL),
(76, 'PC-2026-00072', NULL, '2026-03-08 05:02:57', 175.00, 'pending', 'Pay at the counter (Cash)', 'Dine In - Table 31', '2026-03-07 21:02:57', '', 'pickup', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'dine_in', 'Guest'),
(77, 'SO-2026-001', 3, '2026-03-08 13:56:33', 160.00, 'pending', 'Maya', 'Dine In - Table 4', '2026-03-08 05:56:33', '', 'pickup', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'dine_in', 'Guest'),
(78, 'SO-2026-002', 3, '2026-03-08 13:56:51', 140.00, 'pending', 'Pay at the counter (Cash)', 'Take Out', '2026-03-08 05:56:51', '', 'pickup', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'take_out', 'Guest');

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
(22, 19, 8, 1, 200.00, 'Short', 'Hot', '0%', 'Whole', '', ''),
(23, 20, 4, 1, 195.00, 'Short', 'Hot', '0%', 'Whole', '', 'test'),
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
(40, 29, 55, 1, 160.00, 'Short', 'Hot', '0%', 'Whole', '', ''),
(41, 30, 2, 1, 175.00, 'Short', 'Hot', '0%', 'Whole', '', ''),
(42, 30, 55, 1, 160.00, 'Short', 'Hot', '0%', 'Whole', '', ''),
(43, 31, 3, 1, 140.00, 'Short', 'Hot', '0%', 'Whole', '', ''),
(44, 32, 3, 1, 140.00, 'Short', 'Hot', '0%', 'Whole', NULL, ''),
(45, 32, 4, 1, 195.00, 'Short', 'Hot', '0%', 'Whole', NULL, ''),
(46, 32, 5, 1, 185.00, 'Short', 'Hot', '0%', 'Whole', NULL, ''),
(47, 33, 2, 1, 175.00, 'Short', 'Hot', '0%', 'Whole', NULL, ''),
(48, 34, 3, 1, 140.00, 'Short', 'Hot', '0%', 'Whole', NULL, ''),
(49, 35, 3, 1, 140.00, 'Short', 'Hot', '0%', 'Whole', NULL, ''),
(50, 35, 4, 1, 195.00, 'Short', 'Hot', '0%', 'Whole', NULL, ''),
(51, 35, 5, 1, 185.00, 'Short', 'Hot', '0%', 'Whole', NULL, ''),
(52, 36, 2, 1, 175.00, 'Short', 'Hot', '0%', 'Whole', NULL, ''),
(53, 36, 3, 1, 140.00, 'Short', 'Hot', '0%', 'Whole', NULL, ''),
(54, 36, 55, 1, 160.00, 'Short', 'Hot', '0%', 'Whole', NULL, ''),
(55, 37, 3, 1, 140.00, 'Short', 'Hot', '0%', 'Whole', NULL, ''),
(56, 38, 5, 1, 185.00, 'Short', 'Hot', '0%', 'Whole', NULL, ''),
(57, 39, 3, 1, 140.00, 'Short', 'Hot', '0%', 'Whole', NULL, ''),
(58, 39, 5, 1, 185.00, 'Short', 'Hot', '0%', 'Whole', NULL, ''),
(59, 40, 4, 1, 195.00, 'Short', 'Hot', '0%', 'Whole', NULL, ''),
(60, 40, 5, 1, 185.00, 'Short', 'Hot', '0%', 'Whole', NULL, ''),
(61, 41, 2, 1, 175.00, 'Short', 'Hot', '0%', 'Whole', NULL, ''),
(62, 41, 3, 1, 140.00, 'Short', 'Hot', '0%', 'Whole', NULL, ''),
(63, 41, 4, 1, 195.00, 'Short', 'Hot', '0%', 'Whole', NULL, ''),
(64, 41, 5, 1, 185.00, 'Short', 'Hot', '0%', 'Whole', NULL, ''),
(65, 41, 55, 1, 160.00, 'Short', 'Hot', '0%', 'Whole', NULL, ''),
(66, 42, 3, 1, 140.00, 'Short', 'Hot', '0%', 'Whole', NULL, ''),
(67, 43, 3, 1, 140.00, 'Short', 'Hot', '0%', 'Whole', NULL, ''),
(68, 44, 2, 1, 175.00, 'Short', 'Hot', '0%', 'Whole', NULL, ''),
(69, 44, 55, 1, 160.00, 'Short', 'Hot', '0%', 'Whole', NULL, ''),
(70, 45, 3, 1, 140.00, 'Short', 'Hot', '0%', 'Whole', NULL, ''),
(71, 45, 5, 1, 185.00, 'Short', 'Hot', '0%', 'Whole', NULL, ''),
(72, 46, 3, 1, 140.00, 'Short', 'Hot', '0%', 'Whole', NULL, ''),
(73, 46, 5, 1, 185.00, 'Short', 'Hot', '0%', 'Whole', NULL, ''),
(74, 47, 2, 1, 175.00, 'Short', 'Hot', '0%', 'Whole', NULL, ''),
(75, 47, 3, 1, 140.00, 'Short', 'Hot', '0%', 'Whole', NULL, ''),
(76, 48, 6, 1, 185.00, 'Short', 'Hot', '0%', 'Whole', NULL, ''),
(77, 48, 55, 1, 160.00, 'Short', 'Hot', '0%', 'Whole', NULL, ''),
(78, 49, 3, 1, 140.00, 'Short', 'Hot', '0%', 'Whole', NULL, ''),
(79, 49, 7, 1, 195.00, 'Short', 'Hot', '0%', 'Whole', NULL, ''),
(80, 50, 4, 3, 195.00, 'Short', 'Hot', '0%', 'Whole', '', ''),
(82, 51, 3, 1, 140.00, 'Short', 'Hot', '0%', 'Whole', '', ''),
(83, 51, 5, 1, 185.00, 'Short', 'Hot', '0%', 'Whole', '', ''),
(84, 52, 3, 1, 140.00, 'Short', 'Hot', '0%', 'Whole', '', ''),
(85, 53, 3, 1, 140.00, 'Short', 'Hot', '0%', 'Whole', NULL, ''),
(86, 54, 3, 1, 140.00, 'Short', 'Hot', '0%', 'Whole', NULL, ''),
(87, 55, 2, 1, 175.00, 'Short', 'Hot', '0%', 'Whole', '', ''),
(88, 55, 3, 1, 140.00, 'Short', 'Hot', '0%', 'Whole', '', ''),
(89, 55, 5, 1, 185.00, 'Short', 'Hot', '0%', 'Whole', '', ''),
(91, 56, 55, 1, 160.00, 'Short', 'Hot', '0%', 'Whole', '', ''),
(92, 57, 2, 1, 175.00, 'Short', 'Hot', '0%', 'Whole', '', ''),
(93, 57, 3, 1, 140.00, 'Short', 'Hot', '0%', 'Whole', '', ''),
(94, 58, 3, 1, 140.00, 'Short', 'Hot', '0%', 'Whole', '', ''),
(95, 58, 5, 1, 185.00, 'Short', 'Hot', '0%', 'Whole', '', ''),
(96, 59, 2, 1, 175.00, 'Short', 'Hot', '0%', 'Whole', NULL, ''),
(97, 59, 3, 1, 140.00, 'Short', 'Hot', '0%', 'Whole', NULL, ''),
(98, 60, 2, 1, 175.00, 'Short', 'Hot', '0%', 'Whole', NULL, ''),
(99, 60, 55, 1, 160.00, 'Short', 'Hot', '0%', 'Whole', NULL, ''),
(100, 61, 55, 1, 160.00, 'Short', 'Hot', '0%', 'Whole', NULL, ''),
(101, 62, 3, 1, 140.00, 'Short', 'Hot', '0%', 'Whole', NULL, ''),
(102, 63, 3, 1, 140.00, 'Short', 'Hot', '0%', 'Whole', NULL, ''),
(103, 64, 3, 1, 140.00, 'Short', 'Hot', '0%', 'Whole', NULL, ''),
(104, 65, 55, 1, 160.00, 'Short', 'Hot', '0%', 'Whole', NULL, ''),
(105, 66, 55, 1, 160.00, 'Short', 'Hot', '0%', 'Whole', '', ''),
(106, 67, 55, 1, 160.00, 'Short', 'Hot', '0%', 'Whole', '', ''),
(107, 68, 2, 1, 175.00, 'Short', 'Hot', '0%', 'Whole', NULL, ''),
(108, 69, 2, 1, 175.00, 'Short', 'Hot', '0%', 'Whole', NULL, ''),
(109, 70, 5, 2, 185.00, 'Short', 'Hot', '0%', 'Whole', '', ''),
(110, 70, 7, 2, 195.00, 'Short', 'Hot', '0%', 'Whole', '', ''),
(112, 71, 2, 1, 175.00, 'Short', 'Hot', '0%', 'Whole', '', ''),
(113, 72, 2, 1, 175.00, 'Short', 'Hot', '0%', 'Whole', NULL, ''),
(114, 73, 4, 1, 195.00, 'Short', 'Hot', '0%', 'Whole', NULL, ''),
(115, 73, 5, 1, 185.00, 'Short', 'Hot', '0%', 'Whole', NULL, ''),
(116, 74, 4, 1, 195.00, 'Short', 'Hot', '0%', 'Whole', NULL, ''),
(117, 75, 2, 1, 175.00, 'Short', 'Hot', '0%', 'Whole', '', ''),
(118, 76, 2, 1, 175.00, 'Short', 'Hot', '0%', 'Whole', NULL, ''),
(119, 77, 55, 1, 160.00, 'Short', 'Hot', '0%', 'Whole', NULL, ''),
(120, 78, 3, 1, 140.00, 'Short', 'Hot', '0%', 'Whole', NULL, '');

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
(67, 10, 'Colombia Supremo', 'A standard imported Arabica favored for its mild, consistent, and smooth flavor', 550.00, NULL, '250g', 1, '2026-02-28 05:47:42'),
(68, 10, 'Vietnam Robusta', 'Widely used in commercial blends to create a thick crema and punchy bitterness', 280.00, NULL, '250g', 1, '2026-02-28 05:47:42'),
(69, 10, 'House Blend Espresso', 'A 70% Arabica and 30% Robusta mix designed for daily lattes and cappuccinos', 400.00, NULL, '250g', 1, '2026-02-28 05:47:42'),
(70, 10, 'Decaf Swiss Water Process', 'Premium chemical-free decaffeinated beans for caffeine-sensitive customers', 600.00, NULL, '250g', 1, '2026-02-28 05:47:42'),
(72, 11, 'Skim Milk', 'A fat-free dairy alternative for customers seeking a lighter, lower-calorie coffee texture', 110.00, NULL, '1L', 1, '2026-02-28 05:47:42'),
(74, 11, 'Almond Milk (Emborg)', 'A popular nutty, dairy-free alternative for vegan-friendly menu options', 170.00, NULL, '1L', 1, '2026-02-28 05:47:42'),
(83, 12, 'Breville Barista Express', 'An all-in-one choice common for pop-up carts and low-volume setups', 45000.00, NULL, NULL, 1, '2026-02-28 05:47:42'),
(85, 12, 'Hario V60 Dripper (Ceramic)', 'The manual brewing standard for serving single-origin pour-over coffee', 1200.00, NULL, NULL, 1, '2026-02-28 05:47:42'),
(87, 12, 'AeroPress Coffee Maker', 'A versatile and portable brewer favored by local coffee pop-ups', 2800.00, NULL, NULL, 1, '2026-02-28 05:47:42'),
(89, 12, 'Temperature Controlled Kettle', 'A gooseneck kettle essential for precise water flow in manual brewing', 6500.00, NULL, NULL, 1, '2026-02-28 05:47:42'),
(90, 12, 'French Press (Bodum)', 'A classic immersion brewer used for serving simple, full-bodied coffee', 1800.00, NULL, NULL, 1, '2026-02-28 05:47:42'),
(91, 11, 'Oat Milk', 'A creamy, plant-based milk with a naturally sweet flavor, ideal for lattes and cappuccinos', 150.00, NULL, '1L', 1, '2026-03-07 20:44:26'),
(92, 11, 'Soy Milk', 'A smooth, protein-rich dairy alternative with a mild, neutral flavor for everyday coffee drinks', 130.00, NULL, '1L', 1, '2026-03-07 20:44:26'),
(93, 11, 'Whole Milk', 'Full-fat fresh dairy milk for rich, creamy coffee textures and traditional espresso drinks', 120.00, NULL, '1L', 1, '2026-03-07 20:44:26');

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
(1, 'Samantha Lewis Virtudazo', 'admin@purgecoffee.com', '09603150070', 'uploads/avatars/avatar_1_1772720738.jpg', '', '', '', '', '', '', '$2a$12$F4wbKxPcOnhD6K5An76ke.4gS2.m9JQTH88Q8NZYUzGwNaYhY6YKC', 'admin', '2026-02-20 02:12:15'),
(2, 'Customer', 'customer@purgecoffee.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '$2a$12$nvPoBAKZQZpBpybEbGlsNeYwwMFn.YcvPM1SqGgM0iR.iHMw2efGW', 'customer', '2026-02-20 02:23:55'),
(3, 'John Doe', 'johndoe@gmail.com', '09603150070', 'uploads/avatars/avatar_3_1772412807.jpg', 'BLK 6 LOT 17', 'Crestview Avenue', 'Ula, Tugbok', 'Davao City', 'Davao del Sur', '8000', '$2y$10$QIGd50QI/MzcG7QnQ.j.xeHGaNP65fxatJA/KEfRiKekeye/GooB6', 'customer', '2026-02-21 07:15:51');

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
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`);

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
-- AUTO_INCREMENT for table `favorites`
--
ALTER TABLE `favorites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=79;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=121;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=94;

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
  MODIFY `cart_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1023;

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
