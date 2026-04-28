-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 28, 2026 at 07:16 PM
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
-- Database: `phpmyadmin`
--
CREATE DATABASE IF NOT EXISTS `phpmyadmin` DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;
USE `phpmyadmin`;

-- --------------------------------------------------------

--
-- Table structure for table `pma__bookmark`
--

CREATE TABLE `pma__bookmark` (
  `id` int(10) UNSIGNED NOT NULL,
  `dbase` varchar(255) NOT NULL DEFAULT '',
  `user` varchar(255) NOT NULL DEFAULT '',
  `label` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
  `query` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Bookmarks';

-- --------------------------------------------------------

--
-- Table structure for table `pma__central_columns`
--

CREATE TABLE `pma__central_columns` (
  `db_name` varchar(64) NOT NULL,
  `col_name` varchar(64) NOT NULL,
  `col_type` varchar(64) NOT NULL,
  `col_length` text DEFAULT NULL,
  `col_collation` varchar(64) NOT NULL,
  `col_isNull` tinyint(1) NOT NULL,
  `col_extra` varchar(255) DEFAULT '',
  `col_default` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Central list of columns';

-- --------------------------------------------------------

--
-- Table structure for table `pma__column_info`
--

CREATE TABLE `pma__column_info` (
  `id` int(5) UNSIGNED NOT NULL,
  `db_name` varchar(64) NOT NULL DEFAULT '',
  `table_name` varchar(64) NOT NULL DEFAULT '',
  `column_name` varchar(64) NOT NULL DEFAULT '',
  `comment` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
  `mimetype` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
  `transformation` varchar(255) NOT NULL DEFAULT '',
  `transformation_options` varchar(255) NOT NULL DEFAULT '',
  `input_transformation` varchar(255) NOT NULL DEFAULT '',
  `input_transformation_options` varchar(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Column information for phpMyAdmin';

-- --------------------------------------------------------

--
-- Table structure for table `pma__designer_settings`
--

CREATE TABLE `pma__designer_settings` (
  `username` varchar(64) NOT NULL,
  `settings_data` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Settings related to Designer';

-- --------------------------------------------------------

--
-- Table structure for table `pma__export_templates`
--

CREATE TABLE `pma__export_templates` (
  `id` int(5) UNSIGNED NOT NULL,
  `username` varchar(64) NOT NULL,
  `export_type` varchar(10) NOT NULL,
  `template_name` varchar(64) NOT NULL,
  `template_data` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Saved export templates';

-- --------------------------------------------------------

--
-- Table structure for table `pma__favorite`
--

CREATE TABLE `pma__favorite` (
  `username` varchar(64) NOT NULL,
  `tables` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Favorite tables';

-- --------------------------------------------------------

--
-- Table structure for table `pma__history`
--

CREATE TABLE `pma__history` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `username` varchar(64) NOT NULL DEFAULT '',
  `db` varchar(64) NOT NULL DEFAULT '',
  `table` varchar(64) NOT NULL DEFAULT '',
  `timevalue` timestamp NOT NULL DEFAULT current_timestamp(),
  `sqlquery` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='SQL history for phpMyAdmin';

-- --------------------------------------------------------

--
-- Table structure for table `pma__navigationhiding`
--

CREATE TABLE `pma__navigationhiding` (
  `username` varchar(64) NOT NULL,
  `item_name` varchar(64) NOT NULL,
  `item_type` varchar(64) NOT NULL,
  `db_name` varchar(64) NOT NULL,
  `table_name` varchar(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Hidden items of navigation tree';

-- --------------------------------------------------------

--
-- Table structure for table `pma__pdf_pages`
--

CREATE TABLE `pma__pdf_pages` (
  `db_name` varchar(64) NOT NULL DEFAULT '',
  `page_nr` int(10) UNSIGNED NOT NULL,
  `page_descr` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='PDF relation pages for phpMyAdmin';

-- --------------------------------------------------------

--
-- Table structure for table `pma__recent`
--

CREATE TABLE `pma__recent` (
  `username` varchar(64) NOT NULL,
  `tables` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Recently accessed tables';

-- --------------------------------------------------------

--
-- Table structure for table `pma__relation`
--

CREATE TABLE `pma__relation` (
  `master_db` varchar(64) NOT NULL DEFAULT '',
  `master_table` varchar(64) NOT NULL DEFAULT '',
  `master_field` varchar(64) NOT NULL DEFAULT '',
  `foreign_db` varchar(64) NOT NULL DEFAULT '',
  `foreign_table` varchar(64) NOT NULL DEFAULT '',
  `foreign_field` varchar(64) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Relation table';

-- --------------------------------------------------------

--
-- Table structure for table `pma__savedsearches`
--

CREATE TABLE `pma__savedsearches` (
  `id` int(5) UNSIGNED NOT NULL,
  `username` varchar(64) NOT NULL DEFAULT '',
  `db_name` varchar(64) NOT NULL DEFAULT '',
  `search_name` varchar(64) NOT NULL DEFAULT '',
  `search_data` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Saved searches';

-- --------------------------------------------------------

--
-- Table structure for table `pma__table_coords`
--

CREATE TABLE `pma__table_coords` (
  `db_name` varchar(64) NOT NULL DEFAULT '',
  `table_name` varchar(64) NOT NULL DEFAULT '',
  `pdf_page_number` int(11) NOT NULL DEFAULT 0,
  `x` float UNSIGNED NOT NULL DEFAULT 0,
  `y` float UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Table coordinates for phpMyAdmin PDF output';

-- --------------------------------------------------------

--
-- Table structure for table `pma__table_info`
--

CREATE TABLE `pma__table_info` (
  `db_name` varchar(64) NOT NULL DEFAULT '',
  `table_name` varchar(64) NOT NULL DEFAULT '',
  `display_field` varchar(64) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Table information for phpMyAdmin';

-- --------------------------------------------------------

--
-- Table structure for table `pma__table_uiprefs`
--

CREATE TABLE `pma__table_uiprefs` (
  `username` varchar(64) NOT NULL,
  `db_name` varchar(64) NOT NULL,
  `table_name` varchar(64) NOT NULL,
  `prefs` text NOT NULL,
  `last_update` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Tables'' UI preferences';

-- --------------------------------------------------------

--
-- Table structure for table `pma__tracking`
--

CREATE TABLE `pma__tracking` (
  `db_name` varchar(64) NOT NULL,
  `table_name` varchar(64) NOT NULL,
  `version` int(10) UNSIGNED NOT NULL,
  `date_created` datetime NOT NULL,
  `date_updated` datetime NOT NULL,
  `schema_snapshot` text NOT NULL,
  `schema_sql` text DEFAULT NULL,
  `data_sql` longtext DEFAULT NULL,
  `tracking` set('UPDATE','REPLACE','INSERT','DELETE','TRUNCATE','CREATE DATABASE','ALTER DATABASE','DROP DATABASE','CREATE TABLE','ALTER TABLE','RENAME TABLE','DROP TABLE','CREATE INDEX','DROP INDEX','CREATE VIEW','ALTER VIEW','DROP VIEW') DEFAULT NULL,
  `tracking_active` int(1) UNSIGNED NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Database changes tracking for phpMyAdmin';

-- --------------------------------------------------------

--
-- Table structure for table `pma__userconfig`
--

CREATE TABLE `pma__userconfig` (
  `username` varchar(64) NOT NULL,
  `timevalue` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `config_data` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='User preferences storage for phpMyAdmin';

--
-- Dumping data for table `pma__userconfig`
--

INSERT INTO `pma__userconfig` (`username`, `timevalue`, `config_data`) VALUES
('root', '2025-12-05 09:28:01', '{\"Console\\/Mode\":\"collapse\"}');

-- --------------------------------------------------------

--
-- Table structure for table `pma__usergroups`
--

CREATE TABLE `pma__usergroups` (
  `usergroup` varchar(64) NOT NULL,
  `tab` varchar(64) NOT NULL,
  `allowed` enum('Y','N') NOT NULL DEFAULT 'N'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='User groups with configured menu items';

-- --------------------------------------------------------

--
-- Table structure for table `pma__users`
--

CREATE TABLE `pma__users` (
  `username` varchar(64) NOT NULL,
  `usergroup` varchar(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Users and their assignments to user groups';

--
-- Indexes for dumped tables
--

--
-- Indexes for table `pma__bookmark`
--
ALTER TABLE `pma__bookmark`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pma__central_columns`
--
ALTER TABLE `pma__central_columns`
  ADD PRIMARY KEY (`db_name`,`col_name`);

--
-- Indexes for table `pma__column_info`
--
ALTER TABLE `pma__column_info`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `db_name` (`db_name`,`table_name`,`column_name`);

--
-- Indexes for table `pma__designer_settings`
--
ALTER TABLE `pma__designer_settings`
  ADD PRIMARY KEY (`username`);

--
-- Indexes for table `pma__export_templates`
--
ALTER TABLE `pma__export_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `u_user_type_template` (`username`,`export_type`,`template_name`);

--
-- Indexes for table `pma__favorite`
--
ALTER TABLE `pma__favorite`
  ADD PRIMARY KEY (`username`);

--
-- Indexes for table `pma__history`
--
ALTER TABLE `pma__history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `username` (`username`,`db`,`table`,`timevalue`);

--
-- Indexes for table `pma__navigationhiding`
--
ALTER TABLE `pma__navigationhiding`
  ADD PRIMARY KEY (`username`,`item_name`,`item_type`,`db_name`,`table_name`);

--
-- Indexes for table `pma__pdf_pages`
--
ALTER TABLE `pma__pdf_pages`
  ADD PRIMARY KEY (`page_nr`),
  ADD KEY `db_name` (`db_name`);

--
-- Indexes for table `pma__recent`
--
ALTER TABLE `pma__recent`
  ADD PRIMARY KEY (`username`);

--
-- Indexes for table `pma__relation`
--
ALTER TABLE `pma__relation`
  ADD PRIMARY KEY (`master_db`,`master_table`,`master_field`),
  ADD KEY `foreign_field` (`foreign_db`,`foreign_table`);

--
-- Indexes for table `pma__savedsearches`
--
ALTER TABLE `pma__savedsearches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `u_savedsearches_username_dbname` (`username`,`db_name`,`search_name`);

--
-- Indexes for table `pma__table_coords`
--
ALTER TABLE `pma__table_coords`
  ADD PRIMARY KEY (`db_name`,`table_name`,`pdf_page_number`);

--
-- Indexes for table `pma__table_info`
--
ALTER TABLE `pma__table_info`
  ADD PRIMARY KEY (`db_name`,`table_name`);

--
-- Indexes for table `pma__table_uiprefs`
--
ALTER TABLE `pma__table_uiprefs`
  ADD PRIMARY KEY (`username`,`db_name`,`table_name`);

--
-- Indexes for table `pma__tracking`
--
ALTER TABLE `pma__tracking`
  ADD PRIMARY KEY (`db_name`,`table_name`,`version`);

--
-- Indexes for table `pma__userconfig`
--
ALTER TABLE `pma__userconfig`
  ADD PRIMARY KEY (`username`);

--
-- Indexes for table `pma__usergroups`
--
ALTER TABLE `pma__usergroups`
  ADD PRIMARY KEY (`usergroup`,`tab`,`allowed`);

--
-- Indexes for table `pma__users`
--
ALTER TABLE `pma__users`
  ADD PRIMARY KEY (`username`,`usergroup`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `pma__bookmark`
--
ALTER TABLE `pma__bookmark`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pma__column_info`
--
ALTER TABLE `pma__column_info`
  MODIFY `id` int(5) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pma__export_templates`
--
ALTER TABLE `pma__export_templates`
  MODIFY `id` int(5) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pma__history`
--
ALTER TABLE `pma__history`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pma__pdf_pages`
--
ALTER TABLE `pma__pdf_pages`
  MODIFY `page_nr` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pma__savedsearches`
--
ALTER TABLE `pma__savedsearches`
  MODIFY `id` int(5) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- Database: `skybuild`
--
CREATE DATABASE IF NOT EXISTS `skybuild` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `skybuild`;

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) UNSIGNED NOT NULL,
  `action` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `action`, `details`, `ip_address`, `created_at`) VALUES
(1, 'Login', 'Admin logged in successfully', '::1', '2026-04-28 16:36:51'),
(2, 'Add Quotation', 'Created quotation \'a\'', '::1', '2026-04-28 16:46:14'),
(3, 'Bulk Soft Delete', '2 consultations moved to trash', '::1', '2026-04-28 16:46:41'),
(4, 'Soft Delete Quotation', 'Quotation ID 13 moved to trash', '::1', '2026-04-28 16:46:48'),
(5, 'Restore Item', 'Restored quotation ID 13 from trash', '::1', '2026-04-28 16:46:53'),
(6, 'Soft Delete Quotation', 'Quotation ID 13 moved to trash', '::1', '2026-04-28 16:47:02'),
(7, 'Restore Item', 'Restored quotation ID 13 from trash', '::1', '2026-04-28 16:47:09'),
(8, 'Restore Item', 'Restored inquiry ID 16 from trash', '::1', '2026-04-28 16:47:16'),
(9, 'Restore Item', 'Restored inquiry ID 17 from trash', '::1', '2026-04-28 16:47:20'),
(10, 'Bulk Soft Delete', '2 consultations moved to trash', '::1', '2026-04-28 16:48:23'),
(11, 'Restore Item', 'Restored inquiry ID 16 from trash', '::1', '2026-04-28 16:48:33'),
(12, 'Bulk Soft Delete', '2 consultations moved to trash', '::1', '2026-04-28 16:55:16'),
(13, 'Restore Item', 'Restored inquiry ID 16 from trash', '::1', '2026-04-28 17:06:21'),
(14, 'Permanent Delete', 'Permanently deleted inquiry ID 18', '::1', '2026-04-28 17:06:24'),
(15, 'Permanent Delete', 'Permanently deleted inquiry ID 17', '::1', '2026-04-28 17:06:26'),
(16, 'Bulk Soft Delete', '1 consultations moved to trash', '::1', '2026-04-28 17:06:33'),
(17, 'Bulk Soft Delete', '1 consultations moved to trash', '::1', '2026-04-28 17:11:52'),
(18, 'Permanent Delete', 'Permanently deleted inquiry ID 16', '::1', '2026-04-28 17:11:59'),
(19, 'Logout', 'Admin logged out', '::1', '2026-04-28 17:12:01');

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) UNSIGNED NOT NULL,
  `username` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `security_maiden` varchar(255) DEFAULT 'Cruz',
  `security_color` varchar(255) DEFAULT 'purple',
  `security_dog` varchar(255) DEFAULT 'Gerrie'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password_hash`, `email`, `created_at`, `security_maiden`, `security_color`, `security_dog`) VALUES
(1, 'skybuild_admin', '$2y$10$7NVAS291MQFOzChqCePtb.QDZlSTX//Sg/zEgyRorfVAWBe5Y6Apq', 'skybuildadmin@gmail.com', '2026-04-28 13:07:32', 'Cruz', 'purple', 'Gerrie');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) UNSIGNED NOT NULL,
  `event_date` date NOT NULL,
  `event_time` varchar(50) DEFAULT '',
  `title` varchar(255) NOT NULL,
  `client_name` varchar(255) DEFAULT '',
  `description` text DEFAULT NULL,
  `color` varchar(20) DEFAULT '#64b5f6',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inquiries`
--

CREATE TABLE `inquiries` (
  `id` int(11) UNSIGNED NOT NULL,
  `fullname` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) NOT NULL,
  `project_type` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `id` int(11) UNSIGNED NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`id`, `item_name`, `quantity`, `updated_at`, `deleted_at`) VALUES
(1, 'Pipe (PVC 2 inch)', 50, '2026-04-17 17:40:54', NULL),
(2, 'Steel Rebar (10mm)', 200, '2026-04-17 17:40:54', NULL),
(3, 'Screwdriver Set', 15, '2026-04-17 17:40:54', NULL),
(4, 'Portland cement (40 kg)', 0, '2026-04-17 17:51:52', NULL),
(5, 'Portland cement (50 kg)', 0, '2026-04-17 17:51:52', NULL),
(6, 'Blended cement (40 kg)', 0, '2026-04-17 17:51:52', NULL),
(7, 'Blended cement (50 kg)', 0, '2026-04-17 17:51:52', NULL),
(8, 'Masonry cement (40 kg)', 0, '2026-04-17 17:51:52', NULL),
(9, 'White cement (40 kg)', 0, '2026-04-17 17:51:52', NULL),
(10, 'Washed sand (cubic meter)', 16, '2026-04-21 08:04:37', NULL),
(11, 'River sand (cubic meter)', 0, '2026-04-17 17:51:52', NULL),
(12, 'Plaster sand (cubic meter)', 0, '2026-04-17 17:51:52', NULL),
(13, 'Gravel / crushed stone 3/4 in', 0, '2026-04-17 17:51:52', NULL),
(14, 'Gravel / crushed stone 1 in', 0, '2026-04-17 17:51:52', NULL),
(15, 'Concrete hollow blocks 4 in (400x200x100mm)', 0, '2026-04-17 17:51:52', NULL),
(16, 'Concrete hollow blocks 5 in (400x200x125mm)', 0, '2026-04-17 17:51:52', NULL),
(17, 'Concrete hollow blocks 6 in (400x200x150mm)', 0, '2026-04-17 17:51:52', NULL),
(18, 'Concrete cover blocks 25 mm', 0, '2026-04-17 17:51:52', NULL),
(19, 'Concrete cover blocks 50 mm', 0, '2026-04-17 17:51:52', NULL),
(20, 'Deformed rebar 10 mm x 6 m', 0, '2026-04-17 17:51:52', NULL),
(21, 'Deformed rebar 12 mm x 6 m', 0, '2026-04-17 17:51:52', NULL),
(22, 'Deformed rebar 16 mm x 6 m', 0, '2026-04-17 17:51:52', NULL),
(23, 'Deformed rebar 20 mm x 6 m', 0, '2026-04-17 17:51:52', NULL),
(24, 'Plain round bar 10 mm x 6 m', 0, '2026-04-17 17:51:52', NULL),
(25, 'Plain round bar 12 mm x 6 m', 0, '2026-04-17 17:51:52', NULL),
(26, 'Welded wire mesh 50x50mm (1.2x2.4m)', 14, '2026-04-17 17:52:27', NULL),
(27, 'Welded wire mesh 100x100mm (2.0x3.0m)', 2, '2026-04-17 18:03:29', NULL),
(28, 'Tie wire Gauge 16 (1 kg)', 0, '2026-04-17 17:51:52', NULL),
(29, 'Tie wire Gauge 16 (25 kg)', 0, '2026-04-17 17:51:52', NULL),
(30, 'Angle bar 25 x 25 x 3 mm x 6m', 0, '2026-04-17 17:51:52', NULL),
(31, 'Angle bar 50 x 50 x 5 mm x 6m', 0, '2026-04-17 17:51:52', NULL),
(32, 'Flat bar 25 x 6 mm x 6m', 0, '2026-04-17 17:51:52', NULL),
(33, 'Flat bar 50 x 6 mm x 6m', 0, '2026-04-17 17:51:52', NULL),
(34, 'Square bar 10 mm x 6m', 0, '2026-04-17 17:51:52', NULL),
(35, 'Square bar 12 mm x 6m', 0, '2026-04-17 17:51:52', NULL),
(36, 'C-channel 50 x 25 x 2.0 mm x 6m', 0, '2026-04-17 17:51:52', NULL),
(37, 'C-channel 100 x 50 x 2.0 mm x 6m', 0, '2026-04-17 17:51:52', NULL),
(38, 'I-beam 150 mm x 6m', 0, '2026-04-17 17:51:52', NULL),
(39, 'I-beam 200 mm x 6m', 0, '2026-04-17 17:51:52', NULL),
(40, 'H-beam 150 x 150 mm', 0, '2026-04-17 17:51:52', NULL),
(41, 'H-beam 200 x 200 mm', 0, '2026-04-17 17:51:52', NULL),
(42, 'Steel plate 1.5 mm (1.2x2.4m)', 0, '2026-04-17 17:51:52', NULL),
(43, 'Steel plate 3 mm (1.2x2.4m)', 0, '2026-04-17 17:51:52', NULL),
(44, 'Steel plate 6 mm (1.2x2.4m)', 0, '2026-04-17 17:51:52', NULL),
(45, 'Rectangular steel tube 25 x 50 mm (1.5mm) x 6m', 0, '2026-04-17 17:51:52', NULL),
(46, 'Rectangular steel tube 50 x 100 mm (2.0mm) x 6m', 0, '2026-04-17 17:51:52', NULL),
(47, 'Square steel tube 25 x 25 mm (1.5mm) x 6m', 0, '2026-04-17 17:51:52', NULL),
(48, 'Square steel tube 50 x 50 mm (2.0mm) x 6m', 0, '2026-04-17 17:51:52', NULL),
(49, 'Round steel tube / pipe 1 in (Sched 40)', 0, '2026-04-17 17:51:52', NULL),
(50, 'Round steel tube / pipe 2 in (Sched 40)', 0, '2026-04-17 17:51:52', NULL),
(51, 'Coco lumber 2 x 2 x 10 ft', 0, '2026-04-17 17:51:52', NULL),
(52, 'Coco lumber 2 x 3 x 10 ft', 0, '2026-04-17 17:51:52', NULL),
(53, 'Coco lumber 2 x 4 x 10 ft', 0, '2026-04-17 17:51:52', NULL),
(54, 'Ordinary plywood 1/4 in (1.22x2.44m)', 0, '2026-04-17 17:51:52', NULL),
(55, 'Ordinary plywood 1/2 in (1.22x2.44m)', 0, '2026-04-17 17:51:52', NULL),
(56, 'Ordinary plywood 3/4 in (1.22x2.44m)', 0, '2026-04-17 17:51:52', NULL),
(57, 'Marine plywood 1/4 in (1.22x2.44m)', 0, '2026-04-17 17:51:52', NULL),
(58, 'Marine plywood 1/2 in (1.22x2.44m)', 0, '2026-04-17 17:51:52', NULL),
(59, 'Marine plywood 3/4 in (1.22x2.44m)', 0, '2026-04-17 17:51:52', NULL),
(60, 'Phenolic board 1/2 in (1.22x2.44m)', 0, '2026-04-17 17:51:52', NULL),
(61, 'Phenolic board 3/4 in (1.22x2.44m)', 0, '2026-04-17 17:51:52', NULL),
(62, 'Clay brick 200 x 100 x 60 mm', 0, '2026-04-17 17:51:52', NULL),
(63, 'AAC block 100 mm (600x200mm)', 0, '2026-04-17 17:51:52', NULL),
(64, 'AAC block 150 mm (600x200mm)', 0, '2026-04-17 17:51:52', NULL),
(65, 'Form lumber 2 x 2', 0, '2026-04-17 17:51:52', NULL),
(66, 'Form lumber 2 x 3', 0, '2026-04-17 17:51:52', NULL),
(67, 'Form lumber 2 x 4', 0, '2026-04-17 17:51:52', NULL),
(68, 'Steel scaffolding tube 48.3 mm OD x 3m', 0, '2026-04-17 17:51:52', NULL),
(69, 'H-frame scaffolding 1.2m x 1.7m', 0, '2026-04-17 17:51:52', NULL),
(70, 'Prepainted rib type roofing 0.40 mm', 0, '2026-04-17 17:51:52', NULL),
(71, 'Prepainted corrugated roofing 0.40 mm', 0, '2026-04-17 17:51:52', NULL),
(72, 'Corrugated GI sheet 0.40 mm x 8 ft', 0, '2026-04-17 17:51:52', NULL),
(73, 'Corrugated GI sheet 0.40 mm x 10 ft', 0, '2026-04-17 17:51:53', NULL),
(74, 'Polycarbonate roofing 6 mm (1.22x2.4m)', 0, '2026-04-17 17:51:53', NULL),
(75, 'Polycarbonate roofing 6 mm (1.22x6.0m)', 0, '2026-04-17 17:51:53', NULL),
(76, 'Foil roof insulation 25 mm', 0, '2026-04-17 17:51:53', NULL),
(77, 'Fiberglass roof insulation 50 mm', 0, '2026-04-17 17:51:53', NULL),
(78, 'Roof gutter 5 in x 2.4m', 0, '2026-04-17 17:51:53', NULL),
(79, 'Roof gutter 6 in x 2.4m', 0, '2026-04-17 17:51:53', NULL),
(80, 'Downspout 2 x 3 in x 2.4m', 0, '2026-04-17 17:51:53', NULL),
(81, 'Downspout 3 x 4 in x 2.4m', 0, '2026-04-17 17:51:53', NULL),
(82, 'C-purlin 100 x 50 x 20 x 2.0 mm x 6m', 0, '2026-04-17 17:51:53', NULL),
(83, 'Liquid waterproofing (16 L)', 0, '2026-04-17 17:51:53', NULL),
(84, 'Liquid waterproofing (4 L)', 0, '2026-04-17 17:51:53', NULL),
(85, 'Cementitious waterproofing (25 kg)', 0, '2026-04-17 17:51:53', NULL),
(86, 'Bituminous membrane 3.0 mm (1x10m)', 0, '2026-04-17 17:51:53', NULL),
(87, 'Fiberglass insulation 50 mm (24 kg/m3)', 0, '2026-04-17 17:51:53', NULL),
(88, 'Rockwool insulation 50 mm', 0, '2026-04-17 17:51:53', NULL),
(89, 'Rigid foam board 50 mm', 0, '2026-04-17 17:51:53', NULL),
(90, 'Gypsum board regular 9 mm (1.2x2.4m)', 0, '2026-04-17 17:51:53', NULL),
(91, 'Gypsum board regular 12 mm (1.2x2.4m)', 0, '2026-04-17 17:51:53', NULL),
(92, 'Gypsum board moisture-resistant 12 mm (1.2x2.4m)', 0, '2026-04-17 17:51:53', NULL),
(93, 'Fiber cement board 4.5 mm (1.2x2.4m)', 0, '2026-04-17 17:51:53', NULL),
(94, 'Fiber cement board 6 mm (1.2x2.4m)', 0, '2026-04-17 17:51:53', NULL),
(95, 'Metal stud 50 mm x 3.0m', 0, '2026-04-17 17:51:53', NULL),
(96, 'Metal stud 75 mm x 3.0m', 0, '2026-04-17 17:51:53', NULL),
(97, 'Metal track 50 mm x 3.0m', 0, '2026-04-17 17:51:53', NULL),
(98, 'Metal track 75 mm x 3.0m', 0, '2026-04-17 17:51:53', NULL),
(99, 'Acoustic ceiling tile 600 x 600 x 12 mm', 0, '2026-04-17 17:51:53', NULL),
(100, 'T-bar exposed grid 24 mm x 3.6m', 0, '2026-04-17 17:51:53', NULL),
(101, 'Furring channel 19 x 50 mm x 5m', 0, '2026-04-17 17:51:53', NULL),
(102, 'Carrying channel 12 x 38 mm x 5m', 0, '2026-04-17 17:51:53', NULL),
(103, 'Ceramic tile 300 x 300 mm', 0, '2026-04-17 17:51:53', NULL),
(104, 'Ceramic tile 600 x 600 mm', 0, '2026-04-17 17:51:53', NULL),
(105, 'Porcelain tile 600 x 600 mm', 0, '2026-04-17 17:51:53', NULL),
(106, 'Porcelain tile 800 x 800 mm', 0, '2026-04-17 17:51:53', NULL),
(107, 'Vinyl plank 150 x 900 mm (3mm)', 0, '2026-04-17 17:51:53', NULL),
(108, 'Laminate flooring 8 mm', 0, '2026-04-17 17:51:53', NULL),
(109, 'Tile adhesive (25 kg)', 0, '2026-04-17 17:51:53', NULL),
(110, 'Tile grout (2 kg)', 0, '2026-04-17 17:51:53', NULL),
(111, 'Flush door 800 x 2100 mm', 0, '2026-04-17 17:51:53', NULL),
(112, 'Flush door 900 x 2100 mm', 0, '2026-04-17 17:51:53', NULL),
(113, 'Solid wood door 800 x 2100 mm', 0, '2026-04-17 17:51:53', NULL),
(114, 'Solid wood door 900 x 2100 mm', 0, '2026-04-17 17:51:53', NULL),
(115, 'Steel door fire-rated 900 x 2100 mm', 0, '2026-04-17 17:51:53', NULL),
(116, 'Clear float glass 6 mm', 0, '2026-04-17 17:51:53', NULL),
(117, 'Tempered glass 10 mm', 0, '2026-04-17 17:51:53', NULL),
(118, 'Primer paint (16 L)', 0, '2026-04-17 17:51:53', NULL),
(119, 'Flat latex paint (16 L)', 0, '2026-04-17 17:51:53', NULL),
(120, 'Semi-gloss latex paint (16 L)', 0, '2026-04-17 17:51:53', NULL),
(121, 'Gloss enamel paint (4 L)', 0, '2026-04-17 17:51:53', NULL),
(122, 'Elastomeric paint (16 L)', 0, '2026-04-17 17:51:53', NULL),
(123, 'Skim coat (20 kg)', 0, '2026-04-17 17:51:53', NULL),
(124, 'PVC pipe Sched 40 - 1/2 in x 3m', 0, '2026-04-17 17:51:53', NULL),
(125, 'PVC pipe Sched 40 - 3/4 in x 3m', 0, '2026-04-17 17:51:53', NULL),
(126, 'PVC pipe Sched 40 - 1 in x 3m', 0, '2026-04-17 17:51:53', NULL),
(127, 'PVC pipe Sched 40 - 2 in x 3m', 0, '2026-04-17 17:51:53', NULL),
(128, 'PVC pipe Sched 40 - 3 in x 3m', 0, '2026-04-17 17:51:53', NULL),
(129, 'PVC pipe Sched 40 - 4 in x 3m', 0, '2026-04-17 17:51:53', NULL),
(130, 'uPVC pipe 20 mm', 0, '2026-04-17 17:51:53', NULL),
(131, 'uPVC pipe 25 mm', 0, '2026-04-17 17:51:53', NULL),
(132, 'PPR pipe 20 mm', 0, '2026-04-17 17:51:53', NULL),
(133, 'PPR pipe 25 mm', 0, '2026-04-17 17:51:53', NULL),
(134, 'GI pipe 1/2 in x 6m', 0, '2026-04-17 17:51:53', NULL),
(135, 'GI pipe 3/4 in x 6m', 0, '2026-04-17 17:51:53', NULL),
(136, 'Gate valve 1/2 in', 0, '2026-04-17 17:51:53', NULL),
(137, 'Gate valve 3/4 in', 0, '2026-04-17 17:51:53', NULL),
(138, 'Gate valve 1 in', 0, '2026-04-17 17:51:53', NULL),
(139, 'Water tank 1000 L', 0, '2026-04-17 17:51:53', NULL),
(140, 'Water closet (300mm rough-in)', 0, '2026-04-17 17:51:53', NULL),
(141, 'Lavatory 500 mm', 0, '2026-04-17 17:51:53', NULL),
(142, 'THHN stranded wire 2.0 sq mm (150m)', 0, '2026-04-17 17:51:53', NULL),
(143, 'THHN stranded wire 3.5 sq mm (150m)', 0, '2026-04-17 17:51:53', NULL),
(144, 'THHN stranded wire 5.5 sq mm (150m)', 0, '2026-04-17 17:51:53', NULL),
(145, 'PVC electrical conduit 20 mm x 3m', 0, '2026-04-17 17:51:53', NULL),
(146, 'PVC electrical conduit 25 mm x 3m', 0, '2026-04-17 17:51:53', NULL),
(147, 'Utility box 2 x 4 in', 0, '2026-04-17 17:51:53', NULL),
(148, 'Junction box octagonal 4 in', 0, '2026-04-17 17:51:53', NULL),
(149, 'Panel board 100 A', 0, '2026-04-17 17:51:53', NULL),
(150, 'Circuit breaker 20 A', 0, '2026-04-17 17:51:53', NULL),
(151, 'Circuit breaker 30 A', 0, '2026-04-17 17:51:53', NULL),
(152, 'Convenience outlet 15 A', 0, '2026-04-17 17:51:53', NULL),
(153, 'Light switch 15 A', 0, '2026-04-17 17:51:53', NULL),
(154, 'LED bulb 9 W', 0, '2026-04-17 17:51:53', NULL),
(155, 'LED bulb 12 W', 0, '2026-04-17 17:51:53', NULL),
(156, 'LED downlight 4 in', 0, '2026-04-17 17:51:53', NULL),
(157, 'LED tube light 4 ft', 0, '2026-04-17 17:51:53', NULL),
(158, 'Air-conditioning unit 1.0 HP', 0, '2026-04-17 17:51:53', NULL),
(159, 'Air-conditioning unit 1.5 HP', 0, '2026-04-17 17:51:53', NULL),
(160, 'Air-conditioning unit 2.0 HP', 0, '2026-04-17 17:51:53', NULL),
(161, 'Exhaust fan 8 in', 0, '2026-04-17 17:51:53', NULL),
(162, 'Exhaust fan 10 in', 0, '2026-04-17 17:51:53', NULL),
(163, 'Common nail 2 in', 0, '2026-04-17 17:51:53', NULL),
(164, 'Common nail 3 in', 0, '2026-04-17 17:51:53', NULL),
(165, 'Common nail 4 in', 0, '2026-04-17 17:51:53', NULL),
(166, 'Concrete nail 2 in', 0, '2026-04-17 17:51:53', NULL),
(167, 'Concrete nail 3 in', 0, '2026-04-17 17:51:53', NULL),
(168, 'Metal screw 1 in', 0, '2026-04-17 17:51:53', NULL),
(169, 'Gypsum drywall screw 1 1/2 in', 0, '2026-04-17 17:51:53', NULL),
(170, 'Anchor bolt 12 x 150 mm', 0, '2026-04-17 17:51:53', NULL),
(171, 'Expansion bolt 3/8 in', 0, '2026-04-17 17:51:53', NULL),
(172, 'Door hinge 3 in', 0, '2026-04-17 17:51:53', NULL),
(173, 'Door hinge 4 in', 0, '2026-04-17 17:51:53', NULL),
(174, 'Door lockset (60mm backset)', 0, '2026-04-17 17:51:53', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quotations`
--

CREATE TABLE `quotations` (
  `id` int(11) UNSIGNED NOT NULL,
  `folder_id` int(11) UNSIGNED DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `grand_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `po_number` varchar(100) NOT NULL DEFAULT '',
  `signee_name` varchar(255) NOT NULL DEFAULT '',
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quotations`
--

INSERT INTO `quotations` (`id`, `folder_id`, `title`, `grand_total`, `created_at`, `po_number`, `signee_name`, `deleted_at`) VALUES
(13, NULL, 'a', 1.00, '2026-04-28 16:46:14', 'a', 'a', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `quotation_items`
--

CREATE TABLE `quotation_items` (
  `id` int(11) UNSIGNED NOT NULL,
  `quotation_id` int(11) UNSIGNED NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_price` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quotation_items`
--

INSERT INTO `quotation_items` (`id`, `quotation_id`, `item_name`, `quantity`, `unit_price`, `total_price`) VALUES
(17, 13, 'a', 1, 1.00, 1.00);

-- --------------------------------------------------------

--
-- Table structure for table `quote_folders`
--

CREATE TABLE `quote_folders` (
  `id` int(11) UNSIGNED NOT NULL,
  `parent_id` int(11) UNSIGNED DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `showcase`
--

CREATE TABLE `showcase` (
  `id` int(11) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `showcase`
--

INSERT INTO `showcase` (`id`, `title`, `description`, `image_path`, `created_at`, `deleted_at`) VALUES
(1, 'The Vineyard Manor - Twin Lakes', 'Located in Laurel, Batangas, this multi-building resort complex features a beautiful vineyard aesthetic, expansive balconies, and elegant hillside architecture designed to harmonize with the natural landscape.', 'twinlakes.png', '2026-04-19 14:28:48', NULL),
(2, 'Three-Storey Residential House', 'A modern three-storey residential home featuring striking red vertical architectural accents, a spacious balcony, and secure perimeter fencing, built with high-quality materials for lasting durability.', 'three-storey.jpg', '2026-04-19 14:28:48', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inquiries`
--
ALTER TABLE `inquiries`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `quotations`
--
ALTER TABLE `quotations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `folder_id` (`folder_id`);

--
-- Indexes for table `quotation_items`
--
ALTER TABLE `quotation_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quotation_id` (`quotation_id`);

--
-- Indexes for table `quote_folders`
--
ALTER TABLE `quote_folders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Indexes for table `showcase`
--
ALTER TABLE `showcase`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `inquiries`
--
ALTER TABLE `inquiries`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=177;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `quotations`
--
ALTER TABLE `quotations`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `quotation_items`
--
ALTER TABLE `quotation_items`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `quote_folders`
--
ALTER TABLE `quote_folders`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `showcase`
--
ALTER TABLE `showcase`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `quotations`
--
ALTER TABLE `quotations`
  ADD CONSTRAINT `quotations_ibfk_1` FOREIGN KEY (`folder_id`) REFERENCES `quote_folders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `quotation_items`
--
ALTER TABLE `quotation_items`
  ADD CONSTRAINT `quotation_items_ibfk_1` FOREIGN KEY (`quotation_id`) REFERENCES `quotations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `quote_folders`
--
ALTER TABLE `quote_folders`
  ADD CONSTRAINT `quote_folders_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `quote_folders` (`id`) ON DELETE CASCADE;
--
-- Database: `skybuild_db`
--
CREATE DATABASE IF NOT EXISTS `skybuild_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `skybuild_db`;

-- --------------------------------------------------------

--
-- Table structure for table `inquiries`
--

CREATE TABLE `inquiries` (
  `id` int(11) NOT NULL,
  `customer_name` int(11) NOT NULL,
  `email` int(11) NOT NULL,
  `message` int(11) NOT NULL,
  `date_sent` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `id` int(11) NOT NULL,
  `item_name` int(11) NOT NULL,
  `category` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit` int(11) NOT NULL,
  `status` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `id` int(11) NOT NULL,
  `title` int(11) NOT NULL,
  `description` int(11) NOT NULL,
  `image` int(11) NOT NULL,
  `completion_date` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` int(11) NOT NULL,
  `email` int(11) NOT NULL,
  `password` int(11) NOT NULL,
  `roles` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
--
-- Database: `test`
--
CREATE DATABASE IF NOT EXISTS `test` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `test`;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
