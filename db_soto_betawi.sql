-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 14, 2025 at 09:46 AM
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
-- Database: `db_soto_betawi`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `booking_id` int(11) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `booking_date` date NOT NULL,
  `booking_time` time NOT NULL,
  `number_of_people` int(11) NOT NULL,
  `table_id` varchar(10) DEFAULT NULL,
  `status` enum('pending','confirmed','used','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`booking_id`, `customer_name`, `customer_phone`, `booking_date`, `booking_time`, `number_of_people`, `table_id`, `status`, `created_at`) VALUES
(8, 'arif hardyansyah', '085871041986', '2025-07-22', '14:05:00', 6, NULL, 'used', '2025-07-22 06:04:41'),
(9, 'send', '012423214124', '2025-08-08', '12:00:00', 10, NULL, 'confirmed', '2025-08-06 06:33:15'),
(10, 'asepongin', '08966666666666', '2025-08-06', '13:50:00', 12, 'T12-01', 'confirmed', '2025-08-06 06:47:22'),
(11, 'asepongin', '08212130430`', '2025-08-06', '17:00:00', 4, 'T4-06', 'confirmed', '2025-08-06 06:47:38');

-- --------------------------------------------------------

--
-- Table structure for table `menu`
--

CREATE TABLE `menu` (
  `menu_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `status` enum('ada','habis') DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `menu`
--

INSERT INTO `menu` (`menu_id`, `name`, `description`, `price`, `status`, `category`, `image_url`) VALUES
(7, 'Soto Betawi Daging', 'Soto Betawi khas dengan kuah santan dan irisan daging sapi pilihan.', 25000.00, 'ada', 'Makanan Utama', 'assets/images/menu/menu_687bebdead384.jpg'),
(8, 'Soto Betawi Campur', 'Perpaduan daging dan jeroan sapi dalam kuah gurih santan.', 27000.00, 'ada', 'Makanan Utama', 'assets/images/menu/menu_687bebcf7dce8.jpg'),
(9, 'Soto Betawi Jeroan', 'Nikmatnya soto dengan campuran babat, paru, dan usus.', 24000.00, 'ada', 'Makanan Utama', 'assets/images/menu/menu_6876bd0679a85.jpg'),
(10, 'Es Teh Manis', 'Minuman segar untuk menemani santapan Anda.', 5000.00, 'ada', 'Minuman', 'assets/images/menu/menu_687beb9c26b6a.jpg'),
(11, 'Es Jeruk Segar', 'Jeruk peras segar dengan es batu.', 7000.00, 'ada', 'Minuman', 'assets/images/menu/menu_687beb8648273.jpg'),
(12, 'Nasi Putih', 'Nasi putih pulen yang cocok disandingkan dengan Soto Betawi.', 4000.00, 'ada', 'Tambahan', 'assets/images/menu/menu_687bebbbd945c.jpg'),
(13, 'Kerupuk Kulit', 'Kerupuk kulit sapi goreng renyah.', 6000.00, 'ada', 'Cemilan', 'assets/images/menu/menu_687bebac9b980.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `table_number` varchar(10) DEFAULT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','processing','ready_to_serve','completed','cancelled','awaiting_payment') NOT NULL DEFAULT 'awaiting_payment',
  `customer_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `table_number`, `order_date`, `status`, `customer_name`) VALUES
(19, '1', '2025-07-22 06:05:33', 'completed', 'arif hardyansyah'),
(20, '1', '2025-07-22 06:05:55', 'completed', 'arif hardyansyah'),
(21, '1', '2025-07-22 06:07:42', 'completed', 'arif hardyansyah'),
(22, '1', '2025-07-22 06:07:42', 'completed', 'arif hardyansyah'),
(23, '1', '2025-07-22 06:08:53', 'completed', 'arif hardyansyah'),
(24, '2', '2025-07-26 15:48:00', 'completed', 'asepongin'),
(25, '12', '2025-08-05 17:20:58', 'completed', 'sdas'),
(26, '90', '2025-08-05 18:27:17', 'completed', 'asep');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `order_item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `menu_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price_at_order` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`order_item_id`, `order_id`, `menu_id`, `quantity`, `price_at_order`) VALUES
(30, 19, 11, 4, 7000.00),
(31, 19, 13, 3, 6000.00),
(32, 19, 12, 2, 4000.00),
(33, 19, 7, 3, 25000.00),
(34, 19, 9, 3, 24000.00),
(35, 20, 11, 3, 7000.00),
(36, 20, 12, 6, 4000.00),
(37, 20, 7, 3, 25000.00),
(38, 20, 9, 3, 24000.00),
(39, 21, 11, 4, 7000.00),
(40, 21, 9, 4, 24000.00),
(41, 22, 11, 4, 7000.00),
(42, 22, 9, 4, 24000.00),
(43, 23, 11, 3, 7000.00),
(44, 23, 10, 3, 5000.00),
(45, 23, 13, 3, 6000.00),
(46, 23, 12, 2, 4000.00),
(47, 23, 8, 3, 27000.00),
(48, 23, 7, 2, 25000.00),
(49, 23, 9, 2, 24000.00),
(50, 24, 13, 4, 6000.00),
(51, 24, 9, 1, 24000.00),
(52, 24, 10, 2, 5000.00),
(53, 25, 11, 12, 7000.00),
(54, 26, 10, 5, 5000.00),
(55, 26, 8, 12, 27000.00);

-- --------------------------------------------------------

--
-- Table structure for table `tables`
--

CREATE TABLE `tables` (
  `table_id` varchar(10) NOT NULL,
  `table_type` varchar(20) NOT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tables`
--

INSERT INTO `tables` (`table_id`, `table_type`, `description`) VALUES
('T12-01', 'table_12', 'Meja Panjang VIP 1'),
('T12-02', 'table_12', 'Meja Panjang VIP 2'),
('T12-03', 'table_12', 'Meja Panjang dekat Bar'),
('T2-01', 'table_2', 'Meja 2 Kursi Romantis'),
('T2-02', 'table_2', 'Meja 2 Kursi Romantis'),
('T2-03', 'table_2', 'Meja 2 Kursi dekat Jendela'),
('T2-04', 'table_2', 'Meja 2 Kursi dekat Jendela'),
('T2-05', 'table_2', 'Meja 2 Kursi Standar'),
('T4-01', 'table_4', 'Meja 4 Kursi dekat Jendela'),
('T4-02', 'table_4', 'Meja 4 Kursi di Tengah'),
('T4-03', 'table_4', 'Meja 4 Kursi di Tengah'),
('T4-04', 'table_4', 'Meja 4 Kursi Pojok'),
('T4-05', 'table_4', 'Meja 4 Kursi Pojok'),
('T4-06', 'table_4', 'Meja 4 Kursi dekat Dapur'),
('T6-01', 'table_6', 'Meja 6 Kursi Sofa'),
('T6-02', 'table_6', 'Meja 6 Kursi Sofa'),
('T6-03', 'table_6', 'Meja 6 Kursi Tengah'),
('T6-04', 'table_6', 'Meja 6 Kursi Tengah');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `transaction_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `transaction_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`transaction_id`, `order_id`, `total_amount`, `payment_method`, `transaction_date`) VALUES
(4, 19, 201000.00, 'Tunai', '2025-07-22 06:10:53'),
(5, 20, 192000.00, 'Tunai', '2025-07-26 15:55:39'),
(6, 22, 124000.00, 'Tunai', '2025-07-26 15:55:43'),
(7, 23, 241000.00, 'Tunai', '2025-07-26 16:13:25'),
(8, 21, 124000.00, 'Tunai', '2025-07-26 16:13:45'),
(9, 25, 84000.00, 'Tunai', '2025-08-05 17:21:33'),
(10, 26, 349000.00, 'Tunai', '2025-08-06 06:11:00');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','pelayan','chef','kasir') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `role`) VALUES
(1, 'admin', 'admin', 'admin'),
(2, 'chef', 'chef', 'chef'),
(3, 'pelayan', 'pelayan', 'pelayan'),
(4, 'kasir', 'kasir', 'kasir');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD KEY `table_id` (`table_id`);

--
-- Indexes for table `menu`
--
ALTER TABLE `menu`
  ADD PRIMARY KEY (`menu_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`order_item_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `menu_id` (`menu_id`);

--
-- Indexes for table `tables`
--
ALTER TABLE `tables`
  ADD PRIMARY KEY (`table_id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `menu`
--
ALTER TABLE `menu`
  MODIFY `menu_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`table_id`) REFERENCES `tables` (`table_id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`),
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`menu_id`) REFERENCES `menu` (`menu_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
