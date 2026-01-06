-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jan 06, 2026 at 08:27 AM
-- Server version: 8.4.7
-- PHP Version: 8.3.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `room_rental`
--

-- --------------------------------------------------------

--
-- Table structure for table `identification`
--

DROP TABLE IF EXISTS `identification`;
CREATE TABLE IF NOT EXISTS `identification` (
  `identification_id` int NOT NULL AUTO_INCREMENT,
  `renter_id` int NOT NULL,
  `document_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `document_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`identification_id`),
  KEY `renter_id` (`renter_id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `identification`
--

INSERT INTO `identification` (`identification_id`, `renter_id`, `document_type`, `document_number`) VALUES
(1, 1, 'Cambodian ID', '101190354'),
(2, 2, 'Passport', 'P0352639B'),
(3, 3, 'Passport', 'N0885237');

-- --------------------------------------------------------

--
-- Table structure for table `monthly_bill`
--

DROP TABLE IF EXISTS `monthly_bill`;
CREATE TABLE IF NOT EXISTS `monthly_bill` (
  `bill_id` int NOT NULL AUTO_INCREMENT,
  `rental_id` int NOT NULL,
  `rate_id` int NOT NULL,
  `bill_month` date NOT NULL,
  `old_electric` decimal(10,0) NOT NULL,
  `new_electric` decimal(10,0) NOT NULL,
  `water_units` decimal(10,0) NOT NULL,
  PRIMARY KEY (`bill_id`),
  KEY `rental_id` (`rental_id`),
  KEY `rate_id` (`rate_id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `monthly_bill`
--

INSERT INTO `monthly_bill` (`bill_id`, `rental_id`, `rate_id`, `bill_month`, `old_electric`, `new_electric`, `water_units`) VALUES
(1, 1, 1, '2024-06-01', 95, 111, 2),
(2, 2, 1, '2023-01-01', 100, 198, 1),
(3, 3, 1, '2025-10-01', 156, 201, 1);

-- --------------------------------------------------------

--
-- Table structure for table `rental`
--

DROP TABLE IF EXISTS `rental`;
CREATE TABLE IF NOT EXISTS `rental` (
  `rental_id` int NOT NULL AUTO_INCREMENT,
  `renter_id` int NOT NULL,
  `room_id` int NOT NULL,
  `rent_date` date NOT NULL,
  `qty_person` int DEFAULT NULL,
  PRIMARY KEY (`rental_id`),
  KEY `renter_id` (`renter_id`),
  KEY `room_id` (`room_id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rental`
--

INSERT INTO `rental` (`rental_id`, `renter_id`, `room_id`, `rent_date`, `qty_person`) VALUES
(1, 1, 6, '2024-06-15', 2),
(2, 2, 5, '2023-01-01', 1),
(3, 3, 4, '2025-10-05', 1);

-- --------------------------------------------------------

--
-- Table structure for table `renter`
--

DROP TABLE IF EXISTS `renter`;
CREATE TABLE IF NOT EXISTS `renter` (
  `renter_id` int NOT NULL AUTO_INCREMENT,
  `renter_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `gender` enum('Male','Female','Other') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mobile_number` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telegram` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `renter_address` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nationality` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`renter_id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `renter`
--

INSERT INTO `renter` (`renter_id`, `renter_name`, `gender`, `mobile_number`, `telegram`, `renter_address`, `nationality`) VALUES
(1, 'Chan Vanank', 'Male', '0962665240', 'vannakchan408', 'Takeo', 'Cambodian'),
(2, 'Mr Sand', 'Male', '0886004544', 'xand_23', 'Philippines', 'Filipino'),
(3, 'Mr Seth', 'Male', '0976828612', 'seth_25', 'Australia', 'Australian');

-- --------------------------------------------------------

--
-- Table structure for table `room`
--

DROP TABLE IF EXISTS `room`;
CREATE TABLE IF NOT EXISTS `room` (
  `room_id` int NOT NULL,
  `room_type_id` int NOT NULL,
  `capacity` int DEFAULT NULL,
  PRIMARY KEY (`room_id`),
  KEY `room_type_id` (`room_type_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `room`
--

INSERT INTO `room` (`room_id`, `room_type_id`, `capacity`) VALUES
(6, 1, 2),
(5, 2, 1),
(4, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `room_type`
--

DROP TABLE IF EXISTS `room_type`;
CREATE TABLE IF NOT EXISTS `room_type` (
  `room_type_id` int NOT NULL AUTO_INCREMENT,
  `room_type_name` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `base_room_fee` decimal(10,2) NOT NULL,
  PRIMARY KEY (`room_type_id`),
  UNIQUE KEY `room_type_name` (`room_type_name`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `room_type`
--

INSERT INTO `room_type` (`room_type_id`, `room_type_name`, `base_room_fee`) VALUES
(1, 'Small', 50.00),
(2, 'Big', 70.00);

-- --------------------------------------------------------

--
-- Table structure for table `utility_rate`
--

DROP TABLE IF EXISTS `utility_rate`;
CREATE TABLE IF NOT EXISTS `utility_rate` (
  `rate_id` int NOT NULL AUTO_INCREMENT,
  `electric_rate` decimal(10,2) NOT NULL,
  `water_rate` decimal(10,2) NOT NULL,
  `effective_date` date NOT NULL,
  PRIMARY KEY (`rate_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `utility_rate`
--

INSERT INTO `utility_rate` (`rate_id`, `electric_rate`, `water_rate`, `effective_date`) VALUES
(1, 0.25, 2.50, '2023-01-01');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
