-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 21, 2025 at 10:57 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `diagnostic_center_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `bills`
--

CREATE TABLE `bills` (
  `id` int(11) NOT NULL,
  `invoice_number` int(11) DEFAULT NULL,
  `patient_id` int(11) NOT NULL,
  `receptionist_id` int(11) NOT NULL,
  `referral_type` enum('Doctor','Self','Other') NOT NULL,
  `referral_doctor_id` int(11) DEFAULT NULL,
  `gross_amount` decimal(10,2) NOT NULL,
  `discount` decimal(10,2) DEFAULT 0.00,
  `discount_by` enum('Center','Doctor') NOT NULL DEFAULT 'Center',
  `net_amount` decimal(10,2) NOT NULL,
  `amount_paid` decimal(10,2) NOT NULL DEFAULT 0.00,
  `balance_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_mode` varchar(50) DEFAULT NULL,
  `payment_status` enum('Paid','Due','Half Paid') NOT NULL DEFAULT 'Due',
  `bill_status` enum('Original','Re-Billed','Void') NOT NULL DEFAULT 'Original',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `referral_source_other` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bills`
--

INSERT INTO `bills` (`id`, `invoice_number`, `patient_id`, `receptionist_id`, `referral_type`, `referral_doctor_id`, `gross_amount`, `discount`, `discount_by`, `net_amount`, `amount_paid`, `balance_amount`, `payment_mode`, `payment_status`, `bill_status`, `created_at`, `updated_at`, `referral_source_other`) VALUES
(3, NULL, 65, 2, 'Doctor', 10, 46232.00, 5655.00, 'Doctor', 40577.00, 40577.00, 0.00, 'Cash', 'Paid', 'Original', '2025-10-02 15:26:53', '2025-10-02 15:26:53', NULL),
(4, NULL, 66, 2, 'Other', NULL, 46232.00, 4623.00, 'Center', 41609.00, 41609.00, 0.00, 'Cash', 'Paid', 'Original', '2025-10-02 15:33:45', '2025-10-02 15:33:45', 'Newspaper'),
(5, NULL, 67, 2, 'Doctor', 10, 48232.00, 4823.00, 'Doctor', 43409.00, 43409.00, 0.00, 'UPI', 'Paid', 'Original', '2025-10-02 15:45:58', '2025-10-02 15:45:58', NULL),
(7, NULL, 69, 2, 'Doctor', 13, 12000.00, 888.00, 'Doctor', 11112.00, 11112.00, 0.00, 'Cash', 'Paid', 'Original', '2025-10-02 16:20:43', '2025-10-02 16:20:43', NULL),
(8, NULL, 70, 2, 'Doctor', 10, 58232.00, 7777.00, 'Center', 50455.00, 50455.00, 0.00, 'UPI', 'Paid', 'Original', '2025-10-04 05:55:50', '2025-10-04 05:55:50', NULL),
(9, NULL, 71, 2, 'Doctor', 10, 46232.00, 5000.00, 'Doctor', 41232.00, 41232.00, 0.00, 'Cash', 'Paid', 'Original', '2025-10-15 15:52:59', '2025-10-15 16:39:37', NULL),
(10, NULL, 72, 2, 'Doctor', 10, 4428.00, 333.00, 'Center', 4095.00, 4095.00, 0.00, 'Cash', 'Paid', 'Original', '2025-10-18 12:38:36', '2025-10-18 12:51:31', NULL),
(11, NULL, 73, 2, 'Doctor', 10, 49160.00, 900.00, 'Center', 48260.00, 48260.00, 0.00, 'UPI', 'Paid', 'Original', '2025-10-18 12:54:41', '2025-10-19 15:40:35', NULL),
(12, NULL, 74, 2, 'Doctor', 9, 46232.00, 3333.00, 'Doctor', 42899.00, 31000.00, 11899.00, 'UPI', 'Half Paid', 'Original', '2025-10-18 13:05:51', '2025-10-18 13:20:05', NULL),
(13, NULL, 75, 2, 'Doctor', 8, 48232.00, 500.00, 'Center', 47732.00, 47732.00, 0.00, 'UPI', 'Paid', 'Original', '2025-10-19 15:18:43', '2025-10-19 15:21:57', NULL),
(14, NULL, 76, 2, 'Doctor', 15, 1500.00, 50.00, 'Doctor', 1450.00, 1450.00, 0.00, 'Cash', 'Paid', 'Original', '2025-10-19 15:36:09', '2025-10-19 15:36:09', NULL),
(15, NULL, 77, 2, 'Doctor', 13, 1200.00, 0.00, 'Center', 1200.00, 1200.00, 0.00, 'Cash', 'Paid', 'Original', '2025-10-21 05:31:42', '2025-10-21 05:31:42', NULL),
(16, NULL, 78, 2, 'Doctor', 10, 12020.00, 0.00, 'Center', 12020.00, 12020.00, 0.00, 'Cash', 'Paid', 'Original', '2025-10-21 08:14:30', '2025-10-21 08:14:30', NULL),
(17, NULL, 79, 2, 'Doctor', 13, 56232.00, 2000.00, 'Doctor', 54232.00, 20000.00, 34232.00, 'Card', 'Half Paid', 'Original', '2025-10-21 08:46:17', '2025-10-21 08:46:17', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `bill_edit_log`
--

CREATE TABLE `bill_edit_log` (
  `id` int(11) NOT NULL,
  `bill_id` int(11) NOT NULL,
  `editor_id` int(11) NOT NULL,
  `reason_for_change` text NOT NULL,
  `previous_data_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`previous_data_json`)),
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bill_edit_requests`
--

CREATE TABLE `bill_edit_requests` (
  `id` int(11) NOT NULL,
  `bill_id` int(11) NOT NULL,
  `receptionist_id` int(11) NOT NULL,
  `reason_for_change` text NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bill_edit_requests`
--

INSERT INTO `bill_edit_requests` (`id`, `bill_id`, `receptionist_id`, `reason_for_change`, `status`, `created_at`) VALUES
(1, 2, 2, 'nothing', 'approved', '2025-09-23 10:04:36'),
(2, 13, 2, 'Wrong test assigned', 'approved', '2025-10-19 20:53:57');

-- --------------------------------------------------------

--
-- Table structure for table `bill_items`
--

CREATE TABLE `bill_items` (
  `id` int(11) NOT NULL,
  `bill_id` int(11) NOT NULL,
  `test_id` int(11) NOT NULL,
  `report_content` longtext DEFAULT NULL,
  `report_status` enum('Pending','Completed') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `item_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=Visible, 1=Deleted'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bill_items`
--

INSERT INTO `bill_items` (`id`, `bill_id`, `test_id`, `report_content`, `report_status`, `created_at`, `updated_at`, `item_status`) VALUES
(6, 3, 22, NULL, 'Pending', '2025-10-02 15:26:53', '2025-10-02 15:26:53', 0),
(7, 4, 22, NULL, 'Pending', '2025-10-02 15:33:45', '2025-10-02 15:33:45', 0),
(8, 5, 10, NULL, 'Pending', '2025-10-02 15:45:58', '2025-10-02 15:45:58', 0),
(9, 5, 22, NULL, 'Pending', '2025-10-02 15:45:58', '2025-10-02 15:45:58', 0),
(10, 7, 10, NULL, 'Pending', '2025-10-02 16:20:43', '2025-10-02 16:20:43', 0),
(11, 7, 11, NULL, 'Pending', '2025-10-02 16:20:43', '2025-10-02 16:20:43', 0),
(12, 8, 10, NULL, 'Pending', '2025-10-04 05:55:50', '2025-10-04 05:55:50', 0),
(13, 8, 14, NULL, 'Pending', '2025-10-04 05:55:50', '2025-10-04 05:55:50', 0),
(14, 8, 22, NULL, 'Pending', '2025-10-04 05:55:50', '2025-10-04 05:55:50', 0),
(15, 9, 22, NULL, 'Pending', '2025-10-15 15:52:59', '2025-10-15 15:52:59', 0),
(16, 10, 10, NULL, 'Pending', '2025-10-18 12:38:36', '2025-10-18 12:38:36', 0),
(17, 10, 23, NULL, 'Pending', '2025-10-18 12:38:36', '2025-10-18 12:38:36', 0),
(18, 11, 8, NULL, 'Pending', '2025-10-18 12:54:41', '2025-10-18 12:54:41', 0),
(19, 11, 22, NULL, 'Pending', '2025-10-18 12:54:41', '2025-10-18 12:54:41', 0),
(20, 11, 23, NULL, 'Pending', '2025-10-18 12:54:41', '2025-10-19 15:13:35', 1),
(21, 12, 22, NULL, 'Pending', '2025-10-18 13:05:51', '2025-10-18 13:05:51', 0),
(22, 13, 10, NULL, 'Pending', '2025-10-19 15:18:43', '2025-10-19 15:18:43', 0),
(23, 13, 22, NULL, 'Pending', '2025-10-19 15:18:43', '2025-10-19 15:18:43', 0),
(24, 14, 74, NULL, 'Pending', '2025-10-19 15:36:09', '2025-10-19 15:36:09', 0),
(25, 15, 75, NULL, 'Pending', '2025-10-21 05:31:42', '2025-10-21 05:31:42', 0),
(26, 16, 7, NULL, 'Pending', '2025-10-21 08:14:30', '2025-10-21 08:14:30', 0),
(28, 16, 14, NULL, 'Pending', '2025-10-21 08:14:30', '2025-10-21 08:14:30', 0),
(29, 16, 63, NULL, 'Pending', '2025-10-21 08:14:30', '2025-10-21 08:14:30', 0),
(30, 17, 14, NULL, 'Pending', '2025-10-21 08:46:17', '2025-10-21 08:46:17', 0),
(31, 17, 22, NULL, 'Pending', '2025-10-21 08:46:17', '2025-10-21 08:46:17', 0);

-- --------------------------------------------------------

--
-- Table structure for table `calendar_events`
--

CREATE TABLE `calendar_events` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `event_date` date NOT NULL,
  `event_type` enum('Doctor Event','Company Event','Holiday','Birthday','Anniversary','Other') NOT NULL,
  `details` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `calendar_events`
--

INSERT INTO `calendar_events` (`id`, `title`, `event_date`, `event_type`, `details`, `created_by`, `created_at`) VALUES
(1, 'dr krishna birthday', '2025-07-29', 'Doctor Event', '', 5, '2025-07-29 05:28:05'),
(2, 'Ratna birthday', '2025-07-30', 'Doctor Event', '', 5, '2025-07-29 16:34:45'),
(3, 'dr krishna birthday', '2025-07-30', 'Doctor Event', '', 5, '2025-07-30 06:16:33'),
(4, 'birthday', '2025-08-06', 'Company Event', '', 5, '2025-08-06 15:59:55'),
(5, 'Dr. Ratna Birthday', '2025-09-20', 'Birthday', 'Bday', 5, '2025-09-19 08:52:07');

-- --------------------------------------------------------

--
-- Table structure for table `doctor_payout_history`
--

CREATE TABLE `doctor_payout_history` (
  `id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `payout_amount` decimal(10,2) NOT NULL,
  `payout_period_start` date NOT NULL,
  `payout_period_end` date NOT NULL,
  `proof_path` varchar(255) DEFAULT NULL,
  `paid_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `accountant_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `doctor_test_payables`
--

CREATE TABLE `doctor_test_payables` (
  `id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `test_id` int(11) NOT NULL,
  `payable_amount` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctor_test_payables`
--

INSERT INTO `doctor_test_payables` (`id`, `doctor_id`, `test_id`, `payable_amount`) VALUES
(45, 8, 12, 5.00),
(46, 8, 10, 7.00),
(47, 8, 9, 20.00),
(48, 8, 6, 30.00),
(49, 8, 11, 80.00),
(50, 8, 7, 2.00),
(51, 8, 8, 0.00),
(59, 10, 12, 2.00),
(60, 10, 10, 5.00),
(61, 10, 9, 3.00),
(62, 10, 6, 6.00),
(63, 10, 11, 10.00),
(64, 10, 7, 2.00),
(65, 10, 13, 11.00),
(66, 7, 10, 20.00),
(67, 7, 9, 20.00),
(68, 7, 8, 50.00),
(69, 12, 12, 2.00),
(70, 12, 10, 2.00),
(71, 12, 9, 2.00),
(72, 12, 6, 2.00),
(73, 12, 11, 2.00),
(74, 12, 7, 2.00),
(75, 12, 13, 2.00),
(76, 12, 8, 2.00);

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int(11) NOT NULL,
  `expense_type` varchar(100) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` varchar(50) DEFAULT NULL,
  `proof_path` varchar(255) DEFAULT NULL,
  `accountant_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `expenses`
--

INSERT INTO `expenses` (`id`, `expense_type`, `amount`, `status`, `proof_path`, `accountant_id`, `created_at`) VALUES
(1, 'Office rent', 1000.00, 'Paid', NULL, 3, '2025-07-24 17:10:12'),
(2, 'salary for security', 500.00, 'Due', NULL, 3, '2025-07-24 17:10:41'),
(3, 'current', 40000.00, 'Paid', '../uploads/expenses/expense_68849617ab7b2.pdf', 3, '2025-07-26 08:47:19'),
(4, 'Ac bill', 12000.00, 'Paid', NULL, 3, '2025-09-20 02:57:03');

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `sex` enum('Male','Female','Other') NOT NULL,
  `age` int(11) NOT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `mobile_number` varchar(15) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`id`, `name`, `sex`, `age`, `address`, `city`, `mobile_number`, `created_at`) VALUES
(4, 'abc', 'Male', 16, 'ASDGH', NULL, NULL, '2025-07-24 06:08:42'),
(8, 'Ratnachand Kancharla', 'Male', 20, 'Vijayawada', NULL, NULL, '2025-07-24 15:21:40'),
(9, 'Ratnachand Kancharla', 'Male', 20, 'Vijayawada', NULL, NULL, '2025-07-24 15:25:17'),
(10, 'Lahari', 'Female', 12, 'MBNR', NULL, NULL, '2025-07-24 15:29:07'),
(11, 'Rani', 'Female', 50, 'Hyderabad', NULL, NULL, '2025-07-24 16:54:40'),
(12, 'Rani', 'Female', 100, 'hyderabad', NULL, NULL, '2025-07-24 17:22:23'),
(13, 'Ratnachand Kancharla', 'Male', 25, 'Vijayawada', NULL, NULL, '2025-07-25 05:00:56'),
(14, 'rat', 'Male', 12, 'asdfghj', NULL, NULL, '2025-07-25 05:02:07'),
(15, 'Lahari', 'Female', 20, 'MBNR', NULL, NULL, '2025-07-25 05:07:42'),
(16, 'Lahari', 'Female', 20, 'wertyu', NULL, NULL, '2025-07-25 05:10:27'),
(17, 'Lahari', 'Female', 20, 'MBNR', NULL, NULL, '2025-07-25 11:45:21'),
(18, 'sai Krishna', 'Female', 21, 'MBNR', NULL, NULL, '2025-07-25 11:49:36'),
(19, 'Rishi', 'Male', 10, 'JDC', NULL, NULL, '2025-07-25 12:20:39'),
(20, 'krishna', 'Male', 50, 'mbnr', NULL, NULL, '2025-07-26 04:48:41'),
(21, 'Lahari', 'Female', 35, 'New town', NULL, NULL, '2025-07-26 05:24:33'),
(22, 'ratnachand', 'Male', 50, 'mbnr', NULL, NULL, '2025-07-26 08:38:58'),
(23, 'Sai Krishna', 'Male', 20, 'Mbnr', NULL, NULL, '2025-07-26 15:33:23'),
(24, 'Ratan Chaand', 'Male', 25, 'Gudiwada', NULL, NULL, '2025-07-26 16:29:53'),
(25, 'Sai Krishna', 'Male', 50, '.....................', NULL, NULL, '2025-07-26 16:57:44'),
(26, 'Chand', 'Male', 30, 'Vijaywada', NULL, NULL, '2025-07-27 01:57:44'),
(27, 'Sai Krishna', 'Male', 22, 'yfjyyhv', NULL, NULL, '2025-07-27 02:22:21'),
(28, 'Chaaand', 'Male', 23, 'Dubai', NULL, NULL, '2025-07-27 17:31:29'),
(29, 'xyz', 'Male', 23, 'mbnr', NULL, NULL, '2025-08-18 06:11:26'),
(30, 'Lahari', 'Male', 19, '\\\';kb', NULL, NULL, '2025-08-19 05:18:51'),
(31, 'abc', 'Male', 20, 'fghjk', NULL, NULL, '2025-08-19 05:19:12'),
(32, 'abc', 'Male', 23, 'sdfghj', NULL, NULL, '2025-08-19 05:28:41'),
(33, 'kisna', 'Male', 25, 'sdfghj', NULL, NULL, '2025-08-19 05:28:59'),
(34, 'priya', 'Male', 23, 'dfgh', NULL, NULL, '2025-08-19 05:29:43'),
(35, 'ram', 'Male', 23, ';lkjv', NULL, NULL, '2025-08-19 05:30:06'),
(36, 'gv khbghv', 'Male', 65, 'ytcfj', NULL, NULL, '2025-09-19 08:21:57'),
(37, 'sahil', 'Male', 25, 'guntur', NULL, NULL, '2025-09-19 11:01:23'),
(38, 'qwert', 'Male', 54, 'tfgyhujik', NULL, NULL, '2025-09-20 02:48:07'),
(39, 'varsha', 'Female', 20, 'mbnr', NULL, NULL, '2025-09-20 02:55:00'),
(40, 'ratnnaaa', 'Male', 35, 'kjihugy', NULL, NULL, '2025-09-20 04:40:45'),
(41, 'Sai Krishna', 'Male', 12, 'jin', 'biujouojb', '9876543210', '2025-09-20 05:41:18'),
(42, 'gv khbghv', 'Male', 15, 'wdwd', 'wdddd', '9063992932', '2025-09-20 08:56:51'),
(43, 'Sai Krishna', 'Male', 15, 'yfgu', NULL, NULL, '2025-09-20 09:26:50'),
(44, 'Ratan Chaand', 'Male', 15, 'sdfghjkl', NULL, NULL, '2025-09-20 09:38:54'),
(45, 'gcfhg', 'Male', 23, 'thedrtfygh', NULL, NULL, '2025-09-20 11:15:07'),
(46, 'moti', 'Male', 100, 'erragadda', NULL, NULL, '2025-09-20 11:27:30'),
(47, 'koteshwarulu', 'Male', 45, 'Matchlipatnam', NULL, NULL, '2025-09-20 11:37:04'),
(48, 'venkat', 'Male', 85, 'Avanigadda', NULL, NULL, '2025-09-20 11:38:53'),
(49, 'koteshwarulu', 'Male', 25, 'vijayawada', NULL, NULL, '2025-09-21 04:33:59'),
(50, 'Rishikesh', 'Male', 23, 'mjgtlwriaekods,ckgm', NULL, NULL, '2025-09-21 06:10:47'),
(51, 'Rishikesh', 'Male', 23, 'mjgtlwriaekods,ckgm', NULL, NULL, '2025-09-21 06:11:03'),
(56, 'sdfhgyjhkjlkl', 'Male', 24, 'dyfgykhjlk;l\'lkbvjch', NULL, NULL, '2025-09-21 06:42:46'),
(57, 'gjhkj', 'Male', 34, 'ryghjk', NULL, NULL, '2025-09-21 06:43:26'),
(58, 'gjhkj', 'Male', 34, 'ryghjk', NULL, NULL, '2025-09-21 06:46:49'),
(59, 'sdfgf`', 'Male', 23, 'fgbnghgdfds', NULL, NULL, '2025-09-21 06:51:05'),
(60, 'qwty', 'Male', 34, 'redtfgyhuj', 'Mbnr', '9392123577', '2025-09-21 10:50:22'),
(61, 'jejnf', 'Male', 454, '', '', '', '2025-09-21 11:15:45'),
(62, 'dhodiuhf', 'Male', 41, '', '', '', '2025-09-21 11:16:05'),
(63, 'Sai krishna', 'Male', 20, 'Mahabubnagar,Telangana,India\r\nSri Ayyappa Nivas ,Chaitnya School road', 'Mahabubnagar', '07416144064', '2025-09-21 11:23:22'),
(64, 'ratnachand', 'Male', 12, 'Mahabubnagar,Telangana,India\r\nSri Ayyappa Nivas ,Chaitnya School road', 'Mahabubnagar', '07416144064', '2025-09-23 04:32:08'),
(65, ',kufhjhg', 'Male', 34, '', '', '', '2025-10-02 15:26:53'),
(66, 'b vbkh', 'Male', 22, '', '', '', '2025-10-02 15:33:45'),
(67, 'Salman Khan`', 'Male', 50, 'MUMBAI', '', '', '2025-10-02 15:45:58'),
(69, 'Shahrukh', 'Male', 56, 'ksag', '', '', '2025-10-02 16:20:43'),
(70, 'Salman Khan', 'Male', 55, '', '', '', '2025-10-04 05:55:50'),
(71, 'Yuvraj singh', 'Male', 44, 'Hyd', '', '', '2025-10-15 15:52:59'),
(72, 'Sheri', 'Female', 22, 'Jdcl', '', '', '2025-10-18 12:38:36'),
(73, 'King', 'Male', 34, 'gfghb', '', '', '2025-10-18 12:54:41'),
(74, 'Krish', 'Male', 32, '', '', '', '2025-10-18 13:05:51'),
(75, 'Ratan Chaanduu', 'Male', 35, 'VIJ', '', '', '2025-10-19 15:18:43'),
(76, 'Mahesh Babu', 'Male', 50, '', '', '', '2025-10-19 15:36:09'),
(77, 'krishna reddy', 'Male', 20, 'mbnr', 'mbnr', '01234567890', '2025-10-21 05:31:42'),
(78, 'Rishikesh Talpalikar', 'Male', 45, 'Mahabubnagar,Telangana,India\r\nSri Ayyappa Nivas ,Chaitnya School road', 'Mahabubnagar', '07416144064', '2025-10-21 08:14:30'),
(79, 'Subbu', 'Male', 7, 'Mahabubnagar,Telangana,India', 'Mahabubnagar', '07416144064', '2025-10-21 08:46:17');

-- --------------------------------------------------------

--
-- Table structure for table `payment_history`
--

CREATE TABLE `payment_history` (
  `id` int(11) NOT NULL,
  `bill_id` int(11) NOT NULL,
  `amount_paid_in_txn` decimal(10,2) NOT NULL,
  `previous_amount_paid` decimal(10,2) NOT NULL,
  `new_total_amount_paid` decimal(10,2) NOT NULL,
  `payment_mode` varchar(50) NOT NULL,
  `user_id` int(11) NOT NULL,
  `paid_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `referral_doctors`
--

CREATE TABLE `referral_doctors` (
  `id` int(11) NOT NULL,
  `doctor_name` varchar(100) NOT NULL,
  `hospital_name` varchar(255) DEFAULT NULL,
  `area` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `phone_number` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `referral_doctors`
--

INSERT INTO `referral_doctors` (`id`, `doctor_name`, `hospital_name`, `area`, `city`, `is_active`, `created_at`, `phone_number`, `email`) VALUES
(1, 'rishikesh', '', '', '', 1, '2025-07-23 08:48:33', NULL, NULL),
(2, 'ratna chand', '', '', '', 0, '2025-07-23 08:48:50', NULL, NULL),
(7, 'krishna', '', '', '', 1, '2025-07-24 05:24:18', NULL, NULL),
(8, 'Vedanth', 'Capital', 'Benz circle', 'Vijayawada', 1, '2025-07-25 09:08:44', NULL, NULL),
(9, 'Lahari', NULL, NULL, NULL, 1, '2025-07-27 01:57:44', NULL, NULL),
(10, 'Krishna Reddy', 'NMIMS', 'Polepalle', 'Hyd', 1, '2025-07-27 02:22:21', NULL, NULL),
(11, 'Sai Krishna Reddy', NULL, NULL, NULL, 1, '2025-07-27 17:31:29', NULL, NULL),
(12, 'ashi sharma', 'abc', 'jdcl', 'mbnr', 1, '2025-08-14 05:10:39', '1234567890', 'aashisharma@gmail.com'),
(13, 'Rayyan', 'Rayyan', 'mbnr', 'Hyd', 1, '2025-09-20 02:45:43', '9876543210', 'rayyan@gmail.com'),
(14, 'Rajeev', 'DIvya', '', '', 0, '2025-09-20 11:35:45', '9876543210', ''),
(15, 'Rishi', 'Rishi Children Hospital', 'Hyd', 'Hyd', 1, '2025-10-19 15:33:39', '7416144064', 'rishikesh@gmail.com'),
(16, 'Vinayak', 'Stme', 'jdcl', 'Mahabubnagar', 1, '2025-10-21 08:52:45', '07416144064', 'vinayak@gmail.com');

-- --------------------------------------------------------

--
-- Table structure for table `system_audit_log`
--

CREATE TABLE `system_audit_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `action_type` varchar(100) NOT NULL COMMENT 'e.g., DELETED_BILL, CHANGED_PASSWORD, DELETED_DOCTOR',
  `target_id` int(11) DEFAULT NULL COMMENT 'ID of the affected record, e.g., bill_id',
  `details` text DEFAULT NULL COMMENT 'More context about the action',
  `logged_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_audit_log`
--

INSERT INTO `system_audit_log` (`id`, `user_id`, `username`, `action_type`, `target_id`, `details`, `logged_at`) VALUES
(1, 5, 'superadmin', 'USER_PASSWORD_CHANGED', 1, 'Password changed for user \'manager\' (ID: 1).', '2025-07-30 07:33:25'),
(2, 5, 'superadmin', 'USER_PASSWORD_CHANGED', 1, 'Password changed for user \'manager\' (ID: 1).', '2025-07-30 07:34:22'),
(25, 1, 'manager', '0', 6, 'Manager created new user \'ratnachand\' (ID: 6) with role \'receptionist\'.', '2025-09-20 11:34:32'),
(39, 1, 'manager', '0', 6, 'Manager updated user \'ratnachand\' (ID: 6) with role \'receptionist\' and status Inactive.', '2025-09-21 11:13:35'),
(40, 1, 'manager', '0', 6, 'Manager updated user \'ratnachand\' (ID: 6) with role \'receptionist\' and status Active.', '2025-09-21 11:13:40'),
(41, 1, 'manager', '0', 6, 'Manager updated user \'ratnachand\' (ID: 6) with role \'receptionist\' and status Inactive.', '2025-09-21 11:13:43'),
(44, 2, 'receptionist1', '0', 1, 'Generated bill for patient \'Sai krishna\' with Net Amount: 12500.', '2025-09-21 11:23:22'),
(45, 2, 'receptionist1', '0', 2, 'Generated bill for patient \'ratnachand\' with Net Amount: 520.', '2025-09-23 04:32:08'),
(46, 1, 'manager', '0', 6, 'Manager updated user \'ratnachand\' (ID: 6) with role \'receptionist\' and status Active.', '2025-09-23 04:35:30'),
(47, 2, 'receptionist1', '0', 3, 'Generated bill for patient \',kufhjhg\' with Net Amount: 40577.', '2025-10-02 15:26:53'),
(48, 2, 'receptionist1', '0', 4, 'Generated bill for patient \'b vbkh\' with Net Amount: 41609.', '2025-10-02 15:33:45'),
(49, 2, 'receptionist1', '0', 5, 'Generated bill for patient \'Salman Khan`\' with Net Amount: 43409.', '2025-10-02 15:45:58'),
(50, 2, 'receptionist1', '0', 7, 'Generated bill for patient \'Shahrukh\' with Net Amount: 11112.', '2025-10-02 16:20:43'),
(51, 2, 'receptionist1', '0', 8, 'Generated bill for patient \'Salman Khan\' with Net Amount: 50455.', '2025-10-04 05:55:55'),
(52, 2, 'receptionist1', '0', 9, 'Generated bill for patient \'Yuvraj singh\' with Net Amount: 41232.', '2025-10-15 15:53:03'),
(53, 2, 'receptionist1', '0', 9, 'Payment updated for Bill #9. New status: Half Paid.', '2025-10-15 16:39:02'),
(54, 2, 'receptionist1', '0', 9, 'Payment updated for Bill #9. New status: Paid.', '2025-10-15 16:39:37'),
(55, 2, 'receptionist1', '0', 10, 'Generated bill for patient \'Sheri\' with Net Amount: 4095.', '2025-10-18 12:38:41'),
(56, 2, 'receptionist1', '0', 10, 'Payment updated for Bill #10. New status: Half Paid.', '2025-10-18 12:40:00'),
(57, 2, 'receptionist1', '0', 10, 'Payment updated for Bill #10. New status: Half Paid.', '2025-10-18 12:40:31'),
(58, 2, 'receptionist1', '0', 10, 'Payment updated for Bill #10. New status: Paid.', '2025-10-18 12:51:31'),
(59, 2, 'receptionist1', '0', 11, 'Generated bill for patient \'King\' with Net Amount: 48260.', '2025-10-18 12:54:42'),
(60, 2, 'receptionist1', '0', 12, 'Generated bill for patient \'Krish\' with Net Amount: 42899.', '2025-10-18 13:05:52'),
(61, 2, 'receptionist1', '0', 12, 'Payment updated for Bill #12. New status: Half Paid.', '2025-10-18 13:20:05'),
(62, 1, 'manager', '0', 2, 'Manager (manager) permanently deleted Bill #2.', '2025-10-19 15:01:39'),
(63, 1, 'manager', '0', 1, 'Manager (manager) permanently deleted Bill #1.', '2025-10-19 15:03:39'),
(64, 2, 'receptionist1', '0', 13, 'Generated bill for patient \'Ratan Chaanduu\' with Net Amount: 47732.', '2025-10-19 15:18:47'),
(65, 2, 'receptionist1', '0', 13, 'Payment updated for Bill #13. New status: Half Paid.', '2025-10-19 15:21:25'),
(66, 2, 'receptionist1', '0', 13, 'Payment updated for Bill #13. New status: Paid.', '2025-10-19 15:21:57'),
(67, 2, 'receptionist1', '0', 14, 'Generated bill for patient \'Mahesh Babu\' with Net Amount: 1450.', '2025-10-19 15:36:09'),
(68, 2, 'receptionist1', '0', 11, 'Payment updated for Bill #11. New status: Paid.', '2025-10-19 15:40:35'),
(69, 2, 'receptionist1', '0', 15, 'Generated bill for patient \'krishna reddy\' with Net Amount: 1200.', '2025-10-21 05:31:51'),
(70, 2, 'receptionist1', '0', 16, 'Generated bill for patient \'Rishikesh Talpalikar\' with Net Amount: 12020.', '2025-10-21 08:14:33'),
(71, 2, 'receptionist1', '0', 17, 'Generated bill for patient \'Subbu\' with Net Amount: 54232.', '2025-10-21 08:46:18'),
(72, 1, 'manager', '0', 6, 'Manager updated user \'ratnachand\' (ID: 6) with role \'receptionist\' and status Inactive.', '2025-10-21 08:53:48');

-- --------------------------------------------------------

--
-- Table structure for table `tests`
--

CREATE TABLE `tests` (
  `id` int(11) NOT NULL,
  `main_test_name` varchar(100) NOT NULL,
  `sub_test_name` varchar(100) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `default_payable_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `report_format` longtext DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `document` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tests`
--

INSERT INTO `tests` (`id`, `main_test_name`, `sub_test_name`, `price`, `default_payable_amount`, `report_format`, `created_at`, `document`) VALUES
(6, 'CT', 'CT knee', 500.00, 0.00, NULL, '2025-07-24 15:02:57', 'test_68824b216689f.docx'),
(7, 'MRI', 'MRI knee', 2000.00, 0.00, NULL, '2025-07-24 15:03:19', 'test_68824b3735022.docx'),
(8, 'US', 'US chest', 500.00, 0.00, NULL, '2025-07-24 15:04:01', 'test_68824b612425d.docx'),
(9, 'CT', 'CT intestine', 500.00, 0.00, '', '2025-07-24 15:04:45', 'test_68824b8d0b342.docx'),
(10, 'CT', 'CT endochrime', 2000.00, 0.00, 'hi Hello \r\n\r\n\r\n\r\nHello \r\n\r\nHi', '2025-07-24 17:21:24', NULL),
(11, 'MRI', 'MRI brain', 10000.00, 0.00, 'Hi\r\n\r\nIs your brain working?\r\n\r\n\r\nDo you have your brain with you?', '2025-07-25 05:05:13', NULL),
(12, 'ABC', 'ABC head', 20.00, 0.00, 'ABC', '2025-07-25 05:05:40', NULL),
(13, 'MRI', 'skull', 12000.00, 0.00, 'clot', '2025-07-26 08:43:28', NULL),
(14, 'MRI', 'joints', 10000.00, 0.00, NULL, '2025-09-20 02:43:09', '../uploads/report_templates/template_1758336189_MRI Both hip joints.docx'),
(15, '', 'pelvis', 10000.00, 0.00, NULL, '2025-09-20 11:32:58', ''),
(16, '', 'brain', 3000.00, 0.00, NULL, '2025-09-20 11:33:46', ''),
(17, '', 'vvgh', 5465.00, 0.00, NULL, '2025-10-02 14:06:18', ''),
(18, '', 'vvgh', 5465.00, 0.00, NULL, '2025-10-02 14:20:03', ''),
(19, '', 'vvgh', 5465.00, 0.00, NULL, '2025-10-02 14:20:14', ''),
(20, '', 'feh', 3767.00, 0.00, NULL, '2025-10-02 14:20:56', ''),
(21, '', 'feh', 3767.00, 0.00, NULL, '2025-10-02 14:22:24', ''),
(22, 'MRI', 'dgtth', 46232.00, 0.00, NULL, '2025-10-02 14:42:06', ''),
(23, 'MRI', 'hkheh', 2428.00, 0.00, NULL, '2025-10-02 14:46:09', ''),
(24, 'CT', 'BRAIN', 0.00, 0.00, NULL, '2025-10-18 17:28:56', NULL),
(25, 'CT', 'BRAIN WITH CONTRAST', 0.00, 0.00, NULL, '2025-10-18 17:28:56', NULL),
(26, 'CT', 'PNS', 0.00, 0.00, NULL, '2025-10-18 17:28:56', NULL),
(27, 'CT', 'ORBITS', 0.00, 0.00, NULL, '2025-10-18 17:28:56', NULL),
(28, 'CT', 'FACE', 0.00, 0.00, NULL, '2025-10-18 17:28:56', NULL),
(29, 'CT', 'NECK', 0.00, 0.00, NULL, '2025-10-18 17:28:56', NULL),
(30, 'CT', 'CHEST', 0.00, 0.00, NULL, '2025-10-18 17:28:56', NULL),
(31, 'CT', 'HRCT CHEST', 0.00, 0.00, NULL, '2025-10-18 17:28:56', NULL),
(32, 'CT', 'KUB', 0.00, 0.00, NULL, '2025-10-18 17:28:56', NULL),
(33, 'CT', 'WHOLE ABDOMEN', 0.00, 0.00, NULL, '2025-10-18 17:28:56', NULL),
(34, 'CT', 'PELVIS', 0.00, 0.00, NULL, '2025-10-18 17:28:56', NULL),
(35, 'CT', 'CERVICAL SPINE', 0.00, 0.00, NULL, '2025-10-18 17:28:56', NULL),
(36, 'CT', 'DORSAL SPINE', 0.00, 0.00, NULL, '2025-10-18 17:28:56', NULL),
(37, 'CT', 'LUMBAR SPINE', 0.00, 0.00, NULL, '2025-10-18 17:28:56', NULL),
(38, 'MRI', 'BRAIN', 0.00, 0.00, NULL, '2025-10-18 17:28:56', NULL),
(39, 'MRI', 'BRAIN WITH CONTRAST', 0.00, 0.00, NULL, '2025-10-18 17:28:56', NULL),
(40, 'MRI', 'ORBITS', 0.00, 0.00, NULL, '2025-10-18 17:28:56', NULL),
(41, 'MRI', 'CERVICAL SPINE', 0.00, 0.00, NULL, '2025-10-18 17:28:56', NULL),
(42, 'MRI', 'DORSAL SPINE', 0.00, 0.00, NULL, '2025-10-18 17:28:56', NULL),
(43, 'MRI', 'LUMBAR SPINE', 0.00, 0.00, NULL, '2025-10-18 17:28:56', NULL),
(44, 'MRI', 'KNEE JOINT', 0.00, 0.00, NULL, '2025-10-18 17:28:56', NULL),
(45, 'MRI', 'ANKLE JOINT', 0.00, 0.00, NULL, '2025-10-18 17:28:56', NULL),
(46, 'MRI', 'SHOULDER JOINT', 0.00, 0.00, NULL, '2025-10-18 17:28:56', NULL),
(47, 'MRI', 'HIP JOINT', 0.00, 0.00, NULL, '2025-10-18 17:28:56', NULL),
(48, 'MRI', 'WRIST JOINT', 0.00, 0.00, NULL, '2025-10-18 17:28:56', NULL),
(49, 'USG', 'WHOLE ABDOMEN', 0.00, 0.00, NULL, '2025-10-18 17:28:56', NULL),
(50, 'USG', 'KUB', 0.00, 0.00, NULL, '2025-10-18 17:28:56', NULL),
(51, 'USG', 'PELVIS', 0.00, 0.00, NULL, '2025-10-18 17:28:56', NULL),
(52, 'USG', 'OBSTETRICS', 0.00, 0.00, NULL, '2025-10-18 17:28:56', NULL),
(53, 'USG', 'NT SCAN', 0.00, 0.00, NULL, '2025-10-18 17:28:56', NULL),
(54, 'USG', 'ANOMALY SCAN', 0.00, 0.00, NULL, '2025-10-18 17:28:56', NULL),
(55, 'XRAY', 'CHEST PA VIEW', 0.00, 0.00, NULL, '2025-10-18 17:28:56', NULL),
(56, 'XRAY', 'KUB', 0.00, 0.00, NULL, '2025-10-18 17:28:56', NULL),
(57, 'XRAY', 'CERVICAL SPINE AP/LAT', 0.00, 0.00, NULL, '2025-10-18 17:28:56', NULL),
(58, 'XRAY', 'LUMBAR SPINE AP/LAT', 0.00, 0.00, NULL, '2025-10-18 17:28:56', NULL),
(59, 'COLOUR DOPPLER', 'RENAL DOPPLER', 0.00, 0.00, NULL, '2025-10-18 17:28:56', NULL),
(60, 'COLOUR DOPPLER', 'CAROTID DOPPLER', 0.00, 0.00, NULL, '2025-10-18 17:28:56', NULL),
(61, 'OPG', 'OPG', 0.00, 0.00, NULL, '2025-10-18 17:28:56', NULL),
(62, 'MAMMOGRAPHY', 'MAMMOGRAPHY', 0.00, 0.00, NULL, '2025-10-18 17:28:56', NULL),
(63, 'ECG', 'ECG', 0.00, 0.00, NULL, '2025-10-18 17:28:56', NULL),
(64, 'ECHO', 'ECHO', 0.00, 0.00, NULL, '2025-10-18 17:28:56', NULL),
(65, 'TMT', 'TMT', 0.00, 0.00, NULL, '2025-10-18 17:28:56', NULL),
(66, 'PFT', 'PFT', 0.00, 0.00, NULL, '2025-10-18 17:28:56', NULL),
(67, 'EEG', 'EEG', 0.00, 0.00, NULL, '2025-10-18 17:28:56', NULL),
(68, 'NCV', 'NCV', 0.00, 0.00, NULL, '2025-10-18 17:28:56', NULL),
(69, 'BERA', 'BERA', 0.00, 0.00, NULL, '2025-10-18 17:28:56', NULL),
(70, 'VEP', 'VEP', 0.00, 0.00, NULL, '2025-10-18 17:28:56', NULL),
(71, 'FNAC', 'FNAC', 0.00, 0.00, NULL, '2025-10-18 17:28:56', NULL),
(72, 'BIOPSY', 'BIOPSY', 0.00, 0.00, NULL, '2025-10-18 17:28:56', NULL),
(73, 'INTERVENTION', 'INTERVENTION', 0.00, 0.00, NULL, '2025-10-18 17:28:56', NULL),
(74, 'MRI', 'FINGER', 1500.00, 150.00, NULL, '2025-10-19 15:34:27', 'doc_68f5050342dd68.83447552.pdf'),
(75, 'ABC', 'eye', 1200.00, 200.00, NULL, '2025-10-21 05:28:54', NULL),
(76, 'MRI', 'Toe', 2500.00, 100.00, NULL, '2025-10-21 08:51:10', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('manager','receptionist','accountant','writer','superadmin') NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `is_active`, `created_at`) VALUES
(1, 'manager', '$2y$10$X74s2IIUXL4VQ39I7Xs7keAc6GmW3mbRSzYmjRAUhYsOrOM6Zs0iS', 'manager', 1, '2025-07-23 08:38:01'),
(2, 'receptionist1', '$2y$10$imdgLSoM6DCLJEQeN6BdPO7jsjQpdgd.sfzqjnYW.ytcCd12yGlGy', 'receptionist', 1, '2025-07-23 08:38:01'),
(3, 'accountant', '$2y$10$c7ZLQP442jvm/KN6..eLgOheG0mWLeiLNB820vxU8o0fIAzT8TN1S', 'accountant', 1, '2025-07-23 08:38:01'),
(4, 'writer', '$2y$10$Xa4Heg3pDf61hGV4hq5YUOFJOc3.k/.qc5n3FbcNSpcMjdsnTZSCS', 'writer', 1, '2025-07-23 08:38:02'),
(5, 'superadmin', '$2y$10$imdgLSoM6DCLJEQeN6BdPO7jsjQpdgd.sfzqjnYW.ytcCd12yGlGy', 'superadmin', 1, '2025-07-29 05:13:17'),
(6, 'ratnachand', '$2y$10$SrFvcCTMbYmlvUaDJjLTF.3ncsYCU57hjwQa8/NNpx/zfyB1cn.K2', 'receptionist', 0, '2025-09-20 11:34:32');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bills`
--
ALTER TABLE `bills`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_number` (`invoice_number`);

--
-- Indexes for table `bill_edit_log`
--
ALTER TABLE `bill_edit_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bill_edit_requests`
--
ALTER TABLE `bill_edit_requests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bill_items`
--
ALTER TABLE `bill_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `calendar_events`
--
ALTER TABLE `calendar_events`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `doctor_payout_history`
--
ALTER TABLE `doctor_payout_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `doctor_id` (`doctor_id`),
  ADD KEY `accountant_id` (`accountant_id`);

--
-- Indexes for table `doctor_test_payables`
--
ALTER TABLE `doctor_test_payables`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_doctor_test` (`doctor_id`,`test_id`),
  ADD KEY `test_id` (`test_id`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payment_history`
--
ALTER TABLE `payment_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bill_id` (`bill_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `referral_doctors`
--
ALTER TABLE `referral_doctors`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `system_audit_log`
--
ALTER TABLE `system_audit_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tests`
--
ALTER TABLE `tests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bills`
--
ALTER TABLE `bills`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `bill_edit_log`
--
ALTER TABLE `bill_edit_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bill_edit_requests`
--
ALTER TABLE `bill_edit_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `bill_items`
--
ALTER TABLE `bill_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `calendar_events`
--
ALTER TABLE `calendar_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `doctor_payout_history`
--
ALTER TABLE `doctor_payout_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `doctor_test_payables`
--
ALTER TABLE `doctor_test_payables`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=80;

--
-- AUTO_INCREMENT for table `payment_history`
--
ALTER TABLE `payment_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `referral_doctors`
--
ALTER TABLE `referral_doctors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `system_audit_log`
--
ALTER TABLE `system_audit_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT for table `tests`
--
ALTER TABLE `tests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `doctor_test_payables`
--
ALTER TABLE `doctor_test_payables`
  ADD CONSTRAINT `doctor_test_payables_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `referral_doctors` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `doctor_test_payables_ibfk_2` FOREIGN KEY (`test_id`) REFERENCES `tests` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
