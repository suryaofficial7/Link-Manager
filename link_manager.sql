-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3308
-- Generation Time: Aug 30, 2025 at 07:23 AM
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
-- Database: `link_manager`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `color` varchar(7) DEFAULT '#3B82F6',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `color`, `created_at`) VALUES
(1, 'Jobs', '#EF4444', '2025-08-30 05:06:42'),
(2, 'Shopping', '#10B981', '2025-08-30 05:06:42'),
(3, 'News', '#8B5CF6', '2025-08-30 05:06:42'),
(4, 'Social', '#3B82F6', '2025-08-30 05:06:42'),
(5, 'Education', '#F59E0B', '2025-08-30 05:06:42'),
(6, 'code', '#EF4444', '2025-08-30 05:11:00');

-- --------------------------------------------------------

--
-- Table structure for table `links`
--

CREATE TABLE `links` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `url` varchar(500) NOT NULL,
  `description` text DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `links`
--

INSERT INTO `links` (`id`, `title`, `url`, `description`, `category_id`, `created_at`) VALUES
(2, 'cheatcheet', 'https://www.linkedin.com/feed/', 'thisis code', 6, '2025-08-30 05:11:00'),
(4, 'Tech Trends', 'https://techupdates.com/trends', 'technology insights and guides', 5, '2025-08-16 06:00:00'),
(5, 'Health Tips', 'https://healthsite.com/tips', 'simple health and fitness tips', 2, '2025-08-17 02:35:00'),
(6, 'Travel Blog', 'https://travelworld.com/blog', 'exploring new places and culture', 4, '2025-08-18 14:15:00'),
(7, 'Food Recipes', 'https://cookingsite.com/recipes', 'delicious homemade recipes', 2, '2025-08-19 06:40:00'),
(9, 'Study Notes', 'https://studentsite.com/notes', 'academic notes for learners', 1, '2025-08-21 02:05:00'),
(10, 'Sports Buzz', 'https://sportsportal.com/buzz', 'sports highlights and news', 4, '2025-08-22 15:55:00'),
(12, 'cheatcheet', 'https://www.linkedin.com/feed/', 'thisis code', 6, '2025-08-30 05:13:22');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `links`
--
ALTER TABLE `links`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `links`
--
ALTER TABLE `links`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `links`
--
ALTER TABLE `links`
  ADD CONSTRAINT `links_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
