-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 05, 2026 at 04:50 AM
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
(3, 3, 3, 'mariaclara', '$2a$12$oKFzyWwREF/1gKpIndBRq.e50PP.walk7wWIMG4wwjgaRltY89Njq', 1, '2026-09-29 09:27:55', '2026-09-29 09:27:55');

-- --------------------------------------------------------

--
-- Table structure for table `account_settings`
--

CREATE TABLE `account_settings` (
  `account_id` int(10) UNSIGNED NOT NULL,
  `font_size` varchar(20) NOT NULL DEFAULT '16px',
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(5, 'EMP-TEST-0931', 'Non-Teaching', 'Apolinario', 'Mabini', 'Cruz', 'OSEC-DECSB-T9129239', 'Teacher II', 12, 2, '2019-08-10', '2023-08-10', 'apolinariomabini@deped.gov.ph', '2026-10-02 10:03:23', '2026-10-02 10:03:23');

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
(1, 'LR-2026-0001', 2, 1, NULL, '2026-09-29', 'Vacation to america.', '2026-10-05 08:00:00', '2026-10-05 17:00:00', 1.00, 'Pending', '/PERSYS/Documents/PERSYS%20PUP.pdf', NULL, NULL, '2026-09-29 13:16:55', '2026-09-29 13:16:55');

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
  `activity_date` date DEFAULT NULL,
  `credit_amount` decimal(8,2) NOT NULL,
  `transaction_type` enum('EARNED','DEDUCTED') NOT NULL,
  `created_by` int(10) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ;

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
  ADD UNIQUE KEY `uq_accounts_employee` (`employee_id`),
  ADD KEY `fk_accounts_role` (`role_id`);

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
  MODIFY `account_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `credit_upload_batches`
--
ALTER TABLE `credit_upload_batches`
  MODIFY `upload_batch_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `employee_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
