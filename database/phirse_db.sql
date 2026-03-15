-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 11, 2025 at 10:34 AM
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
  `status` enum('pending','paid','confirmed','claiming','completed','cancelled') DEFAULT 'pending',
  `payment_proof_path` varchar(255) DEFAULT NULL,
  `order_date` datetime NOT NULL DEFAULT current_timestamp(),
  `payment_method` enum('onhand','gcash') DEFAULT 'onhand',
  `product_size` varchar(10) DEFAULT NULL,
  `claiming_datetime` datetime DEFAULT NULL,
  `cancellation_reason` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `max_order` int(11) DEFAULT NULL,
  `pdf_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `seller_id`, `name`, `category`, `price`, `stock`, `description`, `image_path`, `status`, `rejection_reason`, `created_at`, `ticket_type`, `allowed_organizations`, `pre_order`, `max_order`) VALUES
(52, 11, 'Sikolohiya ', 'Merchandise', 25.00, 99, 'Organization: PSYCHSOC\nType: Pins\n\n', '../uploads/products/6912da61ca2f0_product_0.JPG', 'approved', NULL, '2025-11-11 06:40:33', NULL, NULL, 0, 1),
(53, 11, 'Be Kind To Your Mind', 'Merchandise', 25.00, 100, 'Organization: PSYCHSOC\nType: Pins\n\n', '../uploads/products/6912deb767dbd_product_0.JPG', 'approved', NULL, '2025-11-11 06:59:03', NULL, NULL, 0, 1),
(54, 11, 'Butterfly1', 'Merchandise', 25.00, 100, 'Organization: PSYCHSOC\nType: Pins\n\n', '../uploads/products/6912df4c16622_product_0.JPG', 'approved', NULL, '2025-11-11 07:01:32', NULL, NULL, 0, 1),
(55, 11, 'Psych', 'Merchandise', 25.00, 100, 'Organization: PSYCHSOC\nType: Pins\n\n', '../uploads/products/6912df7a55d40_product_0.JPG', 'approved', NULL, '2025-11-11 07:02:18', NULL, NULL, 0, 1),
(56, 11, 'Psychology Degree', 'Merchandise', 25.00, 100, 'Organization: PSYCHSOC\nType: Pins\n\n', '../uploads/products/6912dfb877062_product_0.JPG', 'approved', NULL, '2025-11-11 07:03:20', NULL, NULL, 0, 1),
(57, 11, 'Psychologist In Training ', 'Merchandise', 25.00, 100, 'Organization: PSYCHSOC\nType: Pins\n\n', '../uploads/products/6912e00ddfefb_product_0.JPG', 'approved', NULL, '2025-11-11 07:04:45', NULL, NULL, 0, 1),
(58, 11, 'Magagalit Ba Si Maslow?', 'Merchandise', 25.00, 100, 'Organization: PSYCHSOC\nType: Pins\n\n', '../uploads/products/6912e066690bd_product_0.JPG', 'approved', NULL, '2025-11-11 07:06:14', NULL, NULL, 0, 1),
(59, 11, 'Stop Comparing Yourself To Others', 'Merchandise', 25.00, 100, 'Organization: PSYCHSOC\nType: Pins\n\n', '../uploads/products/6912e0a795381_product_0.JPG', 'approved', NULL, '2025-11-11 07:07:19', NULL, NULL, 0, 1),
(60, 11, 'Stop Comparing Yourself To Others', 'Merchandise', 25.00, 100, 'Organization: PSYCHSOC\nType: Pins\n\n', '../uploads/products/6912e0cd52940_product_0.JPG', 'approved', NULL, '2025-11-11 07:07:57', NULL, NULL, 0, 1),
(61, 11, 'Sikolohiya ', 'Merchandise', 100.00, 100, 'Organization: PSYCHSOC\nType: Tote Bag\n\n', '../uploads/products/6912e100ba1b9_product_0.JPG', 'approved', NULL, '2025-11-11 07:08:48', NULL, NULL, 0, 1),
(62, 11, 'End The Stigma ', 'Merchandise', 100.00, 100, 'Organization: PSYCHSOC\nType: Tote Bag\n\nMinimalist merch promoting mental health awareness.\r\nWear it. Share the message. End the stigma.', '../uploads/products/6912e168d6f8b_product_0.JPG', 'rejected', 'sorry wrong product description\r\n', '2025-11-11 07:10:32', NULL, NULL, 0, 1),
(63, 11, 'Suhay Sikolohista, Husay Sikolihista', 'Merchandise', 100.00, 100, 'Organization: PSYCHSOC\nType: Tote Bag\n\nA canvas tote bag designed for those who study, practice, and value Psychology.\r\nMade with durable, thick canvas perfect for everyday use at school, clinic duties, or casual outings.\r\nLightweight, spacious, and easy to carry.', '../uploads/products/6912e23ff3468_product_0.JPG', 'approved', NULL, '2025-11-11 07:14:07', NULL, NULL, 0, 1),
(64, 11, 'End The Stigma', 'Merchandise', 100.00, 100, 'Organization: PSYCHSOC\nType: Tote Bag\n\nA canvas tote bag that carries a message with purpose.\r\nMade from thick, durable canvas that’s perfect for daily use school, errands, or work.\r\nSpacious, reusable, and comfortable to bring anywhere.', '../uploads/products/6912e292d356d_product_0.JPG', 'approved', NULL, '2025-11-11 07:15:30', NULL, NULL, 0, 1),
(65, 9, 'VITS OFFICIAL ORGANIZATIONAL SHIRT 2025', 'Organization Shirt', 500.00, 700, 'Organization: VITS\nType: Polo Shirt\nSizes: XS, S, M, L, XL, 2XL\n\nThe VITS Official Organizational Polo Shirt 2025 represents unity, professionalism, and pride as members of the Information Technology community.\r\nMade with high-quality, breathable fabric that provides long-lasting comfort for everyday wear whether in class, events, or department activities.\r\nDesigned with a clean and modern look, featuring the official VITS branding for a polished and professional appearance.\r\n\r\nThis polo shirt is more than uniform it’s a symbol of belonging, collaboration, and excellence in the field of IT.', '../uploads/products/6912ea5429651_product_0.JPG', 'approved', NULL, '2025-11-11 07:48:36', NULL, NULL, 0, 1),
(66, 9, 'Button Pins VITS', 'Merchandise', 30.00, 100, 'Organization: VITS\nType: Pins\n\nMinimalist button pins designed for Information Technology students and enthusiasts.\r\nMade with durable metal backing and smooth, clear print to ensure long-lasting quality.\r\nPerfect for personalizing your tote bag, ID lace, jacket, or laptop pouch while showing pride in the IT field.', '../uploads/products/6912eacab7a33_product_0.JPG', 'approved', NULL, '2025-11-11 07:50:34', NULL, NULL, 0, 1),
(67, 9, 'Beaded Charms ', 'Merchandise', 30.00, 100, 'Organization: VITS\nType: Bracelet \n\n• Handmade beaded charm bracelet\r\n• Lightweight & comfortable\r\n• Cute, aesthetic, and easy to style\r\n• Perfect for daily wear or gifting', '../uploads/products/6912eb4d2e1f7_product_0.JPG', 'approved', NULL, '2025-11-11 07:52:45', NULL, NULL, 0, 1),
(68, 12, 'Celebratory Shirt UPAS ', 'Merchandise', 500.00, 1000, 'Organization: UPAS\nType: Shirt \nSizes: XS, S, M, L, XL\n\nThe UPAS Celebratory Shirt is designed to commemorate unity, commitment, and pride within the organization.\r\nMade from soft, breathable, and high-quality fabric, it provides comfort for daily wear perfect for events, assemblies, outreach programs, and casual days on campus.\r\nFeaturing a clean and meaningful design, this shirt represents shared achievements and the ongoing spirit of service and solidarity.', '../uploads/products/6912ed3a1a352_product_0.JPG', 'approved', NULL, '2025-11-11 08:00:58', NULL, NULL, 0, 1),
(69, 12, 'UPAS ORGANZATIONAL SHIRT', 'Organization Shirt', 500.00, 1000, 'Organization: UPAS\nType: Polo Shirt\nSizes: XS, S, M, L, XL, 2XL\n\nThe UPAS Organizational Shirt represents unity, identity, and pride within the organization.\r\nMade from soft, breathable, and durable fabric, it’s comfortable for everyday wear whether in classes, organizational events, community activities, or casual days on campus.\r\nFeaturing the official UPAS design, this shirt reflects the organization’s values and collective spirit.', '../uploads/products/6912edcd3a507_product_0.JPG,../uploads/products/6912edcd3af6b_product_1.JPG', 'approved', NULL, '2025-11-11 08:03:25', NULL, NULL, 0, 1),
(70, 13, 'Self Care Cats Set', 'Merchandise', 20.00, 100, 'Organization: JSWAP\nType: Stickers\n\n', '../uploads/products/6912ef2d361ed_product_0.JPG', 'approved', NULL, '2025-11-11 08:09:17', NULL, NULL, 0, 1),
(71, 13, 'SW Badge Set', 'Merchandise', 20.00, 100, 'Organization: JSWAP\nType: Stickers\n\n', '../uploads/products/6912ef850150d_product_0.JPG', 'approved', NULL, '2025-11-11 08:10:45', NULL, NULL, 0, 1),
(72, 13, 'Coqutte Bear Set', 'Merchandise', 20.00, 100, 'Organization: JSWAP\nType: Stickers \n\n', '../uploads/products/6912efbeac597_product_0.JPG', 'approved', NULL, '2025-11-11 08:11:42', NULL, NULL, 0, 1),
(73, 13, 'CAS Set!', 'Merchandise', 20.00, 100, 'Organization: JSWAP\nType: Stickers\n\n', '../uploads/products/6912efee7dfc8_product_0.JPG', 'approved', NULL, '2025-11-11 08:12:30', NULL, NULL, 0, 1),
(74, 13, 'Cartoon Set', 'Merchandise', 20.00, 100, 'Organization: JSWAP\nType: Stickers\n\n', '../uploads/products/6912f0216fa14_product_0.JPG', 'approved', NULL, '2025-11-11 08:13:21', NULL, NULL, 0, 1),
(75, 13, 'Social Work! Set', 'Merchandise', 20.00, 100, 'Organization: JSWAP\nType: Stickers\n\n', '../uploads/products/6912f04d6bcda_product_0.JPG', 'approved', NULL, '2025-11-11 08:14:05', NULL, NULL, 0, 1),
(76, 13, 'Future Loading Set', 'Merchandise', 20.00, 100, 'Organization: JSWAP\nType: Stickers\n\n', '../uploads/products/6912f08e0d20b_product_0.JPG', 'approved', NULL, '2025-11-11 08:15:10', NULL, NULL, 0, 1),
(77, 16, 'JMAP ORGANIZATIONAL SHIRT 2025', 'Organization Shirt', 500.00, 1000, 'Organization: JMAP\nType: Polo Shirt\nSizes: XS, S, M, L, XL, 2XL\n\n', '../uploads/products/6912f5435b25d_product_0.JPG', 'approved', NULL, '2025-11-11 08:35:15', NULL, NULL, 0, 1),
(78, 16, 'JPMAP Brooch Pin', 'Merchandise', 25.00, 50, 'Organization: JMAP\nType: Pins\n\n', '../uploads/products/6912f5d5b4030_product_0.JPG', 'approved', NULL, '2025-11-11 08:37:41', NULL, NULL, 0, 1),
(79, 16, 'CBA Brooch Pin', 'Merchandise', 25.00, 100, 'Organization: JMAP\nType: Pin\n\n', '../uploads/products/6912f5f893104_product_0.JPG', 'approved', NULL, '2025-11-11 08:38:16', NULL, NULL, 0, 1),
(80, 16, 'Crochet Keychains', 'Merchandise', 25.00, 50, 'Organization: JMAP\nType: Keychains\n\n', '../uploads/products/6912f6310562c_product_0.JPG', 'approved', NULL, '2025-11-11 08:39:13', NULL, NULL, 0, 1),
(81, 17, 'ACES ORGANIZATIONAL SHIRT 2025', 'Organization Shirt', 500.00, 1000, 'Organization: ACES\nType: Polo Shirt\nSizes: XS, S, M, L, XL, 2XL\n\nThe ACES Organizational Polo Shirt 2025 embodies professionalism, unity, and pride as part of the Civil Engineering community.\r\nCrafted with high-quality, breathable fabric, it provides lasting comfort whether worn during classes, field activities, organization events, or official representation.\r\nDesigned with a clean and polished look, featuring the ACES emblem and Civil Engineering identity, this polo shirt reflects dedication to service, discipline, and excellence in the field.', '../uploads/products/6912fbad7f465_product_0.JPG,../uploads/products/6912fbad8163d_product_1.JPG', 'approved', NULL, '2025-11-11 09:02:37', NULL, NULL, 0, 1);

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
(9, 'JC MARTIN MENDOZA', 'VITS', NULL, '099532772362', '$2y$10$QYC31qnrXI.6RKI.wkZSDuA/wY/BEA/OhnjGunS8XsiQgU50taW76', NULL, '../uploads/logos/6912cadac878d.jpg', NULL, NULL, 'active', '2025-11-11 05:34:18', NULL, NULL, NULL),
(11, 'ALTHEA EMELINE FRANCISCO', 'PSYCHSOC', NULL, '09451039515', '$2y$10$P2goJZzqLWCiOrflM6nBOezu44pPopTxlDcXXh9GJ/SoiNc/vAE92', NULL, '../uploads/logos/6912d1874537d.jpg', NULL, NULL, 'active', '2025-11-11 06:02:47', NULL, NULL, NULL),
(12, 'ALYSSA VILLERO', 'UPAS', NULL, '098675547656', '$2y$10$DmRHfkxsWsIkjnx6.87tbu4GkfLkYHY8FwA1hB1OUI8TVJGpmp/4q', NULL, '../uploads/logos/6912ec612802e.jpg', NULL, NULL, 'active', '2025-11-11 07:57:21', NULL, NULL, NULL),
(13, 'TREXIE ANN MENDOZA', 'JSWAP', NULL, '094353213133', '$2y$10$rgxibMlaE4Va8cKeTPFoQOP1mGDe98SJBVSaK1POdxBj6myTSTFMO', NULL, '../uploads/logos/6912eebc906d9.jpg', NULL, NULL, 'active', '2025-11-11 08:07:24', NULL, NULL, NULL),
(14, 'PRINCESS KEZEAH ROSITO', 'BACTA', NULL, '098564645646', '$2y$10$o1TaIf6N9pQGMcFZXdODJOb.5hNnir3HMrM8sdVE8Ofyo6LkM4ZlC', NULL, '../uploads/logos/6912f136b3952.jpg', NULL, NULL, 'active', '2025-11-11 08:17:58', NULL, NULL, NULL),
(15, 'CATHERIN GATAB', 'SCIRE', NULL, '095343453453', '$2y$10$/QxCZvhT0hEo1EyHWw.veubsURwD4GeyCQmySCEk3uc8GlJosWHuG', NULL, '../uploads/logos/6912f168b4078.jpg', NULL, NULL, 'active', '2025-11-11 08:18:48', NULL, NULL, NULL),
(16, 'MARIELLE BORJA', 'JMAP', NULL, '095435345354', '$2y$10$ok2BqNU5R2cRAd2BqKpT7eWmIJ47HIpFAAp2WouII37uRJjQpEJam', NULL, '../uploads/logos/6912f4caa967c.jpg', NULL, NULL, 'active', '2025-11-11 08:33:14', NULL, NULL, NULL),
(17, 'CESRIC LEI RESONTOC', 'ACES', NULL, '096545645645', '$2y$10$XrllDm1TLcFcUwoZOCG/vujaFCXCXk8..FT04qr.VwEnzizQ6In.i', NULL, '../uploads/logos/6912f7a194c76.jpg', NULL, NULL, 'active', '2025-11-11 08:45:21', NULL, NULL, NULL),
(18, 'CARL FRANCIS NABATAR', 'BPS', NULL, '095464564564', '$2y$10$r88vlk14c3QveyHdBbbsse2y2eBiWyoF9h7grg.hcTKr1okC4FzzC', NULL, '../uploads/logos/6912fa6b1ae9b.jpg', NULL, NULL, 'active', '2025-11-11 08:57:15', NULL, NULL, NULL),
(19, 'GABRIELLA GEM TALAVERA', 'BASEEC', NULL, '096544565444', '$2y$10$LH8gthLaDxZ.hYezHolroOn7u0cig4zuwsBZ0nD41IL5d2viD5DxW', NULL, '../uploads/logos/6912fdc6cbe23.jpg', NULL, NULL, 'active', '2025-11-11 09:11:35', NULL, NULL, NULL),
(20, 'MARIAN JOY FABELLO', 'SADAFIL', NULL, '094543543534', '$2y$10$lV9XUnqhUkocpDH3MKs.IuOr39TIr64ceMZIpehJs781y7VbAYQ6u', NULL, '../uploads/logos/69130298274f9.jpg', NULL, NULL, 'active', '2025-11-11 09:32:08', NULL, NULL, NULL),
(21, 'JASMINE GAMBOA', 'AEES', NULL, '097655657657', '$2y$10$yNaBw5Pbzrcsz86ss6pZQuPGhuE9jfG8Tu9nFfUvPk3JXk8Un2SAO', NULL, '../uploads/logos/6913030025f62.jpg', NULL, NULL, 'active', '2025-11-11 09:33:52', NULL, NULL, NULL);

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
(145, 9, 67, NULL, 'approved', 'Product Approved', 'Your product \"Beaded Charms \" has been approved by admin and is now live!', 0, '2025-11-11 07:53:04'),
(146, 9, 66, NULL, 'approved', 'Product Approved', 'Your product \"Button Pins VITS\" has been approved by admin and is now live!', 0, '2025-11-11 07:53:06'),
(147, 9, 65, NULL, 'approved', 'Product Approved', 'Your product \"VITS OFFICIAL ORGANIZATIONAL SHIRT 2025\" has been approved by admin and is now live!', 0, '2025-11-11 07:53:08'),
(148, 12, 68, NULL, 'approved', 'Product Approved', 'Your product \"Celebratory Shirt UPAS \" has been approved by admin and is now live!', 0, '2025-11-11 08:03:47'),
(149, 12, 69, NULL, 'approved', 'Product Approved', 'Your product \"UPAS ORGANZATIONAL SHIRT\" has been approved by admin and is now live!', 0, '2025-11-11 08:03:50'),
(150, 13, 70, NULL, 'approved', 'Product Approved', 'Your product \"Self Care Cats Set\" has been approved by admin and is now live!', 0, '2025-11-11 08:09:39'),
(151, 13, 71, NULL, 'approved', 'Product Approved', 'Your product \"SW Badge Set\" has been approved by admin and is now live!', 0, '2025-11-11 08:14:11'),
(152, 13, 75, NULL, 'approved', 'Product Approved', 'Your product \"Social Work! Set\" has been approved by admin and is now live!', 0, '2025-11-11 08:14:12'),
(153, 13, 74, NULL, 'approved', 'Product Approved', 'Your product \"Cartoon Set\" has been approved by admin and is now live!', 0, '2025-11-11 08:14:13'),
(154, 13, 73, NULL, 'approved', 'Product Approved', 'Your product \"CAS Set!\" has been approved by admin and is now live!', 0, '2025-11-11 08:14:14'),
(155, 13, 72, NULL, 'approved', 'Product Approved', 'Your product \"Coqutte Bear Set\" has been approved by admin and is now live!', 0, '2025-11-11 08:14:16'),
(156, 13, 72, NULL, 'approved', 'Product Approved', 'Your product \"Coqutte Bear Set\" has been approved by admin and is now live!', 0, '2025-11-11 08:15:15'),
(157, 13, 76, NULL, 'approved', 'Product Approved', 'Your product \"Future Loading Set\" has been approved by admin and is now live!', 0, '2025-11-11 08:15:17'),
(158, 16, 80, NULL, 'approved', 'Product Approved', 'Your product \"Crochet Keychains\" has been approved by admin and is now live!', 0, '2025-11-11 08:39:19'),
(159, 16, 79, NULL, 'approved', 'Product Approved', 'Your product \"CBA Brooch Pin\" has been approved by admin and is now live!', 0, '2025-11-11 08:39:20'),
(160, 16, 78, NULL, 'approved', 'Product Approved', 'Your product \"JPMAP Brooch Pin\" has been approved by admin and is now live!', 0, '2025-11-11 08:39:21'),
(161, 16, 77, NULL, 'approved', 'Product Approved', 'Your product \"JMAP ORGANIZATIONAL SHIRT 2025\" has been approved by admin and is now live!', 0, '2025-11-11 08:39:23'),
(162, 17, 81, NULL, 'approved', 'Product Approved', 'Your product \"ACES ORGANIZATIONAL SHIRT 2025\" has been approved by admin and is now live!', 0, '2025-11-11 09:03:08');

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
(35, '22-1234', 'KALAMARES', 'PSYCHSOC', 'BSPA 2-1', '0945335333', 'GEGE@plv.edu.ph', '$2y$10$P9eCbVsRI/qxKsZO737Sz.gxBKle1GzMkZ1mfnvMDV0cjm/jYXyrC', '2025-11-11 06:43:06'),
(62, '25-3106', 'ACOSTA, Rain Shia', 'VITS', 'BSIT 3-1', '9152301741', 'rain.acosta@plv.edu.ph', '$2y$10$ONoNWQTNun3qSfX2QWIQReLbnUG0XmUhXVDY4FvJCY9/tgxFmI2bW', '2025-11-11 07:42:39'),
(63, '25-2041', 'LIM, Eurica Danielle', 'VITS', 'BSIT 1-3', '9234415892', 'eurica.lim@plv.edu.ph', '$2y$10$OFRdg22qFinrj0TbZkqd9uwn7hY8kNuYBRjwPhlCYjaJuwq4ZV38e', '2025-11-11 07:42:39'),
(64, '25-2826', 'MAGSIMAPOY, Neil AnthonyNO, Nate Vlademyr', 'VITS', 'BSIT 3-7', '9087723164', 'nate.magsino@plv.edu.ph', '$2y$10$N9CbWfm9.gVongpTVwWop.KENji6GZ3qITjC9UxzNnEMahu1/lawC', '2025-11-11 07:42:40'),
(65, '24-3391', 'MAPOY, Neil Anthony', 'VITS', 'BSIT 3-8', '9168849021', 'neil.mapoy@plv.edu.ph', '$2y$10$LjJSOsa3CE7sL8jJSeSnBuRuPcrQ1jdbJ63vWVsXcuB9U96ChMxZu', '2025-11-11 07:42:40'),
(66, '24-3135', 'RAMIREZ, Justin Raphael', 'VITS', 'BSIT 3-9', '9273516408', 'justin.ramirez@plv.edu.ph', '$2y$10$sQQcPAplOK5G0mEy9ldZ7O0Lx1ZuayHlhF4DLia0PVIDBX/Qwp.we', '2025-11-11 07:42:40'),
(67, '23-3423', 'BERNARDINO, Adrian', 'VITS', 'BSIT 2-1', '9197342285', 'adrian.bernardino@plv.edu.ph', '$2y$10$xWQy7H9bHCubhoa34r3Hu.z0uKTfJe2vDMqX.WR.wwsm7geoMGEQq', '2025-11-11 07:42:40'),
(68, '25-2973', 'BAUTISTA, Chrizelle Andrei', 'VITS', 'BSIT 2-5', '9206689114', 'chrizelle.bautista@plv.edu.ph', '$2y$10$zzEPgGOpjwMAi07PN4tQ5eTFJUE3nZTd4qfcpDUUQeCUwX38m8Py2', '2025-11-11 07:42:40'),
(69, '25-2777', 'PADILLA, Mary Fame', 'VITS', 'BSIT 2-3', '9172435509', 'mary.padilla@plv.edu.ph', '$2y$10$42YG30r2G0/D/xckyfvlSePxst9G5x8yhjDbYyYxotFoqe7wt14Im', '2025-11-11 07:42:40'),
(70, '25-2779', 'WONG, Gabriel', 'VITS', 'BSIT 2-4', '9095537012', 'gabriel.wong@plv.edu.ph', '$2y$10$o6jaAlflKlRv2s06bywgaOlmFjmyl1AiJXfspO4MOueMouqC1Orw2', '2025-11-11 07:42:40'),
(71, '24-3364', 'PEREZ, Ma Fe Camila Nazarene', 'VITS', 'BSIT 2-5', '9183374690', 'mafe.perez@plv.edu.ph', '$2y$10$wMIr53dbVW4p5InqadObouicA0MjE8YypyXhxd4gQafScTom07I.m', '2025-11-11 07:42:40');

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
(35, 35, 11, '2025-11-11 06:43:06'),
(62, 62, 9, '2025-11-11 07:42:39'),
(63, 63, 9, '2025-11-11 07:42:39'),
(64, 64, 9, '2025-11-11 07:42:40'),
(65, 65, 9, '2025-11-11 07:42:40'),
(66, 66, 9, '2025-11-11 07:42:40'),
(67, 67, 9, '2025-11-11 07:42:40'),
(68, 68, 9, '2025-11-11 07:42:40'),
(69, 69, 9, '2025-11-11 07:42:40'),
(70, 70, 9, '2025-11-11 07:42:40'),
(71, 71, 9, '2025-11-11 07:42:40');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=105;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=83;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=82;

--
-- AUTO_INCREMENT for table `sellers`
--
ALTER TABLE `sellers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `seller_notifications`
--
ALTER TABLE `seller_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=163;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=72;

--
-- AUTO_INCREMENT for table `student_seller_affiliations`
--
ALTER TABLE `student_seller_affiliations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=72;

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
