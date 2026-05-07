-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 19, 2026 at 07:25 PM
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
-- Database: `gms_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `auctions`
--

CREATE TABLE `auctions` (
  `id` int(11) NOT NULL,
  `car_id` int(11) NOT NULL,
  `start_price` decimal(10,2) NOT NULL,
  `current_bid` decimal(10,2) NOT NULL,
  `reserve_price` decimal(15,2) DEFAULT NULL,
  `min_increment` decimal(10,2) DEFAULT 100.00,
  `highest_bidder_id` int(11) DEFAULT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `status` enum('Active','Closed','Failed') DEFAULT 'Active',
  `winner_paid` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `auction_bids`
--

CREATE TABLE `auction_bids` (
  `id` int(11) NOT NULL,
  `auction_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `bid_amount` decimal(10,2) NOT NULL,
  `bid_time` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `auction_sales`
--

CREATE TABLE `auction_sales` (
  `id` int(11) NOT NULL,
  `auction_id` int(11) DEFAULT NULL,
  `car_id` int(11) DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `sale_price` decimal(15,2) DEFAULT NULL,
  `sale_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `builder_temporary`
--

CREATE TABLE `builder_temporary` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `build_items`
--

CREATE TABLE `build_items` (
  `id` int(11) NOT NULL,
  `build_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cars`
--

CREATE TABLE `cars` (
  `id` int(11) NOT NULL,
  `brand` varchar(255) NOT NULL,
  `model` varchar(255) NOT NULL,
  `year` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `price` decimal(15,2) NOT NULL,
  `quantity` int(11) DEFAULT 0,
  `status` enum('In Stock','Upcoming','Out of Stock') DEFAULT 'Upcoming',
  `transmission` enum('Manual','Automatic','CVT','DCT','AMT') DEFAULT 'Automatic',
  `mileage` int(11) DEFAULT 0,
  `description` text DEFAULT NULL,
  `regular_warranty` tinyint(1) DEFAULT 1,
  `replacement_warranty` tinyint(1) DEFAULT 0,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `in_auction` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `car_repairs`
--

CREATE TABLE `car_repairs` (
  `repair_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `repair_type` enum('Service','Repair') DEFAULT 'Repair',
  `brand` varchar(100) DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  `mileage` int(11) DEFAULT NULL,
  `issue_description` text DEFAULT NULL,
  `car_image` varchar(255) DEFAULT NULL,
  `status` enum('Pending','Inspecting','Awaiting Payment','Repairing','Ready','Completed','Rejected') DEFAULT 'Pending',
  `repair_notes` text DEFAULT NULL,
  `labor_fee` decimal(10,2) DEFAULT 500.00,
  `parts_total` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `category_name` varchar(255) NOT NULL,
  `type` enum('Part','Car') DEFAULT 'Part',
  `is_mandatory` tinyint(1) DEFAULT 0,
  `category_img` varchar(255) DEFAULT 'default-cat.png'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customer`
--

CREATE TABLE `customer` (
  `customer_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `fname` varchar(127) NOT NULL,
  `lname` varchar(127) NOT NULL,
  `phone_number` varchar(31) NOT NULL,
  `email_address` varchar(255) NOT NULL,
  `address` varchar(255) NOT NULL,
  `gender` varchar(10) NOT NULL,
  `date_of_joined` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `employee_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `fname` varchar(255) NOT NULL,
  `lname` varchar(255) NOT NULL,
  `email_address` varchar(255) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `salary` decimal(10,2) DEFAULT 0.00,
  `date_of_joined` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `manager`
--

CREATE TABLE `manager` (
  `manager_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `fname` varchar(255) NOT NULL,
  `lname` varchar(255) NOT NULL,
  `email_address` varchar(255) DEFAULT NULL,
  `phone_number` varchar(255) DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `date_of_joined` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `manager`
--

INSERT INTO `manager` (`manager_id`, `user_id`, `fname`, `lname`, `email_address`, `phone_number`, `gender`, `date_of_joined`) VALUES
(1, 3, 'Admin', 'Manager', 'admin@revmotors.com', '0123456789', 'Male', '2026-02-24 14:23:05');

-- --------------------------------------------------------

--
-- Table structure for table `message`
--

CREATE TABLE `message` (
  `message_id` int(11) NOT NULL,
  `sender_full_name` varchar(100) NOT NULL,
  `sender_email` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `date_time` datetime NOT NULL DEFAULT current_timestamp(),
  `status` varchar(25) DEFAULT 'Unread'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `car_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `date_ordered` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL,
  `image` varchar(255) DEFAULT 'product-default.png',
  `category_id` int(11) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'In Stock',
  `regular_warranty` tinyint(1) DEFAULT 1,
  `replacement_warranty` tinyint(1) DEFAULT 0,
  `tags` varchar(255) DEFAULT NULL,
  `hp` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `repair_parts_selected`
--

CREATE TABLE `repair_parts_selected` (
  `id` int(11) NOT NULL,
  `repair_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `product_name` varchar(255) DEFAULT NULL,
  `product_image` varchar(255) DEFAULT NULL,
  `category_name` varchar(255) DEFAULT NULL,
  `price_at_time` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `car_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL,
  `comment` text DEFAULT NULL,
  `company_reply` text DEFAULT NULL,
  `date_posted` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `setting`
--

CREATE TABLE `setting` (
  `id` int(11) NOT NULL,
  `garage_name` varchar(127) NOT NULL,
  `slogan` varchar(255) DEFAULT NULL,
  `about` text DEFAULT NULL,
  `current_year` int(4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `setting`
--

INSERT INTO `setting` (`id`, `garage_name`, `slogan`, `about`, `current_year`) VALUES
(1, 'Rev Motors', 'Built on Trust. Tuned for Performance.', 'Our garage was established with the goal of providing reliable vehicle maintenance, skilled mechanical service, and honest workmanship for every customer. Over the years, we have grown by expanding our range of services, upgrading our tools and equipment, and adopting modern diagnostic techniques to ensure efficient and high-quality repairs. We are committed to keeping vehicles safe, road-ready, and performing at their best while building long-term trust with our customers.', 2026);

-- --------------------------------------------------------

--
-- Table structure for table `tags`
--

CREATE TABLE `tags` (
  `id` int(11) NOT NULL,
  `tag_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `uid` int(11) NOT NULL,
  `username` varchar(127) NOT NULL,
  `password` varchar(255) NOT NULL,
  `user_type` enum('manager','employee','customer') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`uid`, `username`, `password`, `user_type`) VALUES
(3, 'admin', '$2y$10$NoUMt1JP7nfZfVyqYCmx5eR0cIwvFmXD22Ey4vOa1B2dxs7uK9D1.', 'manager');

-- --------------------------------------------------------

--
-- Table structure for table `user_builds`
--

CREATE TABLE `user_builds` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `build_name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `warranty_claims`
--

CREATE TABLE `warranty_claims` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `claim_type` varchar(20) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `date_submitted` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

CREATE TABLE `wishlist` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `car_id` int(11) DEFAULT NULL,
  `date_added` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `auctions`
--
ALTER TABLE `auctions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `auction_bids`
--
ALTER TABLE `auction_bids`
  ADD PRIMARY KEY (`id`),
  ADD KEY `auction_id` (`auction_id`);

--
-- Indexes for table `auction_sales`
--
ALTER TABLE `auction_sales`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `builder_temporary`
--
ALTER TABLE `builder_temporary`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_cat` (`user_id`,`category_id`);

--
-- Indexes for table `build_items`
--
ALTER TABLE `build_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cars`
--
ALTER TABLE `cars`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `car_repairs`
--
ALTER TABLE `car_repairs`
  ADD PRIMARY KEY (`repair_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `category_name` (`category_name`);

--
-- Indexes for table `customer`
--
ALTER TABLE `customer`
  ADD PRIMARY KEY (`customer_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`employee_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `manager`
--
ALTER TABLE `manager`
  ADD PRIMARY KEY (`manager_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `message`
--
ALTER TABLE `message`
  ADD PRIMARY KEY (`message_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_persistent_history` (`customer_id`),
  ADD KEY `fk_order_car` (`car_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `repair_parts_selected`
--
ALTER TABLE `repair_parts_selected`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `setting`
--
ALTER TABLE `setting`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tags`
--
ALTER TABLE `tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tag_name` (`tag_name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`uid`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `username_2` (`username`);

--
-- Indexes for table `user_builds`
--
ALTER TABLE `user_builds`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `warranty_claims`
--
ALTER TABLE `warranty_claims`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_wishlist` (`user_id`,`product_id`,`car_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `auctions`
--
ALTER TABLE `auctions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `auction_bids`
--
ALTER TABLE `auction_bids`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `auction_sales`
--
ALTER TABLE `auction_sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `builder_temporary`
--
ALTER TABLE `builder_temporary`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=160;

--
-- AUTO_INCREMENT for table `build_items`
--
ALTER TABLE `build_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `cars`
--
ALTER TABLE `cars`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=124;

--
-- AUTO_INCREMENT for table `car_repairs`
--
ALTER TABLE `car_repairs`
  MODIFY `repair_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=96;

--
-- AUTO_INCREMENT for table `customer`
--
ALTER TABLE `customer`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `employee_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `manager`
--
ALTER TABLE `manager`
  MODIFY `manager_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `message`
--
ALTER TABLE `message`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT for table `repair_parts_selected`
--
ALTER TABLE `repair_parts_selected`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `setting`
--
ALTER TABLE `setting`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tags`
--
ALTER TABLE `tags`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `uid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `user_builds`
--
ALTER TABLE `user_builds`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `warranty_claims`
--
ALTER TABLE `warranty_claims`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=293;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `auction_bids`
--
ALTER TABLE `auction_bids`
  ADD CONSTRAINT `auction_bids_ibfk_1` FOREIGN KEY (`auction_id`) REFERENCES `auctions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cars`
--
ALTER TABLE `cars`
  ADD CONSTRAINT `cars_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `customer`
--
ALTER TABLE `customer`
  ADD CONSTRAINT `customer_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`uid`) ON DELETE CASCADE;

--
-- Constraints for table `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `employees_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`uid`) ON DELETE CASCADE;

--
-- Constraints for table `manager`
--
ALTER TABLE `manager`
  ADD CONSTRAINT `manager_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`uid`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_order_car` FOREIGN KEY (`car_id`) REFERENCES `cars` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_persistent_history` FOREIGN KEY (`customer_id`) REFERENCES `users` (`uid`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
