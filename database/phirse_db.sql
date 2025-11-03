-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 17, 2025 at 08:34 AM
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
-- Database: `phirse_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_notifications`
--

CREATE TABLE `admin_notifications` (
  `id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `type` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `type` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `student_id`, `order_id`, `product_id`, `type`, `title`, `message`, `is_read`, `created_at`) VALUES
(1, 4, 1, 3, 'pending', 'Order Placed Successfully', 'Your order for KEYCHAIN has been placed. Reference: PHRS-20251015-9A3C8', 1, '2025-10-15 14:56:19'),
(2, 3, 2, 3, 'pending', 'Order Placed Successfully', 'Your order for KEYCHAIN has been placed. Reference: PHRS-20251015-980E0', 0, '2025-10-15 15:02:04'),
(3, 4, 3, 7, 'pending', 'Order Placed Successfully', 'Your order for sdasd has been placed. Reference: PHRS-20251015-4D825', 1, '2025-10-15 15:16:46'),
(4, 4, 11, 14, 'pending', 'Order Placed Successfully', 'Your order for papuntang impyerno has been placed. Reference: PHRS-20251016-90BEF', 1, '2025-10-15 16:36:56'),
(5, 4, 12, 14, 'pending', 'Order Placed Successfully', 'Your order for papuntang impyerno has been placed. Reference: PHRS-20251016-3742B', 1, '2025-10-15 16:37:00'),
(6, 4, 13, 14, 'pending', 'Order Placed Successfully', 'Your order for papuntang impyerno has been placed. Reference: PHRS-20251016-F30BB', 1, '2025-10-15 16:38:18'),
(7, 4, 14, 16, 'pending', 'Order Placed Successfully', 'Your order for BAG has been placed. Reference: PHRS-20251016-34DB5', 1, '2025-10-15 16:38:28'),
(8, 4, 15, 15, 'pending', 'Order Placed Successfully', 'Your order for sdads has been placed. Reference: PHRS-20251016-08F70', 1, '2025-10-15 16:38:52'),
(9, 4, 16, 15, 'pending', 'Order Placed Successfully', 'Your order for sdads has been placed. Reference: PHRS-20251016-E8277', 1, '2025-10-15 16:39:09'),
(10, 4, 17, 15, 'pending', 'Order Placed Successfully', 'Your order for sdads has been placed. Reference: PHRS-20251016-24EE3', 1, '2025-10-15 16:42:16'),
(11, 4, 18, 15, 'pending', 'Order Placed Successfully', 'Your order for sdads has been placed. Reference: PHRS-20251016-08E22', 1, '2025-10-15 16:42:33'),
(12, 4, 19, 16, 'pending', 'Order Placed Successfully', 'Your order for BAG has been placed. Reference: PHRS-20251016-EC791', 1, '2025-10-15 16:50:16'),
(13, 4, 20, 16, 'pending', 'Order Placed Successfully', 'Your order for BAG has been placed. Reference: PHRS-20251016-A6244', 1, '2025-10-15 16:50:49'),
(14, 4, 21, 16, 'pending', 'Order Placed Successfully', 'Your order for BAG has been placed. Reference: PHRS-20251016-068E4', 1, '2025-10-15 16:51:12'),
(15, 4, 22, 16, 'pending', 'Order Placed Successfully', 'Your order for BAG has been placed. Reference: PHRS-20251016-67742', 1, '2025-10-15 16:51:57'),
(16, 4, 23, 12, 'paid', 'Order Placed Successfully', 'Your order for FUN RUN has been placed. Reference: PHRS-20251016-FAC0B', 1, '2025-10-15 16:52:57'),
(17, 4, 24, 12, 'pending', 'Order Placed Successfully', 'Your order for FUN RUN has been placed. Reference: PHRS-20251016-C2F94', 1, '2025-10-15 16:53:50'),
(18, 4, 25, 12, 'pending', 'Order Placed Successfully', 'Your order for FUN RUN has been placed. Reference: PHRS-20251016-58942', 1, '2025-10-15 16:54:00'),
(19, 4, 26, 12, 'paid', 'Order Placed Successfully', 'Your order for FUN RUN has been placed. Reference: PHRS-20251016-FC702', 1, '2025-10-15 16:55:06'),
(20, 4, 27, 17, 'pending', 'Order Placed Successfully', 'Your order for csdcds has been placed. Reference: PHRS-20251016-2B1CD', 1, '2025-10-15 17:01:14'),
(21, 4, NULL, NULL, '', '', 'Your order status was updated to: Payment Received', 1, '2025-10-15 17:11:36'),
(22, 4, 28, 15, 'pending', 'Order Placed Successfully', 'Your order for sdads has been placed. Reference: PHRS-20251016-F580E', 0, '2025-10-15 17:14:01'),
(23, 4, 29, 17, 'pending', 'Order Placed Successfully', 'Your order for csdcds has been placed. Reference: PHRS-20251016-AF62D', 0, '2025-10-15 17:46:42'),
(24, 18, 30, 22, 'pending', 'Order Placed Successfully', 'Your order for Self Care Cats Set has been placed. Reference: PHRS-20251017-179BC', 1, '2025-10-17 02:55:57'),
(25, 18, 31, 27, 'paid', 'Order Placed Successfully', 'Your order for VITS ORGANIZATION SHIRT has been placed. Reference: PHRS-20251017-63EB4', 1, '2025-10-17 02:57:09'),
(26, 18, 32, 27, 'paid', 'Order Placed Successfully', 'Your order for VITS ORGANIZATION SHIRT has been placed. Reference: PHRS-20251017-4C20E', 1, '2025-10-17 03:15:35'),
(27, 18, NULL, NULL, '', '', 'Your order status was updated to: Ready for Claiming | Claiming Date: October 17, 2025 11:16 AM', 1, '2025-10-17 03:16:14'),
(28, 18, NULL, NULL, '', '', 'Your order status was updated to: Completed', 1, '2025-10-17 03:16:32');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `reference_number` varchar(50) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `seller_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `student_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `total_price` decimal(10,2) NOT NULL,
  `status` enum('pending','paid','confirmed','claiming','completed') DEFAULT 'pending',
  `payment_proof_path` varchar(255) DEFAULT NULL,
  `order_date` datetime NOT NULL DEFAULT current_timestamp(),
  `payment_method` enum('onhand','gcash') DEFAULT 'onhand',
  `product_size` varchar(10) DEFAULT NULL,
  `claiming_datetime` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `reference_number`, `user_id`, `seller_id`, `product_id`, `student_id`, `quantity`, `total_price`, `status`, `payment_proof_path`, `order_date`, `payment_method`, `product_size`, `claiming_datetime`) VALUES
(30, 'PHRS-20251017-179BC', 18, 4, 22, 18, 1, 20.00, 'pending', NULL, '2025-10-17 10:55:57', '', NULL, NULL),
(31, 'PHRS-20251017-63EB4', 18, 3, 27, 18, 1, 500.00, 'paid', '../uploads/payment_proofs/payment_18_27_1760669829.jpg', '2025-10-17 10:57:09', 'gcash', NULL, NULL),
(32, 'PHRS-20251017-4C20E', 18, 3, 27, 18, 1, 500.00, 'completed', '../uploads/payment_proofs/payment_18_27_1760670935.jpg', '2025-10-17 11:15:35', 'gcash', NULL, '2025-10-17 11:16:00');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `seller_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `description` text DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `ticket_type` enum('for_public','for_organization') DEFAULT NULL,
  `allowed_organizations` text DEFAULT NULL,
  `pre_order` tinyint(1) DEFAULT 0,
  `max_order` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `seller_id`, `name`, `category`, `price`, `stock`, `description`, `image_path`, `status`, `rejection_reason`, `created_at`, `ticket_type`, `allowed_organizations`, `pre_order`, `max_order`) VALUES
(21, 4, 'FUN', 'Event Ticket', 100.00, 40, 'Ticket Type: For Organization\n\n🏃‍♀️ Fun Run 2025 – Run for Fun and Fitness!\r\n\r\nGet ready to lace up and join us for an unforgettable Fun Run at SM Mall of Asia Grounds!\r\nHappening on November 16, 2025, at 8:00 AM, this exciting event is open to everyone — from first-time joggers to seasoned runners.\r\n\r\nEnjoy a morning filled with energy, laughter, and community spirit as you race along the scenic MOA route. Celebrate fitness, friendship, and fun with music, games, and great vibes waiting for you at the finish line!\r\n\r\n🏅 Event Details:\r\n📍 Location: SM Mall of Asia Grounds, Pasay City\r\n📅 Date: November 16, 2025\r\n🕗 Time: 8:00 AM\r\n\r\nDon’t miss out on this chance to make every step count — run for health, happiness, and good memories!', '../uploads/products/68f1a4afb4c0d_product_0.JPG', 'approved', NULL, '2025-10-17 02:06:39', 'for_organization', NULL, 0, 1),
(22, 4, 'Self Care Cats Set', 'Merchandise', 20.00, 49, 'Organization: JSWAP\nType: Stickers\n\nRelax, recharge, and stay pawsitive with the Self Care Cats Sticker Set!\r\nAdorable cats doing self-care activities perfect for your journal, laptop, or water bottle. A cute reminder to slow down and take care of yourself.', '../uploads/products/68f1a53b22cc4_product_0.JPG', 'approved', NULL, '2025-10-17 02:08:59', NULL, NULL, 0, 1),
(23, 3, 'VITS ORGANIZATION SHIRT', 'Organization Shirt', 500.00, 1000, 'Organization: VITS\nType: Polo Shirt\nSizes: XS, S, M, L, XL, 2XL\n\nShow your tech pride in style with the official IT Organization Polo Shirt!\r\nDesigned for comfort, confidence, and a sleek professional look this polo represents the spirit of innovation and teamwork that defines every IT student.', '../uploads/products/68f1a8625871e_product_0.jpg', 'rejected', 'only size chart\r\n', '2025-10-17 02:22:26', NULL, NULL, 0, NULL),
(27, 3, 'VITS ORGANIZATION SHIRT', 'Organization Shirt', 500.00, 998, 'Organization: VITS\nType: Polo Shirt\nSizes: XS, S, M, L, XL, 2XL\n\nShow your tech pride in style with the official IT Organization Polo Shirt!\r\nDesigned for comfort, confidence, and a sleek professional look this polo represents the spirit of innovation and teamwork that defines every IT student.', '../uploads/products/68f1a9c1137c1_product_0.JPG', 'approved', NULL, '2025-10-17 02:28:17', NULL, NULL, 0, NULL),
(28, 3, 'Button Pins', 'Merchandise', 20.00, 100, 'Organization: VITS\nType: Button Pins\n\nShow off your IT pride with these stylish IT Organization Button Pins!\r\nPerfect for your bags, lanyards, or shirts — these pins let you represent your tech spirit wherever you go.\r\n\r\nEach design reflects creativity, innovation, and the passion that defines every IT student. Whether you’re at an event, in class, or just hanging out, these pins are the perfect way to personalize your look and show your love for tech.\r\n\r\n✨ Details:\r\n\r\nDurable, glossy finish\r\n\r\nVibrant IT-themed designs\r\n\r\nLightweight and easy to pin anywhere\r\n\r\nGreat for giveaways, souvenirs, or daily use\r\n\r\nAdd a touch of tech pride and personality because every IT student deserves to shine!', '../uploads/products/68f1aa4a15203_product_0.JPG', 'approved', NULL, '2025-10-17 02:30:34', NULL, NULL, 0, 1),
(29, 5, 'UPAS ORGANZATIONAL SHIRT', 'Organization Shirt', 500.00, 1000, 'Organization: UPAS\nType: Polo Shirt\nSizes: XS, S, M, L, XL, 2XL\n\nWear your purpose with pride with the official Public Administration Organization Shirt!\r\nDesigned to represent leadership, service, and dedication to public good this shirt embodies what it means to be a true Public Administrator.\r\n\r\nMade from comfortable, high-quality fabric, it’s perfect for events, seminars, and everyday campus wear. Featuring the official organization logo and a clean, professional design, it symbolizes unity, excellence, and the commitment to serve with integrity.\r\n\r\n👕 Details:\r\n\r\nSoft and breathable fabric for all-day comfort\r\n\r\nPrinted Public Administration Organization logo\r\n\r\nAvailable in multiple sizes (Unisex fit)\r\n\r\nIdeal for academic events, organization gatherings, or casual wear\r\n\r\nLead with purpose. Serve with pride. 💼✨', '../uploads/products/68f1ac5909297_product_0.JPG,../uploads/products/68f1ac5909bb2_product_1.JPG', 'approved', NULL, '2025-10-17 02:39:21', NULL, NULL, 0, NULL),
(30, 5, 'UPAS CELEBRATORY SHIRT', 'Merchandise', 300.00, 1000, 'Organization: UPAS\nType: Shirt\nSizes: XS, S, M, L, XL\n\nCelebrate excellence, unity, and pride with the official UPAS Celebratory Shirt!\r\nThis special edition shirt honors the achievements and milestones of the University of Public Administration Students (UPAS) a symbol of leadership, service, and success.\r\n\r\nCrafted from premium, breathable fabric, it’s designed for both comfort and style perfect for organization events, recognition days, and everyday wear. Featuring the UPAS logo and a commemorative design, this shirt represents the dedication and hard work of every future public servant.\r\n\r\nDetails:\r\n\r\nHigh-quality cotton blend for maximum comfort\r\n\r\nVibrant print featuring the UPAS emblem and celebratory design\r\n\r\nAvailable in multiple sizes (Unisex fit)\r\n\r\nIdeal for events, celebrations, and organization pride\r\n\r\nWear it loud, wear it proud  because every achievement deserves to be celebrated! ', '../uploads/products/68f1accf52822_product_0.JPG', 'approved', NULL, '2025-10-17 02:41:19', NULL, NULL, 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `sellers`
--

CREATE TABLE `sellers` (
  `id` int(11) NOT NULL,
  `seller_name` varchar(100) NOT NULL,
  `organization` varchar(100) DEFAULT NULL,
  `organization_name` varchar(100) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `gcash_qr_path` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive','pending') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `qr_code_path` varchar(255) DEFAULT NULL,
  `gcash_number` varchar(12) DEFAULT NULL,
  `gcash_name` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sellers`
--

INSERT INTO `sellers` (`id`, `seller_name`, `organization`, `organization_name`, `contact_number`, `password`, `email`, `logo_path`, `gcash_qr_path`, `description`, `status`, `created_at`, `qr_code_path`, `gcash_number`, `gcash_name`) VALUES
(3, 'Gruu', 'VITS', NULL, '094533453543', '$2y$10$Hsh8xNJAIDK3eo79Wqt8UeXYPEtc2rAXgtIbgarpjzollPhpMVoga', NULL, '../uploads/logos/68f1a371eb7c0.jpg', '../uploads/gcash/gcash_qr_3_1760670943.png', NULL, 'active', '2025-10-17 02:01:22', NULL, NULL, NULL),
(4, 'Kenn', 'JSWAP', NULL, '095345435354', '$2y$10$EfVXQJJefU74iijEwKRk6.rmCbM45L84YRKByhb6bZyfQbfoS4PZW', NULL, '../uploads/logos/68f1a399f077f.jpg', NULL, NULL, 'active', '2025-10-17 02:02:02', NULL, NULL, NULL),
(5, 'Ashley', 'UPAS', NULL, '090453534535', '$2y$10$1eqVZtuh30fmxcoQxYhc..SzJAkeqs5RVwh.UsyFaX0f/KQIfBvyK', NULL, '../uploads/logos/68f1ab3932315.jpg', NULL, NULL, 'active', '2025-10-17 02:34:33', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `seller_notifications`
--

CREATE TABLE `seller_notifications` (
  `id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `order_id` int(11) DEFAULT NULL,
  `type` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `seller_notifications`
--

INSERT INTO `seller_notifications` (`id`, `seller_id`, `product_id`, `order_id`, `type`, `title`, `message`, `is_read`, `created_at`) VALUES
(1, 1, 1, NULL, 'approved', 'Product Approved', 'Your product \"STICKERS\" has been approved by admin and is now live!', 0, '2025-10-15 14:34:21'),
(2, 1, 2, NULL, 'approved', 'Product Approved', 'Your product \"papuntang langit\" has been approved by admin and is now live!', 0, '2025-10-15 14:35:56'),
(3, 1, 3, NULL, 'approved', 'Product Approved', 'Your product \"KEYCHAIN\" has been approved by admin and is now live!', 0, '2025-10-15 14:55:27'),
(4, 1, 3, 1, 'order', 'New Order Received', 'sige ordered 1 x KEYCHAIN - Total: ₱30.00', 0, '2025-10-15 14:56:19'),
(5, 1, 4, NULL, 'approved', 'Product Approved', 'Your product \"aaaaaa\" has been approved by admin and is now live!', 0, '2025-10-15 14:59:00'),
(6, 1, 3, 2, 'order', 'New Order Received', 'Pedro Reyes ordered 1 x KEYCHAIN - Total: ₱30.00', 0, '2025-10-15 15:02:04'),
(7, 1, 5, NULL, 'rejected', 'Product Rejected', 'Your product \"gg\" has been rejected. Reason: wqe', 0, '2025-10-15 15:15:36'),
(8, 1, 6, NULL, 'rejected', 'Product Rejected', 'Your product \"gg\" has been rejected. Reason: adw', 0, '2025-10-15 15:15:41'),
(9, 1, 7, NULL, 'approved', 'Product Approved', 'Your product \"sdasd\" has been approved by admin and is now live!', 0, '2025-10-15 15:16:24'),
(10, 1, 7, 3, 'order', 'New Order Received', 'sige ordered 1 x sdasd - Total: ₱123.00', 0, '2025-10-15 15:16:46'),
(11, 1, 7, NULL, 'approved', 'Product Approved', 'Your product \"sdasd\" has been approved by admin and is now live!', 0, '2025-10-15 15:24:16'),
(12, 1, 8, NULL, 'approved', 'Product Approved', 'Your product \"ticket papuntang langit\" has been approved by admin and is now live!', 0, '2025-10-15 15:25:14'),
(13, 1, 11, NULL, 'rejected', 'Product Rejected', 'Your product \"ticket papuntang langit\" has been rejected. Reason: kl;\r\n', 0, '2025-10-15 15:31:20'),
(14, 1, 12, NULL, 'approved', 'Product Approved', 'Your product \"FUN RUN\" has been approved by admin and is now live!', 0, '2025-10-15 15:31:28'),
(15, 1, 13, NULL, 'approved', 'Product Approved', 'Your product \"daadsdas\" has been approved by admin and is now live!', 0, '2025-10-15 15:36:41'),
(16, 1, 14, NULL, 'approved', 'Product Approved', 'Your product \"papuntang impyerno\" has been approved by admin and is now live!', 0, '2025-10-15 15:44:47'),
(17, 1, 15, NULL, 'approved', 'Product Approved', 'Your product \"sdads\" has been approved by admin and is now live!', 0, '2025-10-15 15:53:32'),
(18, 1, 16, NULL, 'approved', 'Product Approved', 'Your product \"BAG\" has been approved by admin and is now live!', 0, '2025-10-15 16:03:40'),
(19, 1, 16, NULL, 'approved', 'Product Approved', 'Your product \"BAG\" has been approved by admin and is now live!', 0, '2025-10-15 16:36:51'),
(20, 1, 14, 11, 'order', 'New Order Received', 'sige ordered 1 x papuntang impyerno - Total: ₱123.00', 0, '2025-10-15 16:36:56'),
(21, 1, 14, 12, 'order', 'New Order Received', 'sige ordered 1 x papuntang impyerno - Total: ₱123.00', 0, '2025-10-15 16:37:00'),
(22, 1, 14, 13, 'order', 'New Order Received', 'sige ordered 1 x papuntang impyerno - Total: ₱123.00', 0, '2025-10-15 16:38:18'),
(23, 1, 16, 14, 'order', 'New Order Received', 'sige ordered 1 x BAG - Total: ₱56.00', 0, '2025-10-15 16:38:28'),
(24, 1, 15, 15, 'order', 'New Order Received', 'sige ordered 1 x sdads - Total: ₱176.00', 0, '2025-10-15 16:38:52'),
(25, 1, 15, 16, 'order', 'New Order Received', 'sige ordered 1 x sdads - Total: ₱176.00', 0, '2025-10-15 16:39:09'),
(26, 1, 16, NULL, 'approved', 'Product Approved', 'Your product \"BAG\" has been approved by admin and is now live!', 0, '2025-10-15 16:39:23'),
(27, 1, 15, 17, 'order', 'New Order Received', 'sige ordered 1 x sdads - Total: ₱176.00', 0, '2025-10-15 16:42:16'),
(28, 1, 15, 18, 'order', 'New Order Received', 'sige ordered 1 x sdads - Total: ₱176.00', 0, '2025-10-15 16:42:33'),
(29, 1, 16, 19, 'order', 'New Order Received', 'sige ordered 1 x BAG - Total: ₱56.00', 0, '2025-10-15 16:50:16'),
(30, 1, 16, 20, 'order', 'New Order Received', 'sige ordered 1 x BAG - Total: ₱56.00', 0, '2025-10-15 16:50:49'),
(31, 1, 16, 21, 'order', 'New Order Received', 'sige ordered 1 x BAG - Total: ₱56.00', 0, '2025-10-15 16:51:12'),
(32, 1, 16, 22, 'order', 'New Order Received', 'sige ordered 1 x BAG - Total: ₱56.00', 0, '2025-10-15 16:51:57'),
(33, 1, 12, 23, 'order', 'New Order Received', 'sige ordered 1 x FUN RUN - Total: ₱123.00', 0, '2025-10-15 16:52:57'),
(34, 1, 12, 24, 'order', 'New Order Received', 'sige ordered 1 x FUN RUN - Total: ₱123.00', 0, '2025-10-15 16:53:50'),
(35, 1, 12, 25, 'order', 'New Order Received', 'sige ordered 1 x FUN RUN - Total: ₱123.00', 0, '2025-10-15 16:54:00'),
(36, 1, 12, 26, 'order', 'New Order Received', 'sige ordered 1 x FUN RUN - Total: ₱123.00', 0, '2025-10-15 16:55:06'),
(37, 1, 16, NULL, 'approved', 'Product Approved', 'Your product \"BAG\" has been approved by admin and is now live!', 0, '2025-10-15 16:56:29'),
(38, 1, 17, NULL, 'approved', 'Product Approved', 'Your product \"csdcds\" has been approved by admin and is now live!', 0, '2025-10-15 17:00:54'),
(39, 1, 17, 27, 'order', 'New Order Received', 'sige ordered 1 x csdcds - Total: ₱123.00', 0, '2025-10-15 17:01:14'),
(40, 1, 15, 28, 'order', 'New Order Received', 'sige ordered 1 x sdads - Total: ₱176.00', 0, '2025-10-15 17:14:01'),
(41, 2, 19, NULL, 'approved', 'Product Approved', 'Your product \"adsads\" has been approved by admin and is now live!', 0, '2025-10-15 17:15:33'),
(42, 2, 18, NULL, 'approved', 'Product Approved', 'Your product \"sdadsasd\" has been approved by admin and is now live!', 0, '2025-10-15 17:15:34'),
(43, 2, 20, NULL, 'approved', 'Product Approved', 'Your product \"dsadasd\" has been approved by admin and is now live!', 0, '2025-10-15 17:28:46'),
(44, 1, 17, 29, 'order', 'New Order Received', 'sige ordered 1 x csdcds - Total: ₱123.00', 0, '2025-10-15 17:46:42'),
(45, 4, 22, NULL, 'approved', 'Product Approved', 'Your product \"Self Care Cats Set\" has been approved by admin and is now live!', 0, '2025-10-17 02:09:29'),
(46, 4, 21, NULL, 'approved', 'Product Approved', 'Your product \"FUN\" has been approved by admin and is now live!', 0, '2025-10-17 02:09:31'),
(47, 3, 23, NULL, 'rejected', 'Product Rejected', 'Your product \"VITS ORGANIZATION SHIRT\" has been rejected. Reason: only size chart\r\n', 1, '2025-10-17 02:23:06'),
(48, 3, 24, NULL, 'approved', 'Product Approved', 'Your product \"VITS ORGANIZATION SHIRT\" has been approved by admin and is now live!', 1, '2025-10-17 02:24:58'),
(49, 3, 25, NULL, 'approved', 'Product Approved', 'Your product \"VITS ORGANIZATION SHIRT\" has been approved by admin and is now live!', 1, '2025-10-17 02:26:14'),
(50, 3, 26, NULL, 'approved', 'Product Approved', 'Your product \"VITS ORGANIZATION SHIRT\" has been approved by admin and is now live!', 1, '2025-10-17 02:27:29'),
(51, 3, 27, NULL, 'approved', 'Product Approved', 'Your product \"VITS ORGANIZATION SHIRT\" has been approved by admin and is now live!', 1, '2025-10-17 02:28:23'),
(52, 3, 28, NULL, 'approved', 'Product Approved', 'Your product \"Button Pins\" has been approved by admin and is now live!', 1, '2025-10-17 02:30:40'),
(53, 5, 30, NULL, 'approved', 'Product Approved', 'Your product \"UPAS CELEBRATORY SHIRT\" has been approved by admin and is now live!', 0, '2025-10-17 02:41:30'),
(54, 5, 29, NULL, 'approved', 'Product Approved', 'Your product \"UPAS ORGANZATIONAL SHIRT\" has been approved by admin and is now live!', 0, '2025-10-17 02:41:32'),
(55, 4, 22, 30, 'order', 'New Order Received', 'Pedro Reyes ordered 1 x Self Care Cats Set - Total: ₱20.00', 0, '2025-10-17 02:55:57'),
(56, 3, 27, 31, 'order', 'New Order Received', 'Pedro Reyes ordered 1 x VITS ORGANIZATION SHIRT - Total: ₱500.00', 1, '2025-10-17 02:57:09'),
(57, 3, 27, 32, 'order', 'New Order Received', 'Pedro Reyes ordered 1 x VITS ORGANIZATION SHIRT - Total: ₱500.00', 1, '2025-10-17 03:15:35');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `student_number` varchar(50) NOT NULL,
  `student_name` varchar(100) NOT NULL,
  `organization` varchar(100) DEFAULT NULL,
  `course_section` varchar(100) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `student_number`, `student_name`, `organization`, `course_section`, `contact_number`, `email`, `password`, `created_at`) VALUES
(7, '22-1678', 'Ava Srinivasan', 'JSWAP', 'BSSW 3-1', '9123456789', 'AvaSrinivasan@plv.edu.ph', '$2y$10$nXh3tVjNo1KeJ0O5fNQ4rekAJwg6iLyhi5q/Oqe2KiFLlkfgyJgza', '2025-10-17 02:14:44'),
(8, '22-4533', 'Ethan Sriram', 'JSWAP', 'BSSW 5-3', '9187654321', 'EthanSriram@plv.edu.ph', '$2y$10$KICJzEhIKOrqBfGwiSHMCu8PIWjnYfGkTGObSJnhy59f7FBphnUcy', '2025-10-17 02:14:44'),
(9, '22-5678', 'Maya Srisan', 'JSWAP', 'BSSW 3-7', '9156789012', 'MayaSrisan@plv.edu.ph', '$2y$10$ZZlVqdU.mBZMbnc8xAWo4ehXk9NUVve1WKsIqjmYhoIu3uUvTAP3m', '2025-10-17 02:14:44'),
(13, '22-9563', 'Alyssa Marie Santos', 'UPAS', 'BSPA 3-1', '9123456789', 'alyssa.santos@plv.edu.ph', '$2y$10$01WNzT8yJaHb0KvIYODceeDiZx4aMfm8D2MREpe/NdsM2yHwVLopm', '2025-10-17 02:51:03'),
(14, '22-4532', 'John Patrick Dela Cruz', 'UPAS', 'BSPA 5-3', '9187654321', 'john.delacruz@plv.edu.ph', '$2y$10$l8aIkt3HruSYob2KpmDFLei750VcK84xUWgNdyiS3zknbHVP6lXUy', '2025-10-17 02:51:03'),
(15, '22-6546', 'Maria Angela Ramirez', 'UPAS', 'BSPA 3-7', '9156789012', 'maria.ramirez@plv.edu.ph', '$2y$10$l3HRctLQNwi4tQD3vEZKvudRvwzZekVvyFIawjziU9R2ZeI9KsHse', '2025-10-17 02:51:04'),
(16, '22-1034', 'Juan Dela Cruz', 'VITS', 'BSIT 3-1', '09123456789', 'juan.delacruz@plv.edu.ph', '$2y$10$DA29B3msTymK0hF8jg8XTub0.qZX/v6aFXDEVOh.G3a9pmotvBeha', '2025-10-17 02:53:26'),
(17, '22-2222', 'Maria Santos', 'VITS', 'BSIT 5-3', '09187654321', 'maria.santos@plv.edu.ph', '$2y$10$eCbOydV3QXAh18Nc0hhb/ej8K0nKBkXGbza47/lSh1Rcdp36LYdCK', '2025-10-17 02:53:26'),
(18, '22-1234', 'Pedro Reyes', 'VITS', 'BSIT 3-7', '09156789012', 'pedro.reyes@plv.edu.ph', '$2y$10$zYGuhIxOtaAzDpdK6BCwSOkBAm46jhYoC6OS708sW.oHIH6ryah/2', '2025-10-17 02:53:26');

-- --------------------------------------------------------

--
-- Table structure for table `student_seller_affiliations`
--

CREATE TABLE `student_seller_affiliations` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_seller_affiliations`
--

INSERT INTO `student_seller_affiliations` (`id`, `student_id`, `seller_id`, `created_at`) VALUES
(7, 7, 4, '2025-10-17 02:14:44'),
(8, 8, 4, '2025-10-17 02:14:44'),
(9, 9, 4, '2025-10-17 02:14:44'),
(13, 13, 5, '2025-10-17 02:51:03'),
(14, 14, 5, '2025-10-17 02:51:03'),
(15, 15, 5, '2025-10-17 02:51:04'),
(16, 16, 3, '2025-10-17 02:53:26'),
(17, 17, 3, '2025-10-17 02:53:26'),
(18, 18, 3, '2025-10-17 02:53:26');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `user_id` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `user_id`, `password`, `email`, `created_at`) VALUES
(1, 'admin', '$2y$10$lpIJD2nPGEzMWA182UBQzeQER0Cbp2Dnzv9jLtVZLPPZoTDQFoqcy', 'admin@phirse.com', '2025-09-08 12:55:37');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_notifications`
--
ALTER TABLE `admin_notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `reference_number` (`reference_number`),
  ADD KEY `seller_id` (`seller_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `fk_orders_student_fix` (`student_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `seller_id` (`seller_id`);

--
-- Indexes for table `sellers`
--
ALTER TABLE `sellers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `seller_notifications`
--
ALTER TABLE `seller_notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_number` (`student_number`);

--
-- Indexes for table `student_seller_affiliations`
--
ALTER TABLE `student_seller_affiliations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_affiliation` (`student_id`,`seller_id`),
  ADD KEY `student_seller_affiliations_ibfk_2` (`seller_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_notifications`
--
ALTER TABLE `admin_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `sellers`
--
ALTER TABLE `sellers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `seller_notifications`
--
ALTER TABLE `seller_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `student_seller_affiliations`
--
ALTER TABLE `student_seller_affiliations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_orders_student_fix` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`seller_id`) REFERENCES `sellers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orders_ibfk_3` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `sellers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_seller_affiliations`
--
ALTER TABLE `student_seller_affiliations`
  ADD CONSTRAINT `student_seller_affiliations_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_seller_affiliations_ibfk_2` FOREIGN KEY (`seller_id`) REFERENCES `sellers` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
