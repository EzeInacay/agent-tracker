-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 29, 2025 at 09:52 AM
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
-- Database: `katravel_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `admin_id` varchar(50) NOT NULL,
  `password` varchar(100) NOT NULL,
  `admin_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`admin_id`, `password`, `admin_name`) VALUES
('12345', 'password', 'john_doe');

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `booking_id` int(11) NOT NULL,
  `agent_id` varchar(50) DEFAULT NULL,
  `client_name` varchar(100) NOT NULL,
  `hotel_booked` varchar(100) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `ratehawk_price` decimal(10,2) NOT NULL,
  `final_price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `booking_status`
--

CREATE TABLE `booking_status` (
  `status_id` int(11) NOT NULL,
  `booking_id` int(11) DEFAULT NULL,
  `booking_status` enum('Pending','Confirmed','Cancelled','Completed') DEFAULT 'Pending',
  `earnings` decimal(10,2) DEFAULT NULL,
  `payout_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `agent_id` varchar(50) NOT NULL,
  `password` varchar(100) NOT NULL,
  `agent_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`admin_id`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD KEY `agent_id` (`agent_id`);

--
-- Indexes for table `booking_status`
--
ALTER TABLE `booking_status`
  ADD PRIMARY KEY (`status_id`),
  ADD KEY `booking_id` (`booking_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`agent_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `booking_status`
--
ALTER TABLE `booking_status`
  MODIFY `status_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`agent_id`) REFERENCES `users` (`agent_id`) ON DELETE CASCADE;

--
-- Constraints for table `booking_status`
--
ALTER TABLE `booking_status`
  ADD CONSTRAINT `booking_status_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
___________________________________________________________________________________________________________________________


-- ============================================================
-- DATABASE: katravel_system
-- This schema handles admins, agents, bookings, payouts,
-- and client requests for the travel system.
-- ============================================================

CREATE DATABASE IF NOT EXISTS katravel_system;
USE katravel_system;

-- ============================================================
-- Admins Table
-- ============================================================
CREATE TABLE IF NOT EXISTS admins (
    admin_id VARCHAR(50) PRIMARY KEY,
    password VARCHAR(100) NOT NULL,
    admin_name VARCHAR(100) NOT NULL
);

-- ============================================================
-- Users (Agents) Table
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    agent_id VARCHAR(50) PRIMARY KEY,
    password VARCHAR(100) NOT NULL,
    agent_name VARCHAR(100) NOT NULL
);

-- ============================================================
-- Bookings Table
-- Removed: hotel_booked, total_price
-- Changed: ratehawk_price → contracting_rate
-- Changed: final_price → published_rate
-- Added: commission_rate
-- ============================================================
CREATE TABLE IF NOT EXISTS bookings (
    booking_id INT AUTO_INCREMENT PRIMARY KEY,
    agent_id VARCHAR(50),
    client_name VARCHAR(100) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    contracting_rate DECIMAL(10,2) NOT NULL,
    published_rate DECIMAL(10,2) NOT NULL,
    commission_rate DECIMAL(5,2) NOT NULL DEFAULT 60.00,
    FOREIGN KEY (agent_id) REFERENCES users(agent_id) ON DELETE CASCADE
);

-- ============================================================
-- Booking Status Table
-- Removed: payout_date
-- ============================================================
CREATE TABLE IF NOT EXISTS booking_status (
    status_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT,
    booking_status ENUM('Pending','Confirmed','Cancelled','Completed') DEFAULT 'Pending',
    earnings DECIMAL(10,2),
    commission_date DATE NOT NULL DEFAULT CURRENT_DATE,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE
);

-- ============================================================
-- Requests Table
-- ============================================================
CREATE TABLE IF NOT EXISTS requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100),
    email VARCHAR(100),
    password VARCHAR(255),
    contact_number VARCHAR(20),
    address TEXT,
    profile_pic VARCHAR(255),
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- Payout Requests Table
-- ============================================================
CREATE TABLE IF NOT EXISTS payout_requests (
    payout_id INT AUTO_INCREMENT PRIMARY KEY,
    agent_id VARCHAR(50),
    amount DECIMAL(10,2) NOT NULL,
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('Pending','Processed') DEFAULT 'Pending',
    seen_admin TINYINT(1) DEFAULT 0,
    seen TINYINT(1) DEFAULT 0,
    FOREIGN KEY (agent_id) REFERENCES users(agent_id) ON DELETE CASCADE
);
