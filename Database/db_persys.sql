-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 23, 2026 at 11:15 AM
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
-- Database: `db_persys`
--

-- --------------------------------------------------------

--
-- Table structure for table `accounts`
--

CREATE TABLE `accounts` (
  `account_id` int(10) UNSIGNED NOT NULL,
  `employee_id` int(10) UNSIGNED NOT NULL,
  `role_id` int(10) UNSIGNED NOT NULL,
  `username` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `accounts`
--

INSERT INTO `accounts` (`account_id`, `employee_id`, `role_id`, `username`, `password_hash`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'juandelacruz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, '2026-09-28 17:06:48', '2026-09-28 17:06:48'),
(2, 2, 2, 'khyranzarsuela', '$2a$12$bV4ZVxTr7XB6FJDF4EFWw./3NR90IJQ7aL9FXujgzXOxCY1GODSDm', 1, '2026-09-28 17:37:59', '2026-09-28 17:37:59'),
(3, 3, 3, 'mariaclara', '$2a$12$oKFzyWwREF/1gKpIndBRq.e50PP.walk7wWIMG4wwjgaRltY89Njq', 1, '2026-09-29 09:27:55', '2026-09-29 09:27:55'),
(15, 6, 2, 'JOSErizall', '$2y$10$iEqinwlTX1VlLTbzlPpgreeabPPC.qz8R9.lNhYrAG7bdeq7XZbqm', 1, '2026-10-06 14:44:12', '2026-10-06 14:44:12'),
(16, 4, 2, 'dfgfj', '$2y$10$UGES5LSUBNIgUCNbgUiKleSz0mdbp4fc8oUhsakRpM5DoIZr9JWlG', 1, '2026-10-06 14:47:08', '2026-10-06 14:47:08'),
(19, 1, 3, 'juanuser', '$2a$12$CfxUQ.6028dv40DoMbZ4bumrJHiuS.UhrJwx4dMyJhHmrXCXdjkci', 1, '2026-10-07 22:58:00', '2026-10-07 23:29:06'),
(20, 8, 2, 'akoto', '$2y$10$Pxsyv05xa6z/EDxekS2yxOF9vwi7cSiaxqoe0oSMrYmYYkLFGaFRO', 1, '2026-10-10 09:54:42', '2026-10-10 09:54:42'),
(21, 9, 2, 'basta', '$2y$10$ueo8P9VOWFju2r1n2aEjPeRdu9P7EF/n7lT1mmeLjgqnPfkAEdsvC', 1, '2026-10-10 09:55:14', '2026-10-10 09:55:14'),
(22, 10, 3, 'tommy', '$2y$10$ZPfxfXfeFrN3ZXNueaKiVO7MS.aay2BHX3bGuOVSsSaqMQXP61VLO', 1, '2026-10-16 10:24:36', '2026-10-16 10:24:36');

-- --------------------------------------------------------

--
-- Table structure for table `account_settings`
--

CREATE TABLE `account_settings` (
  `account_id` int(10) UNSIGNED NOT NULL,
  `font_size` varchar(20) NOT NULL DEFAULT '16px',
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `text_spacing` decimal(4,2) NOT NULL DEFAULT 0.00,
  `dark_mode` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `account_settings`
--

INSERT INTO `account_settings` (`account_id`, `font_size`, `updated_at`, `text_spacing`, `dark_mode`) VALUES
(1, '16px', '2026-10-17 13:13:08', 0.00, 0),
(19, '20px', '2026-10-16 15:21:09', 0.00, 0);

-- --------------------------------------------------------

--
-- Table structure for table `credit_upload_batches`
--

CREATE TABLE `credit_upload_batches` (
  `upload_batch_id` int(10) UNSIGNED NOT NULL,
  `uploaded_by` int(10) UNSIGNED NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `status` enum('UPLOADED','POSTED','CANCELLED') NOT NULL DEFAULT 'UPLOADED',
  `uploaded_at` datetime NOT NULL DEFAULT current_timestamp(),
  `posted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `credit_upload_batches`
--

INSERT INTO `credit_upload_batches` (`upload_batch_id`, `uploaded_by`, `file_name`, `file_path`, `status`, `uploaded_at`, `posted_at`) VALUES
(8, 1, 'service_credits.xlsx', NULL, 'POSTED', '2026-10-08 22:21:15', '2026-10-08 22:21:15'),
(9, 1, 'service_credits.xlsx', NULL, 'POSTED', '2026-10-08 22:41:48', '2026-10-08 22:41:48'),
(10, 1, 'service_credits.xlsx', NULL, 'POSTED', '2026-10-08 22:49:50', '2026-10-08 22:49:50'),
(11, 1, 'service_credits.xlsx', NULL, 'POSTED', '2026-10-10 09:45:44', '2026-10-10 09:45:44'),
(12, 1, 'service_credits.xlsx', NULL, 'POSTED', '2026-10-10 15:37:31', '2026-10-10 15:37:31'),
(13, 1, 'service_credits.xlsx', NULL, 'POSTED', '2026-10-16 14:24:10', '2026-10-16 14:24:10'),
(14, 1, 'service_credits.xlsx', NULL, 'POSTED', '2026-10-16 15:05:12', '2026-10-16 15:05:12');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `employee_id` int(10) UNSIGNED NOT NULL,
  `employee_number` varchar(50) NOT NULL,
  `personnel_type` enum('Teaching','Non-Teaching') NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `plantilla_item_number` varchar(100) DEFAULT NULL,
  `position` varchar(150) NOT NULL,
  `salary_grade` tinyint(3) UNSIGNED DEFAULT NULL,
  `step_increment` tinyint(3) UNSIGNED DEFAULT NULL,
  `date_original_appointment` date DEFAULT NULL,
  `date_last_promotion` date DEFAULT NULL,
  `deped_email` varchar(150) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`employee_id`, `employee_number`, `personnel_type`, `last_name`, `first_name`, `middle_name`, `plantilla_item_number`, `position`, `salary_grade`, `step_increment`, `date_original_appointment`, `date_last_promotion`, `deped_email`, `created_at`, `updated_at`) VALUES
(1, 'EMP-2026-0001', 'Teaching', 'Dela Cruz', 'Juan', 'Santos', 'OSEC-DECSB-TCH1-510001-2024', 'Admin', 11, 1, '2024-06-15', NULL, 'juan.delacruz001@deped.gov.ph', '2026-09-28 17:02:31', '2026-09-28 17:36:16'),
(2, 'EMP-2026-8941', 'Teaching', 'Zarsuela', 'Khyran', 'Alvarez', 'OSEC-DECSB-T9292339', 'Teacher I', 11, 1, '2021-06-15', '2024-09-01', 'khyranzarsuela@deped.gov.ph', '2026-09-28 17:35:59', '2026-09-28 17:35:59'),
(3, 'EMP-2026-0891', 'Non-Teaching', 'Santos', 'Maria Clara', 'De la Cruz', 'OSEC-DECSB-TCH3-123456-2021', 'Teacher III', 13, 2, '2021-06-15', '2024-09-01', 'maria.santos003@deped.gov.ph', '2026-09-29 09:23:41', '2026-09-29 09:23:41'),
(4, 'EMP-2026-0193', 'Teaching', 'Bonifacio', 'Andres', 'Jose', 'OSEC-DECSB-T039034', 'Teacher I', 11, 1, '2020-06-15', '2024-06-15', 'andresbonifacio@deped.gov.ph', '2026-10-02 10:03:23', '2026-10-02 10:03:23'),
(5, 'EMP-TEST-0931', 'Non-Teaching', 'Apolinario', 'Mabini', 'Cruz', 'OSEC-DECSB-T9129239', 'Teacher II', 12, 2, '2019-08-10', '2023-08-10', 'apolinariomabini@deped.gov.ph', '2026-10-02 10:03:23', '2026-10-02 10:03:23'),
(6, 'EMP-TEST-001', 'Teaching', 'Rizal', 'Jose', 'Andres', '`EMP-2026-1022', 'Teacher I', 11, 1, '2020-06-15', NULL, 'jose.j@deped.gov.pph', '2026-10-06 14:36:10', '2026-10-06 14:36:10'),
(7, 'EMP-TEST-002', 'Teaching', 'Luna', 'Juan', 'bayani', 'EMP-2026-9565', 'Teacher II', 12, 2, '2019-08-10', '2021-08-10', 'juan.luna@deped.gov.ph', '2026-10-06 14:36:10', '2026-10-06 14:36:10'),
(8, 'EMP-TEST-003', 'Teaching', 'Limuco', 'James', 'pikachu', '`EMP-2026-2027', 'Teacher III', 13, 3, '2019-06-15', NULL, 'james.L@deped.gov.pph', '2026-10-10 09:53:29', '2026-10-10 09:53:29'),
(9, 'EMP-TEST-004', 'Non-Teaching', 'Tolarba', 'Ash', 'Tore', 'EMP-2026-9567', 'Teacher  V', 15, 4, '2020-08-10', '2021-08-10', 'ash.tore@deped.gov.ph', '2026-10-10 09:53:29', '2026-10-10 09:53:29'),
(10, 'EMP-03304-9292', 'Non-Teaching', 'Shields', 'Tommy', 'Analia', '02203-13949-1932', 'TEACHER II', 2, 1, '2026-10-01', NULL, 'tommyshields@deped.com.ph', '2026-10-16 10:16:40', '2026-10-16 10:16:40'),
(11, 'EMP-4647844', 'Teaching', 'Santos', 'Juan', 'Gonzales', 'OSEC-DECSB-TCH1-59284-2020', 'Teacher III', 13, 3, '2019-06-15', '2022-07-19', 'juang.santos@deped.gov.ph', '2026-10-16 10:22:46', '2026-10-16 10:22:46'),
(12, 'EMP- 5123987', 'Teaching', 'Aquino', 'Mark Anthony', 'Roxas', 'OSEC-DECSB-MTCH1-30491-2019', 'Master Teacher I', 18, 2, '2012-11-05', '2023-05-20', 'markanthony.aquino@deped.gov.ph', '2026-10-16 10:22:46', '2026-10-16 10:22:46'),
(13, 'EMP-4647855', 'Teaching', 'Alcantara', 'John', 'Gonzales', 'OSEC-DECSB-TCH1-59284-2020', 'Teacher III', 13, 3, '2019-06-15', '2022-07-19', 'john.alcantara@deped.gov.ph', '2026-10-16 14:28:24', '2026-10-16 14:28:24'),
(14, 'EMP- 5123975', 'Non-Teaching', 'Aquino', 'Angela', 'Roxas', 'OSEC-DECSB-MTCH1-30491-2019', 'ADMIN OFFICER I', 18, 2, '2012-11-05', '2023-05-20', 'angel.aquino@deped.gov.ph', '2026-10-16 14:28:24', '2026-10-16 14:28:24');

-- --------------------------------------------------------

--
-- Table structure for table `leave_requests`
--

CREATE TABLE `leave_requests` (
  `leave_request_id` int(10) UNSIGNED NOT NULL,
  `request_number` varchar(50) NOT NULL,
  `employee_id` int(10) UNSIGNED NOT NULL,
  `leave_type_id` smallint(5) UNSIGNED NOT NULL,
  `credit_type_id` tinyint(3) UNSIGNED DEFAULT NULL,
  `date_filed` date NOT NULL,
  `reason_details` text DEFAULT NULL,
  `start_datetime` datetime NOT NULL,
  `end_datetime` datetime NOT NULL,
  `total_leave_days` decimal(5,2) NOT NULL,
  `status` enum('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  `document_path` varchar(500) DEFAULT NULL,
  `reviewed_by` int(10) UNSIGNED DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Dumping data for table `leave_requests`
--

INSERT INTO `leave_requests` (`leave_request_id`, `request_number`, `employee_id`, `leave_type_id`, `credit_type_id`, `date_filed`, `reason_details`, `start_datetime`, `end_datetime`, `total_leave_days`, `status`, `document_path`, `reviewed_by`, `reviewed_at`, `created_at`, `updated_at`) VALUES
(1, 'LR-2026-0001', 1, 1, NULL, '2026-10-15', '1', '2026-10-01 08:00:00', '2026-10-03 17:00:00', 2.00, 'Pending', NULL, NULL, NULL, '2026-10-15 22:38:49', '2026-10-15 22:38:49'),
(2, 'LR-2026-0002', 1, 2, NULL, '2026-10-15', '2', '2026-10-01 08:00:00', '2026-10-08 17:00:00', 6.00, 'Pending', NULL, NULL, NULL, '2026-10-15 22:39:03', '2026-10-15 22:39:03'),
(3, 'LR-2026-0003', 1, 3, NULL, '2026-10-15', '3', '2026-10-09 08:00:00', '2026-10-10 17:00:00', 1.00, 'Pending', NULL, NULL, NULL, '2026-10-15 22:39:21', '2026-10-15 22:39:21'),
(4, 'LR-2026-0004', 1, 4, NULL, '2026-10-15', '4', '2026-10-01 08:00:00', '2026-10-11 17:00:00', 7.00, 'Pending', NULL, NULL, NULL, '2026-10-15 22:40:01', '2026-10-15 22:40:01'),
(5, 'LR-2026-0005', 1, 5, NULL, '2026-10-15', '5', '2026-10-04 08:00:00', '2026-10-09 17:00:00', 5.00, 'Pending', NULL, NULL, NULL, '2026-10-15 22:40:53', '2026-10-15 22:40:53'),
(6, 'LR-2026-0006', 1, 6, NULL, '2026-10-15', '6', '2026-10-01 08:00:00', '2026-10-02 17:00:00', 2.00, 'Pending', NULL, NULL, NULL, '2026-10-15 22:41:49', '2026-10-15 22:41:49'),
(7, 'LR-2026-0007', 1, 7, NULL, '2026-10-15', '7', '2026-10-05 08:00:00', '2026-10-08 17:00:00', 4.00, 'Pending', NULL, NULL, NULL, '2026-10-15 22:42:10', '2026-10-15 22:42:10'),
(8, 'LR-2026-0008', 1, 8, NULL, '2026-10-15', '8', '2026-10-01 08:00:00', '2026-10-03 17:00:00', 2.00, 'Pending', NULL, NULL, NULL, '2026-10-15 22:42:35', '2026-10-15 22:42:35'),
(9, 'LR-2026-0009', 1, 9, NULL, '2026-10-15', '9', '2026-10-08 08:00:00', '2026-10-10 17:00:00', 2.00, 'Pending', NULL, NULL, NULL, '2026-10-15 22:42:58', '2026-10-15 22:42:58'),
(10, 'LR-2026-0010', 1, 10, NULL, '2026-10-15', '10', '2026-10-01 08:00:00', '2026-10-14 17:00:00', 10.00, 'Pending', NULL, NULL, NULL, '2026-10-15 22:43:24', '2026-10-15 22:43:24'),
(11, 'LR-2026-0011', 1, 11, NULL, '2026-10-15', '11', '2026-10-01 08:00:00', '2026-10-03 17:00:00', 2.00, 'Pending', NULL, NULL, NULL, '2026-10-15 22:43:52', '2026-10-15 22:43:52'),
(12, 'LR-2026-0012', 1, 12, NULL, '2026-10-15', '12', '2026-10-01 08:00:00', '2026-10-11 17:00:00', 7.00, 'Pending', NULL, NULL, NULL, '2026-10-15 22:44:30', '2026-10-15 22:44:30'),
(13, 'LR-2026-0013', 1, 13, 2, '2026-10-15', '13', '2026-10-01 08:00:00', '2026-10-05 17:00:00', 3.00, 'Approved', NULL, 1, '2026-10-15 23:41:01', '2026-10-15 22:44:48', '2026-10-15 23:41:01'),
(14, 'LR-2026-0014', 1, 14, NULL, '2026-10-15', '14', '2026-09-27 08:00:00', '2026-10-03 17:00:00', 5.00, 'Rejected', NULL, 1, '2026-10-16 00:55:57', '2026-10-15 22:45:04', '2026-10-16 00:55:57'),
(15, 'LR-2026-0015', 1, 1, NULL, '2026-10-15', '20', '2026-09-27 08:00:00', '2026-10-03 17:00:00', 5.00, 'Rejected', NULL, 1, '2026-10-15 23:39:43', '2026-10-15 23:00:39', '2026-10-15 23:39:43'),
(16, 'LR-2026-0016', 1, 5, 1, '2026-10-16', 'okay', '2026-10-01 08:00:00', '2026-10-10 17:00:00', 7.00, 'Approved', NULL, 1, '2026-10-16 00:55:32', '2026-10-16 00:54:03', '2026-10-16 00:55:32'),
(17, 'LR-2026-0017', 1, 1, NULL, '2026-10-16', 'w', '2026-10-01 08:00:00', '2026-10-10 17:00:00', 7.00, 'Pending', NULL, NULL, NULL, '2026-10-16 11:40:38', '2026-10-16 11:40:38'),
(18, 'LR-2026-0018', 1, 14, 1, '2026-10-16', 'ayaw ko pumasok', '2026-10-01 08:00:00', '2026-10-09 17:00:00', 7.00, 'Approved', NULL, 1, '2026-10-16 14:14:27', '2026-10-16 14:12:23', '2026-10-16 14:14:27'),
(19, 'LR-2026-0019', 9, 5, NULL, '2026-10-16', 'ZERO BALANCE', '2026-10-01 08:00:00', '2026-10-10 17:00:00', 7.00, 'Pending', NULL, NULL, NULL, '2026-10-16 14:34:51', '2026-10-16 14:34:51'),
(20, 'LR-2026-0020', 1, 1, NULL, '2026-10-16', 'hahahaha', '2026-10-16 08:00:00', '2026-10-26 17:00:00', 7.00, 'Pending', NULL, NULL, NULL, '2026-10-16 15:25:06', '2026-10-16 15:25:06');

-- --------------------------------------------------------

--
-- Table structure for table `leave_types`
--

CREATE TABLE `leave_types` (
  `leave_type_id` smallint(5) UNSIGNED NOT NULL,
  `leave_type_name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `leave_types`
--

INSERT INTO `leave_types` (`leave_type_id`, `leave_type_name`, `description`, `is_active`) VALUES
(1, 'Vacation Leave', NULL, 1),
(2, 'Mandatory/Forced Leave', NULL, 1),
(3, 'Sick Leave', NULL, 1),
(4, 'Maternity Leave', NULL, 1),
(5, 'Paternity Leave', NULL, 1),
(6, 'Special Privilege Leave', NULL, 1),
(7, 'Solo Parent Leave', NULL, 1),
(8, 'Study Leave', NULL, 1),
(9, '10-Day VAWC Leave', NULL, 1),
(10, 'Rehabilitation Privilege Leave', NULL, 1),
(11, 'Special Leave Benefits for Women', NULL, 1),
(12, 'Special Emergency (Calamity) Leave', NULL, 1),
(13, 'Adoption Leave', NULL, 1),
(14, 'Others', NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `role_id` int(10) UNSIGNED NOT NULL,
  `role_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`role_id`, `role_name`) VALUES
(1, 'Admin'),
(3, 'Non-Teaching Personnel'),
(2, 'Teaching Personnel');

-- --------------------------------------------------------

--
-- Table structure for table `service_credit_transactions`
--

CREATE TABLE `service_credit_transactions` (
  `transaction_id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` int(10) UNSIGNED NOT NULL,
  `credit_type_id` tinyint(3) UNSIGNED NOT NULL,
  `upload_batch_id` int(10) UNSIGNED DEFAULT NULL,
  `leave_request_id` int(10) UNSIGNED DEFAULT NULL,
  `activity_name` varchar(255) NOT NULL,
  `activity_start_date` date DEFAULT NULL,
  `activity_end_date` date DEFAULT NULL,
  `credit_amount` decimal(8,2) NOT NULL,
  `transaction_type` enum('EARNED','DEDUCTED') NOT NULL,
  `created_by` int(10) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ;

--
-- Dumping data for table `service_credit_transactions`
--

INSERT INTO `service_credit_transactions` (`transaction_id`, `employee_id`, `credit_type_id`, `upload_batch_id`, `leave_request_id`, `activity_name`, `activity_start_date`, `activity_end_date`, `credit_amount`, `transaction_type`, `created_by`, `created_at`) VALUES
(1, 2, 2, 10, NULL, 'ASTRO CAMP', '2026-09-25', '2026-09-26', 10.00, 'EARNED', 1, '2026-10-08 22:49:50'),
(2, 1, 1, 10, NULL, 'ASTRO CAMP', '2026-09-25', '2026-09-26', 30.00, 'EARNED', 1, '2026-10-08 22:49:50'),
(3, 2, 2, NULL, 2, 'Leave deduction - LR-2026-0002', '2026-09-27', '2026-09-30', 3.00, 'DEDUCTED', 1, '2026-10-09 21:27:09'),
(4, 2, 1, 11, NULL, 'ASTRO CAMP', '2026-09-25', '2026-09-26', 30.00, 'EARNED', 1, '2026-10-10 09:45:44'),
(5, 1, 2, 11, NULL, 'ASTRO CAMP', '2026-09-25', '2026-09-26', 50.00, 'EARNED', 1, '2026-10-10 09:45:44'),
(6, 2, 1, 12, NULL, 'ASTRO CAMP', '2026-09-25', '2026-09-26', 30.00, 'EARNED', 1, '2026-10-10 15:37:31'),
(7, 1, 2, 12, NULL, 'ASTRO CAMP', '2026-09-25', '2026-09-26', 50.00, 'EARNED', 1, '2026-10-10 15:37:31'),
(8, 2, 1, NULL, 3, 'Leave deduction - LR-2026-0003', '2026-10-01', '2026-10-03', 2.00, 'DEDUCTED', 1, '2026-10-14 22:56:25'),
(9, 1, 2, NULL, 13, 'Leave deduction - LR-2026-0013', '2026-10-01', '2026-10-05', 3.00, 'DEDUCTED', 1, '2026-10-15 23:41:01'),
(10, 1, 1, NULL, 16, 'Leave deduction - LR-2026-0016', '2026-10-01', '2026-10-10', 7.00, 'DEDUCTED', 1, '2026-10-16 00:55:32'),
(11, 1, 1, NULL, 18, 'Leave deduction - LR-2026-0018', '2026-10-01', '2026-10-09', 7.00, 'DEDUCTED', 1, '2026-10-16 14:14:27'),
(12, 2, 1, 13, NULL, 'ASTRO CAMP', '2026-09-25', '2026-09-26', 30.00, 'EARNED', 1, '2026-10-16 14:24:10'),
(13, 1, 2, 13, NULL, 'ASTRO CAMP', '2026-09-25', '2026-09-26', 50.00, 'EARNED', 1, '2026-10-16 14:24:10'),
(14, 2, 1, 14, NULL, 'ASTRO CAMP', '2026-09-25', '2026-09-26', 30.00, 'EARNED', 1, '2026-10-16 15:05:12'),
(15, 1, 2, 14, NULL, 'ASTRO CAMP', '2026-09-25', '2026-09-26', 50.00, 'EARNED', 1, '2026-10-16 15:05:12');

-- --------------------------------------------------------

--
-- Table structure for table `service_credit_types`
--

CREATE TABLE `service_credit_types` (
  `credit_type_id` tinyint(3) UNSIGNED NOT NULL,
  `credit_type_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `service_credit_types`
--

INSERT INTO `service_credit_types` (`credit_type_id`, `credit_type_name`) VALUES
(1, 'Local'),
(2, 'National');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accounts`
--
ALTER TABLE `accounts`
  ADD PRIMARY KEY (`account_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `uq_employee_role` (`employee_id`,`role_id`),
  ADD KEY `fk_accounts_role` (`role_id`),
  ADD KEY `idx_accounts_employee` (`employee_id`);

--
-- Indexes for table `account_settings`
--
ALTER TABLE `account_settings`
  ADD PRIMARY KEY (`account_id`);

--
-- Indexes for table `credit_upload_batches`
--
ALTER TABLE `credit_upload_batches`
  ADD PRIMARY KEY (`upload_batch_id`),
  ADD KEY `fk_upload_uploaded_by` (`uploaded_by`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`employee_id`),
  ADD UNIQUE KEY `employee_number` (`employee_number`),
  ADD UNIQUE KEY `deped_email` (`deped_email`);

--
-- Indexes for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD PRIMARY KEY (`leave_request_id`),
  ADD UNIQUE KEY `request_number` (`request_number`),
  ADD KEY `fk_leave_employee` (`employee_id`),
  ADD KEY `fk_leave_type` (`leave_type_id`),
  ADD KEY `fk_leave_credit_type` (`credit_type_id`),
  ADD KEY `fk_leave_reviewed_by` (`reviewed_by`);

--
-- Indexes for table `leave_types`
--
ALTER TABLE `leave_types`
  ADD PRIMARY KEY (`leave_type_id`),
  ADD UNIQUE KEY `leave_type_name` (`leave_type_name`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `service_credit_transactions`
--
ALTER TABLE `service_credit_transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `fk_transaction_employee` (`employee_id`),
  ADD KEY `fk_transaction_credit_type` (`credit_type_id`),
  ADD KEY `fk_transaction_upload_batch` (`upload_batch_id`),
  ADD KEY `fk_transaction_leave` (`leave_request_id`),
  ADD KEY `fk_transaction_created_by` (`created_by`);

--
-- Indexes for table `service_credit_types`
--
ALTER TABLE `service_credit_types`
  ADD PRIMARY KEY (`credit_type_id`),
  ADD UNIQUE KEY `credit_type_name` (`credit_type_name`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accounts`
--
ALTER TABLE `accounts`
  MODIFY `account_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `credit_upload_batches`
--
ALTER TABLE `credit_upload_batches`
  MODIFY `upload_batch_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `employee_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `leave_requests`
--
ALTER TABLE `leave_requests`
  MODIFY `leave_request_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leave_types`
--
ALTER TABLE `leave_types`
  MODIFY `leave_type_id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `service_credit_transactions`
--
ALTER TABLE `service_credit_transactions`
  MODIFY `transaction_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `service_credit_types`
--
ALTER TABLE `service_credit_types`
  MODIFY `credit_type_id` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `accounts`
--
ALTER TABLE `accounts`
  ADD CONSTRAINT `fk_accounts_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_accounts_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`) ON UPDATE CASCADE;

--
-- Constraints for table `account_settings`
--
ALTER TABLE `account_settings`
  ADD CONSTRAINT `fk_settings_account` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`account_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `credit_upload_batches`
--
ALTER TABLE `credit_upload_batches`
  ADD CONSTRAINT `fk_upload_uploaded_by` FOREIGN KEY (`uploaded_by`) REFERENCES `accounts` (`account_id`) ON UPDATE CASCADE;

--
-- Constraints for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD CONSTRAINT `fk_leave_credit_type` FOREIGN KEY (`credit_type_id`) REFERENCES `service_credit_types` (`credit_type_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_leave_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_leave_reviewed_by` FOREIGN KEY (`reviewed_by`) REFERENCES `accounts` (`account_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_leave_type` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`leave_type_id`) ON UPDATE CASCADE;

--
-- Constraints for table `service_credit_transactions`
--
ALTER TABLE `service_credit_transactions`
  ADD CONSTRAINT `fk_transaction_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts` (`account_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_transaction_credit_type` FOREIGN KEY (`credit_type_id`) REFERENCES `service_credit_types` (`credit_type_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_transaction_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_transaction_leave` FOREIGN KEY (`leave_request_id`) REFERENCES `leave_requests` (`leave_request_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_transaction_upload_batch` FOREIGN KEY (`upload_batch_id`) REFERENCES `credit_upload_batches` (`upload_batch_id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
