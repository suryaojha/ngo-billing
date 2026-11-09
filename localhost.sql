-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Sep 12, 2026 at 03:40 PM
-- Server version: 10.11.11-MariaDB-cll-lve
-- PHP Version: 8.4.24

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `reli446436_ReliefWaveDB`
--
CREATE DATABASE IF NOT EXISTS `reli446436_ReliefWaveDB` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `reli446436_ReliefWaveDB`;

-- --------------------------------------------------------

--
-- Table structure for table `AboutInfo`
--

CREATE TABLE `AboutInfo` (
  `AboutID` int(11) NOT NULL,
  `Title` varchar(255) DEFAULT NULL,
  `Description` text DEFAULT NULL,
  `Mission` text DEFAULT NULL,
  `Vision` text DEFAULT NULL,
  `CreatedDate` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `AboutInfo`
--

INSERT INTO `AboutInfo` (`AboutID`, `Title`, `Description`, `Mission`, `Vision`, `CreatedDate`) VALUES
(1, 'About Relief Wave Foundation', 'Relief Wave Foundation is dedicated to creating positive change.', 'To empower underprivileged communities.', 'A world where everyone has access to opportunities.', '2025-12-18 19:00:09');

-- --------------------------------------------------------

--
-- Table structure for table `AdminLogs`
--

CREATE TABLE `AdminLogs` (
  `LogID` int(11) NOT NULL,
  `AdminEmail` varchar(255) DEFAULT NULL,
  `Action` varchar(255) DEFAULT NULL,
  `Details` text DEFAULT NULL,
  `LogDate` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `AdminSettings`
--

CREATE TABLE `AdminSettings` (
  `SettingID` int(11) NOT NULL,
  `SettingKey` varchar(100) NOT NULL,
  `SettingValue` text DEFAULT NULL,
  `UpdatedDate` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `AdminSettings`
--

INSERT INTO `AdminSettings` (`SettingID`, `SettingKey`, `SettingValue`, `UpdatedDate`) VALUES
(1, 'HeroVideoPath', '', '2025-12-23 18:09:40'),
(2, 'ThemeColor', 'gray', '2025-12-23 18:09:40'),
(3, 'SecondaryColor', '#000000', '2025-12-24 02:49:52'),
(4, 'GradientType', 'linear', '2025-12-23 18:09:40'),
(5, 'GradientDirection', '135deg', '2025-12-23 18:09:40'),
(6, 'LogoPath', 'uploads/logo.jpg', '2025-12-23 18:09:40'),
(7, 'BackgroundPath', 'uploads/background.jpg', '2025-12-23 18:09:40'),
(8, 'SiteHeading', 'Reliefwave Foundation ( NGO)', '2025-12-23 18:09:40'),
(9, 'FontColor', '#30a103', '2025-12-23 18:09:40'),
(10, 'PrimaryColor', '#2b991a', '2025-12-24 01:27:34'),
(11, 'QRCodePath', 'uploads/qr-code.png', '2025-12-23 18:09:40'),
(12, 'UpiId', 'reliefwavengosakoli@cnrb', '2025-12-23 18:09:40'),
(13, 'AboutSubtitle', '\"Imandaar samaj seva.\"', '2025-12-23 18:09:40'),
(14, 'MissionText', '\"Hamara mission ek behtar aur samaveshi samaj banana hai jahan har zaruratmand ko sahara mile. Reliefwave Foundation sports aur education ke madhyam se yuvaon ko aage badhata hai, environment protection par kaam karta hai, social work aur animal rescue karta hai, aur needy logon ki madad imandari, karuna aur transparency ke sath ground level par karta hai.\"', '2025-12-23 18:09:40'),
(15, 'VisionText', '\"Hamara vision ek aisa samaj banana hai jahan har gareeb aur zaruratmand ko madad mile, har bachcha education aur sports se aage badhe, paryavaran aur pashuon ki raksha ho, aur sab log izzat aur suraksha ke sath jee saken.\"', '2025-12-23 18:09:40'),
(16, 'LivesImpacted', '1200', '2026-01-02 02:45:00'),
(17, 'ProjectsCompleted', '3', '2026-01-02 02:45:00'),
(18, 'CommunitiesServed', '25', '2025-12-23 18:09:40'),
(19, 'YearsOfService', '1', '2025-12-23 18:09:40'),
(20, 'HeroFontColor', '#ffff00', '2025-12-24 02:50:48'),
(21, 'ImageOpacity', '0.3', '2025-12-23 18:09:40'),
(51, 'SectionOrder_about', '3', '2025-12-23 18:23:24'),
(52, 'SectionOrder_stats', '6', '2025-12-23 18:23:24'),
(53, 'SectionOrder_programs', '1', '2025-12-23 18:23:24'),
(54, 'SectionOrder_gallery', '4', '2025-12-23 18:23:24'),
(55, 'SectionOrder_partners', '5', '2025-12-23 18:23:24');

-- --------------------------------------------------------

--
-- Table structure for table `AdminSettings_backup`
--

CREATE TABLE `AdminSettings_backup` (
  `SettingID` int(11) NOT NULL DEFAULT 0,
  `SettingKey` varchar(100) DEFAULT NULL,
  `SettingValue` text DEFAULT NULL,
  `UpdatedDate` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `AdminSettings_backup`
--

INSERT INTO `AdminSettings_backup` (`SettingID`, `SettingKey`, `SettingValue`, `UpdatedDate`) VALUES
(2, 'HeroVideoPath', '', '2025-12-23 01:57:48'),
(3, 'ThemeColor', 'gray', '2025-12-18 19:00:09'),
(4, 'SecondaryColor', '#38ef7d', '2025-12-19 04:59:05'),
(5, 'GradientType', 'linear', '2025-12-19 05:01:50'),
(6, 'GradientDirection', '135deg', '2025-12-19 05:01:50'),
(7, 'LogoPath', 'uploads/logo.jpg', '2025-12-18 19:07:52'),
(8, 'BackgroundPath', 'uploads/background.jpg', '2025-12-23 01:58:44'),
(9, 'SiteHeading', 'Reliefwave Foundation ( NGO)', '2025-12-19 04:56:39'),
(10, 'FontColor', '#30a103', '2025-12-19 04:56:39'),
(11, 'PrimaryColor', '#11998e', '2025-12-19 04:59:05'),
(44, 'QRCodePath', 'uploads/qr-code.png', '2025-12-20 07:19:50'),
(45, 'UpiId', 'reliefwavengosakoli@cnrb', '2025-12-23 06:39:50'),
(46, 'AboutSubtitle', '\"Imandaar samaj seva.\"\r\n', '2025-12-23 01:51:41'),
(47, 'MissionText', '“Hamara mission ek behtar aur samaveshi samaj banana hai jahan har zaruratmand ko sahara mile. Reliefwave Foundation sports aur education ke madhyam se yuvaon ko aage badhata hai, environment protection par kaam karta hai, social work aur animal rescue karta hai, aur needy logon ki madad imandari, karuna aur transparency ke sath ground level par karta hai.”', '2025-12-23 01:40:15'),
(48, 'VisionText', '\"Hamara vision ek aisa samaj banana hai jahan har gareeb aur zaruratmand ko madad mile, har bachcha education aur sports se aage badhe, paryavaran aur pashuon ki raksha ho, aur sab log izzat aur suraksha ke sath jee saken.\"', '2025-12-23 01:46:29'),
(49, 'LivesImpacted', '1000', '2025-12-20 09:25:00'),
(50, 'ProjectsCompleted', '2', '2025-12-23 01:40:15'),
(51, 'CommunitiesServed', '25', '2025-12-20 09:25:00'),
(52, 'YearsOfService', '1', '2025-12-23 01:40:15');

-- --------------------------------------------------------

--
-- Table structure for table `Benefits`
--

CREATE TABLE `Benefits` (
  `BenefitID` int(11) NOT NULL,
  `Title` varchar(255) DEFAULT NULL,
  `Description` text DEFAULT NULL,
  `Icon` varchar(50) DEFAULT NULL,
  `DisplayOrder` int(11) DEFAULT 0,
  `IsActive` tinyint(4) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Campaigns`
--

CREATE TABLE `Campaigns` (
  `CampaignID` int(11) NOT NULL,
  `Title` varchar(255) DEFAULT NULL,
  `Description` text DEFAULT NULL,
  `TargetAmount` decimal(10,2) DEFAULT NULL,
  `CurrentAmount` decimal(10,2) DEFAULT 0.00,
  `ImagePath` varchar(500) DEFAULT NULL,
  `IsFeatured` tinyint(4) DEFAULT 0,
  `IsActive` tinyint(4) DEFAULT 1,
  `CreatedDate` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `Campaigns`
--

INSERT INTO `Campaigns` (`CampaignID`, `Title`, `Description`, `TargetAmount`, `CurrentAmount`, `ImagePath`, `IsFeatured`, `IsActive`, `CreatedDate`) VALUES
(1, 'Education', 'A child’s classroom today shapes the nation’s tomorrow.\r\n\r\nAt ReliefWave Foundation, we believe education is not charity—it is empowerment.\r\nYour donation helps provide school supplies, scholarships, and learning opportunities to underprivileged children across India.\r\n\r\nTogether, we can turn dreams into degrees and hope into reality.\r\n?? Donate now. Be the force behind education.\r\n\r\n#ReliefWaveFoundation #EducationForAll #DonateForEducation #India #ChildrensFuture', 10000.00, 0.00, '', 1, 1, '2025-12-18 19:29:35'),
(2, 'Sports ', 'Ple donet the money ', 5000.00, 0.00, 'uploads/campaign_1766763192.jpg', 1, 0, '2025-12-26 15:33:12');

-- --------------------------------------------------------

--
-- Table structure for table `ContactInfo`
--

CREATE TABLE `ContactInfo` (
  `ContactID` int(11) NOT NULL,
  `Phone` varchar(50) DEFAULT NULL,
  `Email` varchar(255) DEFAULT NULL,
  `Address` text DEFAULT NULL,
  `FacebookURL` varchar(500) DEFAULT NULL,
  `TwitterURL` varchar(500) DEFAULT NULL,
  `InstagramURL` varchar(500) DEFAULT NULL,
  `LinkedInURL` varchar(500) DEFAULT NULL,
  `WhatsAppNumber` varchar(50) DEFAULT NULL,
  `UpdatedDate` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `ContactInfo`
--

INSERT INTO `ContactInfo` (`ContactID`, `Phone`, `Email`, `Address`, `FacebookURL`, `TwitterURL`, `InstagramURL`, `LinkedInURL`, `WhatsAppNumber`, `UpdatedDate`) VALUES
(1, '09800055564', 'reliefwave2025@gmail.com', '', '', '', 'https://www.instagram.com/reliefwave_foundation?igsh=dXllYWczM3UzOWVk', '', '919800055564', '2025-12-18 19:19:28');

-- --------------------------------------------------------

--
-- Table structure for table `Donations`
--

CREATE TABLE `Donations` (
  `DonationID` int(11) NOT NULL,
  `DonorName` varchar(255) DEFAULT NULL,
  `Email` varchar(255) DEFAULT NULL,
  `Phone` varchar(20) DEFAULT NULL,
  `Amount` decimal(10,2) DEFAULT NULL,
  `CampaignID` int(11) DEFAULT NULL,
  `DonationType` varchar(100) DEFAULT NULL,
  `Message` text DEFAULT NULL,
  `Status` varchar(50) DEFAULT 'Pending',
  `ScreenshotPath` varchar(500) DEFAULT NULL,
  `DonationDate` timestamp NULL DEFAULT current_timestamp(),
  `IsAnonymous` tinyint(4) DEFAULT 0,
  `IsActive` tinyint(4) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Events`
--

CREATE TABLE `Events` (
  `EventID` int(11) NOT NULL,
  `Title` varchar(255) DEFAULT NULL,
  `Description` text DEFAULT NULL,
  `EventDate` date DEFAULT NULL,
  `Location` varchar(255) DEFAULT NULL,
  `ShowPopup` tinyint(4) DEFAULT 0,
  `IsActive` tinyint(4) DEFAULT 1,
  `CreatedDate` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `Events`
--

INSERT INTO `Events` (`EventID`, `Title`, `Description`, `EventDate`, `Location`, `ShowPopup`, `IsActive`, `CreatedDate`) VALUES
(1, 'gau sewa', 'sakoli', '2025-12-28', 'sakoli', 1, 0, '2025-12-18 19:22:06'),
(2, 'Sport ', 'Cricket tournament ', '2025-12-28', 'Sakoli', 1, 0, '2025-12-26 15:30:42'),
(3, 'G', ': ?????????? ???????, ????? ??? ???????? ???, ?????? ???. 09 ??????', '2026-01-05', ': ?????????? ???????, ????? ??? ???????? ???, ?????? ???. 09 ??????', 1, 0, '2025-12-30 12:52:40'),
(4, 'Free Suvarnaprashan Camp', 'A free Suvarnaprashan camp is being organized for children to improve immunity and overall health.\r\n\r\nTime: 10:00 AM to 1:00 PM\r\n\r\nAge Group: 0 to 16 years\r\n\r\nOrganizer: Sonu Biragi', '2026-01-05', 'Near the water tank, Sindhu Bhavan, Lakhandoor Road, Ward No. 09, Sakoli', 1, 1, '2025-12-30 12:54:27');

-- --------------------------------------------------------

--
-- Table structure for table `Gallery`
--

CREATE TABLE `Gallery` (
  `GalleryID` int(11) NOT NULL,
  `ImagePath` varchar(500) DEFAULT NULL,
  `Title` varchar(255) DEFAULT NULL,
  `Description` text DEFAULT NULL,
  `Category` varchar(100) DEFAULT NULL,
  `UploadDate` timestamp NULL DEFAULT current_timestamp(),
  `IsActive` tinyint(4) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `Gallery`
--

INSERT INTO `Gallery` (`GalleryID`, `ImagePath`, `Title`, `Description`, `Category`, `UploadDate`, `IsActive`) VALUES
(1, 'uploads/gallery_1766118849.jpg', 'School book ', '', 'Education', '2025-12-19 04:34:11', 1),
(2, 'uploads/gallery_1766118970.jpg', 'School ', '', 'Education', '2025-12-19 04:36:14', 1),
(3, 'uploads/gallery_1766139051.jpg', 'Food', '', 'Community', '2025-12-19 10:10:54', 1),
(4, 'uploads/gallery_1766644500.jpg', 'School ', '', 'Education', '2025-12-25 06:35:03', 1),
(5, 'uploads/gallery_1767014535.jpg', 'School ', '', 'Community', '2025-12-29 13:22:15', 1),
(6, 'uploads/gallery_1767014610.jpg', 'School ', '', 'Community', '2025-12-29 13:23:33', 1);

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` int(11) NOT NULL,
  `invoice_no` varchar(20) DEFAULT NULL,
  `customer_name` varchar(255) DEFAULT NULL,
  `customer_address` text DEFAULT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `status` int(11) NOT NULL DEFAULT 1,
  `admin_id` int(11) DEFAULT NULL,
  `staff_id` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`id`, `invoice_no`, `customer_name`, `customer_address`, `customer_phone`, `total_amount`, `created_at`, `status`, `admin_id`, `staff_id`, `created_by`) VALUES
(13, '001', 'Raju kanerkar.SIR', 'Amrawati ', '9850645282', 22033.00, '2026-03-18 07:22:41', 1, NULL, NULL, NULL),
(14, '002', 'Jagdish Kapgate sir ', 'LAVARI', '9405125112', 719.00, '2026-03-18 07:32:50', 1, NULL, NULL, NULL),
(15, '003', 'Ashish Wandare SIR ', 'CHANDRAPUR ', '9423613227', 3413.00, '2026-03-18 07:51:53', 1, NULL, NULL, NULL),
(16, '004', 'Ehp. Sawan ', 'TIRODA', '8468846539', 12936.00, '2026-03-21 10:00:35', 1, NULL, NULL, NULL),
(17, '005', 'Ehp. P.S. Patil. SIR', 'PANDHARPUR ', '9766780751', 16695.00, '2026-03-22 10:26:51', 1, NULL, NULL, NULL),
(18, '006', 'Ehp. Dinesh mandal SIR ', 'GADCHIROLI ', '9049292282', 9713.00, '2026-03-23 06:43:09', 1, NULL, NULL, NULL),
(19, '007', 'Ehp. Vyankat kumbhare sir', 'Pimpalgaon', '9423375339', 11655.00, '2026-03-24 06:57:09', 1, NULL, NULL, NULL),
(20, '008', 'Ehp. Mahendra. Meshram Sir ', 'DAMBI VIRLI ', '9765625992', 1365.00, '2026-03-24 07:04:24', 1, NULL, NULL, NULL),
(21, '009', 'Ehp. Khemraj Pathode .sir ', 'TILLI', '8806261433', 3413.00, '2026-03-25 05:50:18', 1, NULL, NULL, NULL),
(22, '010', 'Ehp. Ashitp mandal SIR ', 'JARAWANDI', ' 82756 06021', 5565.00, '2026-03-27 05:16:42', 1, NULL, NULL, NULL),
(23, '011', 'Ehp. Kuldip Dhamgaye sir', 'MURRI', '96738 94119', 7067.00, '2026-03-27 05:39:34', 1, NULL, NULL, NULL),
(24, '012', 'Ehp. Prakash kotagle sir ', 'Lakhni ', '94217 11559', 2363.00, '2026-03-27 14:57:13', 1, NULL, NULL, NULL),
(25, '013', 'Ehp. Shivdas Pagar SIR', 'soygaon', '9881991150', 9576.00, '2026-03-28 07:59:45', 1, NULL, NULL, NULL),
(26, '014', 'Ehp. Yogesh Turkar SIR ', 'Thana', '98817 93410', 1386.00, '2026-03-29 08:28:50', 1, NULL, NULL, NULL),
(27, '015', 'Ehp. Biswas sir ', 'Ramket ', '79726 16783', 6668.00, '2026-03-30 05:22:22', 1, NULL, NULL, NULL),
(28, '016', 'Manohar Singh Sir', 'Kitadi', ' 85518 24919', 788.00, '2026-03-31 06:18:00', 1, NULL, NULL, NULL),
(29, '017', 'Yogesh Vaidya sir ', 'Pauni', '99234 84263', 4725.00, '2026-03-31 06:20:40', 1, NULL, NULL, NULL),
(30, '018', 'Ehp. Madhukar Baghele SIR ', 'RAJEGAON ', '9673948914', 4426.00, '2026-04-02 05:55:17', 1, NULL, NULL, NULL),
(31, '019', 'Ehp. Jagdish Kapgate sir ', 'LAVARI ', '9405125112', 1323.00, '2026-04-03 05:35:08', 1, NULL, NULL, NULL),
(32, '020', 'Ehp. Kuldip Dhamgaye Sir ', 'Gondia ', '9673894119', 315.00, '2026-04-03 05:37:47', 1, NULL, NULL, NULL),
(33, '021', 'Ehp. Rajkumar waghade Sir ', 'Navegaon ', '9049203157', 2772.00, '2026-04-04 07:57:23', 1, NULL, NULL, NULL),
(34, '022', 'Ehp. Bhadade SIR ', 'Amgaon ', '9730283126', 13104.00, '2026-04-05 04:44:11', 1, NULL, NULL, NULL),
(35, '023', 'Ehp. Khomendra Rahangdale sir ', 'HIRAPUR ', '9764098948', 8190.00, '2026-04-07 08:00:52', 1, NULL, NULL, NULL),
(36, '024', 'Ehp. Sunanda Nagpure . Madam ', 'Amgaon ', '99705 95338', 4599.00, '2026-04-08 08:33:07', 1, NULL, NULL, NULL),
(37, '025', 'Ehp. Rahul kurve SIR ', 'Soni', '76201 91175', 2804.00, '2026-04-08 08:45:51', 1, NULL, NULL, NULL),
(38, '026', 'Ehp. Omkar Shahare Sir', 'SAKOLI ', '90227 20463', 2394.00, '2026-04-10 07:20:17', 1, NULL, NULL, NULL),
(39, '027', 'Ehp.  Omkar Shahare Sir ', 'Sakoli ', '90227 20463', 1475.00, '2026-04-10 07:29:29', 1, NULL, NULL, NULL),
(40, '028', 'Ehp. G.O. Bisen sir', 'Mohagaon ', '85528 11551', 5276.00, '2026-04-14 09:16:27', 1, NULL, NULL, NULL),
(41, '029', 'Ehp. Shyamkunar. Faye Sir ', 'CHOPA ', '9421809862', 7009.00, '2026-04-14 10:11:58', 1, NULL, NULL, NULL),
(42, '030', 'Ehp. Tiwari SIR ', 'PANDRI ', '94218 08458', 1460.00, '2026-04-16 05:17:51', 1, NULL, NULL, NULL),
(43, '031', 'Ehp. Shivlal Sonwane ', 'HARDOLI ', '94218 08458', 2184.00, '2026-04-16 05:22:36', 1, NULL, NULL, NULL),
(44, '032', 'Ehp. Mende sir', 'Girola', '', 420.00, '2026-04-16 11:06:53', 1, NULL, NULL, NULL),
(45, '033', 'Ehp. Devendra chaudhary sir ', 'Lohara ', '99239 29348', 788.00, '2026-04-16 11:09:21', 1, NULL, NULL, NULL),
(46, '034', 'Ehp. Bhushan Gajade Sir ', 'SUKDI.tumsar', '.', 1208.00, '2026-04-23 13:40:56', 1, NULL, NULL, NULL),
(47, '035', 'Ehp.  Pramod Dhale SIR ', 'Dava . Toli', '9923141595', 5565.00, '2026-04-24 06:42:26', 1, NULL, NULL, NULL),
(48, '036', 'Ehp. Shailendra sir ', 'KUDWA', '9579437162', 1129.00, '2026-04-24 07:06:07', 1, NULL, NULL, NULL),
(49, '037', 'Ehp. Rajesh Hudme SIR ', 'Karhandala', ' 95455 42944 ', 9083.00, '2026-04-26 04:25:13', 1, NULL, NULL, NULL),
(50, '038', 'Ehp. Bhure sir ', 'Pachkhedi', '7875605654', 4253.00, '2026-04-26 04:37:56', 1, NULL, NULL, NULL),
(51, '039', 'Ehp. Bhadade SIR ', 'AMGAON ', '9730283126', 11466.00, '2026-04-26 04:58:46', 1, NULL, NULL, NULL),
(52, '040', 'Ehp. Devanand Meshram SIR ', 'KHARBI ', '99232 59029', 1638.00, '2026-04-26 07:12:22', 1, NULL, NULL, NULL),
(53, '041', 'Ehp. Dayamay Bala SIR ', 'Sakoli ', '93254 07833', 7245.00, '2026-04-27 13:06:46', 1, NULL, NULL, NULL),
(54, '042', 'Ehp. Kawte SIR ', ' BAJAR VIHAR', '94228 35253', 4253.00, '2026-04-28 04:59:30', 1, NULL, NULL, NULL),
(55, '043', 'Ehp. Kalidas Mahaldar SIR', 'JIPGAON', '96374 58227', 1470.00, '2026-04-28 05:03:51', 1, NULL, NULL, NULL),
(56, '044', 'Ehp. Om chaudhari SIR ', 'Lohara ', '7066841875', 6038.00, '2026-05-01 10:07:34', 1, NULL, NULL, NULL),
(57, '045', 'Ehp. Shivlal Sonwane SIR ', 'HARDOLI ', '72184 06593', 1365.00, '2026-05-01 10:45:20', 1, NULL, NULL, NULL),
(58, '046', 'Ehp.  Sailendra Bhandarkar SIR', 'PAUNI ', '9067725892', 1649.00, '2026-05-02 09:54:46', 1, NULL, NULL, NULL),
(59, '047', 'Ehp. Belkhode ', 'PALANPUR ', '8055408765', 630.00, '2026-05-02 09:59:12', 1, NULL, NULL, NULL),
(60, '048', 'Ehp. Ismail Pathan SIR ', 'CHANDRASHEKHAR ', '9689182995', 3113.00, '2026-05-05 06:33:59', 1, NULL, NULL, NULL),
(61, '049', 'Ehp. Raj  Mohurle SIR. ', 'MUL', '9922541679', 3150.00, '2026-05-05 06:40:14', 1, NULL, NULL, NULL),
(62, '050', 'Ehp. R. Nandagawadi Sir ', 'KOKA', '9823139427', 3596.00, '2026-05-10 09:40:05', 1, NULL, NULL, NULL),
(63, '051', 'Ehp. Ramkrishn. Lale Sir', 'DEWADA', '9049798636', 1071.00, '2026-05-10 09:46:56', 1, NULL, NULL, NULL),
(64, '052', 'Ehp. Vikas Ray Sir ', 'Pardi ', '9764425379', 1202.00, '2026-05-13 03:57:10', 1, NULL, NULL, NULL),
(65, '053', 'Ehp. Laxmikant Burade Sir', 'MUNDARI', '9673126357', 1502.00, '2026-05-14 08:33:29', 1, NULL, NULL, NULL),
(66, '054', 'Ehp. Triyadra Meshram SIR ', 'BETADA', '8806731793', 2741.00, '2026-05-14 08:37:49', 1, NULL, NULL, NULL),
(67, '055', 'Ehp. Bhupendra Wankhede Sir ', 'BETADA ', '9970630718', 4111.00, '2026-05-14 08:53:11', 1, NULL, NULL, NULL),
(68, '056', 'Ehp. Kishor Bisen Sir ', 'Thana', '97652 35231', 683.00, '2026-05-15 08:45:01', 1, NULL, NULL, NULL),
(69, '057', 'Ehp. Yogesh Turkar SIR ', 'Thana', ' 98817 93410', 2079.00, '2026-05-15 08:49:34', 1, NULL, NULL, NULL),
(70, '058', 'Ehp. Yogesh Vaidhe Sir ', 'PAUNI ', '9529531348', 3150.00, '2026-05-16 05:00:02', 1, NULL, NULL, NULL),
(71, '059', 'Ehp. Sawan ', 'Bhandara ', '8468846539', 3465.00, '2026-05-18 16:21:33', 1, NULL, NULL, NULL),
(79, '060', 'Ehp. Prakash kotagle sir ', 'Lakhni', '94217 11559', 210.00, '2026-05-21 05:25:15', 1, NULL, NULL, NULL),
(81, '061', 'Ehp. Ps. Lanje ', 'Wadegoan', '9326985435', 1890.00, '2026-05-21 09:30:09', 1, NULL, NULL, NULL),
(82, '062', 'Ehp. Ps. Lanje Sir ', 'Wadegoan', '9326985435', 756.00, '2026-05-21 09:32:22', 1, NULL, NULL, NULL),
(83, '063', '', '', '', 3413.00, '2026-05-23 18:32:40', 1, NULL, NULL, NULL),
(84, '064', 'Ehp. Bhadade SIR ', 'AMGAON ', '9730283126', 13079.00, '2026-05-26 10:59:28', 1, NULL, NULL, 2),
(85, '065', 'Ehp. Biswas Sir ', 'RAMTEK ', '7972616783', 9634.00, '2026-05-29 03:01:48', 1, NULL, NULL, 2),
(86, '066', 'Ehp. Sawan ', 'Tiroda ', ' 84688 46539', 4074.00, '2026-05-30 05:58:35', 1, NULL, NULL, 2),
(87, '067', 'Ehp. Kamlesh Meshram SIR ', 'KoKa', ' 9657857848', 4053.00, '2026-05-31 11:43:09', 1, NULL, NULL, 2),
(88, '068', 'Ehp. Ashit mandal SIR', 'JARAWANDI', '82756 06021', 4358.00, '2026-06-01 00:38:51', 1, NULL, NULL, 2),
(89, '069', 'Ehp. Kalidas Mahaldar SIR', 'JIPGAON', '96374 58227', 1838.00, '2026-06-01 00:44:00', 1, NULL, NULL, 2),
(90, '070', 'Ehp. Dinesh mandal SIR', 'GADCHIROLI', '9049292282', 3491.00, '2026-06-01 00:49:31', 1, NULL, NULL, 2),
(91, '071', 'Ehp. Kawte SIR', 'VEHAD ', '94228 35253', 3069.00, '2026-06-01 00:56:03', 1, NULL, NULL, 2),
(92, '072', 'Ehp. Rakesh Dakau', 'Ghot', ' 75883 39324', 1890.00, '2026-06-04 07:10:29', 1, NULL, NULL, 2),
(93, '073', 'Ehp. Anil Thalal Sir', 'Sedepar', '7262993291', 2730.00, '2026-06-05 03:41:32', 1, NULL, NULL, 1),
(94, '074', 'Ehp. B. R. Gautam sir', 'Bhagi', '9764580342', 420.00, '2026-06-05 03:47:46', 1, NULL, NULL, 1),
(95, '075', 'Ehp. P.S. Patil Sir ', 'Pandharpur ', '9766780751', 6694.00, '2026-06-08 05:52:47', 1, NULL, NULL, 2),
(96, '076', 'Ehp. Vinod Lalhare SIR', 'SALEKSA', '95796 73452', 1733.00, '2026-06-08 08:02:59', 1, NULL, NULL, 2),
(97, '077', 'Ehp. Om chaudhari SIR', 'Lohara', '7066841875', 4200.00, '2026-06-08 08:06:10', 1, NULL, NULL, 2),
(98, '078', ' Ehp. Tiwari SIR', 'PANDRI', '94218 08458', 1134.00, '2026-06-08 08:11:49', 1, NULL, NULL, 2),
(99, '079', ' Ehp. Rahul kurve SIR', 'Soni', '76201 91175', 1775.00, '2026-06-08 08:14:56', 1, NULL, NULL, 2),
(100, '080', 'Ehp. Ashok Patel Sir', 'Soudad ', '96733 94998', 3308.00, '2026-06-10 05:14:35', 1, NULL, NULL, 2),
(101, '081', 'Ehp. Raj Mohurle SIR.', 'MUL', '9922541679', 4851.00, '2026-06-13 05:55:48', 1, NULL, NULL, 2),
(102, '082', 'Ehp. Ismail Pathan SIR', 'CHANDRAPUR ', '9689182995', 6038.00, '2026-06-13 13:05:50', 1, NULL, NULL, 2),
(103, '083', 'Ehp. Dayamay Bala SIR', 'Sakoli ', '93254 07833', 11235.00, '2026-06-14 04:16:32', 1, NULL, NULL, 2),
(104, '084', 'Ehp. Dayamay Bala SIR', 'Sakoli ', '93254 07833', 1365.00, '2026-06-14 04:26:04', 1, NULL, NULL, 2),
(105, '085', 'Ehp. PratapSingh Ballarkhede SIR', 'Amgaon ', '79744 00723', 7665.00, '2026-06-14 07:32:58', 1, NULL, NULL, 2),
(106, '086', 'Ehp. Bhadade SIR', 'AMGAON ', '9730283126', 11368.00, '2026-06-16 13:53:38', 1, NULL, NULL, 2),
(107, '087', 'EHP. Sawan ', 'Tiroda', '8468846539', 4599.00, '2026-06-17 15:47:49', 1, NULL, NULL, 2),
(108, '088', ' Ehp. Rajesh Hudme SIR', 'Karhandala', '9545542944', 11760.00, '2026-06-20 02:31:35', 1, NULL, NULL, 2),
(109, '089', 'Ehp. Bhure sir', 'Pachkhedi', '7875605654', 1796.00, '2026-06-20 02:35:07', 1, NULL, NULL, 2),
(110, '090', 'Ehp. Kunal Mahaldar SIR ', 'Kitadi ', '85518 21202', 945.00, '2026-06-20 02:38:36', 1, NULL, NULL, 2),
(111, '091', 'Ehp. Bhadade SIR ', 'AMGAON ', '9730283126', 1365.00, '2026-06-22 03:49:10', 1, NULL, NULL, 2),
(112, '092', 'Ehp. Suredra Bramhakar  Sir ', '????????', '9545541520', 11760.00, '2026-06-22 07:40:02', 1, NULL, NULL, 2),
(113, '093', 'Ehp. Gulab Pardi', 'Parastola', '9764089816', 9539.00, '2026-06-22 11:25:28', 1, NULL, NULL, 2),
(114, '094', 'Ehp.  S.N Yadav Nagpur.  SIR ', 'Darekasa ', '94039 58142', 4589.00, '2026-06-26 14:06:50', 1, NULL, NULL, 2),
(115, '095', 'Ehp. Sanju Mende sir ', 'Girola ', '', 914.00, '2026-06-27 05:00:59', 1, NULL, NULL, 2),
(116, '096', 'Ehp. Hitesh Yerpude SIR ', 'MOUDA ', '91586 30812', 6941.00, '2026-06-29 05:08:41', 1, NULL, NULL, 2),
(117, '097', 'Ehp. Sailendra Bhandarkar SIR', 'PAUNI ', '9067725892', 1943.00, '2026-06-29 05:57:55', 1, NULL, NULL, 2),
(118, '098', 'Ehp. Yogesh Turkar SIR', 'THANA ', '9881793410', 945.00, '2026-07-01 10:00:42', 1, NULL, NULL, 2),
(119, '099', 'Ehp. Bopche sir ', 'Bodra', '93591 86041', 851.00, '2026-07-03 06:14:38', 1, NULL, NULL, 2),
(120, '100', 'Ehp.Ramchandra.Rahangdale.Sir', '9673392709', 'Bodra', 1890.00, '2026-07-05 14:56:45', 1, NULL, NULL, 2),
(121, '101', 'Ehp. Sadashiv Dhabe sir ', 'NANDED ', '9665971365', 24570.00, '2026-07-07 03:51:16', 1, NULL, NULL, 2),
(122, '102', 'Ehp. Ashish Wandare sir', 'CHANDRAPUR ', '9423613227', 1365.00, '2026-07-07 08:42:05', 1, NULL, NULL, 2),
(123, '103', 'Ehp. Sawan ', 'Tiroda ', '8468846539', 4242.00, '2026-07-09 07:57:37', 1, NULL, NULL, 2),
(124, '104', 'Ehp. Yashwant Naktode sir', 'Mandad', ' 99232 89092', 1391.00, '2026-07-09 09:07:16', 1, NULL, NULL, 2),
(127, '105', 'Ehp. Hemraj Tondare. Sir', 'SARADI ', '9764489637', 1286.00, '2026-07-09 09:15:46', 1, NULL, NULL, 2),
(128, '106', 'Ehp, P.S. Patil SIR', 'PANDHARPUR', '9766780751', 12338.00, '2026-07-12 02:50:37', 1, NULL, NULL, 2),
(129, '107', 'Ehp. Bhadade SIR ', 'AMGAON ', '9730283126', 11176.00, '2026-07-13 03:59:36', 1, NULL, NULL, 2),
(130, '108', 'Ehp. Kamlesh Meshram SIR', 'KOKA', '9657857848', 4940.00, '2026-07-13 06:56:14', 1, NULL, NULL, 2),
(131, '109', 'Ehp. Sanju Mende sir', 'Girola ', '', 1323.00, '2026-07-15 03:56:33', 1, NULL, NULL, 2),
(132, '110', 'Ehp. Vinod Lalhare SIR', 'SALEKSA', '9579673452', 1890.00, '2026-07-15 04:00:49', 1, NULL, NULL, 2),
(133, '111', 'Ehp. Rahul kurve SIR ', 'Soni', '7620191175', 1617.00, '2026-07-15 04:04:40', 1, NULL, NULL, 2),
(134, '112', 'Om chaudhari SIR. ', 'Lohara', '7066841875', 3255.00, '2026-07-15 04:08:50', 1, NULL, NULL, 2),
(135, '113', 'Ehp. Naresh Ragandale Sir', 'KHOLGAL', '88061 62505', 2071.00, '2026-07-15 04:18:23', 1, NULL, NULL, 2),
(136, '114', 'Ehp. Manish Chouragade Sir ', 'Salekasa ', '9923171060', 4993.00, '2026-07-15 04:29:07', 1, NULL, NULL, 2),
(137, '115', 'Ehp. Kishor Lothe SIR ', 'Gandhitola', '96238 35577', 7009.00, '2026-07-15 05:20:19', 1, NULL, NULL, 2),
(138, '116', 'Ehp.Manish Chouragade Sir', 'Salekasa ', '9923171060', 205.00, '2026-07-15 05:30:43', 1, NULL, NULL, 2),
(139, '117', 'Ehp. Tiwari SIR ', 'PANDRI ', '94218 08458', 1197.00, '2026-07-15 05:42:21', 1, NULL, NULL, 2),
(140, '118', 'Ehp. Shivlal Sonwane SIR', 'Hardoli ', '72184 06593', 1680.00, '2026-07-15 07:03:03', 1, NULL, NULL, 2),
(141, '119', 'Ehp. Sawan ', 'Tiroda ', '', 1785.00, '2026-07-18 14:48:06', 1, NULL, NULL, 2),
(142, '120', 'Ehp. Bhadade SIR ', 'AMGAON ', '97302 83126', 2300.00, '2026-07-20 08:59:52', 1, NULL, NULL, 2),
(143, '121', 'Ehp. Sanju kore SIR ', 'AMGAON ', '7798938683', 6169.00, '2026-07-22 08:52:31', 1, NULL, NULL, 2),
(144, '122', 'Ehp.Ps. Lanje SIR ', 'Wadegoan', '9326985435', 2835.00, '2026-07-22 08:55:58', 1, NULL, NULL, 2),
(145, '123', 'Ehp. Gendlal Patle SIR ', 'Purada', '9823904458', 840.00, '2026-07-22 09:14:26', 1, NULL, NULL, 2),
(146, '124', 'Ehp. Malakar sir ', 'Deori ', '77749 04717', 2079.00, '2026-07-22 09:17:08', 1, NULL, NULL, 2),
(147, '125', 'Ehp. Janardhan Donode SIR ', 'BHAMNI', '86984 45546', 1418.00, '2026-07-23 04:52:35', 1, NULL, NULL, 2),
(148, '126', 'Ehp. Savan ', 'Tiroda ', '', 7459.00, '2026-07-24 07:25:17', 1, NULL, NULL, 2),
(149, '127', 'Ehp. Kalidas Mahaldar SIR', 'JIPGAON', '96374 58227', 2678.00, '2026-07-25 03:55:15', 1, NULL, NULL, 2),
(150, '128', 'Ehp. Shadakant  Kawte SIR', 'VEHAD ', '94228 35253', 8237.00, '2026-07-25 04:03:43', 1, NULL, NULL, 2),
(151, '129', ' Ehp. Vyankat kumbhare sir', 'Pimpalgaon', '9423375339', 1733.00, '2026-07-25 04:06:18', 1, NULL, NULL, 2),
(152, '130', 'Ehp.  Pramod Dharne Sir ', 'AMIRAJHA', '083789 33318', 5492.00, '2026-07-25 04:11:20', 1, NULL, NULL, 2),
(153, '131', 'Ehp. Sun Sir', 'VYAHAD', '9823728188', 6563.00, '2026-07-25 04:20:06', 1, NULL, NULL, 2),
(154, '132', '???????? ?. ??. ???????. ?, ?. ??. ????? ?????? ??????', '????????,?.??.???????,????-??????(MH)', ' 7184299473 / 996039', 2252.00, '2026-07-27 06:47:59', 1, NULL, NULL, 2),
(155, '133', 'Ehp. Shyamkunar. Faye Sir', 'CHOPA ', '9421809862', 3182.00, '2026-07-28 11:02:34', 1, NULL, NULL, 2),
(156, '134', 'Ehp. Sawan ', 'Tiroda ', '8468846539', 4099.00, '2026-07-28 12:47:54', 1, NULL, NULL, 2),
(157, '135', 'Ehp. Gulab Pardi. Sir ', 'Parastola', '9764089816', 3465.00, '2026-07-30 04:46:22', 1, NULL, NULL, 2),
(158, '136', ' Ehp. Raj Mohurle SIR.', 'MUL ', '9922541679', 4174.00, '2026-07-31 14:04:56', 1, NULL, NULL, 2),
(159, '137', 'Ehp. Khomendra Rahangdale sir', 'HIRAPUR', '9764098948', 6090.00, '2026-08-04 03:50:53', 1, NULL, NULL, 2),
(160, '138', 'Ehp. Bhumesh Thakre SIR ', 'Goregaon ', '9764322313', 3938.00, '2026-08-04 03:55:06', 1, NULL, NULL, 2),
(161, '139', 'Ehp.Yogesh Turkar SIR', 'THANA', '9881793410', 1638.00, '2026-08-04 03:59:09', 1, NULL, NULL, 2),
(162, '140', 'Ehp. PratapSingh Ballarkhede SIR', 'AMGAON ', '79744 00723', 8216.00, '2026-08-04 04:07:02', 1, NULL, NULL, 2),
(163, '141', 'Ehp. Tiwari SIR ', 'PANDRI ', '9421808458', 1880.00, '2026-08-04 04:13:31', 1, NULL, NULL, 2),
(164, '142', 'Ehp. Dayamay Bala SIR', 'Sakoli ', '93254 07833', 2100.00, '2026-08-04 04:41:26', 1, NULL, NULL, 2),
(165, '143', ' Ehp. Ashok Patel Sir', 'Soudad', '96733 94998', 5744.00, '2026-08-05 10:50:09', 1, NULL, NULL, 2),
(166, '144', 'Ehp. Tikesh Yerpude SIR ', 'Dhamangav ', '9158630814', 2678.00, '2026-08-07 04:17:03', 1, NULL, NULL, 2),
(167, '145', 'Ehp. Sailendra Ukey SIR ', 'KUDVA', '9579437162', 536.00, '2026-08-07 07:47:23', 1, NULL, NULL, 2),
(168, '146', 'Ehp. Khushal chaudhari SIR ', 'KOHKA.  Dist:GONDIA ', '9309139344', 420.00, '2026-08-07 07:50:54', 1, NULL, NULL, 2),
(169, '147', 'Ehp. Biswas Sir', 'Ramtek ', '7972616783', 8915.00, '2026-08-09 03:52:29', 1, NULL, NULL, 2),
(170, '148', 'Ehp. Prakash kotagle sir', 'Lakhani ', '94217 11559', 735.00, '2026-08-09 04:24:36', 1, NULL, NULL, 2),
(171, '149', 'Ehp. Bhadade SIR ', 'AMGAON ', '97302 83126', 12808.00, '2026-08-10 07:20:14', 1, NULL, NULL, 2),
(172, '150', 'Ehp. Savan ', 'Tiroda ', '+918468846539', 6502.00, '2026-08-10 15:45:40', 1, NULL, NULL, 2),
(173, '151', 'Ehp. Rakesh Dakua SIR ', 'GHOT', '7588339324', 2520.00, '2026-08-11 05:51:30', 1, NULL, NULL, 2),
(174, '152', 'Ehp. Sujit Kirtania SIR ', 'Golewadi', '89758 31559', 1567.00, '2026-08-12 08:11:59', 1, NULL, NULL, 2),
(175, '153', 'Ehp. Narendra Borkar SIR ', 'Golewadi', '8975833289', 714.00, '2026-08-12 08:19:14', 1, NULL, NULL, 2),
(176, '154', 'Ehp. Manoj Biswas Sir ', 'MASAD', '96895 16695', 2027.00, '2026-08-12 08:35:30', 1, NULL, NULL, 2),
(177, '155', ' Ehp. Ashok Patel Sir', 'Soudad', '96733 94998', 2646.00, '2026-08-12 11:55:25', 1, NULL, NULL, 2),
(178, '156', 'Ehp. Rakesh Dakua SIR ', 'GHOT', '7588339324', 1890.00, '2026-08-13 07:45:05', 1, NULL, NULL, 2),
(179, '157', 'Ehp. Ganpat Bhendarkar. Sir ', 'KUMBHALI', '+919764091474', 1496.00, '2026-08-13 08:42:45', 1, NULL, NULL, 2),
(180, '158', 'Ehp. Belkhode', 'PALANDUR ', '8055408765', 630.00, '2026-08-13 08:44:52', 1, NULL, NULL, 2),
(181, '159', 'Ehp. Moharkar', 'NANDED', '9765927078', 433.00, '2026-08-15 03:50:21', 1, NULL, NULL, 2),
(182, '160', 'Ehp. Anand Bokde SIR ', 'DHARMAPURI ', '9049901093', 425.00, '2026-08-15 03:58:38', 1, NULL, NULL, 2),
(183, '161', 'Ehp. Savan ', 'Tiroda ', '+918468846539', 5292.00, '2026-08-15 14:33:20', 1, NULL, NULL, 2),
(184, '162', 'Ehp. Subhash Lambe sir ', ' RAHURI ', '9604771847', 3418.00, '2026-08-16 07:53:29', 1, NULL, NULL, 2),
(185, '163', 'Ehp. Shivdas Pagar SIR', 'SHEGAON ', '9881991150', 5880.00, '2026-08-18 14:10:11', 1, NULL, NULL, 2),
(186, '164', 'Ehp. Brijlal Rauth Sir ', 'CHICHGAD', '9699023266', 11175.00, '2026-08-19 04:53:49', 1, NULL, NULL, 2),
(187, '165', 'Ehp. Rajesh Hudme SIR', 'Karhandala', '9545542944', 11550.00, '2026-08-20 04:22:58', 1, NULL, NULL, 2),
(188, '166', 'Ehp. Mahendra. Meshram Sir', 'Irali. Dambi', '9765625992', 1365.00, '2026-08-21 04:34:01', 1, NULL, NULL, 2),
(189, '167', 'Ehp. Dr Pundalik Thombare SIR ', 'BHAMPURI', '9421725137', 315.00, '2026-08-21 04:36:28', 1, NULL, NULL, 2),
(190, '168', 'Ehp. Dr. Sudhir. Donakar Sir ', 'Wadsa', '95189 80315', 3927.00, '2026-08-21 04:40:53', 1, NULL, NULL, 2),
(191, '169', 'Ehp. Ankush Chichmalkar SIR', 'MANDED', '97641 24846', 1890.00, '2026-08-21 04:44:03', 1, NULL, NULL, 2),
(192, '170', 'Ehp. Funde Sir ', 'Tai', '7887990380', 683.00, '2026-08-21 04:46:25', 1, NULL, NULL, 2),
(193, '171', 'Ehp. Jivan Dash Sir ', 'LAKHANDUR ', '78755 23999', 473.00, '2026-08-21 04:51:49', 1, NULL, NULL, 2),
(194, '172', 'Dr. Bidhan Mallik Sir ', 'BHAMPURI ', '9823835862', 1428.00, '2026-08-21 04:55:58', 1, NULL, NULL, 2),
(195, '173', 'Eh. Savan ', 'Tiroda ', '+918468846539', 2016.00, '2026-08-22 05:31:22', 1, NULL, NULL, 2),
(201, '174', 'Ehp. P.S Patil Sir ', 'PANDARPUR ', '97667 80751', 38792.00, '2026-08-24 05:11:37', 1, NULL, NULL, 2),
(202, '175', 'Ehp. Ashish Wandare Sir ', 'Chandrapur', '9423613227', 8584.00, '2026-08-24 11:15:16', 1, NULL, NULL, 2),
(203, '176', 'Ehp. Dayamay Bala SIR', 'Sakoli ', '93254 07833', 6878.00, '2026-08-25 05:53:34', 1, NULL, NULL, 2),
(204, '177', 'Ehp. Katre Sir ', '8550929371', 'DAWWA', 3890.00, '2026-08-25 06:05:15', 1, NULL, NULL, 2),
(205, '178', 'Ehp. Ganesh kolhe Sir ', 'Gondia ', '96374 87538', 3426.00, '2026-08-25 06:10:31', 1, NULL, NULL, 2),
(206, '179', 'Ehp. Om chaudhari SIR', 'LOHARA', '7066841875', 5906.00, '2026-08-27 04:27:17', 1, NULL, NULL, 2),
(207, '180', ' Ehp. kishor Lothe SIR', 'GANDHITOLA', ' 96238 35577', 410.00, '2026-08-27 04:34:08', 1, NULL, NULL, 2),
(208, '181', 'Ehp. Bharne SIR', 'GORE', '9823995885', 1365.00, '2026-08-27 05:13:07', 1, NULL, NULL, 2),
(209, '182', 'Ehp. Gendlal Patle SIR', 'PURADA', '9823904458', 1260.00, '2026-08-27 05:16:39', 1, NULL, NULL, 2),
(210, '183', 'Ehp. Sanjay kore SIR', 'SALEKASA ', '7798938683', 3245.00, '2026-08-27 05:22:21', 1, NULL, NULL, 2),
(211, '184', ' Ehp. Sanju Mende sir', 'Girola', '', 1208.00, '2026-08-27 05:24:17', 1, NULL, NULL, 2),
(212, '185', 'Ehp. Devendra Choudhari sir ', 'LOHARA ', '99239 29348', 945.00, '2026-08-27 05:42:35', 1, NULL, NULL, 2),
(215, '186', 'Ehp. G.O. Bisen sir', 'Mohagaon', '8552811551', 3150.00, '2026-08-30 08:34:19', 1, NULL, NULL, 2),
(216, '187', 'Ehp. Biswas Sir', 'RAMTEK ', '7972616783', 1733.00, '2026-08-31 06:37:35', 1, NULL, NULL, 2),
(217, '188', 'Ehp. Lokesh Wasnik Sir ', 'Sawari Bidkar Tah-Chimur Dist-Chandrapur Pin-442906', '7620451948', 2095.00, '2026-09-01 13:53:40', 1, NULL, NULL, 2),
(218, '189', 'Ehp. Sawan ', 'Tiroda ', '8468846539', 10668.00, '2026-09-01 14:36:15', 1, NULL, NULL, 2),
(220, '190', 'Ehp.Shubham Narule Sir ', 'Post.Wahangaon Ta.Chimur Dist.Chandrapur pin,442906', '76203 37107', 1670.00, '2026-09-02 05:22:23', 1, NULL, NULL, 2),
(221, '191', 'Ehp.Viktu Gajbhiye Sir ', 'BHOTALI.Ta.Chimur Dist:Chandrpur', '9673249509', 4825.00, '2026-09-02 05:41:45', 1, NULL, NULL, 2),
(222, '192', 'Ehp. Gulab Pardi. Sir', 'Parastola', '9764089816', 5423.00, '2026-09-03 09:53:11', 1, NULL, NULL, 2),
(223, '193', 'Ehp. Rutan Lonare Sir ', 'Khambi Pimpalgaon ', '98237 61758', 1449.00, '2026-09-04 12:55:19', 1, NULL, NULL, 2),
(224, '194', 'Ehp.Mangesh Chincholkar SIR ', 'Wani,Yavatmal (MH) PIN:445304', '9049641040', 5849.00, '2026-09-05 08:30:28', 1, NULL, NULL, 2),
(225, '195', 'Ehp. Vivek Dongre Sir ', 'GUDHARI', '8830962288', 181.00, '2026-09-05 10:55:17', 1, NULL, NULL, 2),
(226, '196', 'Ehp. Shanikumar  Shambhark  SIR ', 'At,post Navtala, Tah:Chimur Dist:chandrpur', '9307702668', 1790.00, '2026-09-05 14:45:55', 1, NULL, NULL, 2),
(227, '197', 'Ehp. Vishwajeet Surkar  SIR ', 'At.Post.Bothali Ta:Chimur Dist:Chandrapur pin:442906', '9420013004', 2363.00, '2026-09-05 14:57:56', 1, NULL, NULL, 2),
(228, '198', 'Ehp. Bhadade SIR ', 'AMGAON ', '9730283126', 12565.00, '2026-09-10 04:26:14', 1, NULL, NULL, 2);

-- --------------------------------------------------------

--
-- Table structure for table `invoice_items`
--

CREATE TABLE `invoice_items` (
  `id` int(11) NOT NULL,
  `invoice_id` int(11) DEFAULT NULL,
  `product_name` varchar(255) DEFAULT NULL,
  `batch` varchar(50) DEFAULT NULL,
  `expiry` varchar(50) DEFAULT NULL,
  `capacity` varchar(50) DEFAULT NULL,
  `qty` int(11) DEFAULT NULL,
  `rate` decimal(10,2) DEFAULT NULL,
  `discount` decimal(5,2) DEFAULT NULL,
  `gross_amount` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `invoice_items`
--

INSERT INTO `invoice_items` (`id`, `invoice_id`, `product_name`, `batch`, `expiry`, `capacity`, `qty`, `rate`, `discount`, `gross_amount`, `created_at`) VALUES
(17, 13, 'Hemagyne', 'SR-03', '2029-03', '200 ml', 50, 130.00, 60.00, 6500.00, '2026-03-18 07:22:41'),
(18, 13, 'Urical-SR', 'SR-02', '2029-02', '200 ml', 30, 130.00, 60.00, 3900.00, '2026-03-18 07:22:41'),
(19, 13, 'Urical -SR', 'SR-03', '2029-03', '200ml', 20, 130.00, 60.00, 2600.00, '2026-03-18 07:22:41'),
(20, 13, 'Hemacuf', 'SR-01', '2028-01', '100 ml', 50, 80.00, 60.00, 4000.00, '2026-03-18 07:22:41'),
(21, 13, 'R-Cid', 'SR-03', '2029-03', '200 ml', 50, 130.00, 60.00, 6500.00, '2026-03-18 07:22:41'),
(22, 13, 'R-Skine Drop', 'SR-02', '2028-02', '50 ml', 50, 150.00, 60.00, 7500.00, '2026-03-18 07:22:41'),
(23, 13, 'Pile-X Drop', 'SR-01', '2028-01', '50 ml', 50, 150.00, 60.00, 7500.00, '2026-03-18 07:22:41'),
(24, 13, 'R-Tone', 'SR-01', '2029-01', '200 ml', 31, 130.00, 60.00, 4030.00, '2026-03-18 07:22:41'),
(25, 13, 'R-Tone syrup', 'SR-03 ', '2029-03', '200-ml', 19, 130.00, 60.00, 2470.00, '2026-03-18 07:22:41'),
(26, 13, 'Pain-x ointment', 'SR-05', '2027-11', '30 gm', 12, 80.00, 60.00, 960.00, '2026-03-18 07:22:41'),
(27, 13, 'Rheumo-SR', 'SR-03', '2029-03', '200 ml', 34, 130.00, 60.00, 4420.00, '2026-03-18 07:22:41'),
(28, 13, 'Rheumo -SR', 'SR-03', '2029-03', '200-ml', 16, 130.00, 60.00, 2080.00, '2026-03-18 07:22:41'),
(29, 14, 'Power-X Capsule', 'SR-03', '2028-03', '30 capsul', 2, 180.00, 50.00, 360.00, '2026-03-18 07:32:50'),
(30, 14, 'R-Warts Capsule', 'SR-03', '2028-03', '30 capsul', 2, 180.00, 50.00, 360.00, '2026-03-18 07:32:50'),
(31, 14, 'R-Artho Capsule', 'SR-03', '2028-03', '30 capsul1', 2, 180.00, 50.00, 360.00, '2026-03-18 07:32:50'),
(32, 14, 'Face Care Cream', 'SR-02', '2028-02', '50 gm', 2, 110.00, 50.00, 220.00, '2026-03-18 07:32:50'),
(33, 14, 'Urical Tab', 'SR-01', '2029-01', '25 gm', 1, 70.00, 50.00, 70.00, '2026-03-18 07:32:50'),
(34, 15, 'R-Cid', 'SR-03', '2029-03', '200 ml', 25, 130.00, 50.00, 3250.00, '2026-03-18 07:51:53'),
(35, 15, 'R-cid syrup', 'SR-03', '2029-03', '200-ml', 25, 130.00, 50.00, 3250.00, '2026-03-18 07:51:53'),
(36, 16, 'Face Care Cream', 'SR-02', '2028-02', '50 gm', 30, 110.00, 60.00, 3300.00, '2026-03-21 10:00:35'),
(37, 16, 'Chest Care Oil', 'SR-04', '2028-09', '30 ml', 30, 80.00, 60.00, 2400.00, '2026-03-21 10:00:35'),
(38, 16, 'R-Liv', 'SR-02', '2029-02', '200 ml', 30, 130.00, 60.00, 3900.00, '2026-03-21 10:00:35'),
(39, 16, 'R-Artho Capsule', 'SR-03', '2029-03', '30 capsule ', 40, 180.00, 60.00, 7200.00, '2026-03-21 10:00:35'),
(40, 16, 'R-Neuro Drop', 'SR-02', '2028-02', '50 ml', 20, 150.00, 60.00, 3000.00, '2026-03-21 10:00:35'),
(41, 16, 'R-neuro drop', 'SR-03', '2028-03', '50ml', 30, 150.00, 60.00, 4500.00, '2026-03-21 10:00:35'),
(42, 16, 'R-cid syrup', 'SR-03', '2029-03', '200-ml', 30, 130.00, 60.00, 3900.00, '2026-03-21 10:00:35'),
(43, 16, 'Rheumo -SR', 'SR-03', '2029-03', '200-ml', 20, 130.00, 60.00, 2600.00, '2026-03-21 10:00:35'),
(44, 17, 'Hemacuf Tab', 'SR-03', '2028-06', '25 gm', 20, 70.00, 50.00, 1400.00, '2026-03-22 10:26:51'),
(45, 17, 'Helcoplas Tab', 'SR-01', '2029-01', '25 gm', 20, 70.00, 50.00, 1400.00, '2026-03-22 10:26:51'),
(46, 17, 'R-Skine Tab', 'SR-02', '2029-02', '25 gm', 20, 70.00, 50.00, 1400.00, '2026-03-22 10:26:51'),
(47, 17, 'Hemo Tab', 'SR-02', '2029-02', '25 gm', 20, 70.00, 50.00, 1400.00, '2026-03-22 10:26:51'),
(48, 17, 'Anty Cold Tab', 'SR-06', '2028-12', '25 gm', 20, 70.00, 50.00, 1400.00, '2026-03-22 10:26:51'),
(49, 17, 'Feveron Tab', 'SR-02', '2029-02', '25 gm', 20, 70.00, 50.00, 1400.00, '2026-03-22 10:26:51'),
(50, 17, 'R-Power Tab', 'SR-06', '2028-12', '25 gm', 20, 70.00, 50.00, 1400.00, '2026-03-22 10:26:51'),
(51, 17, 'Anty Viral Tab', 'SR-03', '2029-02', '25 gm', 20, 70.00, 50.00, 1400.00, '2026-03-22 10:26:51'),
(52, 17, 'Ostolief Tab', 'SR-02', '2029-02', '25 gm', 20, 70.00, 50.00, 1400.00, '2026-03-22 10:26:51'),
(53, 17, 'R-Skine', 'SR-02', '2029-02', '200 ml', 20, 130.00, 50.00, 2600.00, '2026-03-22 10:26:51'),
(54, 17, 'R-Tone syrup', 'SR-03 ', '2029-03', '200-ml', 20, 130.00, 50.00, 2600.00, '2026-03-22 10:26:51'),
(55, 17, 'Heart Care', 'SR-02', '2029-02', '200 ml', 14, 130.00, 50.00, 1820.00, '2026-03-22 10:26:51'),
(56, 17, 'Heart care syrup', 'SR-03', '2029-03', '200-ml', 6, 130.00, 50.00, 780.00, '2026-03-22 10:26:51'),
(57, 17, 'Rheumo -SR', 'SR-03', '2029-03', '200-ml', 20, 130.00, 50.00, 2600.00, '2026-03-22 10:26:51'),
(58, 17, 'Diabotone', 'SR-03', '2029-03', '200 ml', 20, 130.00, 50.00, 2600.00, '2026-03-22 10:26:51'),
(59, 17, 'R-cid syrup', 'SR-03', '2029-03', '200-ml', 20, 130.00, 50.00, 2600.00, '2026-03-22 10:26:51'),
(60, 17, 'R-Artho Capsule', 'SR-03', '2029-03', '30 capsule ', 20, 180.00, 50.00, 3600.00, '2026-03-22 10:26:51'),
(61, 18, 'Hemacuf', 'SR-01', '2028-01', '100 ml', 10, 80.00, 50.00, 800.00, '2026-03-23 06:43:09'),
(62, 18, 'Heart care syrup', 'SR-03', '2029-03', '200-ml', 10, 130.00, 50.00, 1300.00, '2026-03-23 06:43:09'),
(63, 18, 'Rheumo -SR', 'SR-03', '2029-03', '200-ml', 10, 130.00, 50.00, 1300.00, '2026-03-23 06:43:09'),
(64, 18, 'R-Skine', 'SR-02', '2029-02', '200 ml', 9, 130.00, 50.00, 1170.00, '2026-03-23 06:43:09'),
(65, 18, 'R-Skin syrup', 'SR-03', '2029-03', '200-ml', 11, 130.00, 50.00, 1430.00, '2026-03-23 06:43:09'),
(66, 18, 'R-warts capsule', 'SR-03', '2029-03', '30 capsule ', 10, 180.00, 50.00, 1800.00, '2026-03-23 06:43:09'),
(67, 18, 'Pile-X Capsul', 'SR-01', '2028-01', '30 capsul', 10, 180.00, 50.00, 1800.00, '2026-03-23 06:43:09'),
(68, 18, 'Hemagyne Drop', 'SR-02', '2028-02', '50 ml', 20, 150.00, 50.00, 3000.00, '2026-03-23 06:43:09'),
(69, 18, 'Livo Care', 'RLC/JUL/25', '2027-07', '50 ml', 10, 150.00, 50.00, 1500.00, '2026-03-23 06:43:09'),
(70, 18, 'R-Skine Drop', 'SR-02', '2028-02', '50 ml', 20, 150.00, 50.00, 3000.00, '2026-03-23 06:43:09'),
(71, 18, 'R-Skine Tab', 'SR-02', '2029-02', '25 gm', 20, 70.00, 50.00, 1400.00, '2026-03-23 06:43:09'),
(72, 19, 'R-Tone syrup', 'SR-03 ', '2029-03', '200-ml', 10, 130.00, 50.00, 1300.00, '2026-03-24 06:57:09'),
(73, 19, 'R-Cid syrup', 'SR-03', '2029-03', '200-ml', 10, 130.00, 50.00, 1300.00, '2026-03-24 06:57:09'),
(74, 19, 'Rheumo -SR syrup', 'SR-03', '2029-03', '200-ml', 10, 130.00, 50.00, 1300.00, '2026-03-24 06:57:09'),
(75, 19, 'R-Liv', 'SR-02', '2029-02', '200 ml', 10, 130.00, 50.00, 1300.00, '2026-03-24 06:57:09'),
(76, 19, 'Urical -SR', 'SR-03', '2029-03', '200ml', 10, 130.00, 50.00, 1300.00, '2026-03-24 06:57:09'),
(77, 19, 'R-Lax', 'SR-04', '2027-09', '100 ml', 10, 80.00, 50.00, 800.00, '2026-03-24 06:57:09'),
(78, 19, 'Livo Care', 'RLC/JUL/25', '2027-07', '50 ml', 10, 150.00, 50.00, 1500.00, '2026-03-24 06:57:09'),
(79, 19, 'Pile-X Drop', 'SR-01', '2028-01', '50 ml', 10, 150.00, 50.00, 1500.00, '2026-03-24 06:57:09'),
(80, 19, 'R-Skin syrup', 'SR-03', '2029-03', '200-ml', 10, 130.00, 50.00, 1300.00, '2026-03-24 06:57:09'),
(81, 19, 'Ostolief Tab', 'SR-02', '2029-02', '25 gm', 10, 70.00, 50.00, 700.00, '2026-03-24 06:57:09'),
(82, 19, 'Pile-X Capsul', 'SR-01', '2028-01', '30 capsul', 10, 180.00, 50.00, 1800.00, '2026-03-24 06:57:09'),
(83, 19, 'R-Artho Capsule', 'SR-03', '2029-03', '30 capsule ', 10, 180.00, 50.00, 1800.00, '2026-03-24 06:57:09'),
(84, 19, 'R-warts capsule', 'SR-03', '2029-03', '30 capsule ', 10, 180.00, 50.00, 1800.00, '2026-03-24 06:57:09'),
(85, 19, 'Pain-X Oil', 'SR-02', '2029-02', '30 ml', 10, 80.00, 50.00, 800.00, '2026-03-24 06:57:09'),
(86, 19, 'Urical Tab', 'SR-01', '2029-01', '25 gm', 10, 70.00, 50.00, 700.00, '2026-03-24 06:57:09'),
(87, 19, 'R-neuro drop', 'SR-03', '2028-03', '50ml', 10, 150.00, 50.00, 1500.00, '2026-03-24 06:57:09'),
(88, 19, 'Spondy Drop', 'SR-01', '2028-01', '50 ml', 10, 150.00, 50.00, 1500.00, '2026-03-24 06:57:09'),
(89, 20, 'Rheumo -SR', 'SR-03', '2029-03', '200-ml', 5, 130.00, 50.00, 650.00, '2026-03-24 07:04:24'),
(90, 20, 'Rheumo -SR syrup', 'SR-03', '2029-03', '200-ml', 5, 130.00, 50.00, 650.00, '2026-03-24 07:04:24'),
(91, 20, 'R-Cid syrup', 'SR-03', '2029-03', '200-ml', 5, 130.00, 50.00, 650.00, '2026-03-24 07:04:24'),
(92, 20, 'R-Cid syrup', 'SR-03', '2029-03', '200-ml', 5, 130.00, 50.00, 650.00, '2026-03-24 07:04:24'),
(93, 21, 'R-Skin syrup', 'SR-03', '2029-03', '200-ml', 10, 130.00, 50.00, 1300.00, '2026-03-25 05:50:18'),
(94, 21, 'Rheumo -SR syrup', 'SR-03', '2029-03', '200-ml', 10, 130.00, 50.00, 1300.00, '2026-03-25 05:50:18'),
(95, 21, 'Alleres-SR Drop', 'SR-03', '2027-06', '50 ml', 6, 150.00, 50.00, 900.00, '2026-03-25 05:50:18'),
(96, 21, 'R-neuro drop', 'SR-03', '2028-03', '50ml', 10, 150.00, 50.00, 1500.00, '2026-03-25 05:50:18'),
(97, 21, 'R-Skine Drop', 'SR-02', '2028-02', '50 ml', 10, 150.00, 50.00, 1500.00, '2026-03-25 05:50:18'),
(98, 22, 'R-Skin syrup', 'SR-03', '2029-03', '200-ml', 10, 130.00, 50.00, 1300.00, '2026-03-27 05:16:42'),
(99, 22, 'R-Cid syrup', 'SR-03', '2029-03', '200-ml', 10, 130.00, 50.00, 1300.00, '2026-03-27 05:16:42'),
(100, 22, 'Hemagyne Drop', 'SR-02', '2028-02', '50 ml', 20, 150.00, 50.00, 3000.00, '2026-03-27 05:16:42'),
(101, 22, 'Power-X Capsule', 'SR-03', '2028-03', '30 capsul', 6, 180.00, 50.00, 1080.00, '2026-03-27 05:16:42'),
(102, 22, 'R-Skine Drop', 'SR-02', '2028-02', '50 ml', 6, 150.00, 50.00, 900.00, '2026-03-27 05:16:42'),
(103, 22, 'R-Artho Capsule', 'SR-03', '2029-03', '30 capsule ', 6, 180.00, 50.00, 1080.00, '2026-03-27 05:16:42'),
(104, 22, 'Ostolief Tab', 'SR-02', '2029-02', '25 gm', 6, 70.00, 50.00, 420.00, '2026-03-27 05:16:42'),
(105, 22, 'Helcoplas Tab', 'SR-01', '2029-01', '25 gm', 6, 70.00, 50.00, 420.00, '2026-03-27 05:16:42'),
(106, 22, 'Face Care Cream', 'SR-02', '2028-02', '50 gm', 10, 110.00, 50.00, 1100.00, '2026-03-27 05:16:42'),
(107, 23, 'Hemo Tab', 'SR-02', '2029-02', '25 gm', 3, 70.00, 50.00, 210.00, '2026-03-27 05:39:34'),
(108, 23, 'Hemacuf Tab', 'SR-03', '2028-06', '25 gm', 3, 70.00, 50.00, 210.00, '2026-03-27 05:39:34'),
(109, 23, 'R-Power Tab', 'SR-06', '2028-12', '25 gm', 3, 70.00, 50.00, 210.00, '2026-03-27 05:39:34'),
(110, 23, 'R-Skine Tab', 'SR-02', '2029-02', '25 gm', 3, 70.00, 50.00, 210.00, '2026-03-27 05:39:34'),
(111, 23, 'Anty Cold Tab', 'SR-06', '2028-12', '25 gm', 3, 70.00, 50.00, 210.00, '2026-03-27 05:39:34'),
(112, 23, 'Diarrhoea Tab', 'SR-02', '2029-02', '25 gm', 3, 70.00, 50.00, 210.00, '2026-03-27 05:39:34'),
(113, 23, 'Ostolief Tab', 'SR-02', '2029-02', '25 gm', 3, 70.00, 50.00, 210.00, '2026-03-27 05:39:34'),
(114, 23, 'Urical Tab', 'SR-01', '2029-01', '25 gm', 3, 70.00, 50.00, 210.00, '2026-03-27 05:39:34'),
(115, 23, 'Feveron Tab', 'SR-02', '2029-02', '25 gm', 3, 70.00, 50.00, 210.00, '2026-03-27 05:39:34'),
(116, 23, 'Anty Viral Tab', 'SR-03', '2029-02', '25 gm', 3, 70.00, 50.00, 210.00, '2026-03-27 05:39:34'),
(117, 23, 'Brain Tab', 'SR-04', '2028-09', '25 gm', 3, 70.00, 50.00, 210.00, '2026-03-27 05:39:34'),
(118, 23, 'Antispos Tab', 'SR-02', '2029-02', '25 gm', 3, 70.00, 50.00, 210.00, '2026-03-27 05:39:34'),
(119, 23, 'Helcoplas Tab', 'SR-01', '2029-01', '25 gm', 3, 70.00, 50.00, 210.00, '2026-03-27 05:39:34'),
(120, 23, 'R-Cid syrup', 'SR-03', '2029-03', '200-ml', 3, 130.00, 50.00, 390.00, '2026-03-27 05:39:34'),
(121, 23, 'Rheumo -SR syrup', 'SR-03', '2029-03', '200-ml', 3, 130.00, 50.00, 390.00, '2026-03-27 05:39:34'),
(122, 23, 'Hemagyne', 'SR-03', '2029-03', '200 ml', 3, 130.00, 50.00, 390.00, '2026-03-27 05:39:34'),
(123, 23, 'R-Tone syrup', 'SR-03 ', '2029-03', '200-ml', 3, 130.00, 50.00, 390.00, '2026-03-27 05:39:34'),
(124, 23, 'R-Skin syrup', 'SR-03', '2029-03', '200-ml', 3, 130.00, 50.00, 390.00, '2026-03-27 05:39:34'),
(125, 23, 'Urical -SR', 'SR-03', '2029-03', '200ml', 3, 130.00, 50.00, 390.00, '2026-03-27 05:39:34'),
(126, 23, 'Heart care syrup', 'SR-03', '2029-03', '200-ml', 3, 130.00, 50.00, 390.00, '2026-03-27 05:39:34'),
(127, 23, 'R-Liv', 'SR-02', '2029-02', '200 ml', 3, 130.00, 50.00, 390.00, '2026-03-27 05:39:34'),
(128, 23, 'Diabotone', 'SR-03', '2029-03', '200 ml', 3, 130.00, 50.00, 390.00, '2026-03-27 05:39:34'),
(129, 23, 'R-Lax', 'SR-04', '2027-09', '100 ml', 3, 80.00, 50.00, 240.00, '2026-03-27 05:39:34'),
(130, 23, 'R-Typho', 'SR-03', '2029-03', '100 ml', 3, 70.00, 50.00, 210.00, '2026-03-27 05:39:34'),
(131, 23, 'Hemacuf', 'SR-01', '2028-01', '100 ml', 3, 80.00, 50.00, 240.00, '2026-03-27 05:39:34'),
(132, 23, 'Hemagyne Drop', 'SR-02', '2028-02', '50 ml', 3, 150.00, 50.00, 450.00, '2026-03-27 05:39:34'),
(133, 23, 'Livo Care', 'RLC/JUL/25', '2027-07', '50 ml', 3, 150.00, 50.00, 450.00, '2026-03-27 05:39:34'),
(134, 23, 'BP Care Drop', 'SR-04', '2027-09', '50 ml', 3, 150.00, 50.00, 450.00, '2026-03-27 05:39:34'),
(135, 23, 'Pile-X Drop', 'SR-01', '2028-01', '50 ml', 3, 150.00, 50.00, 450.00, '2026-03-27 05:39:34'),
(136, 23, 'R-Skine Drop', 'SR-02', '2028-02', '50 ml', 3, 150.00, 50.00, 450.00, '2026-03-27 05:39:34'),
(137, 23, 'R-neuro drop', 'SR-03', '2028-03', '50ml', 3, 150.00, 50.00, 450.00, '2026-03-27 05:39:34'),
(138, 23, 'Spondy Drop', 'SR-01', '2028-01', '50 ml', 3, 150.00, 50.00, 450.00, '2026-03-27 05:39:34'),
(139, 23, 'Alleres-SR Drop', 'SR-03', '2027-06', '50 ml', 3, 150.00, 50.00, 450.00, '2026-03-27 05:39:34'),
(140, 23, 'Power-X Capsule', 'SR-03', '2028-03', '30 capsul', 3, 180.00, 50.00, 540.00, '2026-03-27 05:39:34'),
(141, 23, 'R-warts capsule', 'SR-03', '2029-03', '30 capsule ', 3, 180.00, 50.00, 540.00, '2026-03-27 05:39:34'),
(142, 23, 'Pile-X Capsul', 'SR-01', '2028-01', '30 capsul', 3, 180.00, 50.00, 540.00, '2026-03-27 05:39:34'),
(143, 23, 'R-Artho Capsule', 'SR-03', '2029-03', '30 capsule ', 3, 180.00, 50.00, 540.00, '2026-03-27 05:39:34'),
(144, 23, 'Face Care Cream', 'SR-02', '2028-02', '50 gm', 3, 110.00, 50.00, 330.00, '2026-03-27 05:39:34'),
(145, 23, 'Chest Care Oil', 'SR-04', '2028-09', '30 ml', 3, 80.00, 50.00, 240.00, '2026-03-27 05:39:34'),
(146, 23, 'Dant Care', 'SR-03', '2029-03', '60 gm', 2, 60.00, 50.00, 120.00, '2026-03-27 05:39:34'),
(147, 23, 'Pain-X Oil', 'SR-02', '2029-02', '30 ml', 1, 80.00, 50.00, 80.00, '2026-03-27 05:39:34'),
(148, 24, 'R-neuro drop', 'SR-03', '2028-03', '50ml', 30, 150.00, 50.00, 4500.00, '2026-03-27 14:57:13'),
(149, 25, 'Hemo Tab', 'SR-02', '2029-02', '25 gm', 5, 70.00, 60.00, 350.00, '2026-03-28 07:59:45'),
(150, 25, 'Hemacuf Tab', 'SR-03', '2028-06', '25 gm', 5, 70.00, 60.00, 350.00, '2026-03-28 07:59:45'),
(151, 25, 'R-Power Tab', 'SR-06', '2028-12', '25 gm', 5, 70.00, 60.00, 350.00, '2026-03-28 07:59:45'),
(152, 25, 'R-Skine Tab', 'SR-02', '2029-02', '25 gm', 5, 70.00, 60.00, 350.00, '2026-03-28 07:59:45'),
(153, 25, 'Anty Cold Tab', 'SR-06', '2028-12', '25 gm', 5, 70.00, 60.00, 350.00, '2026-03-28 07:59:45'),
(154, 25, 'Diarrhoea Tab', 'SR-02', '2029-02', '25 gm', 5, 70.00, 60.00, 350.00, '2026-03-28 07:59:45'),
(155, 25, 'Ostolief Tab', 'SR-02', '2029-02', '25 gm', 5, 70.00, 60.00, 350.00, '2026-03-28 07:59:45'),
(156, 25, 'Urical Tab', 'SR-01', '2029-01', '25 gm', 5, 70.00, 60.00, 350.00, '2026-03-28 07:59:45'),
(157, 25, 'Feveron Tab', 'SR-02', '2029-02', '25 gm', 5, 70.00, 60.00, 350.00, '2026-03-28 07:59:45'),
(158, 25, 'Anty Viral Tab', 'SR-03', '2029-02', '25 gm', 5, 70.00, 60.00, 350.00, '2026-03-28 07:59:45'),
(159, 25, 'Brain Tab', 'SR-04', '2028-09', '25 gm', 5, 70.00, 60.00, 350.00, '2026-03-28 07:59:45'),
(160, 25, 'Antispos Tab', 'SR-02', '2029-02', '25 gm', 5, 70.00, 60.00, 350.00, '2026-03-28 07:59:45'),
(161, 25, 'Helcoplas Tab', 'SR-01', '2029-01', '25 gm', 5, 70.00, 60.00, 350.00, '2026-03-28 07:59:45'),
(162, 25, 'R-Cid syrup', 'SR-03', '2029-03', '200-ml', 5, 130.00, 60.00, 650.00, '2026-03-28 07:59:45'),
(163, 25, 'Rheumo -SR syrup', 'SR-03', '2029-03', '200-ml', 5, 130.00, 60.00, 650.00, '2026-03-28 07:59:45'),
(164, 25, 'Hemagyne', 'SR-03', '2029-03', '200 ml', 5, 130.00, 60.00, 650.00, '2026-03-28 07:59:45'),
(165, 25, 'R-Tone syrup', 'SR-03', '2029-03', '200-ml', 5, 130.00, 60.00, 650.00, '2026-03-28 07:59:45'),
(166, 25, 'R-Skin syrup', 'SR-03', '2029-03', '200-ml', 5, 130.00, 60.00, 650.00, '2026-03-28 07:59:45'),
(167, 25, 'Urical -SR', 'SR-03', '2029-03', '200ml', 5, 130.00, 60.00, 650.00, '2026-03-28 07:59:45'),
(168, 25, 'Heart care syrup', 'SR-03', '2029-03', '200-ml', 5, 130.00, 60.00, 650.00, '2026-03-28 07:59:45'),
(169, 25, 'R-Liv', 'SR-02', '2029-02', '200 ml', 5, 130.00, 60.00, 650.00, '2026-03-28 07:59:45'),
(170, 25, 'Diabotone', 'SR-03', '2029-03', '200 ml', 5, 130.00, 60.00, 650.00, '2026-03-28 07:59:45'),
(171, 25, 'R-Lax', 'SR-04', '2027-09', '100 ml', 5, 80.00, 60.00, 400.00, '2026-03-28 07:59:45'),
(172, 25, 'R-Typho', 'SR-03', '2029-03', '100 ml', 5, 70.00, 60.00, 350.00, '2026-03-28 07:59:45'),
(173, 25, 'Hemacuf', 'SR-01', '2028-01', '100 ml', 5, 80.00, 60.00, 400.00, '2026-03-28 07:59:45'),
(174, 25, 'Hemagyne Drop', 'SR-02', '2028-02', '50 ml', 5, 150.00, 60.00, 750.00, '2026-03-28 07:59:45'),
(175, 25, 'Livo Care', 'RLC/JUL/25', '2027-07', '50 ml', 5, 150.00, 60.00, 750.00, '2026-03-28 07:59:45'),
(176, 25, 'BP Care Drop', 'SR-04', '2027-09', '50 ml', 5, 150.00, 60.00, 750.00, '2026-03-28 07:59:45'),
(177, 25, 'Pile-X Drop', 'SR-01', '2028-01', '50 ml', 5, 150.00, 60.00, 750.00, '2026-03-28 07:59:45'),
(178, 25, 'R-Skine Drop', 'SR-02', '2028-02', '50 ml', 5, 150.00, 60.00, 750.00, '2026-03-28 07:59:45'),
(179, 25, 'R-neuro drop', 'SR-03', '2028-03', '50ml', 5, 150.00, 60.00, 750.00, '2026-03-28 07:59:45'),
(180, 25, 'Spondy Drop', 'SR-01', '2028-01', '50 ml', 5, 150.00, 60.00, 750.00, '2026-03-28 07:59:45'),
(181, 25, 'Alleres-SR Drop', 'SR-03', '2027-06', '50 ml', 5, 150.00, 60.00, 750.00, '2026-03-28 07:59:45'),
(182, 25, 'Power-X Capsule', 'SR-03', '2028-03', '30 capsul', 5, 180.00, 60.00, 900.00, '2026-03-28 07:59:45'),
(183, 25, 'R-warts capsule', 'SR-03', '2029-03', '30 capsule ', 5, 180.00, 60.00, 900.00, '2026-03-28 07:59:45'),
(184, 25, 'Pile-X Capsul', 'SR-01', '2028-01', '30 capsul', 5, 180.00, 60.00, 900.00, '2026-03-28 07:59:45'),
(185, 25, 'R-Artho Capsule', 'SR-03', '2029-03', '30 capsule ', 5, 180.00, 60.00, 900.00, '2026-03-28 07:59:45'),
(186, 25, 'Face Care Cream', 'SR-02', '2028-02', '50 gm', 5, 110.00, 60.00, 550.00, '2026-03-28 07:59:45'),
(187, 25, 'Chest Care Oil', 'SR-04', '2028-09', '30 ml', 5, 80.00, 60.00, 400.00, '2026-03-28 07:59:45'),
(188, 25, 'Dant Care', 'SR-03', '2029-03', '60 gm', 5, 60.00, 60.00, 300.00, '2026-03-28 07:59:45'),
(189, 25, 'Pain-x oil', 'SR-03', '2029-03', '30ml', 5, 80.00, 60.00, 400.00, '2026-03-28 07:59:45'),
(190, 26, 'Pile-X Capsul', 'SR-01', '2028-01', '30 capsul', 10, 180.00, 50.00, 1800.00, '2026-03-29 08:28:50'),
(191, 26, 'R-Typho', 'SR-03', '2029-03', '100 ml', 12, 70.00, 50.00, 840.00, '2026-03-29 08:28:50'),
(192, 27, 'R-Skin syrup', 'SR-03', '2029-03', '200-ml', 2, 130.00, 50.00, 260.00, '2026-03-30 05:22:22'),
(193, 27, 'R-Skin syrup', 'SR-03', '2029-03', '200-ml', 28, 130.00, 50.00, 3640.00, '2026-03-30 05:22:22'),
(194, 27, 'R-Skine Tab', 'SR-02', '2029-02', '25 gm', 30, 70.00, 50.00, 2100.00, '2026-03-30 05:22:22'),
(195, 27, 'R-Skine Drop', 'SR-02', '2028-02', '50 ml', 30, 150.00, 50.00, 4500.00, '2026-03-30 05:22:22'),
(196, 27, 'Face care cream', 'SR-03', '2028-03', '50 gram', 20, 110.00, 50.00, 2200.00, '2026-03-30 05:22:22'),
(197, 28, 'R-Typho', 'SR-03', '2029-03', '100 ml', 10, 70.00, 50.00, 700.00, '2026-03-31 06:18:00'),
(198, 28, 'Hemacuf syrup', 'SR-03', '2028-03', '100 ml', 2, 80.00, 50.00, 160.00, '2026-03-31 06:18:00'),
(199, 28, 'Hemacuf', 'SR-01', '2028-01', '100 ml', 8, 80.00, 50.00, 640.00, '2026-03-31 06:18:00'),
(200, 29, 'R-neuro drop', 'SR-03', '2028-03', '50ml', 20, 150.00, 50.00, 3000.00, '2026-03-31 06:20:40'),
(201, 29, 'R-Skine Drop', 'SR-02', '2028-02', '50 ml', 20, 150.00, 50.00, 3000.00, '2026-03-31 06:20:40'),
(202, 29, 'Hemagyne Drop', 'SR-02', '2028-02', '50 ml', 20, 150.00, 50.00, 3000.00, '2026-03-31 06:20:40'),
(203, 30, 'R-Skin syrup', 'SR-03', '2029-03', '200-ml', 6, 130.00, 50.00, 780.00, '2026-04-02 05:55:17'),
(204, 30, 'R-Skine Tab', 'SR-02', '2029-02', '25 gm', 6, 70.00, 50.00, 420.00, '2026-04-02 05:55:17'),
(205, 30, 'R-Skine Drop', 'SR-02', '2028-02', '50 ml', 5, 150.00, 50.00, 750.00, '2026-04-02 05:55:17'),
(206, 30, 'Face Care Cream', 'SR-02', '2028-02', '50 gm', 6, 110.00, 50.00, 660.00, '2026-04-02 05:55:17'),
(207, 30, 'Rheumo -SR syrup', 'SR-03', '2029-03', '200-ml', 6, 130.00, 50.00, 780.00, '2026-04-02 05:55:17'),
(208, 30, 'Spondy Drop', 'SR-01', '2028-01', '50 ml', 6, 150.00, 50.00, 900.00, '2026-04-02 05:55:17'),
(209, 30, 'R-Artho Capsule', 'SR-03', '2029-03', '30 capsule ', 2, 180.00, 50.00, 360.00, '2026-04-02 05:55:17'),
(210, 30, 'R-Artho Capsule', 'SR-03', '2028-03', '30 capsule ', 4, 180.00, 50.00, 720.00, '2026-04-02 05:55:17'),
(211, 30, 'Pain-X Oil', 'SR-02', '2029-02', '30 ml', 1, 80.00, 50.00, 80.00, '2026-04-02 05:55:17'),
(212, 30, 'Pain-x oil', 'SR-03', '2029-03', '30ml', 5, 80.00, 50.00, 400.00, '2026-04-02 05:55:17'),
(213, 30, 'R-neuro drop', 'SR-03', '2028-03', '50ml', 6, 150.00, 50.00, 900.00, '2026-04-02 05:55:17'),
(214, 30, 'Urical Tab', 'SR-01', '2029-01', '25 gm', 6, 70.00, 50.00, 420.00, '2026-04-02 05:55:17'),
(215, 30, 'Diarrhoea Tab', 'SR-02', '2029-02', '25 gm', 6, 70.00, 50.00, 420.00, '2026-04-02 05:55:17'),
(216, 30, 'Helcoplas Tab', 'SR-01', '2029-01', '25 gm', 6, 70.00, 50.00, 420.00, '2026-04-02 05:55:17'),
(217, 30, 'Feveron Tab', 'SR-02', '2029-02', '25 gm', 6, 70.00, 50.00, 420.00, '2026-04-02 05:55:17'),
(218, 31, 'R-Warts Capsule', 'SR-03', '2028-03', '30 capsul', 5, 180.00, 50.00, 900.00, '2026-04-03 05:35:08'),
(219, 31, 'R-Artho Capsule', 'SR-03', '2028-03', '30 capsule ', 5, 180.00, 50.00, 900.00, '2026-04-03 05:35:08'),
(220, 31, 'Pile-x capsule', 'SR-03', '2029-04', '30 capsule ', 4, 180.00, 50.00, 720.00, '2026-04-03 05:35:08'),
(221, 32, 'Dant Care', 'SR-03', '2029-03', '60 gm', 10, 60.00, 50.00, 600.00, '2026-04-03 05:37:47'),
(222, 33, 'Hemagyne Drop', 'SR-02', '2028-02', '50 ml', 12, 150.00, 50.00, 1800.00, '2026-04-04 07:57:23'),
(223, 33, 'R-neuro drop', 'SR-03', '2028-03', '50ml', 12, 150.00, 50.00, 1800.00, '2026-04-04 07:57:23'),
(224, 33, 'Urical Tab', 'SR-01', '2029-01', '25 gm', 12, 70.00, 50.00, 840.00, '2026-04-04 07:57:23'),
(225, 33, 'R-Power Tab', 'SR-06', '2028-12', '25 gm', 12, 70.00, 50.00, 840.00, '2026-04-04 07:57:23'),
(226, 34, 'Anty Viral Tab', 'SR-03', '2029-02', '25 gm', 20, 70.00, 40.00, 1400.00, '2026-04-05 04:44:11'),
(227, 34, 'Anty Cold Tab', 'SR-06', '2028-12', '25 gm', 20, 70.00, 40.00, 1400.00, '2026-04-05 04:44:11'),
(228, 34, 'Antispos Tab', 'SR-02', '2029-02', '25 gm', 20, 70.00, 40.00, 1400.00, '2026-04-05 04:44:11'),
(229, 34, 'Helcoplas Tab', 'SR-01', '2029-01', '25 gm', 20, 70.00, 40.00, 1400.00, '2026-04-05 04:44:11'),
(230, 34, 'Hemo Tab', 'SR-02', '2029-02', '25 gm', 20, 70.00, 40.00, 1400.00, '2026-04-05 04:44:11'),
(231, 34, 'Hemacuf Tab', 'SR-03', '2028-06', '25 gm', 20, 70.00, 40.00, 1400.00, '2026-04-05 04:44:11'),
(232, 34, 'Urical Tab', 'SR-01', '2029-01', '25 gm', 20, 70.00, 40.00, 1400.00, '2026-04-05 04:44:11'),
(233, 34, 'Ostolief Tab', 'SR-02', '2029-02', '25 gm', 20, 70.00, 40.00, 1400.00, '2026-04-05 04:44:11'),
(234, 34, 'Brain Tab', 'SR-04', '2028-09', '25 gm', 20, 70.00, 40.00, 1400.00, '2026-04-05 04:44:11'),
(235, 34, 'R-Skine Tab', 'SR-02', '2029-02', '25 gm', 20, 70.00, 40.00, 1400.00, '2026-04-05 04:44:11'),
(236, 34, 'R-Power Tab', 'SR-06', '2028-12', '25 gm', 20, 70.00, 40.00, 1400.00, '2026-04-05 04:44:11'),
(237, 34, 'Feveron Tab', 'SR-02', '2029-02', '25 gm', 20, 70.00, 40.00, 1400.00, '2026-04-05 04:44:11'),
(238, 34, 'Diarrhoea Tab', 'SR-02', '2029-02', '25 gm', 20, 70.00, 40.00, 1400.00, '2026-04-05 04:44:11'),
(239, 34, 'Rheumo -SR syrup', 'SR-03', '2029-04', '200-ml', 20, 130.00, 40.00, 2600.00, '2026-04-05 04:44:11'),
(240, 35, 'Rheumo -SR syrup', 'SR-03', '2029-04', '200-ml', 5, 130.00, 50.00, 650.00, '2026-04-07 08:00:52'),
(241, 35, 'Rheumo -SR syrup', 'SR-04', '2029-04', '200-ml', 15, 130.00, 50.00, 1950.00, '2026-04-07 08:00:52'),
(242, 35, 'Hemagyne', 'SR-03', '2029-03', '200 ml', 2, 130.00, 50.00, 260.00, '2026-04-07 08:00:52'),
(243, 35, 'Hemagyne syrup', 'SR-03', '2029-03', '200-ml', 18, 130.00, 50.00, 2340.00, '2026-04-07 08:00:52'),
(244, 35, 'R-Lax', 'SR-04', '2027-09', '100 ml', 20, 80.00, 50.00, 1600.00, '2026-04-07 08:00:52'),
(245, 35, 'Spondy Drop', 'SR-01', '2028-01', '50 ml', 20, 150.00, 50.00, 3000.00, '2026-04-07 08:00:52'),
(246, 35, 'R-neuro drop', 'SR-03', '2028-03', '50ml', 20, 150.00, 50.00, 3000.00, '2026-04-07 08:00:52'),
(247, 35, 'Feveron Tab', 'SR-02', '2029-02', '25 gm', 20, 70.00, 50.00, 1400.00, '2026-04-07 08:00:52'),
(248, 35, 'R-Power Tab', 'SR-06', '2028-12', '25 gm', 20, 70.00, 50.00, 1400.00, '2026-04-07 08:00:52'),
(249, 36, 'Hemagyne Drop', 'SR-02', '2028-02', '50 ml', 12, 150.00, 50.00, 1800.00, '2026-04-08 08:33:07'),
(250, 36, 'Heart care syrup', 'SR-03', '2029-03', '200-ml', 12, 130.00, 50.00, 1560.00, '2026-04-08 08:33:07'),
(251, 36, 'R-Typho', 'SR-03', '2029-03', '100 ml', 12, 70.00, 50.00, 840.00, '2026-04-08 08:33:07'),
(252, 36, 'Pile-x capsule', 'SR-03', '2029-04', '30 capsule ', 12, 180.00, 50.00, 2160.00, '2026-04-08 08:33:07'),
(253, 36, 'R-Artho Capsule', 'SR-03', '2028-03', '30 capsule ', 12, 180.00, 50.00, 2160.00, '2026-04-08 08:33:07'),
(254, 36, 'R-Lax', 'SR-04', '2027-09', '100 ml', 3, 80.00, 50.00, 240.00, '2026-04-08 08:33:07'),
(255, 37, 'Hemagyne syrup', 'SR-03', '2029-03', '200-ml', 6, 130.00, 50.00, 780.00, '2026-04-08 08:45:51'),
(256, 37, 'R-Artho Capsule', 'SR-03', '2029-04', '30 capsule ', 6, 180.00, 50.00, 1080.00, '2026-04-08 08:45:51'),
(257, 37, 'Pain-x oil', 'SR-03', '2029-03', '30ml', 6, 80.00, 50.00, 480.00, '2026-04-08 08:45:51'),
(258, 37, 'Face care cream', 'SR-04', '2028-04', '50 gram', 6, 110.00, 50.00, 660.00, '2026-04-08 08:45:51'),
(259, 37, 'R-Cid syrup', 'SR-03', '2029-03', '200-ml', 6, 130.00, 50.00, 780.00, '2026-04-08 08:45:51'),
(260, 37, 'Urical -SR', 'SR-03', '2029-03', '200ml', 6, 130.00, 50.00, 780.00, '2026-04-08 08:45:51'),
(261, 37, 'Rheumo -SR syrup', 'SR-04', '2029-04', '200-ml', 6, 130.00, 50.00, 780.00, '2026-04-08 08:45:51'),
(262, 38, 'R-Skin syrup', 'SR-03', '2029-03', '200-ml', 3, 130.00, 50.00, 390.00, '2026-04-10 07:20:17'),
(263, 38, 'R-Skine Tab', 'SR-02', '2029-02', '25 gm', 3, 70.00, 50.00, 210.00, '2026-04-10 07:20:17'),
(264, 38, 'Rheumo -SR syrup', 'SR-04', '2029-04', '200-ml', 3, 130.00, 50.00, 390.00, '2026-04-10 07:20:17'),
(265, 38, 'Ostolief Tab', 'SR-02', '2029-02', '25 gm', 3, 70.00, 50.00, 210.00, '2026-04-10 07:20:17'),
(266, 38, 'Urical Tab', 'SR-01', '2029-01', '25 gm', 3, 70.00, 50.00, 210.00, '2026-04-10 07:20:17'),
(267, 38, 'Spondy drop', 'SR-05', '2027-06', '50ml', 3, 150.00, 50.00, 450.00, '2026-04-10 07:20:17'),
(268, 38, 'R-neuro drop', 'SR-03', '2028-03', '50ml', 3, 150.00, 50.00, 450.00, '2026-04-10 07:20:17'),
(269, 38, 'R-Artho Capsule', 'SR-03', '2029-04', '30 capsule ', 3, 180.00, 50.00, 540.00, '2026-04-10 07:20:17'),
(270, 38, 'Pain-x oil', 'SR-03', '2029-03', '30ml', 3, 80.00, 50.00, 240.00, '2026-04-10 07:20:17'),
(271, 38, 'R-Cid syrup', 'SR-03', '2029-03', '200-ml', 3, 130.00, 50.00, 390.00, '2026-04-10 07:20:17'),
(272, 38, 'R-Typho', 'SR-03', '2029-03', '100 ml', 3, 70.00, 50.00, 210.00, '2026-04-10 07:20:17'),
(273, 38, 'Hemacuf Tab', 'SR-03', '2028-06', '25 gm', 3, 70.00, 50.00, 210.00, '2026-04-10 07:20:17'),
(274, 38, 'Hemacuf syrup', 'SR-03', '2028-03', '100 ml', 3, 80.00, 50.00, 240.00, '2026-04-10 07:20:17'),
(275, 38, 'Feveron Tab', 'SR-02', '2029-02', '25 gm', 3, 70.00, 50.00, 210.00, '2026-04-10 07:20:17'),
(276, 38, 'Anty Cold Tab', 'SR-06', '2028-12', '25 gm', 3, 70.00, 50.00, 210.00, '2026-04-10 07:20:17'),
(277, 39, 'Urical -SR', 'SR-03', '2029-03', '200ml', 1, 130.00, 50.00, 130.00, '2026-04-10 07:29:29'),
(278, 39, 'Hemagyne syrup', 'SR-03', '2029-03', '200-ml', 1, 130.00, 50.00, 130.00, '2026-04-10 07:29:29'),
(279, 39, 'Heart care syrup', 'SR-03', '2029-03', '200-ml', 1, 130.00, 50.00, 130.00, '2026-04-10 07:29:29'),
(280, 39, 'R-Tone syrup', 'SR-03', '2029-03', '200-ml', 1, 130.00, 50.00, 130.00, '2026-04-10 07:29:29'),
(281, 39, 'Diabotone', 'SR-03', '2029-03', '200 ml', 1, 130.00, 50.00, 130.00, '2026-04-10 07:29:29'),
(282, 39, 'R-Liv', 'SR-02', '2029-02', '200 ml', 1, 130.00, 50.00, 130.00, '2026-04-10 07:29:29'),
(283, 39, 'Hemagyne Drop', 'SR-02', '2028-02', '50 ml', 1, 150.00, 50.00, 150.00, '2026-04-10 07:29:29'),
(284, 39, 'Pile-X Drop', 'SR-01', '2028-01', '50 ml', 1, 150.00, 50.00, 150.00, '2026-04-10 07:29:29'),
(285, 39, 'Livo Care', 'RLC/JUL/25', '2027-07', '50 ml', 1, 150.00, 50.00, 150.00, '2026-04-10 07:29:29'),
(286, 39, 'BP care drop', 'SR-01', '2027-06', '50ml', 1, 150.00, 50.00, 150.00, '2026-04-10 07:29:29'),
(287, 39, 'Alleres-SR Drop', 'SR-03', '2027-06', '50 ml', 1, 150.00, 50.00, 150.00, '2026-04-10 07:29:29'),
(288, 39, 'Power-X Capsule', 'SR-03', '2028-03', '30 capsul', 1, 180.00, 50.00, 180.00, '2026-04-10 07:29:29'),
(289, 39, 'R-Warts Capsule', 'SR-03', '2028-03', '30 capsul', 1, 180.00, 50.00, 180.00, '2026-04-10 07:29:29'),
(290, 39, 'Pile-x capsule', 'SR-03', '2029-04', '30 capsule ', 1, 180.00, 50.00, 180.00, '2026-04-10 07:29:29'),
(291, 39, 'Hemo Tab', 'SR-02', '2029-02', '25 gm', 1, 70.00, 50.00, 70.00, '2026-04-10 07:29:29'),
(292, 39, 'Helcoplas Tab', 'SR-01', '2029-01', '25 gm', 1, 70.00, 50.00, 70.00, '2026-04-10 07:29:29'),
(293, 39, 'Diarrhoea Tab', 'SR-02', '2029-02', '25 gm', 1, 70.00, 50.00, 70.00, '2026-04-10 07:29:29'),
(294, 39, 'Antispos Tab', 'SR-02', '2029-02', '25 gm', 1, 70.00, 50.00, 70.00, '2026-04-10 07:29:29'),
(295, 39, 'Anty Viral Tab', 'SR-03', '2029-02', '25 gm', 1, 70.00, 50.00, 70.00, '2026-04-10 07:29:29'),
(296, 39, 'Brain Tab', 'SR-04', '2028-09', '25 gm', 1, 70.00, 50.00, 70.00, '2026-04-10 07:29:29'),
(297, 39, 'R-Power Tab', 'SR-06', '2028-12', '25 gm', 1, 70.00, 50.00, 70.00, '2026-04-10 07:29:29'),
(298, 39, 'Chest Care Oil', 'SR-04', '2028-09', '30 ml', 1, 80.00, 50.00, 80.00, '2026-04-10 07:29:29'),
(299, 39, 'Face Care Cream', 'SR-02', '2028-02', '50 gm', 1, 110.00, 50.00, 110.00, '2026-04-10 07:29:29'),
(300, 39, 'Dant Care', 'SR-03', '2029-03', '60 gm', 1, 60.00, 50.00, 60.00, '2026-04-10 07:29:29'),
(301, 40, 'Urical -SR', 'SR-03', '2029-03', '200ml', 5, 130.00, 50.00, 650.00, '2026-04-14 09:16:27'),
(302, 40, 'R-Liv', 'SR-02', '2029-02', '200 ml', 5, 130.00, 50.00, 650.00, '2026-04-14 09:16:27'),
(303, 40, 'R-Tone syrup', 'SR-03', '2029-03', '200-ml', 5, 130.00, 50.00, 650.00, '2026-04-14 09:16:27'),
(304, 40, 'R-Cid syrup', 'SR-03', '2029-03', '200-ml', 5, 130.00, 50.00, 650.00, '2026-04-14 09:16:27'),
(305, 40, 'Heart care syrup', 'SR-03', '2029-03', '200-ml', 5, 130.00, 50.00, 650.00, '2026-04-14 09:16:27'),
(306, 40, 'Diabotone syrup', 'SR-03', '2029-04', '200-ml', 5, 130.00, 50.00, 650.00, '2026-04-14 09:16:27'),
(307, 40, 'Rheumo -SR syrup', 'SR-04', '2029-04', '200-ml', 5, 130.00, 50.00, 650.00, '2026-04-14 09:16:27'),
(308, 40, 'Hemagyne syrup', 'SR-04', '2029-04', '200-ml', 5, 130.00, 50.00, 650.00, '2026-04-14 09:16:27'),
(309, 40, 'R-Skin syrup', 'SR-03', '2029-03', '200-ml', 5, 130.00, 50.00, 650.00, '2026-04-14 09:16:27'),
(310, 40, 'R-Typho', 'SR-03', '2029-03', '100 ml', 5, 70.00, 50.00, 350.00, '2026-04-14 09:16:27'),
(311, 40, 'Hemacuf syrup', 'SR-03', '2028-03', '100 ml', 5, 80.00, 50.00, 400.00, '2026-04-14 09:16:27'),
(312, 40, 'Power -x capsule', 'SR-04', '2029-04', '25 capsule ', 5, 150.00, 50.00, 750.00, '2026-04-14 09:16:27'),
(313, 40, 'R-Artho Capsule', 'SR-03', '2029-04', '30 capsule ', 5, 180.00, 50.00, 900.00, '2026-04-14 09:16:27'),
(314, 40, 'R-Warts Capsule', 'SR-03', '2028-03', '30 capsul', 5, 180.00, 50.00, 900.00, '2026-04-14 09:16:27'),
(315, 40, 'Pile-x capsule', 'SR-03', '2029-04', '30 capsule ', 5, 180.00, 50.00, 900.00, '2026-04-14 09:16:27'),
(316, 41, 'Brain Tab', 'SR-04', '2028-09', '25 gm', 3, 70.00, 50.00, 210.00, '2026-04-14 10:11:58'),
(317, 41, 'Anty Viral Tab', 'SR-03', '2029-02', '25 gm', 3, 70.00, 50.00, 210.00, '2026-04-14 10:11:58'),
(318, 41, 'Antispos Tab', 'SR-02', '2029-02', '25 gm', 3, 70.00, 50.00, 210.00, '2026-04-14 10:11:58'),
(319, 41, 'Hemo Tab', 'SR-02', '2029-02', '25 gm', 3, 70.00, 50.00, 210.00, '2026-04-14 10:11:58'),
(320, 41, 'Feveron Tab', 'SR-02', '2029-02', '25 gm', 3, 70.00, 50.00, 210.00, '2026-04-14 10:11:58'),
(321, 41, 'R-Skine Tab', 'SR-02', '2029-02', '25 gm', 3, 70.00, 50.00, 210.00, '2026-04-14 10:11:58'),
(322, 41, 'Helcoplas Tab', 'SR-01', '2029-01', '25 gm', 3, 70.00, 50.00, 210.00, '2026-04-14 10:11:58'),
(323, 41, 'Ostolief Tab', 'SR-02', '2029-02', '25 gm', 3, 70.00, 50.00, 210.00, '2026-04-14 10:11:58'),
(324, 41, 'Diarrhoea Tab', 'SR-02', '2029-02', '25 gm', 3, 70.00, 50.00, 210.00, '2026-04-14 10:11:58'),
(325, 41, 'R-Power Tab', 'SR-06', '2028-12', '25 gm', 3, 70.00, 50.00, 210.00, '2026-04-14 10:11:58'),
(326, 41, 'Anty Cold Tab', 'SR-06', '2028-12', '25 gm', 3, 70.00, 50.00, 210.00, '2026-04-14 10:11:58'),
(327, 41, 'Urical Tab', 'SR-01', '2029-01', '25 gm', 3, 70.00, 50.00, 210.00, '2026-04-14 10:11:58'),
(328, 41, 'Hemacuf Tab', 'SR-03', '2028-06', '25 gm', 3, 70.00, 50.00, 210.00, '2026-04-14 10:11:58'),
(329, 41, 'Hemagyne syrup', 'SR-04', '2029-04', '200-ml', 3, 130.00, 50.00, 390.00, '2026-04-14 10:11:58'),
(330, 41, 'Heart care syrup', 'SR-03', '2029-03', '200-ml', 3, 130.00, 50.00, 390.00, '2026-04-14 10:11:58'),
(331, 41, 'R-Liv', 'SR-02', '2029-02', '200 ml', 3, 130.00, 50.00, 390.00, '2026-04-14 10:11:58'),
(332, 41, 'R-Tone syrup', 'SR-03', '2029-03', '200-ml', 3, 130.00, 50.00, 390.00, '2026-04-14 10:11:58'),
(333, 41, 'Diabotone syrup', 'SR-03', '2029-04', '200-ml', 3, 130.00, 50.00, 390.00, '2026-04-14 10:11:58'),
(334, 41, 'R-Skin syrup', 'SR-03', '2029-03', '200-ml', 3, 130.00, 50.00, 390.00, '2026-04-14 10:11:58'),
(335, 41, 'R-Cid syrup', 'SR-03', '2029-03', '200-ml', 3, 130.00, 50.00, 390.00, '2026-04-14 10:11:58'),
(336, 41, 'Rheumo -SR syrup', 'SR-04', '2029-04', '200-ml', 3, 130.00, 50.00, 390.00, '2026-04-14 10:11:58'),
(337, 41, 'R-Typho', 'SR-03', '2029-03', '100 ml', 3, 70.00, 50.00, 210.00, '2026-04-14 10:11:58'),
(338, 41, 'Hemacuf syrup', 'SR-03', '2028-03', '100 ml', 3, 80.00, 50.00, 240.00, '2026-04-14 10:11:58'),
(339, 41, 'Urical -SR', 'SR-03', '2029-03', '200ml', 3, 130.00, 50.00, 390.00, '2026-04-14 10:11:58'),
(340, 41, 'Hemagyne Drop', 'SR-02', '2028-02', '50 ml', 3, 150.00, 50.00, 450.00, '2026-04-14 10:11:58'),
(341, 41, 'R-neuro drop', 'SR-03', '2028-03', '50ml', 3, 150.00, 50.00, 450.00, '2026-04-14 10:11:58'),
(342, 41, 'Alleres-SR Drop', 'SR-03', '2027-06', '50 ml', 3, 150.00, 50.00, 450.00, '2026-04-14 10:11:58'),
(343, 41, 'BP care drop', 'SR-01', '2027-06', '50ml', 3, 150.00, 50.00, 450.00, '2026-04-14 10:11:58'),
(344, 41, 'R-skin drop', 'SR-04', '2028-04', '50ml', 3, 150.00, 50.00, 450.00, '2026-04-14 10:11:58'),
(345, 41, 'Livo Care', 'RLC/JUL/25', '2027-07', '50 ml', 3, 150.00, 50.00, 450.00, '2026-04-14 10:11:58'),
(346, 41, 'Spondy drop', 'SR-05', '2027-06', '50ml', 3, 150.00, 50.00, 450.00, '2026-04-14 10:11:58'),
(347, 41, 'Pile-X Drop', 'SR-01', '2028-01', '50 ml', 3, 150.00, 50.00, 450.00, '2026-04-14 10:11:58'),
(348, 41, 'R-Artho Capsule', 'SR-03', '2029-04', '30 capsule ', 3, 180.00, 50.00, 540.00, '2026-04-14 10:11:58'),
(349, 41, 'R-Warts Capsule', 'SR-03', '2028-03', '30 capsul', 3, 180.00, 50.00, 540.00, '2026-04-14 10:11:58'),
(350, 41, 'Pile-x capsule', 'SR-03', '2029-04', '30 capsule ', 3, 180.00, 50.00, 540.00, '2026-04-14 10:11:58'),
(351, 41, 'Power -x capsule', 'SR-04', '2029-04', '25 capsule ', 3, 150.00, 50.00, 450.00, '2026-04-14 10:11:58'),
(352, 41, 'Pain-x oil', 'SR-03', '2029-03', '30ml', 3, 80.00, 50.00, 240.00, '2026-04-14 10:11:58'),
(353, 41, 'Chest Care Oil', 'SR-04', '2028-09', '30 ml', 3, 80.00, 50.00, 240.00, '2026-04-14 10:11:58'),
(354, 41, 'Dant Care', 'SR-03', '2029-03', '60 gm', 3, 60.00, 50.00, 180.00, '2026-04-14 10:11:58'),
(355, 41, 'Face care cream', 'SR-04', '2028-04', '50 gram', 3, 110.00, 50.00, 330.00, '2026-04-14 10:11:58'),
(356, 42, 'Rhemo-Sr', 'SR-04', '2029-04', '200ml', 6, 130.00, 50.00, 780.00, '2026-04-16 05:17:51'),
(357, 42, 'R-neuro drop', 'SR-03', '2028-03', '50ml', 6, 150.00, 50.00, 900.00, '2026-04-16 05:17:51'),
(358, 42, 'Face care cream', 'SR-04', '2028-04', '50 gram', 10, 110.00, 50.00, 1100.00, '2026-04-16 05:17:51'),
(359, 43, 'Rhemo-Sr', 'SR-04', '2029-04', '200ml', 10, 130.00, 50.00, 1300.00, '2026-04-16 05:22:36'),
(360, 43, 'R-Cid', 'SR-04', '2029-03', '200ml', 12, 130.00, 50.00, 1560.00, '2026-04-16 05:22:36'),
(361, 43, 'Urical-SR syrup', 'SR-03', '2029-03', '200-ml', 10, 130.00, 50.00, 1300.00, '2026-04-16 05:22:36'),
(362, 44, 'Pain-x oil', 'SR-03', '2029-03', '30ml', 10, 80.00, 50.00, 800.00, '2026-04-16 11:06:53'),
(363, 45, 'Hemagyne Drop', 'SR-02', '2028-02', '50 ml', 10, 150.00, 50.00, 1500.00, '2026-04-16 11:09:21'),
(364, 46, 'Anty Cold Tab', 'SR-06', '2028-12', '25 gm', 5, 70.00, 50.00, 350.00, '2026-04-23 13:40:56'),
(365, 46, 'Anty Viral Tab', 'SR-03', '2029-02', '25 gm', 5, 70.00, 50.00, 350.00, '2026-04-23 13:40:56'),
(366, 46, 'Helcoplas Tab', 'SR-01', '2029-01', '25 gm', 5, 70.00, 50.00, 350.00, '2026-04-23 13:40:56'),
(367, 46, 'Urical Tab', 'SR-01', '2029-01', '25 gm', 5, 70.00, 50.00, 350.00, '2026-04-23 13:40:56'),
(368, 46, 'Ostolief Tab', 'SR-02', '2029-02', '25 gm', 5, 70.00, 50.00, 350.00, '2026-04-23 13:40:56'),
(369, 46, 'Face care cream', 'SR-04', '2028-04', '50 gram', 5, 110.00, 50.00, 550.00, '2026-04-23 13:40:56'),
(370, 47, 'Rheumo- SR', 'SR-04', '2029-04', '200ml', 20, 130.00, 50.00, 2600.00, '2026-04-24 06:42:26'),
(371, 47, 'Spondy drop', 'SR-05', '2027-06', '50ml', 10, 150.00, 50.00, 1500.00, '2026-04-24 06:42:26'),
(372, 47, 'Spondy Drop', 'SR-01', '2028-01', '50 ml', 10, 150.00, 50.00, 1500.00, '2026-04-24 06:42:26'),
(373, 47, 'R-Artho Capsule', 'SR-04', '2029-04', '30 capsule ', 20, 180.00, 50.00, 3600.00, '2026-04-24 06:42:26'),
(374, 47, 'Urical Tab', 'SR-01', '2029-01', '25 gm', 20, 70.00, 50.00, 1400.00, '2026-04-24 06:42:26'),
(375, 48, 'Dant Care', 'SR-03', '2029-03', '60 gm', 3, 60.00, 50.00, 180.00, '2026-04-24 07:06:07'),
(376, 48, 'R-Skine Tab', 'SR-02', '2029-02', '25 gm', 2, 70.00, 50.00, 140.00, '2026-04-24 07:06:07'),
(377, 48, 'Rheumo- SR', 'SR-04', '2029-04', '200ml', 2, 130.00, 50.00, 260.00, '2026-04-24 07:06:07'),
(378, 48, 'Heart care syrup', 'SR-03', '2029-03', '200-ml', 1, 130.00, 50.00, 130.00, '2026-04-24 07:06:07'),
(379, 48, 'Urical-SR syrup', 'SR-03', '2029-03', '200-ml', 1, 130.00, 50.00, 130.00, '2026-04-24 07:06:07'),
(380, 48, 'Urical Tab', 'SR-01', '2029-01', '25 gm', 1, 70.00, 50.00, 70.00, '2026-04-24 07:06:07'),
(381, 48, 'R-Tone syrup', 'SR-03', '2029-03', '200-ml', 1, 130.00, 50.00, 130.00, '2026-04-24 07:06:07'),
(382, 48, 'R-Typho', 'SR-03', '2029-03', '100 ml', 2, 70.00, 50.00, 140.00, '2026-04-24 07:06:07'),
(383, 48, 'Face Care Cream', 'SR-02', '2028-02', '50 gm', 1, 110.00, 50.00, 110.00, '2026-04-24 07:06:07'),
(384, 48, 'R-Lax syrup', 'SR-04', '2029-04', '100 ml', 1, 80.00, 50.00, 80.00, '2026-04-24 07:06:07'),
(385, 48, 'R-Skin syrup', 'SR-03', '2029-03', '200-ml', 1, 130.00, 50.00, 130.00, '2026-04-24 07:06:07'),
(386, 48, 'Pain-x oil', 'SR-03', '2029-03', '30ml', 2, 80.00, 50.00, 160.00, '2026-04-24 07:06:07'),
(387, 48, 'Brain Tab', 'SR-04', '2028-09', '25 gm', 2, 70.00, 50.00, 140.00, '2026-04-24 07:06:07'),
(388, 48, 'Helcoplas Tab', 'SR-01', '2029-01', '25 gm', 2, 70.00, 50.00, 140.00, '2026-04-24 07:06:07'),
(389, 48, 'Hemo Tab', 'SR-02', '2029-02', '25 gm', 1, 70.00, 50.00, 70.00, '2026-04-24 07:06:07'),
(390, 48, 'Antispos Tab', 'SR-02', '2029-02', '25 gm', 1, 70.00, 50.00, 70.00, '2026-04-24 07:06:07'),
(391, 48, 'Diarrhoea Tab', 'SR-02', '2029-02', '25 gm', 1, 70.00, 50.00, 70.00, '2026-04-24 07:06:07'),
(392, 49, 'R-neuro drop', 'SR-03', '2028-03', '50ml', 20, 150.00, 50.00, 3000.00, '2026-04-26 04:25:13'),
(393, 49, 'R-skin drop', 'SR-04', '2028-04', '50ml', 20, 150.00, 50.00, 3000.00, '2026-04-26 04:25:13'),
(394, 49, 'R-Artho Capsule', 'SR-04', '2029-04', '30 capsule ', 20, 180.00, 50.00, 3600.00, '2026-04-26 04:25:13'),
(395, 49, 'R-Warts Capsule', 'SR-03', '2028-03', '30 capsul', 20, 180.00, 50.00, 3600.00, '2026-04-26 04:25:13'),
(396, 49, 'Pile-X Drop', 'SR-01', '2028-01', '50 ml', 10, 150.00, 50.00, 1500.00, '2026-04-26 04:25:13'),
(397, 49, 'R-Skin syrup', 'SR-04', '2029-04', '200-ml', 20, 130.00, 50.00, 2600.00, '2026-04-26 04:25:13'),
(398, 50, 'Heart care syrup', 'SR-03', '2029-03', '200-ml', 6, 130.00, 50.00, 780.00, '2026-04-26 04:37:56'),
(399, 50, 'Heart care syrup', 'SR-04', '2029-04', '200-ml', 4, 130.00, 50.00, 520.00, '2026-04-26 04:37:56'),
(400, 50, 'Hemagyne syrup', 'SR-04', '2029-04', '200-ml', 10, 130.00, 50.00, 1300.00, '2026-04-26 04:37:56'),
(401, 50, 'R-Skin syrup', 'SR-03', '2029-03', '200-ml', 6, 130.00, 50.00, 780.00, '2026-04-26 04:37:56'),
(402, 50, 'R-Skin syrup', 'SR-04', '2029-04', '200-ml', 4, 130.00, 50.00, 520.00, '2026-04-26 04:37:56'),
(403, 50, 'Face care cream', 'SR-04', '2028-04', '50 gram', 10, 110.00, 50.00, 1100.00, '2026-04-26 04:37:56'),
(404, 50, 'ostolife tablet', 'SR-04', '2029-04', '25 gram', 10, 70.00, 50.00, 700.00, '2026-04-26 04:37:56'),
(405, 50, 'Urical tablet', 'SR-04', '2029-04', '25 gram', 10, 70.00, 50.00, 700.00, '2026-04-26 04:37:56'),
(406, 50, 'Rheumo- SR', 'SR-04', '2029-04', '200ml', 5, 130.00, 50.00, 650.00, '2026-04-26 04:37:56'),
(407, 50, 'Rheumo -SR syrup', 'SR-04', '2029-04', '200-ml', 5, 130.00, 50.00, 650.00, '2026-04-26 04:37:56'),
(408, 50, 'Pain-x oil', 'SR-03', '2029-03', '30ml', 5, 80.00, 50.00, 400.00, '2026-04-26 04:37:56'),
(409, 51, 'Urical Tab', 'SR-01', '2029-01', '25 gm', 20, 70.00, 40.00, 1400.00, '2026-04-26 04:58:46'),
(410, 51, 'Ostolief Tab', 'SR-02', '2029-02', '25 gm', 15, 70.00, 40.00, 1050.00, '2026-04-26 04:58:46'),
(411, 51, 'ostolife tablet', 'SR-04', '2029-04', '25 gram', 5, 70.00, 40.00, 350.00, '2026-04-26 04:58:46'),
(412, 51, 'R-Skine Tab', 'SR-02', '2029-02', '25 gm', 20, 70.00, 40.00, 1400.00, '2026-04-26 04:58:46'),
(413, 51, 'Feveron Tab', 'SR-02', '2029-02', '25 gm', 20, 70.00, 40.00, 1400.00, '2026-04-26 04:58:46'),
(414, 51, 'Anty Viral Tab', 'SR-03', '2029-02', '25 gm', 20, 70.00, 40.00, 1400.00, '2026-04-26 04:58:46'),
(415, 51, 'Anty Cold Tab', 'SR-06', '2028-12', '25 gm', 20, 70.00, 40.00, 1400.00, '2026-04-26 04:58:46'),
(416, 51, 'Helcoplas Tab', 'SR-01', '2029-01', '25 gm', 6, 70.00, 40.00, 420.00, '2026-04-26 04:58:46'),
(417, 51, 'Helco plas Tab', 'SR-03', '2029-03', '25 gram', 14, 70.00, 40.00, 980.00, '2026-04-26 04:58:46'),
(418, 51, 'Brain Tab', 'SR-04', '2028-09', '25 gm', 20, 70.00, 40.00, 1400.00, '2026-04-26 04:58:46'),
(419, 51, 'Hemacuf Tab', 'SR-03', '2028-06', '25 gm', 10, 70.00, 40.00, 700.00, '2026-04-26 04:58:46'),
(420, 51, 'Hemacuf tablet', 'SR-03', '2029-03', '25 gram', 10, 70.00, 40.00, 700.00, '2026-04-26 04:58:46'),
(421, 51, 'Diarrhoea Tab', 'SR-02', '2029-02', '25 gm', 20, 70.00, 40.00, 1400.00, '2026-04-26 04:58:46'),
(422, 51, 'Antispos Tab', 'SR-02', '2029-02', '25 gm', 20, 70.00, 40.00, 1400.00, '2026-04-26 04:58:46'),
(423, 51, 'R-Power Tab', 'SR-06', '2028-12', '25 gm', 16, 70.00, 40.00, 1120.00, '2026-04-26 04:58:46'),
(424, 51, 'R-power tablet', 'SR-04', '2029-04', '25 gram', 4, 70.00, 40.00, 280.00, '2026-04-26 04:58:46'),
(425, 51, 'Hemo Tab', 'SR-02', '2029-02', '25 gm', 20, 70.00, 40.00, 1400.00, '2026-04-26 04:58:46'),
(426, 52, 'R-Cid syrup', 'SR-04', '2029-04', '200-ml', 6, 130.00, 50.00, 780.00, '2026-04-26 07:12:22'),
(427, 52, 'Urical-SR syrup', 'SR-03', '2029-03', '200-ml', 6, 130.00, 50.00, 780.00, '2026-04-26 07:12:22'),
(428, 52, 'Rheumo -SR syrup', 'SR-04', '2029-04', '200-ml', 6, 130.00, 50.00, 780.00, '2026-04-26 07:12:22'),
(429, 52, 'R-Skin syrup', 'SR-04', '2029-04', '200-ml', 6, 130.00, 50.00, 780.00, '2026-04-26 07:12:22'),
(430, 53, 'R-Skin syrup', 'SR-04', '2029-04', '200-ml', 24, 130.00, 50.00, 3120.00, '2026-04-27 13:06:46'),
(431, 53, 'R-Skin syrup', 'SR-04', '2029-04', '200-ml', 6, 130.00, 50.00, 780.00, '2026-04-27 13:06:46'),
(432, 53, 'R-Cid syrup', 'SR-04', '2029-04', '200-ml', 30, 130.00, 50.00, 3900.00, '2026-04-27 13:06:46'),
(433, 53, 'Hemacuf syrup', 'SR-03', '2028-03', '100 ml', 30, 80.00, 50.00, 2400.00, '2026-04-27 13:06:46'),
(434, 53, 'R-Artho Capsule', 'SR-03', '2029-04', '30 capsule ', 3, 180.00, 50.00, 540.00, '2026-04-27 13:06:46'),
(435, 53, 'R-Artho Capsule', 'SR-04', '2029-04', '30 capsule ', 17, 180.00, 50.00, 3060.00, '2026-04-27 13:06:46'),
(436, 54, 'R-Artho Capsule', 'SR-04', '2029-04', '30 capsule ', 10, 180.00, 50.00, 1800.00, '2026-04-28 04:59:30'),
(437, 54, 'Rheumo -SR syrup', 'SR-04', '2029-04', '200-ml', 10, 130.00, 50.00, 1300.00, '2026-04-28 04:59:30'),
(438, 54, 'R-skin drop', 'SR-04', '2028-04', '50ml', 10, 150.00, 50.00, 1500.00, '2026-04-28 04:59:30'),
(439, 54, 'Heart care syrup', 'SR-04', '2029-04', '200-ml', 10, 130.00, 50.00, 1300.00, '2026-04-28 04:59:30'),
(440, 54, 'R-Skine Tab', 'SR-02', '2029-02', '25 gm', 10, 70.00, 50.00, 700.00, '2026-04-28 04:59:30'),
(441, 54, 'Helco plas Tab', 'SR-03', '2029-03', '25 gram', 10, 70.00, 50.00, 700.00, '2026-04-28 04:59:30'),
(442, 54, 'Pain-x oil', 'SR-03', '2029-03', '30ml', 10, 80.00, 50.00, 800.00, '2026-04-28 04:59:30'),
(443, 55, 'Hemagyne Drop', 'SR-02', '2028-02', '50 ml', 10, 150.00, 50.00, 1500.00, '2026-04-28 05:03:51'),
(444, 55, 'Hemagyne syrup', 'SR-04', '2029-04', '200-ml', 10, 130.00, 50.00, 1300.00, '2026-04-28 05:03:51'),
(445, 56, 'Heart care syrup', 'SR-04', '2029-04', '200-ml', 10, 130.00, 50.00, 1300.00, '2026-05-01 10:07:34'),
(446, 56, 'R-Skin syrup', 'SR-04', '2029-04', '200-ml', 10, 130.00, 50.00, 1300.00, '2026-05-01 10:07:34'),
(447, 56, 'Alleres-SR Drop', 'SR-03', '2027-06', '50 ml', 10, 150.00, 50.00, 1500.00, '2026-05-01 10:07:34'),
(448, 56, 'R-neuro drop', 'SR-03', '2028-03', '50ml', 10, 150.00, 50.00, 1500.00, '2026-05-01 10:07:34'),
(449, 56, 'R-skin drop', 'SR-04', '2028-04', '50ml', 10, 150.00, 50.00, 1500.00, '2026-05-01 10:07:34'),
(450, 56, 'Pile-x capsule', 'SR-03', '2029-04', '30 capsule ', 9, 180.00, 50.00, 1620.00, '2026-05-01 10:07:34'),
(451, 56, 'Pile-x capsule', 'SR-04', '2029-04', '30 capsule ', 1, 180.00, 50.00, 180.00, '2026-05-01 10:07:34'),
(452, 56, 'R-warts capsule', 'SR-04', '2029-04', '30 capsule ', 10, 180.00, 50.00, 1800.00, '2026-05-01 10:07:34'),
(453, 56, 'Pain-x oil', 'SR-03', '2029-03', '30ml', 10, 80.00, 50.00, 800.00, '2026-05-01 10:07:34'),
(454, 57, 'R-Liv', 'SR-02', '2029-02', '200 ml', 15, 130.00, 50.00, 1950.00, '2026-05-01 10:45:20'),
(455, 57, 'R-Liv syrup', 'SR-03', '2029-03', '200-ml', 5, 130.00, 50.00, 650.00, '2026-05-01 10:45:20'),
(456, 58, 'Face care cream', 'SR-04', '2028-04', '50 gram', 14, 110.00, 50.00, 1540.00, '2026-05-02 09:54:46'),
(457, 58, 'Pain-x oil', 'SR-03', '2029-03', '30ml', 20, 80.00, 50.00, 1600.00, '2026-05-02 09:54:46'),
(458, 59, 'Dant Care', 'SR-03', '2029-03', '60 gm', 19, 60.00, 50.00, 1140.00, '2026-05-02 09:59:12'),
(459, 59, 'Dant care', 'SR-04', '2029-04', '60 gram ', 1, 60.00, 50.00, 60.00, '2026-05-02 09:59:12'),
(460, 60, 'Livo Care', 'RLC/JUL/25', '2027-07', '50 ml', 12, 150.00, 50.00, 1800.00, '2026-05-05 06:33:59'),
(461, 60, 'Face care cream', 'SR-04', '2028-04', '50 gram', 12, 110.00, 50.00, 1320.00, '2026-05-05 06:33:59'),
(462, 60, 'R-Artho Capsule', 'SR-05', '2029-05', '30 capsule ', 12, 180.00, 50.00, 2160.00, '2026-05-05 06:33:59'),
(463, 60, 'Urical-SR syrup', 'SR-03', '2029-03', '200-ml', 5, 130.00, 50.00, 650.00, '2026-05-05 06:33:59'),
(464, 61, 'Rheumo -SR syrup', 'SR-04', '2029-04', '200-ml', 10, 130.00, 50.00, 1300.00, '2026-05-05 06:40:14'),
(465, 61, 'R-Artho Capsule', 'SR-05', '2029-05', '30 capsule ', 5, 180.00, 50.00, 900.00, '2026-05-05 06:40:14'),
(466, 61, 'R-Cid syrup', 'SR-04', '2029-04', '200-ml', 10, 130.00, 50.00, 1300.00, '2026-05-05 06:40:14'),
(467, 61, 'Urical-SR syrup', 'SR-03', '2029-03', '200-ml', 10, 130.00, 50.00, 1300.00, '2026-05-05 06:40:14'),
(468, 61, 'Hemacuf syrup', 'SR-03', '2028-03', '100 ml', 5, 80.00, 50.00, 400.00, '2026-05-05 06:40:14'),
(469, 61, 'Pain-x oil', 'SR-03', '2029-03', '30ml', 10, 80.00, 50.00, 800.00, '2026-05-05 06:40:14'),
(470, 62, 'R-Cid', 'SR-05', '2029-05', '200ml', 5, 130.00, 50.00, 650.00, '2026-05-10 09:40:05'),
(471, 62, 'Diabotone syrup', 'SR-03', '2029-04', '200-ml', 10, 130.00, 50.00, 1300.00, '2026-05-10 09:40:05'),
(472, 62, 'Rheumo -SR syrup', 'SR-04', '2029-04', '200-ml', 10, 130.00, 50.00, 1300.00, '2026-05-10 09:40:05'),
(473, 62, 'R-Liv syrup', 'SR-03', '2029-03', '200-ml', 10, 130.00, 50.00, 1300.00, '2026-05-10 09:40:05'),
(474, 62, 'R-Typho syrup', 'SR-03', '2029-03', '100 ml', 10, 80.00, 50.00, 800.00, '2026-05-10 09:40:05'),
(475, 62, 'R-neuro drop', 'SR-03', '2028-03', '50ml', 10, 150.00, 50.00, 1500.00, '2026-05-10 09:40:05'),
(476, 63, 'Pain-x oil', 'SR-03', '2029-03', '30ml', 6, 80.00, 50.00, 480.00, '2026-05-10 09:46:56'),
(477, 63, 'R-Skin syrup', 'SR-04', '2029-04', '200-ml', 6, 130.00, 50.00, 780.00, '2026-05-10 09:46:56'),
(478, 63, 'R-Cid syrup', 'SR-04', '2029-04', '200-ml', 6, 130.00, 50.00, 780.00, '2026-05-10 09:46:56'),
(479, 64, 'Diabotone syrup', 'SR-03', '2029-04', '200-ml', 3, 130.00, 50.00, 390.00, '2026-05-13 03:57:10'),
(480, 64, 'Urical-SR syrup', 'SR-03', '2029-03', '200-ml', 5, 130.00, 50.00, 650.00, '2026-05-13 03:57:10'),
(481, 64, 'R-Tone syrup', 'SR-03', '2029-03', '200-ml', 5, 130.00, 50.00, 650.00, '2026-05-13 03:57:10'),
(482, 64, 'Dant care', 'SR-04', '2029-04', '60 gram ', 10, 60.00, 50.00, 600.00, '2026-05-13 03:57:10'),
(483, 65, 'R-Artho Capsule', 'SR-05', '2029-05', '30 capsule ', 10, 180.00, 50.00, 1800.00, '2026-05-14 08:33:29'),
(484, 65, 'Urical-SR syrup', 'SR-03', '2029-03', '200-ml', 6, 130.00, 50.00, 780.00, '2026-05-14 08:33:29'),
(485, 65, 'Urical tablet', 'SR-04', '2029-04', '25 gram', 4, 70.00, 50.00, 280.00, '2026-05-14 08:33:29'),
(486, 66, 'R-Liv syrup', 'SR-03', '2029-03', '200-ml', 6, 130.00, 50.00, 780.00, '2026-05-14 08:37:49'),
(487, 66, 'R-Lax syrup', 'SR-04', '2029-04', '100 ml', 6, 80.00, 50.00, 480.00, '2026-05-14 08:37:49'),
(488, 66, 'Alleres-SR Drop', 'SR-03', '2027-06', '50 ml', 6, 150.00, 50.00, 900.00, '2026-05-14 08:37:49'),
(489, 66, 'Pile-X Drop', 'SR-01', '2028-01', '50 ml', 6, 150.00, 50.00, 900.00, '2026-05-14 08:37:49'),
(490, 66, 'Pile-x capsule', 'SR-04', '2029-04', '30 capsule ', 6, 180.00, 50.00, 1080.00, '2026-05-14 08:37:49'),
(491, 66, 'R-Warts Capsule', 'SR-03', '2028-03', '30 capsul', 2, 180.00, 50.00, 360.00, '2026-05-14 08:37:49'),
(492, 66, 'R-warts capsule', 'SR-04', '2029-04', '30 capsule ', 2, 180.00, 50.00, 360.00, '2026-05-14 08:37:49'),
(493, 66, 'R-warts capsule', 'SR-05', '2029-05', '30 capsule ', 2, 180.00, 50.00, 360.00, '2026-05-14 08:37:49'),
(494, 67, 'R-Cid', 'SR-05', '2029-05', '200ml', 10, 130.00, 50.00, 1300.00, '2026-05-14 08:53:11');
INSERT INTO `invoice_items` (`id`, `invoice_id`, `product_name`, `batch`, `expiry`, `capacity`, `qty`, `rate`, `discount`, `gross_amount`, `created_at`) VALUES
(495, 67, 'R-Tone syrup', 'SR-03', '2029-03', '200-ml', 10, 130.00, 50.00, 1300.00, '2026-05-14 08:53:11'),
(496, 67, 'R-Liv syrup', 'SR-03', '2029-03', '200-ml', 10, 130.00, 50.00, 1300.00, '2026-05-14 08:53:11'),
(497, 67, 'R-Typho syrup', 'SR-03', '2029-03', '100 ml', 10, 80.00, 50.00, 800.00, '2026-05-14 08:53:11'),
(498, 67, 'Alleres-SR Drop', 'SR-03', '2027-06', '50 ml', 10, 150.00, 50.00, 1500.00, '2026-05-14 08:53:11'),
(499, 67, 'Pile-x capsule', 'SR-04', '2029-04', '30 capsule ', 4, 180.00, 50.00, 720.00, '2026-05-14 08:53:11'),
(500, 67, 'R-Skin syrup', 'SR-04', '2029-04', '200-ml', 7, 130.00, 50.00, 910.00, '2026-05-14 08:53:11'),
(501, 68, 'Urical-SR syrup', 'SR-03', '2029-03', '200-ml', 10, 130.00, 50.00, 1300.00, '2026-05-15 08:45:01'),
(502, 69, 'Pile-X Drop', 'SR-01', '2028-01', '50 ml', 12, 150.00, 50.00, 1800.00, '2026-05-15 08:49:34'),
(503, 69, 'Pile-x capsule', 'SR-04', '2029-04', '30 capsule ', 12, 180.00, 50.00, 2160.00, '2026-05-15 08:49:34'),
(504, 70, 'R-skin drop', 'SR-04', '2028-04', '50ml', 20, 150.00, 50.00, 3000.00, '2026-05-16 05:00:02'),
(505, 70, 'R-neuro drop', 'SR-04', '2028-04', '50ml', 20, 150.00, 50.00, 3000.00, '2026-05-16 05:00:02'),
(506, 71, 'R-Artho Capsule', 'SR-05', '2029-05', '30 capsule ', 20, 180.00, 60.00, 3600.00, '2026-05-18 16:21:33'),
(507, 71, 'R-neuro drop', 'SR-03', '2028-03', '50ml', 15, 150.00, 60.00, 2250.00, '2026-05-18 16:21:33'),
(508, 71, 'R-neuro drop', 'SR-04', '2028-04', '50ml', 10, 150.00, 60.00, 1500.00, '2026-05-18 16:21:33'),
(509, 71, 'R-Artho Capsule', 'SR-05', '2029-05', '30 capsule ', 5, 180.00, 60.00, 900.00, '2026-05-18 16:21:33'),
(525, 79, 'R-Typho syrup', 'SR-03', '2029-03', '100 ml', 5, 80.00, 50.00, 400.00, '2026-05-21 05:25:15'),
(528, 81, 'R-Artho Capsule', 'SR-05', '2029-05', '30 capsule ', 20, 180.00, 50.00, 3600.00, '2026-05-21 09:30:09'),
(529, 82, 'R-Artho Capsule', 'SR-05', '2029-05', '30 capsule ', 8, 180.00, 50.00, 1440.00, '2026-05-21 09:32:22'),
(530, 83, 'R-Skin syrup', 'SR-05', '2029-05', '200-ml', 25, 130.00, 0.00, 3250.00, '2026-05-23 18:32:40'),
(531, 84, 'Urical tablet', 'SR-04', '2029-04', '25 gram', 20, 70.00, 40.00, 1400.00, '2026-05-26 10:59:28'),
(532, 84, 'ostolife tablet', 'SR-04', '2029-04', '25 gram', 20, 70.00, 40.00, 1400.00, '2026-05-26 10:59:28'),
(533, 84, 'R-Skine Tab', 'SR-02', '2029-02', '25 gm', 20, 70.00, 40.00, 1400.00, '2026-05-26 10:59:28'),
(534, 84, 'Feveron Tab', 'SR-02', '2029-02', '25 gm', 20, 70.00, 40.00, 1400.00, '2026-05-26 10:59:28'),
(535, 84, 'Anty Viral Tab', 'SR-03', '2029-02', '25 gm', 20, 70.00, 40.00, 1400.00, '2026-05-26 10:59:28'),
(536, 84, 'Anty Cold Tab', 'SR-06', '2028-12', '25 gm', 20, 70.00, 40.00, 1400.00, '2026-05-26 10:59:28'),
(537, 84, 'Helco plas Tab', 'SR-03', '2029-03', '25 gram', 12, 70.00, 40.00, 840.00, '2026-05-26 10:59:28'),
(538, 84, 'Helco plas Tab', 'SR-03', '2029-03', '25 gram', 8, 65.00, 40.00, 520.00, '2026-05-26 10:59:28'),
(539, 84, 'Brain Tab', 'SR-04', '2028-09', '25 gm', 20, 70.00, 40.00, 1400.00, '2026-05-26 10:59:28'),
(540, 84, 'Hemacuf tablet', 'SR-03', '2029-03', '25 gram', 20, 70.00, 40.00, 1400.00, '2026-05-26 10:59:28'),
(541, 84, 'Diarrhoea Tab', 'SR-02', '2029-02', '25 gm', 20, 70.00, 40.00, 1400.00, '2026-05-26 10:59:28'),
(542, 84, 'Antispos Tab', 'SR-02', '2029-02', '25 gm', 20, 70.00, 40.00, 1400.00, '2026-05-26 10:59:28'),
(543, 84, 'R-power tablet', 'SR-04', '2029-04', '25 gram', 20, 70.00, 40.00, 1400.00, '2026-05-26 10:59:28'),
(544, 84, 'Hemo Tab', 'SR-02', '2029-02', '25 gm', 20, 70.00, 40.00, 1400.00, '2026-05-26 10:59:28'),
(545, 84, 'Rheumo -SR syrup', 'SR-04', '2029-04', '200-ml', 20, 130.00, 40.00, 2600.00, '2026-05-26 10:59:28'),
(546, 85, 'R-Skine Tab', 'SR-02', '2029-02', '25 gm', 15, 70.00, 50.00, 1050.00, '2026-05-29 03:01:48'),
(547, 85, 'R-Skine syrup', 'Sr-05', '2029-05', '200ml', 25, 130.00, 50.00, 3250.00, '2026-05-29 03:01:48'),
(548, 85, 'R-Skin syrup', 'SR-04', '2029-04', '200-ml', 5, 130.00, 50.00, 650.00, '2026-05-29 03:01:48'),
(549, 85, 'R-Skin syrup', 'SR-05', '2029-05', '200-ml', 20, 130.00, 50.00, 2600.00, '2026-05-29 03:01:48'),
(550, 85, 'R-skin drop', 'SR-04', '2028-04', '50ml', 50, 150.00, 50.00, 7500.00, '2026-05-29 03:01:48'),
(551, 85, 'Face Care', 'SR-05', '2028-05', '50g', 30, 110.00, 50.00, 3300.00, '2026-05-29 03:01:48'),
(552, 86, 'Face Care', 'SR-05', '2028-05', '50g', 20, 110.00, 60.00, 2200.00, '2026-05-30 05:58:35'),
(553, 86, 'R-skin drop', 'SR-04', '2028-04', '50ml', 30, 150.00, 60.00, 4500.00, '2026-05-30 05:58:35'),
(554, 86, 'Hemagyne Drop', 'SR-02', '2028-02', '50 ml', 20, 150.00, 60.00, 3000.00, '2026-05-30 05:58:35'),
(555, 87, 'R-Artho Capsule', 'Sr.05', '2029-05', '30 capsule ', 10, 180.00, 50.00, 1800.00, '2026-05-31 11:43:09'),
(556, 87, 'Rheumo -SR syrup', 'SR-04', '2029-04', '200-ml', 4, 130.00, 50.00, 520.00, '2026-05-31 11:43:09'),
(557, 87, 'Hemagyne syrup', 'SR-04', '2029-04', '200-ml', 6, 130.00, 50.00, 780.00, '2026-05-31 11:43:09'),
(558, 87, 'Urical-SR syrup', 'SR-05', '2029-05', '200-ml', 6, 130.00, 50.00, 780.00, '2026-05-31 11:43:09'),
(559, 87, 'R-Cid', 'SR-05', '2029-05', '200ml', 12, 130.00, 50.00, 1560.00, '2026-05-31 11:43:09'),
(560, 87, 'Diabotone syrup', 'SR-05', '2029-05', '200-ml', 4, 130.00, 50.00, 520.00, '2026-05-31 11:43:09'),
(561, 87, 'Pain-x oil', 'SR-03', '2029-03', '30ml', 12, 80.00, 50.00, 960.00, '2026-05-31 11:43:09'),
(562, 87, 'Chest care oil', 'SR-04', '2029-04', '30 ml', 10, 80.00, 50.00, 800.00, '2026-05-31 11:43:09'),
(563, 88, 'R-Artho Capsule', 'Sr.05', '2029-05', '30 capsule ', 10, 180.00, 50.00, 1800.00, '2026-06-01 00:38:51'),
(564, 88, 'Rheumo -SR syrup', 'SR-04', '2029-04', '200-ml', 10, 130.00, 50.00, 1300.00, '2026-06-01 00:38:51'),
(565, 88, 'Urical-SR syrup', 'SR-05', '2029-05', '200-ml', 10, 130.00, 50.00, 1300.00, '2026-06-01 00:38:51'),
(566, 88, 'R-Cid', 'SR-05', '2029-05', '200ml', 10, 130.00, 50.00, 1300.00, '2026-06-01 00:38:51'),
(567, 88, 'Hemagyne syrup', 'SR-04', '2029-04', '200-ml', 20, 130.00, 50.00, 2600.00, '2026-06-01 00:38:51'),
(568, 89, 'Urical Tab', 'Sr-03', '2029-03', '25g', 10, 70.00, 50.00, 700.00, '2026-06-01 00:44:00'),
(569, 89, 'Urical-SR syrup', 'SR-05', '2029-05', '200-ml', 10, 130.00, 50.00, 1300.00, '2026-06-01 00:44:00'),
(570, 89, 'Pile-X Drop', 'SR-01', '2028-01', '50 ml', 10, 150.00, 50.00, 1500.00, '2026-06-01 00:44:00'),
(571, 90, 'Hemagyne Drop', 'SR-02', '2028-02', '50 ml', 20, 150.00, 50.00, 3000.00, '2026-06-01 00:49:31'),
(572, 90, 'Diabotone syrup', 'SR-03', '2029-04', '200-ml', 5, 130.00, 50.00, 650.00, '2026-06-01 00:49:31'),
(573, 90, 'Livo Care', 'RLC/JUL/25', '2027-07', '50 ml', 8, 150.00, 50.00, 1200.00, '2026-06-01 00:49:31'),
(574, 90, 'Livo care drop', 'SR-05', '2028-05', '50ml', 12, 150.00, 50.00, 1800.00, '2026-06-01 00:49:31'),
(575, 91, 'R-Skine syrup', 'SR-05', '2029-05', '200ml', 10, 130.00, 50.00, 1300.00, '2026-06-01 00:56:03'),
(576, 91, 'Face Care', 'SR-05', '2028-05', '50g', 10, 110.00, 50.00, 1100.00, '2026-06-01 00:56:03'),
(577, 91, 'Rheumo -SR syrup', 'SR-04', '2029-04', '200-ml', 9, 130.00, 50.00, 1170.00, '2026-06-01 00:56:03'),
(578, 91, 'Urical-SR syrup', 'SR-05', '2029-05', '200-ml', 5, 130.00, 50.00, 650.00, '2026-06-01 00:56:03'),
(579, 91, 'R-Cid', 'SR-05', '2029-05', '200ml', 10, 130.00, 50.00, 1300.00, '2026-06-01 00:56:03'),
(580, 91, 'Helco plas Tab', 'SR-03', '2029-03', '25 gram', 5, 65.00, 50.00, 325.00, '2026-06-01 00:56:03'),
(581, 92, 'Dant care', 'SR-04', '2029-04', '60 gram ', 60, 60.00, 50.00, 3600.00, '2026-06-04 07:10:29'),
(582, 93, 'Feveron Tab', 'SR-02', '2029-02', '25 gm', 10, 70.00, 50.00, 700.00, '2026-06-05 03:41:32'),
(583, 93, 'Anty Cold Tab', 'SR-06', '2028-12', '25 gm', 10, 70.00, 50.00, 700.00, '2026-06-05 03:41:32'),
(584, 93, 'Pile-X Drop', 'SR-01', '2028-01', '50 ml', 7, 150.00, 50.00, 1050.00, '2026-06-05 03:41:32'),
(585, 93, 'Pile -x drop', 'SR-03', '2028-03', '50ml', 3, 150.00, 50.00, 450.00, '2026-06-05 03:41:32'),
(586, 93, 'Spondy Drop', 'SR-01', '2028-01', '50 ml', 10, 150.00, 50.00, 1500.00, '2026-06-05 03:41:32'),
(587, 93, 'R-Typho syrup', 'SR-03', '2029-03', '100 ml', 10, 80.00, 50.00, 800.00, '2026-06-05 03:41:32'),
(588, 94, 'Pain-x oil', 'SR-03', '2029-03', '30ml', 10, 80.00, 50.00, 800.00, '2026-06-05 03:47:46'),
(589, 95, 'Urical Tab', 'Sr-03', '2029-03', '25g', 10, 70.00, 50.00, 700.00, '2026-06-08 05:52:47'),
(590, 95, 'Brain Tab', 'SR-04', '2028-09', '25 gm', 20, 70.00, 50.00, 1400.00, '2026-06-08 05:52:47'),
(591, 95, 'R-Skine Tab', 'SR-02', '2029-02', '25 gm', 5, 70.00, 50.00, 350.00, '2026-06-08 05:52:47'),
(592, 95, 'R-Skine Tab', 'SR-05', '2029-05', '25 g', 5, 70.00, 50.00, 350.00, '2026-06-08 05:52:47'),
(593, 95, 'Helco plas Tab', 'SR-03', '2029-03', '25 gram', 10, 65.00, 50.00, 650.00, '2026-06-08 05:52:47'),
(594, 95, 'ostolife tablet', 'SR-04', '2029-04', '25 gram', 10, 70.00, 50.00, 700.00, '2026-06-08 05:52:47'),
(595, 95, 'Feveron Tab', 'SR-02', '2029-02', '25 gm', 10, 70.00, 50.00, 700.00, '2026-06-08 05:52:47'),
(596, 95, 'Anty Cold Tab', 'SR-06', '2028-12', '25 gm', 10, 70.00, 50.00, 700.00, '2026-06-08 05:52:47'),
(597, 95, 'Anty Viral Tab', 'SR-03', '2029-02', '25 gm', 10, 70.00, 50.00, 700.00, '2026-06-08 05:52:47'),
(598, 95, 'Rhuemo-Sr', 'Sr-05', '2029-05', '200ml', 10, 130.00, 50.00, 1300.00, '2026-06-08 05:52:47'),
(599, 95, 'Urical-SR syrup', 'SR-05', '2029-05', '200-ml', 10, 130.00, 50.00, 1300.00, '2026-06-08 05:52:47'),
(600, 95, 'R-Skine syrup', 'SR-05', '2029-05', '200ml', 10, 130.00, 50.00, 1300.00, '2026-06-08 05:52:47'),
(601, 95, 'R-Cid', 'SR-05', '2029-05', '200ml', 5, 130.00, 50.00, 650.00, '2026-06-08 05:52:47'),
(602, 95, 'R- Cid syr', 'SR-06', '2029-06', '200ml', 5, 130.00, 50.00, 650.00, '2026-06-08 05:52:47'),
(603, 95, 'R-Liv syrup', 'SR-03', '2029-03', '200-ml', 10, 130.00, 50.00, 1300.00, '2026-06-08 05:52:47'),
(604, 96, 'R-Artho Capsule', 'Sr.05', '2029-05', '30 capsule ', 6, 180.00, 50.00, 1080.00, '2026-06-08 08:02:59'),
(605, 96, 'Rhuemo-Sr', 'Sr-05', '2029-05', '200ml', 6, 130.00, 50.00, 780.00, '2026-06-08 08:02:59'),
(606, 96, 'Hemagyne syrup', 'SR-04', '2029-04', '200-ml', 4, 130.00, 50.00, 520.00, '2026-06-08 08:02:59'),
(607, 96, 'Hemagyne Syr', 'SR-06', '2029-06', '200ml', 2, 130.00, 50.00, 260.00, '2026-06-08 08:02:59'),
(608, 96, 'Face Care', 'SR-05', '2028-05', '50g', 6, 110.00, 50.00, 660.00, '2026-06-08 08:02:59'),
(609, 97, 'Heart care syrup', 'SR-04', '2029-04', '200-ml', 10, 130.00, 50.00, 1300.00, '2026-06-08 08:06:10'),
(610, 97, 'R-Tone syrup', 'SR-03', '2029-03', '200-ml', 10, 130.00, 50.00, 1300.00, '2026-06-08 08:06:10'),
(611, 97, 'Urical-SR syrup', 'SR-05', '2029-05', '200-ml', 10, 130.00, 50.00, 1300.00, '2026-06-08 08:06:10'),
(612, 97, 'R-Artho Capsule', 'Sr.05', '2029-05', '30 capsule ', 10, 180.00, 50.00, 1800.00, '2026-06-08 08:06:10'),
(613, 97, 'Pain-x oil', 'SR-03', '2029-03', '30ml', 10, 80.00, 50.00, 800.00, '2026-06-08 08:06:10'),
(614, 97, 'R-neuro drop', 'SR-04', '2028-04', '50ml', 10, 150.00, 50.00, 1500.00, '2026-06-08 08:06:10'),
(615, 98, 'Hemagyne Syr', 'SR-06', '2029-06', '200ml', 6, 130.00, 50.00, 780.00, '2026-06-08 08:11:49'),
(616, 98, 'R-Typho syrup', 'SR-03', '2029-03', '100 ml', 6, 80.00, 50.00, 480.00, '2026-06-08 08:11:49'),
(617, 98, 'R-neuro drop', 'SR-04', '2028-04', '50ml', 6, 150.00, 50.00, 900.00, '2026-06-08 08:11:49'),
(618, 99, 'Face Care', 'SR-05', '2028-05', '50g', 10, 110.00, 50.00, 1100.00, '2026-06-08 08:14:56'),
(619, 99, 'Hemagyne Syr', 'SR-06', '2029-06', '200ml', 6, 130.00, 50.00, 780.00, '2026-06-08 08:14:56'),
(620, 99, 'Pile -x drop', 'SR-03', '2028-03', '50ml', 10, 150.00, 50.00, 1500.00, '2026-06-08 08:14:56'),
(621, 100, 'Rhuemo-Sr', 'Sr-05', '2029-05', '200ml', 6, 130.00, 50.00, 780.00, '2026-06-10 05:14:35'),
(622, 100, 'R-Artho Capsule', 'SR-06', '2029-06', '30 capsule ', 6, 180.00, 50.00, 1080.00, '2026-06-10 05:14:35'),
(623, 100, 'ostolife tablet', 'SR-04', '2029-04', '25 gram', 6, 70.00, 50.00, 420.00, '2026-06-10 05:14:35'),
(624, 100, 'R-Liv syrup', 'SR-03', '2029-03', '200-ml', 6, 130.00, 50.00, 780.00, '2026-06-10 05:14:35'),
(625, 100, 'R- Cid syr', 'SR-06', '2029-06', '200ml', 6, 130.00, 50.00, 780.00, '2026-06-10 05:14:35'),
(626, 100, 'Pile -x drop', 'SR-03', '2028-03', '50ml', 6, 150.00, 50.00, 900.00, '2026-06-10 05:14:35'),
(627, 100, 'R-Tone syrup', 'SR-03', '2029-03', '200-ml', 6, 130.00, 50.00, 780.00, '2026-06-10 05:14:35'),
(628, 100, 'R-Skin syrup', 'SR-05', '2029-05', '200-ml', 4, 130.00, 50.00, 520.00, '2026-06-10 05:14:35'),
(629, 100, 'R-Skin syrup', 'SR-06', '2029-06', '200-ml', 2, 130.00, 50.00, 260.00, '2026-06-10 05:14:35'),
(630, 101, 'Rhuemo-Sr', 'Sr-05', '2029-05', '200ml', 10, 130.00, 50.00, 1300.00, '2026-06-13 05:55:48'),
(631, 101, 'R-Tone syrup', 'SR-06', '2029-06', '200-ml', 6, 130.00, 50.00, 780.00, '2026-06-13 05:55:48'),
(632, 101, 'Urical-SR syrup', 'SR-05', '2029-05', '200-ml', 2, 130.00, 50.00, 260.00, '2026-06-13 05:55:48'),
(633, 101, 'Urical -SR', 'SR-06 ', '2029-06', '200ml', 4, 130.00, 50.00, 520.00, '2026-06-13 05:55:48'),
(634, 101, 'R-Artho Capsule', 'Sr.05', '2029-05', '30 capsule ', 4, 180.00, 50.00, 720.00, '2026-06-13 05:55:48'),
(635, 101, 'R-Artho Capsule', 'SR-06', '2029-06', '30 capsule ', 2, 180.00, 50.00, 360.00, '2026-06-13 05:55:48'),
(636, 101, 'Pain-x oil', 'SR-03', '2029-03', '30ml', 10, 80.00, 50.00, 800.00, '2026-06-13 05:55:48'),
(637, 101, 'Spondy Drop', 'SR-01', '2028-01', '50 ml', 6, 150.00, 50.00, 900.00, '2026-06-13 05:55:48'),
(638, 101, 'BP care drop', 'SR-01', '2027-06', '50ml', 6, 150.00, 50.00, 900.00, '2026-06-13 05:55:48'),
(639, 101, 'R-neuro drop', 'SR-04', '2028-04', '50ml', 6, 150.00, 50.00, 900.00, '2026-06-13 05:55:48'),
(640, 101, 'Livo care drop', 'SR-05', '2028-05', '50ml', 6, 150.00, 50.00, 900.00, '2026-06-13 05:55:48'),
(641, 101, 'Brain Tab', 'SR-04', '2028-09', '25 gm', 6, 70.00, 50.00, 420.00, '2026-06-13 05:55:48'),
(642, 101, 'R-Typho syrup', 'SR-03', '2029-03', '100 ml', 6, 80.00, 50.00, 480.00, '2026-06-13 05:55:48'),
(643, 102, 'R-Skine Tab', 'SR-05', '2029-05', '25 g', 20, 70.00, 50.00, 1400.00, '2026-06-13 13:05:50'),
(644, 102, 'R-skin drop', 'SR-04', '2028-04', '50ml', 20, 150.00, 50.00, 3000.00, '2026-06-13 13:05:50'),
(645, 102, 'R-Skin syrup', 'SR-06', '2029-06', '200-ml', 10, 130.00, 50.00, 1300.00, '2026-06-13 13:05:50'),
(646, 102, 'Face care cream', 'SR-06', '2029-06', '50 gram', 20, 110.00, 50.00, 2200.00, '2026-06-13 13:05:50'),
(647, 102, 'Pile-x capsule', 'SR-04', '2029-04', '30 capsule ', 10, 180.00, 50.00, 1800.00, '2026-06-13 13:05:50'),
(648, 102, 'Pile-x capsule', 'SR-05', '2028-05', '30 capsule ', 10, 180.00, 50.00, 1800.00, '2026-06-13 13:05:50'),
(649, 103, 'R-Tone syrup', 'SR-06', '2029-06', '200-ml', 20, 130.00, 50.00, 2600.00, '2026-06-14 04:16:32'),
(650, 103, 'R-Skin syrup', 'SR-06', '2029-06', '200-ml', 20, 130.00, 50.00, 2600.00, '2026-06-14 04:16:32'),
(651, 103, 'Rhuemo-Sr', 'Sr-05', '2029-05', '200ml', 20, 130.00, 50.00, 2600.00, '2026-06-14 04:16:32'),
(652, 103, 'R-Typho syrup', 'SR-03', '2029-03', '100 ml', 5, 80.00, 50.00, 400.00, '2026-06-14 04:16:32'),
(653, 103, 'R-Typho syrup', 'SR-05', '2029-06', '100 ml', 15, 80.00, 50.00, 1200.00, '2026-06-14 04:16:32'),
(654, 103, 'Hemacuf syrup', 'SR-03', '2028-03', '100 ml', 14, 80.00, 50.00, 1120.00, '2026-06-14 04:16:32'),
(655, 103, 'Hemacuf syrup', 'SR-05', '2028-05', '100 ml', 6, 80.00, 50.00, 480.00, '2026-06-14 04:16:32'),
(656, 103, 'ostolife tablet', 'SR-04', '2029-04', '25 gram', 9, 70.00, 50.00, 630.00, '2026-06-14 04:16:32'),
(657, 103, 'ostolife tablet', 'SR-04', '2029-05', '25 gram', 11, 70.00, 50.00, 770.00, '2026-06-14 04:16:32'),
(658, 103, 'Pile-x capsule', 'SR-05', '2028-05', '30 capsule ', 25, 180.00, 50.00, 4500.00, '2026-06-14 04:16:32'),
(659, 103, 'R-warts capsule', 'SR-05', '2029-05', '30 capsule ', 25, 180.00, 50.00, 4500.00, '2026-06-14 04:16:32'),
(660, 104, 'R- Cid syr', 'SR-06', '2029-06', '200ml', 20, 130.00, 50.00, 2600.00, '2026-06-14 04:26:04'),
(661, 105, 'Dant care', 'SR-06', '2029-06', '60 gram ', 50, 60.00, 50.00, 3000.00, '2026-06-14 07:32:58'),
(662, 105, 'R-Tone syrup', 'SR-06', '2029-06', '200-ml', 20, 130.00, 50.00, 2600.00, '2026-06-14 07:32:58'),
(663, 105, 'R-Skin syrup', 'SR-06', '2029-06', '200-ml', 20, 130.00, 50.00, 2600.00, '2026-06-14 07:32:58'),
(664, 105, 'Urical -SR', 'SR-06 ', '2029-06', '200ml', 20, 130.00, 50.00, 2600.00, '2026-06-14 07:32:58'),
(665, 105, 'Heart care syrup', 'SR-04', '2029-04', '200-ml', 10, 130.00, 50.00, 1300.00, '2026-06-14 07:32:58'),
(666, 105, 'BP care drop', 'SR-01', '2027-06', '50ml', 6, 150.00, 50.00, 900.00, '2026-06-14 07:32:58'),
(667, 105, 'Spondy Drop', 'SR-01', '2028-01', '50 ml', 6, 150.00, 50.00, 900.00, '2026-06-14 07:32:58'),
(668, 105, 'Brain Tab', 'SR-04', '2028-09', '25 gm', 10, 70.00, 50.00, 700.00, '2026-06-14 07:32:58'),
(669, 106, 'R-Skine Tab', 'SR-05', '2029-05', '25 g', 20, 70.00, 40.00, 1400.00, '2026-06-16 13:53:38'),
(670, 106, 'Hemo tablet', 'SR-06', '2029-06', '25 gram', 20, 70.00, 40.00, 1400.00, '2026-06-16 13:53:38'),
(671, 106, 'Helco plas Tab', 'SR-03', '2029-03', '25 gram', 20, 65.00, 40.00, 1300.00, '2026-06-16 13:53:38'),
(672, 106, 'Hemacuf tab', 'SR-03', '2029-03', '25 g', 20, 70.00, 40.00, 1400.00, '2026-06-16 13:53:38'),
(673, 106, 'ostolife tablet', 'SR-04', '2029-05', '25 gram', 20, 70.00, 40.00, 1400.00, '2026-06-16 13:53:38'),
(674, 106, 'Urical tablet', 'SR-04', '2029-04', '25 gram', 20, 70.00, 40.00, 1400.00, '2026-06-16 13:53:38'),
(675, 106, 'Brain Tab', 'SR-04', '2028-09', '25 gm', 20, 70.00, 40.00, 1400.00, '2026-06-16 13:53:38'),
(676, 106, 'R-power tablet', 'SR-04', '2029-04', '25 gram', 20, 70.00, 40.00, 1400.00, '2026-06-16 13:53:38'),
(677, 106, 'Diarrhoea Tab', 'SR-02', '2029-02', '25 gm', 20, 70.00, 40.00, 1400.00, '2026-06-16 13:53:38'),
(678, 106, 'Anty Viral Tab', 'SR-03', '2029-02', '25 gm', 20, 70.00, 40.00, 1400.00, '2026-06-16 13:53:38'),
(679, 106, 'Antispos Tab', 'SR-02', '2029-02', '25 gm', 20, 70.00, 40.00, 1400.00, '2026-06-16 13:53:38'),
(680, 106, 'Anty Cold Tab', 'SR-06', '2028-12', '25 gm', 17, 70.00, 40.00, 1190.00, '2026-06-16 13:53:38'),
(681, 106, 'Anty cold tablet', 'SR-06', '2029-06', '25 gram', 3, 65.00, 40.00, 195.00, '2026-06-16 13:53:38'),
(682, 106, 'Feveron Tab', 'SR-02', '2029-02', '25 gm', 12, 70.00, 40.00, 840.00, '2026-06-16 13:53:38'),
(683, 106, 'Feveron Tablet', 'SR-06', '2029-06', '25 gram', 8, 65.00, 40.00, 520.00, '2026-06-16 13:53:38'),
(684, 107, 'R- Cid syr', 'SR-06', '2029-06', '200ml', 20, 130.00, 60.00, 2600.00, '2026-06-17 15:47:49'),
(685, 107, 'Pile -x drop', 'SR-03', '2028-03', '50ml', 30, 150.00, 60.00, 4500.00, '2026-06-17 15:47:49'),
(686, 107, 'Face care cream', 'SR-06', '2029-06', '50 gram', 35, 110.00, 60.00, 3850.00, '2026-06-17 15:47:49'),
(687, 108, 'R-skin drop', 'SR-04', '2028-04', '50ml', 20, 150.00, 50.00, 3000.00, '2026-06-20 02:31:35'),
(688, 108, 'R-neuro drop', 'SR-04', '2028-04', '50ml', 20, 150.00, 50.00, 3000.00, '2026-06-20 02:31:35'),
(689, 108, 'Pile -x drop', 'SR-03', '2028-03', '50ml', 20, 150.00, 50.00, 3000.00, '2026-06-20 02:31:35'),
(690, 108, 'R-warts capsule', 'SR-05', '2029-05', '30 capsule ', 13, 180.00, 50.00, 2340.00, '2026-06-20 02:31:35'),
(691, 108, 'R-warts capsule', 'SR-06', '2029-06', '30 capsule ', 7, 180.00, 50.00, 1260.00, '2026-06-20 02:31:35'),
(692, 108, 'Pile-x capsule', 'SR-05', '2028-05', '30 capsule ', 5, 180.00, 50.00, 900.00, '2026-06-20 02:31:35'),
(693, 108, 'Pile -x capsule', 'SR-06', '2029-06', '30 capsule ', 15, 180.00, 50.00, 2700.00, '2026-06-20 02:31:35'),
(694, 108, 'R-Artho Capsule', 'SR-06', '2029-06', '30 capsule ', 12, 180.00, 50.00, 2160.00, '2026-06-20 02:31:35'),
(695, 108, 'R-Artho Capsule', 'SR-06', '2029-06', '30 capsule ', 8, 180.00, 50.00, 1440.00, '2026-06-20 02:31:35'),
(696, 108, 'R-Skin syrup', 'SR-06', '2029-06', '200-ml', 18, 130.00, 50.00, 2340.00, '2026-06-20 02:31:35'),
(697, 108, 'R-Skin syrup', 'SR-06', '2029-06', '200-ml', 2, 130.00, 50.00, 260.00, '2026-06-20 02:31:35'),
(698, 109, 'R-Tone syrup', 'SR-06', '2029-06', '200-ml', 10, 130.00, 50.00, 1300.00, '2026-06-20 02:35:07'),
(699, 109, 'R-Lax syrup', 'SR-04', '2029-04', '100 ml', 10, 80.00, 50.00, 800.00, '2026-06-20 02:35:07'),
(700, 109, 'Urical tablet', 'SR-04', '2029-04', '25 gram', 6, 70.00, 50.00, 420.00, '2026-06-20 02:35:07'),
(701, 109, 'ostolife tablet', 'SR-04', '2029-05', '25 gram', 6, 70.00, 50.00, 420.00, '2026-06-20 02:35:07'),
(702, 109, 'Pain-x oil', 'SR-03', '2029-03', '30ml', 6, 80.00, 50.00, 480.00, '2026-06-20 02:35:07'),
(703, 110, 'R-Artho Capsule', 'SR-06', '2029-06', '30 capsule ', 10, 180.00, 50.00, 1800.00, '2026-06-20 02:38:36'),
(704, 111, 'Rheumo -SR syrup', 'SR-06', '2029-06', '200-ml', 20, 130.00, 50.00, 2600.00, '2026-06-22 03:49:10'),
(705, 112, 'Pile-x capsule', 'SR-06', '2029-06', '30 capsule ', 35, 180.00, 50.00, 6300.00, '2026-06-22 07:40:02'),
(706, 112, 'Pile -x capsule', 'SR-06', '2029-06', '30 capsule ', 5, 180.00, 50.00, 900.00, '2026-06-22 07:40:02'),
(707, 112, 'R-warts capsule', 'SR-06', '2029-06', '30 capsule ', 40, 180.00, 50.00, 7200.00, '2026-06-22 07:40:02'),
(708, 112, 'R-Artho Capsule', 'SR-06', '2029-06', '30 capsule ', 10, 180.00, 50.00, 1800.00, '2026-06-22 07:40:02'),
(709, 112, 'R-Artho Capsule', 'SR-06', '2029-06', '30 capsule ', 30, 180.00, 50.00, 5400.00, '2026-06-22 07:40:02'),
(710, 112, 'R-Typho syrup', 'SR-05', '2029-06', '100 ml', 10, 80.00, 50.00, 800.00, '2026-06-22 07:40:02'),
(711, 113, 'R-Skine Tab', 'SR-05', '2029-05', '25 g', 6, 70.00, 50.00, 420.00, '2026-06-22 11:25:28'),
(712, 113, 'R-Skin syrup', 'SR-06', '2029-06', '200-ml', 6, 130.00, 50.00, 780.00, '2026-06-22 11:25:28'),
(713, 113, 'Hemacuf tablet', 'SR-03', '2029-03', '25 gram', 6, 70.00, 50.00, 420.00, '2026-06-22 11:25:28'),
(714, 113, 'Hemacuf syrup', 'SR-05', '2028-05', '100 ml', 10, 80.00, 50.00, 800.00, '2026-06-22 11:25:28'),
(715, 113, 'R-Typho syrup', 'SR-05', '2029-06', '100 ml', 10, 80.00, 50.00, 800.00, '2026-06-22 11:25:28'),
(716, 113, 'R-Cid syrup', 'SR-06', '2029-06', '200-ml', 10, 130.00, 50.00, 1300.00, '2026-06-22 11:25:28'),
(717, 113, 'Rheumo -SR syrup', 'SR-06', '2029-06', '200-ml', 10, 130.00, 50.00, 1300.00, '2026-06-22 11:25:28'),
(718, 113, 'R-Lax syrup', 'SR-04', '2029-04', '100 ml', 10, 80.00, 50.00, 800.00, '2026-06-22 11:25:28'),
(719, 113, 'R-Tone syrup', 'SR-03', '2029-03', '200-ml', 2, 130.00, 50.00, 260.00, '2026-06-22 11:25:28'),
(720, 113, 'R-Tone syrup', 'SR-06', '2029-06', '200-ml', 2, 130.00, 50.00, 260.00, '2026-06-22 11:25:28'),
(721, 113, 'R-Tone syrup', 'SR-06', '2029-06', '200-ml', 6, 130.00, 50.00, 780.00, '2026-06-22 11:25:28'),
(722, 113, 'Urical Tab', 'SR-01', '2029-01', '25 gm', 2, 70.00, 50.00, 140.00, '2026-06-22 11:25:28'),
(723, 113, 'Urical tablet', 'SR-04', '2029-05', '25 gram', 8, 70.00, 50.00, 560.00, '2026-06-22 11:25:28'),
(724, 113, 'Urical -SR', 'SR-06 ', '2029-06', '200ml', 10, 130.00, 50.00, 1300.00, '2026-06-22 11:25:28'),
(725, 113, 'Helco plas Tab', 'SR-03', '2029-03', '25 gram', 6, 65.00, 50.00, 390.00, '2026-06-22 11:25:28'),
(726, 113, 'R-neuro drop', 'SR-04', '2028-04', '50ml', 10, 150.00, 50.00, 1500.00, '2026-06-22 11:25:28'),
(727, 113, 'Spondy Drop', 'SR-01', '2028-01', '50 ml', 10, 150.00, 50.00, 1500.00, '2026-06-22 11:25:28'),
(728, 113, 'Pile -x drop', 'SR-03', '2028-03', '50ml', 6, 150.00, 50.00, 900.00, '2026-06-22 11:25:28'),
(729, 113, 'Pile -x capsule', 'SR-06', '2029-06', '30 capsule ', 6, 180.00, 50.00, 1080.00, '2026-06-22 11:25:28'),
(730, 113, 'R-warts capsule', 'SR-06', '2029-06', '30 capsule ', 6, 180.00, 50.00, 1080.00, '2026-06-22 11:25:28'),
(731, 113, 'R-Artho Capsule', 'SR-06', '2029-06', '30 capsule ', 10, 180.00, 50.00, 1800.00, '2026-06-22 11:25:28'),
(732, 114, 'R-Typho syrup', 'SR-05', '2029-06', '100 ml', 10, 80.00, 50.00, 800.00, '2026-06-26 14:06:50'),
(733, 114, 'R-Cid syrup', 'SR-06', '2029-06', '200-ml', 6, 130.00, 50.00, 780.00, '2026-06-26 14:06:50'),
(734, 114, 'Hemacuf syrup', 'SR-05', '2028-05', '100 ml', 6, 80.00, 50.00, 480.00, '2026-06-26 14:06:50'),
(735, 114, 'Urical -SR', 'SR-06 ', '2029-06', '200ml', 6, 130.00, 50.00, 780.00, '2026-06-26 14:06:50'),
(736, 114, 'Rheumo -SR syrup', 'SR-06', '2029-06', '200-ml', 6, 130.00, 50.00, 780.00, '2026-06-26 14:06:50'),
(737, 114, 'R-Tone syrup', 'SR-06', '2029-06', '200-ml', 6, 130.00, 50.00, 780.00, '2026-06-26 14:06:50'),
(738, 114, 'Hemacuf tablet', 'SR-03', '2029-03', '25 gram', 4, 70.00, 50.00, 280.00, '2026-06-26 14:06:50'),
(739, 114, 'Hemo tablet', 'SR-06', '2029-06', '25 gram', 6, 70.00, 50.00, 420.00, '2026-06-26 14:06:50'),
(740, 114, 'Anty Viral Tab', 'SR-03', '2029-02', '25 gm', 6, 70.00, 50.00, 420.00, '2026-06-26 14:06:50'),
(741, 114, 'Diarrhoea Tab', 'SR-02', '2029-02', '25 gm', 4, 70.00, 50.00, 280.00, '2026-06-26 14:06:50'),
(742, 114, 'Alleres -SR drop', 'SR-03 ', '2027-06', '50ml', 6, 150.00, 50.00, 900.00, '2026-06-26 14:06:50'),
(743, 114, 'R-Artho Capsule', 'SR-06', '2029-06', '30 capsule ', 6, 180.00, 50.00, 1080.00, '2026-06-26 14:06:50'),
(744, 114, 'Pain-x oil', 'SR-03', '2029-03', '30ml', 6, 80.00, 50.00, 480.00, '2026-06-26 14:06:50'),
(745, 114, 'Chest care oil', 'SR-04', '2029-04', '30 ml', 6, 80.00, 50.00, 480.00, '2026-06-26 14:06:50'),
(746, 115, 'R-neuro drop', 'SR-04', '2028-04', '50ml', 6, 150.00, 50.00, 900.00, '2026-06-27 05:00:59'),
(747, 115, 'Pile -x drop', 'SR-03', '2028-03', '50ml', 4, 150.00, 50.00, 600.00, '2026-06-27 05:00:59'),
(748, 115, 'Dant care', 'SR-04', '2029-04', '60 gram ', 4, 60.00, 50.00, 240.00, '2026-06-27 05:00:59'),
(749, 116, 'Rheumo -SR syrup', 'SR-06', '2029-06', '200-ml', 10, 130.00, 50.00, 1300.00, '2026-06-29 05:08:41'),
(750, 116, 'R-Cid syrup', 'SR-06', '2029-06', '200-ml', 4, 130.00, 50.00, 520.00, '2026-06-29 05:08:41'),
(751, 116, 'R-Skin syrup', 'SR-06', '2029-06', '200-ml', 5, 130.00, 50.00, 650.00, '2026-06-29 05:08:41'),
(752, 116, 'R-Typho syrup', 'SR-05', '2029-06', '100 ml', 5, 80.00, 50.00, 400.00, '2026-06-29 05:08:41'),
(753, 116, 'Hemacuf syrup', 'SR-05', '2028-05', '100 ml', 10, 80.00, 50.00, 800.00, '2026-06-29 05:08:41'),
(754, 116, 'Heart care syrup', 'SR-04', '2029-04', '200-ml', 3, 130.00, 50.00, 390.00, '2026-06-29 05:08:41'),
(755, 116, 'Urical -SR', 'SR-06 ', '2029-06', '200ml', 5, 130.00, 50.00, 650.00, '2026-06-29 05:08:41'),
(756, 116, 'Urical tablet', 'SR-04', '2029-05', '25 gram', 10, 70.00, 50.00, 700.00, '2026-06-29 05:08:41'),
(757, 116, 'ostolife tablet', 'SR-04', '2029-05', '25 gram', 10, 70.00, 50.00, 700.00, '2026-06-29 05:08:41'),
(758, 116, 'Feveron Tablet', 'SR-06', '2029-06', '25 gram', 10, 65.00, 50.00, 650.00, '2026-06-29 05:08:41'),
(759, 116, 'Brain Tab', 'SR-04', '2028-09', '25 gm', 10, 70.00, 50.00, 700.00, '2026-06-29 05:08:41'),
(760, 116, 'Hemo Tab', 'SR-02', '2029-02', '25 gm', 2, 70.00, 50.00, 140.00, '2026-06-29 05:08:41'),
(761, 116, 'Hemo tablet', 'SR-06', '2029-06', '25 gram', 4, 70.00, 50.00, 280.00, '2026-06-29 05:08:41'),
(762, 116, 'Dant care', 'SR-04', '2029-04', '60 gram ', 3, 60.00, 50.00, 180.00, '2026-06-29 05:08:41'),
(763, 116, 'Dant care', 'SR-06', '2029-06', '60 gram ', 7, 60.00, 50.00, 420.00, '2026-06-29 05:08:41'),
(764, 116, 'Hemacuf tablet', 'SR-03', '2029-03', '25 gram', 10, 70.00, 50.00, 700.00, '2026-06-29 05:08:41'),
(765, 116, 'Antispos Tab', 'SR-02', '2029-02', '25 gm', 5, 70.00, 50.00, 350.00, '2026-06-29 05:08:41'),
(766, 116, 'R-neuro drop', 'SR-04', '2028-04', '50ml', 5, 150.00, 50.00, 750.00, '2026-06-29 05:08:41'),
(767, 116, 'Spondy Drop', 'SR-01', '2028-01', '50 ml', 4, 150.00, 50.00, 600.00, '2026-06-29 05:08:41'),
(768, 116, 'Pile -x drop', 'SR-03', '2028-03', '50ml', 4, 150.00, 50.00, 600.00, '2026-06-29 05:08:41'),
(769, 116, 'R-warts capsule', 'SR-06', '2029-06', '30 capsule ', 3, 180.00, 50.00, 540.00, '2026-06-29 05:08:41'),
(770, 116, 'Pile-x capsule', 'SR-06', '2029-06', '30 capsule ', 3, 180.00, 50.00, 540.00, '2026-06-29 05:08:41'),
(771, 116, 'Face care cream', 'SR-06', '2029-06', '50 gram', 6, 110.00, 50.00, 660.00, '2026-06-29 05:08:41'),
(772, 117, 'Pile-x capsule', 'SR-06', '2029-06', '30 capsule ', 10, 180.00, 50.00, 1800.00, '2026-06-29 05:57:55'),
(773, 117, 'Face care cream', 'SR-06', '2029-06', '50 gram', 10, 110.00, 50.00, 1100.00, '2026-06-29 05:57:55'),
(774, 117, 'Pain-x oil', 'SR-03', '2029-03', '30ml', 10, 80.00, 50.00, 800.00, '2026-06-29 05:57:55'),
(775, 118, 'Hemagyne Drop', 'SR-02', '2028-02', '50 ml', 12, 150.00, 50.00, 1800.00, '2026-07-01 10:00:42'),
(776, 119, 'R-Artho Capsule', 'SR-06', '2029-06', '30 capsule ', 2, 180.00, 50.00, 360.00, '2026-07-03 06:14:38'),
(777, 119, 'Pile-x capsule', 'SR-06', '2029-06', '30 capsule ', 2, 180.00, 50.00, 360.00, '2026-07-03 06:14:38'),
(778, 119, 'Pile -x drop', 'SR-03', '2028-03', '50ml', 2, 150.00, 50.00, 300.00, '2026-07-03 06:14:38'),
(779, 119, 'R-neuro drop', 'SR-04', '2028-04', '50ml', 2, 150.00, 50.00, 300.00, '2026-07-03 06:14:38'),
(780, 119, 'ostolife tablet', 'SR-04', '2029-05', '25 gram', 2, 70.00, 50.00, 140.00, '2026-07-03 06:14:38'),
(781, 119, 'Pain-x oil', 'SR-03', '2029-03', '30ml', 2, 80.00, 50.00, 160.00, '2026-07-03 06:14:38'),
(782, 120, 'R-neuro drop', 'SR-04', '2028-04', '50ml', 6, 150.00, 50.00, 900.00, '2026-07-05 14:56:45'),
(783, 120, 'R-Tone syrup', 'SR-06', '2029-06', '200-ml', 6, 130.00, 50.00, 780.00, '2026-07-05 14:56:45'),
(784, 120, 'R-Cid syrup', 'SR-06', '2029-07', '200-ml', 3, 130.00, 50.00, 390.00, '2026-07-05 14:56:45'),
(785, 120, 'Hemacuf syrup', 'SR-05', '2028-05', '100 ml', 3, 80.00, 50.00, 240.00, '2026-07-05 14:56:45'),
(786, 120, 'Anty Viral Tab', 'SR-03', '2029-02', '25 gm', 6, 70.00, 50.00, 420.00, '2026-07-05 14:56:45'),
(787, 120, 'R-Typho syrup', 'SR-05', '2029-06', '100 ml', 6, 80.00, 50.00, 480.00, '2026-07-05 14:56:45'),
(788, 120, 'Rheumo -SR syrup', 'SR-06', '2029-06', '200-ml', 3, 130.00, 50.00, 390.00, '2026-07-05 14:56:45'),
(789, 121, 'Rheumo -SR syrup', 'SR-06', '2029-07', '200-ml', 20, 130.00, 60.00, 2600.00, '2026-07-07 03:51:16'),
(790, 121, 'R-Cid syrup', 'SR-06', '2029-07', '200-ml', 20, 130.00, 60.00, 2600.00, '2026-07-07 03:51:16'),
(791, 121, 'Hemagyne Syr', 'SR-06', '2029-06', '200ml', 20, 130.00, 60.00, 2600.00, '2026-07-07 03:51:16'),
(792, 121, 'R-Skin syrup', 'SR-07', '2029-07', '200-ml', 20, 130.00, 60.00, 2600.00, '2026-07-07 03:51:16'),
(793, 121, 'R-Tone syrup', 'SR-07', '2029-07', '200-ml', 20, 130.00, 60.00, 2600.00, '2026-07-07 03:51:16'),
(794, 121, 'Urical-SR syrup', 'SR-06', '2029-07', '200-ml', 20, 130.00, 60.00, 2600.00, '2026-07-07 03:51:16'),
(795, 121, 'Hart Care syrup', 'SR-06', '2029-06', '200-ml', 20, 130.00, 60.00, 2600.00, '2026-07-07 03:51:16'),
(796, 121, 'R-Liv syrup', 'SR-05', '2029-06', '200-ml', 20, 130.00, 60.00, 2600.00, '2026-07-07 03:51:16'),
(797, 121, 'Diabotone syrup', 'SR-05', '2029-05', '200-ml', 20, 130.00, 60.00, 2600.00, '2026-07-07 03:51:16'),
(798, 121, 'Hemacuf syrup', 'SR-05', '2028-05', '100 ml', 19, 80.00, 60.00, 1520.00, '2026-07-07 03:51:16'),
(799, 121, 'Hemacuf syrup', 'SR-07', '2029-07', '100 ml', 1, 80.00, 60.00, 80.00, '2026-07-07 03:51:16'),
(800, 121, 'R-Typho syrup', 'SR-05', '2029-06', '100 ml', 20, 80.00, 60.00, 1600.00, '2026-07-07 03:51:16'),
(801, 121, 'R-Lax syrup', 'SR-04', '2029-04', '100 ml', 20, 80.00, 60.00, 1600.00, '2026-07-07 03:51:16'),
(802, 121, 'Anty Viral Tab', 'SR-03', '2029-02', '25 gm', 10, 70.00, 60.00, 700.00, '2026-07-07 03:51:16'),
(803, 121, 'Anty cold tablet', 'SR-06', '2029-06', '25 gram', 10, 65.00, 60.00, 650.00, '2026-07-07 03:51:16'),
(804, 121, 'Feveron Tablet', 'SR-06', '2029-06', '25 gram', 10, 65.00, 60.00, 650.00, '2026-07-07 03:51:16'),
(805, 121, 'Antispos tablet', 'SR-06', '2029-06', '25 gram', 10, 65.00, 60.00, 650.00, '2026-07-07 03:51:16'),
(806, 121, 'Hemacuf tablet', 'SR-03', '2029-03', '25 gram', 10, 70.00, 60.00, 700.00, '2026-07-07 03:51:16'),
(807, 121, 'Brain tablet', 'SR-06', '2029-06', '25 gram', 10, 65.00, 60.00, 650.00, '2026-07-07 03:51:16'),
(808, 121, 'ostolife tablet', 'SR-04', '2029-05', '25 gram', 10, 70.00, 60.00, 700.00, '2026-07-07 03:51:16'),
(809, 121, 'Diarrhoea Tab', 'SR-02', '2029-02', '25 gm', 10, 70.00, 60.00, 700.00, '2026-07-07 03:51:16'),
(810, 121, 'Helco plas Tab', 'SR-03', '2029-03', '25 gram', 10, 65.00, 60.00, 650.00, '2026-07-07 03:51:16'),
(811, 121, 'Hemo tablet', 'SR-06', '2029-06', '25 gram', 10, 70.00, 60.00, 700.00, '2026-07-07 03:51:16'),
(812, 121, 'R-Skine Tab', 'SR-05', '2029-05', '25 g', 10, 70.00, 60.00, 700.00, '2026-07-07 03:51:16'),
(813, 121, 'R-power tablet', 'SR-04', '2029-04', '25 gram', 10, 70.00, 60.00, 700.00, '2026-07-07 03:51:16'),
(814, 121, 'Urical tablet', 'SR-04', '2029-05', '25 gram', 10, 70.00, 60.00, 700.00, '2026-07-07 03:51:16'),
(815, 121, 'Livo care drop', 'SR-05', '2028-05', '50ml', 10, 150.00, 60.00, 1500.00, '2026-07-07 03:51:16'),
(816, 121, 'Hemagyne Drop', 'SR-02', '2028-02', '50 ml', 10, 150.00, 60.00, 1500.00, '2026-07-07 03:51:16'),
(817, 121, 'R-neuro drop', 'SR-05', '2028-05', '50ml', 10, 150.00, 60.00, 1500.00, '2026-07-07 03:51:16'),
(818, 121, 'R-skin drop', 'SR-04', '2028-04', '50ml', 10, 150.00, 60.00, 1500.00, '2026-07-07 03:51:16'),
(819, 121, 'Spondy Drop', 'SR-01', '2028-01', '50 ml', 10, 150.00, 60.00, 1500.00, '2026-07-07 03:51:16'),
(820, 121, 'Pile -x drop', 'SR-03', '2028-03', '50ml', 10, 150.00, 60.00, 1500.00, '2026-07-07 03:51:16'),
(821, 121, 'Alleres -SR drop', 'SR-03 ', '2027-06', '50ml', 10, 150.00, 60.00, 1500.00, '2026-07-07 03:51:16'),
(822, 121, 'BP care drop', 'SR-01', '2027-06', '50ml', 5, 150.00, 60.00, 750.00, '2026-07-07 03:51:16'),
(823, 121, 'Pile-x capsule', 'SR-06', '2029-06', '30 capsule ', 10, 180.00, 60.00, 1800.00, '2026-07-07 03:51:16'),
(824, 121, 'R-warts capsule', 'SR-06', '2029-06', '30 capsule ', 10, 180.00, 60.00, 1800.00, '2026-07-07 03:51:16'),
(825, 121, 'Power -x capsule', 'SR-04', '2029-04', '25 capsule ', 10, 150.00, 60.00, 1500.00, '2026-07-07 03:51:16'),
(826, 121, 'R-Artho Capsule', 'SR-06 ', '2029-06', '30 capsule ', 10, 180.00, 60.00, 1800.00, '2026-07-07 03:51:16'),
(827, 121, 'Pain-x oil', 'SR-03', '2029-03', '30ml', 10, 80.00, 60.00, 800.00, '2026-07-07 03:51:16'),
(828, 121, 'Chest care oil', 'SR-04', '2029-04', '30 ml', 10, 80.00, 60.00, 800.00, '2026-07-07 03:51:16'),
(829, 121, 'Face care cream', 'SR-07', '2029-07', '50 gram', 10, 110.00, 60.00, 1100.00, '2026-07-07 03:51:16'),
(830, 121, 'Dant care', 'SR-06', '2029-06', '60 gram ', 10, 60.00, 60.00, 600.00, '2026-07-07 03:51:16'),
(831, 122, 'R-Skin syrup', 'SR-06', '2029-06', '200-ml', 5, 130.00, 50.00, 650.00, '2026-07-07 08:42:05'),
(832, 122, 'R-Skin syrup', 'SR-07', '2029-07', '200-ml', 15, 130.00, 50.00, 1950.00, '2026-07-07 08:42:05'),
(833, 123, 'R-neuro drop', 'SR-04', '2028-04', '50ml', 6, 150.00, 60.00, 900.00, '2026-07-09 07:57:37'),
(834, 123, 'R-neuro drop', 'SR-05', '2028-05', '50ml', 9, 150.00, 60.00, 1350.00, '2026-07-09 07:57:37'),
(835, 123, 'R-skin drop', 'SR-04', '2028-04', '50ml', 10, 150.00, 60.00, 1500.00, '2026-07-09 07:57:37'),
(836, 123, 'R-Artho Capsule', 'SR-06 ', '2029-06', '30 capsule ', 10, 180.00, 60.00, 1800.00, '2026-07-09 07:57:37'),
(837, 123, 'Rheumo -SR syrup', 'SR-06', '2029-07', '200-ml', 15, 130.00, 60.00, 1950.00, '2026-07-09 07:57:37'),
(838, 123, 'R-Cid syrup', 'SR-06', '2029-07', '200-ml', 20, 130.00, 60.00, 2600.00, '2026-07-09 07:57:37'),
(839, 124, 'R-Typho syrup', 'SR-06', '2029-07', '100 ml', 6, 80.00, 50.00, 480.00, '2026-07-09 09:07:16'),
(840, 124, 'Hemacuf syrup', 'SR-07', '2029-07', '100 ml', 6, 80.00, 50.00, 480.00, '2026-07-09 09:07:16'),
(841, 124, 'R-Skin syrup', 'SR-07', '2029-07', '200-ml', 2, 130.00, 50.00, 260.00, '2026-07-09 09:07:16'),
(842, 124, 'R-Tone syrup', 'SR-06', '2029-06', '200-ml', 2, 130.00, 50.00, 260.00, '2026-07-09 09:07:16'),
(843, 124, 'Anty Viral Tab', 'SR-03', '2029-02', '25 gm', 2, 70.00, 50.00, 140.00, '2026-07-09 09:07:16'),
(844, 124, 'Feveron Tablet', 'SR-06', '2029-06', '25 gram', 2, 65.00, 50.00, 130.00, '2026-07-09 09:07:16'),
(845, 124, 'R-Skine Tab', 'SR-05', '2029-05', '25 g', 2, 70.00, 50.00, 140.00, '2026-07-09 09:07:16'),
(846, 124, 'Helco plas Tab', 'SR-03', '2029-03', '25 gram', 1, 65.00, 50.00, 65.00, '2026-07-09 09:07:16'),
(847, 124, 'Hemacuf tablet', 'SR-03', '2029-03', '25 gram', 2, 70.00, 50.00, 140.00, '2026-07-09 09:07:16'),
(848, 124, 'ostolife tablet', 'SR-04', '2029-05', '25 gram', 2, 70.00, 50.00, 140.00, '2026-07-09 09:07:16'),
(849, 124, 'Anty cold tablet', 'SR-06', '2029-06', '25 gram', 1, 65.00, 50.00, 65.00, '2026-07-09 09:07:16'),
(850, 124, 'Antispos Tab', 'SR-02', '2029-02', '25 gm', 1, 70.00, 50.00, 70.00, '2026-07-09 09:07:16'),
(851, 124, 'R-power tablet', 'SR-04', '2029-04', '25 gram', 2, 70.00, 50.00, 140.00, '2026-07-09 09:07:16'),
(852, 124, 'Hemo tablet', 'SR-06', '2029-06', '25 gram', 2, 70.00, 50.00, 140.00, '2026-07-09 09:07:16'),
(865, 127, 'R-Cid syrup', 'SR-06', '2029-07', '200-ml', 2, 130.00, 50.00, 260.00, '2026-07-09 09:15:46'),
(866, 127, 'R-Skin syrup', 'SR-07', '2029-07', '200-ml', 2, 130.00, 50.00, 260.00, '2026-07-09 09:15:46'),
(867, 127, 'R-power tablet', 'SR-04', '2029-04', '25 gram', 4, 70.00, 50.00, 280.00, '2026-07-09 09:15:46'),
(868, 127, 'R-Skine Tab', 'SR-05', '2029-05', '25 g', 2, 70.00, 50.00, 140.00, '2026-07-09 09:15:46'),
(869, 127, 'ostolife tablet', 'SR-04', '2029-05', '25 gram', 2, 70.00, 50.00, 140.00, '2026-07-09 09:15:46'),
(870, 127, 'Antispos Tab', 'SR-02', '2029-02', '25 gm', 2, 70.00, 50.00, 140.00, '2026-07-09 09:15:46'),
(871, 127, 'Diarrhoea Tab', 'SR-02', '2029-02', '25 gm', 1, 70.00, 50.00, 70.00, '2026-07-09 09:15:46'),
(872, 127, 'Helco plas Tab', 'SR-03', '2029-03', '25 gram', 2, 65.00, 50.00, 130.00, '2026-07-09 09:15:46'),
(873, 127, 'Feveron Tablet', 'SR-06', '2029-06', '25 gram', 2, 65.00, 50.00, 130.00, '2026-07-09 09:15:46'),
(874, 127, 'Anty cold tablet', 'SR-06', '2029-06', '25 gram', 2, 65.00, 50.00, 130.00, '2026-07-09 09:15:46'),
(875, 127, 'Hemo tablet', 'SR-06', '2029-06', '25 gram', 2, 70.00, 50.00, 140.00, '2026-07-09 09:15:46'),
(876, 127, 'Pain-x oil', 'SR-03', '2029-03', '30ml', 4, 80.00, 50.00, 320.00, '2026-07-09 09:15:46'),
(877, 127, 'Spondy Drop', 'SR-01', '2028-01', '50 ml', 1, 150.00, 50.00, 150.00, '2026-07-09 09:15:46'),
(878, 127, 'Hemacuf syrup', 'SR-07', '2029-07', '100 ml', 2, 80.00, 50.00, 160.00, '2026-07-09 09:15:46'),
(879, 128, 'Diabotone syrup', 'SR-07', '2029-07', '200-ml', 10, 130.00, 50.00, 1300.00, '2026-07-12 02:50:37'),
(880, 128, 'R-Skin syrup', 'SR-07', '2029-07', '200-ml', 20, 130.00, 50.00, 2600.00, '2026-07-12 02:50:37'),
(881, 128, 'Rheumo -SR syrup', 'SR-06', '2029-07', '200-ml', 16, 130.00, 50.00, 2080.00, '2026-07-12 02:50:37'),
(882, 128, 'Rheumo -SR syrup', 'SR-06', '2029-06', '200-ml', 4, 130.00, 50.00, 520.00, '2026-07-12 02:50:37'),
(883, 128, 'Urical-SR syrup', 'SR-06', '2029-07', '200-ml', 20, 130.00, 50.00, 2600.00, '2026-07-12 02:50:37'),
(884, 128, 'R-Liv syrup', 'SR-05', '2029-06', '200-ml', 20, 130.00, 50.00, 2600.00, '2026-07-12 02:50:37'),
(885, 128, 'R-Cid syrup', 'SR-07', '2029-07', '200-ml', 20, 130.00, 50.00, 2600.00, '2026-07-12 02:50:37'),
(886, 128, 'Hart Care syrup', 'SR-06', '2029-06', '200-ml', 10, 130.00, 50.00, 1300.00, '2026-07-12 02:50:37'),
(887, 128, 'R-Artho Capsule', 'SR-06 ', '2029-06', '30 capsule ', 20, 180.00, 50.00, 3600.00, '2026-07-12 02:50:37'),
(888, 128, 'Brain tablet', 'SR-06', '2029-06', '25 gram', 20, 65.00, 50.00, 1300.00, '2026-07-12 02:50:37'),
(889, 128, 'R-neuro drop', 'SR-05', '2028-05', '50ml', 20, 150.00, 50.00, 3000.00, '2026-07-12 02:50:37'),
(890, 129, 'Anty Viral Tab', 'SR-03', '2029-02', '25 gm', 20, 70.00, 40.00, 1400.00, '2026-07-13 03:59:36'),
(891, 129, 'Antispos Tab', 'SR-02', '2029-02', '25 gm', 4, 70.00, 40.00, 280.00, '2026-07-13 03:59:36'),
(892, 129, 'Antispos tablet', 'SR-06', '2029-06', '25 gram', 16, 65.00, 40.00, 1040.00, '2026-07-13 03:59:36'),
(893, 129, 'Anty cold tablet', 'SR-06', '2029-06', '25 gram', 20, 65.00, 40.00, 1300.00, '2026-07-13 03:59:36'),
(894, 129, 'Diarrhoea Tab', 'SR-02', '2029-02', '25 gm', 20, 70.00, 40.00, 1400.00, '2026-07-13 03:59:36'),
(895, 129, 'R-Skine Tab', 'SR-05', '2029-05', '25 g', 20, 70.00, 40.00, 1400.00, '2026-07-13 03:59:36'),
(896, 129, 'Hemo tablet', 'SR-06', '2029-06', '25 gram', 20, 70.00, 40.00, 1400.00, '2026-07-13 03:59:36'),
(897, 129, 'Feveron Tablet', 'SR-06', '2029-06', '25 gram', 20, 65.00, 40.00, 1300.00, '2026-07-13 03:59:36'),
(898, 129, 'R-power tablet', 'SR-04', '2029-05', '25 gram', 20, 70.00, 40.00, 1400.00, '2026-07-13 03:59:36'),
(899, 129, 'Ostolife tablet', 'SR-04', '2029-04', '25 gram', 20, 70.00, 40.00, 1400.00, '2026-07-13 03:59:36'),
(900, 129, 'Hemacuf tablet', 'SR-03', '2029-03', '25 gram', 20, 70.00, 40.00, 1400.00, '2026-07-13 03:59:36'),
(901, 129, 'Brain Tab', 'SR-04', '2028-09', '25 gm', 4, 70.00, 40.00, 280.00, '2026-07-13 03:59:36'),
(902, 129, 'Brain tablet', 'SR-06', '2029-06', '25 gram', 16, 65.00, 40.00, 1040.00, '2026-07-13 03:59:36'),
(903, 129, 'Helco plas Tab', 'SR-03', '2029-03', '25 gram', 20, 65.00, 40.00, 1300.00, '2026-07-13 03:59:36'),
(904, 129, 'Urical tablet', 'SR-04', '2029-05', '25 gram', 20, 70.00, 40.00, 1400.00, '2026-07-13 03:59:36'),
(905, 130, 'Anty Viral Tab', 'SR-03', '2029-02', '25 gm', 2, 70.00, 50.00, 140.00, '2026-07-13 06:56:14'),
(906, 130, 'Antispos tablet', 'SR-06', '2029-06', '25 gram', 2, 65.00, 50.00, 130.00, '2026-07-13 06:56:14'),
(907, 130, 'Anty cold tablet', 'SR-06', '2029-06', '25 gram', 2, 65.00, 50.00, 130.00, '2026-07-13 06:56:14'),
(908, 130, 'Diarrhoea Tab', 'SR-02', '2029-02', '25 gm', 2, 70.00, 50.00, 140.00, '2026-07-13 06:56:14'),
(909, 130, 'R-Skine Tab', 'SR-05', '2029-05', '25 g', 2, 70.00, 50.00, 140.00, '2026-07-13 06:56:14'),
(910, 130, 'Hemo tablet', 'SR-06', '2029-06', '25 gram', 2, 70.00, 50.00, 140.00, '2026-07-13 06:56:14'),
(911, 130, 'Feveron Tablet', 'SR-06', '2029-06', '25 gram', 2, 65.00, 50.00, 130.00, '2026-07-13 06:56:14'),
(912, 130, 'R-power tablet', 'SR-04', '2029-05', '25 gram', 2, 70.00, 50.00, 140.00, '2026-07-13 06:56:14'),
(913, 130, 'Ostolife tablet', 'SR-04', '2029-04', '25 gram', 2, 70.00, 50.00, 140.00, '2026-07-13 06:56:14'),
(914, 130, 'Hemacuf tablet', 'SR-03', '2029-03', '25 gram', 2, 70.00, 50.00, 140.00, '2026-07-13 06:56:14'),
(915, 130, 'Brain tablet', 'SR-06', '2029-06', '25 gram', 2, 65.00, 50.00, 130.00, '2026-07-13 06:56:14'),
(916, 130, 'Helco plas Tab', 'SR-03', '2029-03', '25 gram', 2, 65.00, 50.00, 130.00, '2026-07-13 06:56:14'),
(917, 130, 'Urical tablet', 'SR-04', '2029-05', '25 gram', 2, 70.00, 50.00, 140.00, '2026-07-13 06:56:14'),
(918, 130, 'Rhuemo-Sr', 'Sr-05', '2029-05', '200ml', 1, 130.00, 50.00, 130.00, '2026-07-13 06:56:14'),
(919, 130, 'Hemagyne Syr', 'SR-06', '2029-06', '200ml', 2, 130.00, 50.00, 260.00, '2026-07-13 06:56:14'),
(920, 130, 'R-Skin syrup', 'SR-07', '2029-07', '200-ml', 2, 130.00, 50.00, 260.00, '2026-07-13 06:56:14'),
(921, 130, 'R-Cid syrup', 'SR-06', '2029-07', '200-ml', 2, 130.00, 50.00, 260.00, '2026-07-13 06:56:14'),
(922, 130, 'Urical-SR syrup', 'SR-06', '2029-07', '200-ml', 2, 130.00, 50.00, 260.00, '2026-07-13 06:56:14'),
(923, 130, 'R-Liv syrup', 'SR-03', '2029-03', '200-ml', 2, 130.00, 50.00, 260.00, '2026-07-13 06:56:14'),
(924, 130, 'R-Lax syrup', 'SR-04', '2029-04', '100 ml', 2, 80.00, 50.00, 160.00, '2026-07-13 06:56:14'),
(925, 130, 'Heart care syrup', 'SR-04', '2029-04', '200-ml', 2, 130.00, 50.00, 260.00, '2026-07-13 06:56:14'),
(926, 130, 'R-Typho syrup', 'SR-05', '2029-06', '100 ml', 2, 80.00, 50.00, 160.00, '2026-07-13 06:56:14'),
(927, 130, 'Hemacuf syrup', 'SR-07', '2029-07', '100 ml', 2, 80.00, 50.00, 160.00, '2026-07-13 06:56:14'),
(928, 130, 'R-Tone syrup', 'SR-06', '2029-06', '200-ml', 1, 130.00, 50.00, 130.00, '2026-07-13 06:56:14'),
(929, 130, 'R-Tone syrup', 'SR-07', '2029-07', '200-ml', 1, 130.00, 50.00, 130.00, '2026-07-13 06:56:14'),
(930, 130, 'Hemagyne Drop', 'SR-02', '2028-02', '50 ml', 2, 150.00, 50.00, 300.00, '2026-07-13 06:56:14'),
(931, 130, 'BP care drop', 'SR-01', '2027-06', '50ml', 2, 150.00, 50.00, 300.00, '2026-07-13 06:56:14'),
(932, 130, 'Livo care drop', 'SR-05', '2028-05', '50ml', 2, 150.00, 50.00, 300.00, '2026-07-13 06:56:14'),
(933, 130, 'Alleres -SR drop', 'SR-03 ', '2027-06', '50ml', 2, 150.00, 50.00, 300.00, '2026-07-13 06:56:14'),
(934, 130, 'Pile -x drop', 'SR-03', '2028-03', '50ml', 2, 150.00, 50.00, 300.00, '2026-07-13 06:56:14'),
(935, 130, 'R-neuro drop', 'SR-05', '2028-05', '50ml', 5, 150.00, 50.00, 750.00, '2026-07-13 06:56:14'),
(936, 130, 'R-skin drop', 'SR-04', '2028-04', '50ml', 2, 150.00, 50.00, 300.00, '2026-07-13 06:56:14'),
(937, 130, 'Spondy Drop', 'SR-01', '2028-01', '50 ml', 2, 150.00, 50.00, 300.00, '2026-07-13 06:56:14'),
(938, 130, 'Pile-x capsule', 'SR-06', '2029-06', '30 capsule ', 2, 180.00, 50.00, 360.00, '2026-07-13 06:56:14'),
(939, 130, 'Pain-x oil', 'SR-03', '2029-03', '30ml', 2, 80.00, 50.00, 160.00, '2026-07-13 06:56:14'),
(940, 130, 'R-Artho Capsule', 'SR-06 ', '2029-06', '30 capsule ', 2, 180.00, 50.00, 360.00, '2026-07-13 06:56:14'),
(941, 130, 'Pile-x capsule', 'SR-06', '2029-06', '30 capsule ', 2, 180.00, 50.00, 360.00, '2026-07-13 06:56:14'),
(942, 130, 'Power-X Capsule', 'SR-03', '2028-03', '30 capsul', 2, 180.00, 50.00, 360.00, '2026-07-13 06:56:14'),
(943, 130, 'Chest care oil', 'SR-04', '2029-04', '30 ml', 2, 80.00, 50.00, 160.00, '2026-07-13 06:56:14'),
(944, 130, 'Face care cream', 'SR-07', '2029-07', '50 gram', 2, 110.00, 50.00, 220.00, '2026-07-13 06:56:14'),
(945, 130, 'Dant care', 'SR-06', '2029-06', '60 gram ', 2, 60.00, 50.00, 120.00, '2026-07-13 06:56:14'),
(946, 130, 'Diabotone syrup', 'SR-05', '2029-05', '200-ml', 2, 130.00, 50.00, 260.00, '2026-07-13 06:56:14'),
(947, 131, 'R-Liv syrup', 'SR-03', '2029-03', '200-ml', 5, 130.00, 50.00, 650.00, '2026-07-15 03:56:33'),
(948, 131, 'R-Liv syrup', 'SR-05', '2029-06', '200-ml', 1, 130.00, 50.00, 130.00, '2026-07-15 03:56:33'),
(949, 131, 'Hemagyne Syr', 'SR-06', '2029-06', '200ml', 6, 130.00, 50.00, 780.00, '2026-07-15 03:56:33'),
(950, 131, 'Hemacuf syrup', 'SR-07', '2029-07', '100 ml', 6, 80.00, 50.00, 480.00, '2026-07-15 03:56:33'),
(951, 131, 'R-Typho syrup', 'SR-06', '2029-07', '100 ml', 6, 80.00, 50.00, 480.00, '2026-07-15 03:56:33'),
(952, 132, 'R-Artho Capsule', 'SR-06 ', '2029-06', '30 capsule ', 6, 180.00, 50.00, 1080.00, '2026-07-15 04:00:49'),
(953, 132, 'Rheumo- SR', 'SR-07', '2029-07', '30 capsule ', 6, 130.00, 50.00, 780.00, '2026-07-15 04:00:49'),
(954, 132, 'Hemagyne Syr', 'SR-06', '2029-06', '200ml', 6, 130.00, 50.00, 780.00, '2026-07-15 04:00:49'),
(955, 132, 'Hemacuf syrup', 'SR-07', '2029-07', '100 ml', 6, 80.00, 50.00, 480.00, '2026-07-15 04:00:49'),
(956, 132, 'R-Typho syrup', 'SR-05', '2029-06', '100 ml', 2, 80.00, 50.00, 160.00, '2026-07-15 04:00:49'),
(957, 132, 'R-Typho syrup', 'SR-06', '2029-07', '100 ml', 4, 80.00, 50.00, 320.00, '2026-07-15 04:00:49'),
(958, 133, 'R-neuro drop', 'SR-05', '2028-05', '50ml', 6, 150.00, 50.00, 900.00, '2026-07-15 04:04:40'),
(959, 133, 'Face care cream', 'SR-07', '2029-07', '50 gram', 4, 110.00, 50.00, 440.00, '2026-07-15 04:04:40'),
(960, 133, 'Pain-x oil', 'SR-07', '2029-07', '30ml', 6, 80.00, 50.00, 480.00, '2026-07-15 04:04:40'),
(961, 133, 'Rheumo- SR', 'SR-07', '2029-07', '30 capsule ', 6, 130.00, 50.00, 780.00, '2026-07-15 04:04:40'),
(962, 133, 'R-Typho syrup', 'SR-06', '2029-07', '100 ml', 6, 80.00, 50.00, 480.00, '2026-07-15 04:04:40'),
(963, 134, 'Heart care syrup', 'SR-04', '2029-04', '200-ml', 3, 130.00, 50.00, 390.00, '2026-07-15 04:08:50'),
(964, 134, 'Hart Care syrup', 'SR-06', '2029-06', '200-ml', 17, 130.00, 50.00, 2210.00, '2026-07-15 04:08:50'),
(965, 134, 'Pain-x oil', 'SR-07', '2029-07', '30ml', 10, 80.00, 50.00, 800.00, '2026-07-15 04:08:50'),
(966, 134, 'R-Tone syrup', 'SR-07', '2029-07', '200-ml', 10, 130.00, 50.00, 1300.00, '2026-07-15 04:08:50'),
(967, 134, 'Hemagyne Drop', 'SR-02', '2028-02', '50 ml', 10, 150.00, 50.00, 1500.00, '2026-07-15 04:08:50'),
(968, 135, 'R-Skin syrup', 'SR-07', '2029-07', '200-ml', 3, 130.00, 50.00, 390.00, '2026-07-15 04:18:23'),
(969, 135, 'R-Typho syrup', 'SR-06', '2029-07', '100 ml', 3, 80.00, 50.00, 240.00, '2026-07-15 04:18:23'),
(970, 135, 'R-Cid syrup', 'SR-06', '2029-07', '200-ml', 3, 130.00, 50.00, 390.00, '2026-07-15 04:18:23'),
(971, 135, 'R-Tone syrup', 'SR-07', '2029-07', '200-ml', 3, 130.00, 50.00, 390.00, '2026-07-15 04:18:23'),
(972, 135, 'Rheumo- SR', 'SR-07', '2029-07', '30 capsule ', 3, 130.00, 50.00, 390.00, '2026-07-15 04:18:23'),
(973, 135, 'Anty Viral Tab', 'SR-03', '2029-02', '25 gm', 3, 70.00, 50.00, 210.00, '2026-07-15 04:18:23'),
(974, 135, 'Hemo tablet', 'SR-06', '2029-06', '25 gram', 3, 70.00, 50.00, 210.00, '2026-07-15 04:18:23'),
(975, 135, 'Hemacuf tablet', 'SR-03', '2029-03', '25 gram', 3, 70.00, 50.00, 210.00, '2026-07-15 04:18:23'),
(976, 135, 'Ostolife tablet', 'SR-04', '2029-04', '25 gram', 3, 70.00, 50.00, 210.00, '2026-07-15 04:18:23'),
(977, 135, 'Urical tablet', 'SR-04', '2029-05', '25 gram', 3, 70.00, 50.00, 210.00, '2026-07-15 04:18:23'),
(978, 135, 'R-power tablet', 'SR-04', '2029-05', '25 gram', 3, 70.00, 50.00, 210.00, '2026-07-15 04:18:23'),
(979, 135, 'Brain tablet', 'SR-06', '2029-06', '25 gram', 3, 65.00, 50.00, 195.00, '2026-07-15 04:18:23'),
(980, 135, 'Alleres -SR drop', 'SR-03 ', '2027-06', '50ml', 3, 150.00, 50.00, 450.00, '2026-07-15 04:18:23'),
(981, 135, 'Pain-x oil', 'SR-07', '2029-07', '30ml', 3, 80.00, 50.00, 240.00, '2026-07-15 04:18:23'),
(982, 136, 'R-Artho Capsule', 'SR-06 ', '2029-06', '30 capsule ', 6, 180.00, 50.00, 1080.00, '2026-07-15 04:29:07'),
(983, 136, 'Antispos tablet', 'SR-06', '2029-06', '25 gram', 6, 65.00, 50.00, 390.00, '2026-07-15 04:29:07'),
(984, 136, 'Helco plas Tab', 'SR-03', '2029-03', '25 gram', 6, 65.00, 50.00, 390.00, '2026-07-15 04:29:07'),
(985, 136, 'R-power tablet', 'SR-04', '2029-05', '25 gram', 6, 70.00, 50.00, 420.00, '2026-07-15 04:29:07'),
(986, 136, 'Anty Viral Tab', 'SR-03', '2029-02', '25 gm', 6, 70.00, 50.00, 420.00, '2026-07-15 04:29:07'),
(987, 136, 'Brain tablet', 'SR-06', '2029-06', '25 gram', 6, 65.00, 50.00, 390.00, '2026-07-15 04:29:07'),
(988, 136, 'Hemacuf tablet', 'SR-03', '2029-03', '25 gram', 6, 70.00, 50.00, 420.00, '2026-07-15 04:29:07'),
(989, 136, 'R-Lax syrup', 'SR-04', '2029-04', '100 ml', 2, 80.00, 50.00, 160.00, '2026-07-15 04:29:07'),
(990, 136, 'R-Lax syrup', 'SR-07', '2029-07', '100 ml', 4, 80.00, 50.00, 320.00, '2026-07-15 04:29:07'),
(991, 136, 'R-Skin syrup', 'SR-07', '2029-07', '200-ml', 6, 130.00, 50.00, 780.00, '2026-07-15 04:29:07'),
(992, 136, 'R-Cid syrup', 'SR-07', '2029-07', '200-ml', 6, 130.00, 50.00, 780.00, '2026-07-15 04:29:07');
INSERT INTO `invoice_items` (`id`, `invoice_id`, `product_name`, `batch`, `expiry`, `capacity`, `qty`, `rate`, `discount`, `gross_amount`, `created_at`) VALUES
(993, 136, 'R-Tone syrup', 'SR-07', '2029-07', '200-ml', 6, 130.00, 50.00, 780.00, '2026-07-15 04:29:07'),
(994, 136, 'R-Typho syrup', 'SR-06', '2029-07', '100 ml', 6, 80.00, 50.00, 480.00, '2026-07-15 04:29:07'),
(995, 136, 'Hemagyne Syr', 'SR-06', '2029-06', '200ml', 4, 130.00, 50.00, 520.00, '2026-07-15 04:29:07'),
(996, 136, 'Hemagyne Syr', 'SR-07', '2029-07', '200ml', 2, 130.00, 50.00, 260.00, '2026-07-15 04:29:07'),
(997, 136, 'Urical -SR', 'SR-06 ', '2029-06', '200ml', 6, 130.00, 50.00, 780.00, '2026-07-15 04:29:07'),
(998, 136, 'Face care cream', 'SR-07', '2029-07', '50 gram', 6, 110.00, 50.00, 660.00, '2026-07-15 04:29:07'),
(999, 136, 'Pain-x oil', 'SR-07', '2029-07', '30ml', 6, 80.00, 50.00, 480.00, '2026-07-15 04:29:07'),
(1000, 137, 'Urical tablet', 'SR-04', '2029-05', '25 gram', 3, 70.00, 50.00, 210.00, '2026-07-15 05:20:19'),
(1001, 137, 'R-Skine Tab', 'SR-05', '2029-05', '25 g', 3, 70.00, 50.00, 210.00, '2026-07-15 05:20:19'),
(1002, 137, 'Feveron Tablet', 'SR-06', '2029-06', '25 gram', 3, 65.00, 50.00, 195.00, '2026-07-15 05:20:19'),
(1003, 137, 'Anty Viral Tab', 'SR-03', '2029-02', '25 gm', 3, 70.00, 50.00, 210.00, '2026-07-15 05:20:19'),
(1004, 137, 'Anty Viral Tab', 'SR-03', '2029-02', '25 gm', 3, 70.00, 50.00, 210.00, '2026-07-15 05:20:19'),
(1005, 137, 'Helco plas Tab', 'SR-03', '2029-03', '25 gram', 3, 65.00, 50.00, 195.00, '2026-07-15 05:20:19'),
(1006, 137, 'Brain tablet', 'SR-06', '2029-06', '25 gram', 3, 65.00, 50.00, 195.00, '2026-07-15 05:20:19'),
(1007, 137, 'Hemacuf tablet', 'SR-03', '2029-03', '25 gram', 3, 70.00, 50.00, 210.00, '2026-07-15 05:20:19'),
(1008, 137, 'Diarrhoea Tab', 'SR-02', '2029-02', '25 gm', 3, 70.00, 50.00, 210.00, '2026-07-15 05:20:19'),
(1009, 137, 'Antispos tablet', 'SR-06', '2029-06', '25 gram', 3, 65.00, 50.00, 195.00, '2026-07-15 05:20:19'),
(1010, 137, 'R-power tablet', 'SR-04', '2029-05', '25 gram', 3, 70.00, 50.00, 210.00, '2026-07-15 05:20:19'),
(1011, 137, 'Hemo tablet', 'SR-06', '2029-06', '25 gram', 3, 70.00, 50.00, 210.00, '2026-07-15 05:20:19'),
(1012, 137, 'Rheumo- SR', 'SR-07', '2029-07', '30 capsule ', 3, 130.00, 50.00, 390.00, '2026-07-15 05:20:19'),
(1013, 137, 'Diabotone syrup', 'SR-07', '2029-07', '200-ml', 3, 130.00, 50.00, 390.00, '2026-07-15 05:20:19'),
(1014, 137, 'R-Liv syrup', 'SR-05', '2029-06', '200-ml', 3, 130.00, 50.00, 390.00, '2026-07-15 05:20:19'),
(1015, 137, 'Urical-SR syrup', 'SR-06', '2029-07', '200-ml', 3, 130.00, 50.00, 390.00, '2026-07-15 05:20:19'),
(1016, 137, 'R-Tone syrup', 'SR-07', '2029-07', '200-ml', 3, 130.00, 50.00, 390.00, '2026-07-15 05:20:19'),
(1017, 137, 'R-Cid syrup', 'SR-06', '2029-07', '200-ml', 3, 130.00, 50.00, 390.00, '2026-07-15 05:20:19'),
(1018, 137, 'R-Skin syrup', 'SR-07', '2029-07', '200-ml', 2, 130.00, 50.00, 260.00, '2026-07-15 05:20:19'),
(1019, 137, 'R-Skin syrup', 'SR-07', '2029-07', '200-ml', 1, 130.00, 50.00, 130.00, '2026-07-15 05:20:19'),
(1020, 137, 'Hemagyne Syr', 'SR-07', '2029-07', '200ml', 3, 130.00, 50.00, 390.00, '2026-07-15 05:20:19'),
(1021, 137, 'R-Typho syrup', 'SR-06', '2029-07', '100 ml', 3, 80.00, 50.00, 240.00, '2026-07-15 05:20:19'),
(1022, 137, 'Hemacuf syrup', 'SR-07', '2029-07', '100 ml', 3, 80.00, 50.00, 240.00, '2026-07-15 05:20:19'),
(1023, 137, 'R-Lax syrup', 'SR-07', '2029-07', '100 ml', 3, 80.00, 50.00, 240.00, '2026-07-15 05:20:19'),
(1024, 137, 'Hart Care syrup', 'SR-06', '2029-06', '200-ml', 3, 130.00, 50.00, 390.00, '2026-07-15 05:20:19'),
(1025, 137, 'Hemagyne Drop', 'SR-02', '2028-02', '50 ml', 3, 150.00, 50.00, 450.00, '2026-07-15 05:20:19'),
(1026, 137, 'BP Care Drop', 'SR-04', '2027-09', '50 ml', 3, 150.00, 50.00, 450.00, '2026-07-15 05:20:19'),
(1027, 137, 'Livo care drop', 'SR-05', '2028-05', '50ml', 3, 150.00, 50.00, 450.00, '2026-07-15 05:20:19'),
(1028, 137, 'Pile -x drop', 'SR-03', '2028-03', '50ml', 3, 150.00, 50.00, 450.00, '2026-07-15 05:20:19'),
(1029, 137, 'R-neuro drop', 'SR-05', '2028-05', '50ml', 3, 150.00, 50.00, 450.00, '2026-07-15 05:20:19'),
(1030, 137, 'R-Skin drop', 'SR-07', '2028-07', '50ml', 3, 150.00, 50.00, 450.00, '2026-07-15 05:20:19'),
(1031, 137, 'Alleres -SR drop', 'SR-03 ', '2027-06', '50ml', 3, 150.00, 50.00, 450.00, '2026-07-15 05:20:19'),
(1032, 137, 'Spondy Drop', 'SR-01', '2028-01', '50 ml', 3, 150.00, 50.00, 450.00, '2026-07-15 05:20:19'),
(1033, 137, 'R-Artho Capsule', 'SR-06 ', '2029-06', '30 capsule ', 3, 180.00, 50.00, 540.00, '2026-07-15 05:20:19'),
(1034, 137, 'R-wats capsule', 'SR-07', '2029-07', '30 capsule ', 3, 180.00, 50.00, 540.00, '2026-07-15 05:20:19'),
(1035, 137, 'Pile-x capsule', 'SR-06', '2029-06', '30 capsule ', 3, 180.00, 50.00, 540.00, '2026-07-15 05:20:19'),
(1036, 137, 'Power -x capsule', 'SR-04', '2029-04', '25 capsule ', 3, 150.00, 50.00, 450.00, '2026-07-15 05:20:19'),
(1037, 137, 'Face care cream', 'SR-07', '2029-07', '50 gram', 3, 110.00, 50.00, 330.00, '2026-07-15 05:20:19'),
(1038, 137, 'Chest care oil', 'SR-04', '2029-04', '30 ml', 3, 80.00, 50.00, 240.00, '2026-07-15 05:20:19'),
(1039, 137, 'Pain-x oil', 'SR-07', '2029-07', '30ml', 3, 80.00, 50.00, 240.00, '2026-07-15 05:20:19'),
(1040, 137, 'Dant care', 'SR-06', '2029-06', '60gram ', 3, 60.00, 50.00, 180.00, '2026-07-15 05:20:19'),
(1041, 138, 'Diabotone', 'SR-03', '2029-03', '200 ml', 1, 130.00, 50.00, 130.00, '2026-07-15 05:30:43'),
(1042, 138, 'Diabotone syrup', 'SR-07', '2029-07', '200-ml', 2, 130.00, 50.00, 260.00, '2026-07-15 05:30:43'),
(1043, 139, 'Hemagyne Syr', 'SR-07', '2029-07', '200ml', 6, 130.00, 50.00, 780.00, '2026-07-15 05:42:21'),
(1044, 139, 'R-neuro drop', 'SR-05', '2028-05', '50ml', 10, 150.00, 50.00, 1500.00, '2026-07-15 05:42:21'),
(1045, 140, 'Urical -SR', 'SR-06 ', '2029-06', '200ml', 4, 130.00, 50.00, 520.00, '2026-07-15 07:03:03'),
(1046, 140, 'Urical-SR syrup', 'SR-06', '2029-07', '200-ml', 6, 130.00, 50.00, 780.00, '2026-07-15 07:03:03'),
(1047, 140, 'R-Cid syrup', 'SR-07', '2029-07', '200-ml', 10, 120.00, 50.00, 1200.00, '2026-07-15 07:03:03'),
(1048, 140, 'Urical Tab', 'SR-04', '2029-04', '25 g', 10, 70.00, 50.00, 700.00, '2026-07-15 07:03:03'),
(1049, 141, 'R-Lax syrup', 'SR-07', '2029-07', '100 ml', 10, 80.00, 60.00, 800.00, '2026-07-18 14:48:06'),
(1050, 141, 'R-Cid syrup', 'SR-07', '2029-07', '200-ml', 15, 130.00, 60.00, 1950.00, '2026-07-18 14:48:06'),
(1051, 141, 'Dant care', 'SR-06', '2029-06', '60gram ', 25, 60.00, 60.00, 1500.00, '2026-07-18 14:48:06'),
(1052, 142, 'Ostolife tablet', 'SR-04', '2029-04', '25 gram', 15, 70.00, 40.00, 1050.00, '2026-07-20 08:59:52'),
(1053, 142, 'Rheumo- SR', 'SR-07', '2029-07', '30 capsule ', 20, 130.00, 40.00, 2600.00, '2026-07-20 08:59:52'),
(1054, 143, 'Pain-x oil', 'SR-07', '2029-07', '30ml', 10, 80.00, 50.00, 800.00, '2026-07-22 08:52:31'),
(1055, 143, 'Urical-SR syrup', 'SR-06', '2029-07', '200-ml', 2, 130.00, 50.00, 260.00, '2026-07-22 08:52:31'),
(1056, 143, 'Urical-SR syrup', 'SR-07', '2029-07', '200-ml', 8, 130.00, 50.00, 1040.00, '2026-07-22 08:52:31'),
(1057, 143, 'R-Tone syrup', 'SR-07', '2029-07', '200-ml', 10, 130.00, 50.00, 1300.00, '2026-07-22 08:52:31'),
(1058, 143, 'R-Cid syrup', 'SR-07', '2029-07', '200-ml', 10, 130.00, 50.00, 1300.00, '2026-07-22 08:52:31'),
(1059, 143, 'Rheumo- SR', 'SR-07', '2029-07', '30 capsule ', 10, 130.00, 50.00, 1300.00, '2026-07-22 08:52:31'),
(1060, 143, 'Helco plas Tab', 'SR-03', '2029-03', '25 gram', 10, 65.00, 50.00, 650.00, '2026-07-22 08:52:31'),
(1061, 143, 'R-Artho Capsule', 'SR-06 ', '2029-06', '30 capsule ', 5, 180.00, 50.00, 900.00, '2026-07-22 08:52:31'),
(1062, 143, 'R-Artho Capsule', 'SR-07', '2029-07', '30 capsule ', 5, 180.00, 50.00, 900.00, '2026-07-22 08:52:31'),
(1063, 143, 'Pile-X capsule', 'SR-07', '2029-07', '30 capsule ', 10, 180.00, 50.00, 1800.00, '2026-07-22 08:52:31'),
(1064, 143, 'Livo care drop', 'SR-05', '2028-05', '50ml', 10, 150.00, 50.00, 1500.00, '2026-07-22 08:52:31'),
(1065, 144, 'R-Artho Capsule', 'SR-07', '2029-07', '30 capsule ', 30, 180.00, 50.00, 5400.00, '2026-07-22 08:55:58'),
(1066, 145, 'Pain-x oil', 'SR-07', '2029-07', '30ml', 20, 80.00, 50.00, 1600.00, '2026-07-22 09:14:26'),
(1067, 146, 'Pile -x drop', 'SR-03', '2028-03', '50ml', 12, 150.00, 50.00, 1800.00, '2026-07-22 09:17:08'),
(1068, 146, 'R-wats capsule', 'SR-07', '2029-07', '30 capsule ', 12, 180.00, 50.00, 2160.00, '2026-07-22 09:17:08'),
(1069, 147, 'Rheumo- SR', 'SR-07', '2029-07', '30 capsule ', 5, 130.00, 50.00, 650.00, '2026-07-23 04:52:35'),
(1070, 147, 'R-Artho Capsule', 'SR-07', '2029-07', '30 capsule ', 5, 180.00, 50.00, 900.00, '2026-07-23 04:52:35'),
(1071, 147, 'Pile -x drop', 'SR-03', '2028-03', '50ml', 5, 150.00, 50.00, 750.00, '2026-07-23 04:52:35'),
(1072, 147, 'R-Typho syrup', 'SR-06', '2029-07', '100 ml', 5, 80.00, 50.00, 400.00, '2026-07-23 04:52:35'),
(1073, 148, 'R-Skin drop', 'SR-07', '2028-07', '50ml', 25, 150.00, 60.00, 3750.00, '2026-07-24 07:25:17'),
(1074, 148, 'Hemagyne Syr', 'SR-07', '2029-07', '200ml', 15, 130.00, 60.00, 1950.00, '2026-07-24 07:25:17'),
(1075, 148, 'Urical-SR syrup', 'SR-07', '2029-07', '200-ml', 15, 130.00, 60.00, 1950.00, '2026-07-24 07:25:17'),
(1076, 148, 'R-Skine Tab', 'SR-05', '2029-05', '25 g', 15, 70.00, 60.00, 1050.00, '2026-07-24 07:25:17'),
(1077, 148, 'Rheumo- SR', 'SR-07', '2029-07', '30 capsule ', 3, 130.00, 60.00, 390.00, '2026-07-24 07:25:17'),
(1078, 148, 'Rheumo -SR syrup', 'SR-07', '2029-07', '200-ml', 12, 130.00, 60.00, 1560.00, '2026-07-24 07:25:17'),
(1079, 148, 'R-Artho Capsule', 'SR-06', '2029-06', '30 capsule ', 1, 180.00, 60.00, 180.00, '2026-07-24 07:25:17'),
(1080, 148, 'R-Artho Capsule', 'SR-07', '2029-07', '30 capsule ', 11, 180.00, 60.00, 1980.00, '2026-07-24 07:25:17'),
(1081, 148, 'R-neuro drop', 'SR-05', '2028-05', '50ml', 20, 150.00, 60.00, 3000.00, '2026-07-24 07:25:17'),
(1082, 148, 'R-Cid syrup', 'SR-07', '2029-07', '200-ml', 15, 130.00, 60.00, 1950.00, '2026-07-24 07:25:17'),
(1083, 149, 'Urical-SR syrup', 'SR-07', '2029-07', '200-ml', 20, 130.00, 50.00, 2600.00, '2026-07-25 03:55:15'),
(1084, 149, 'Urical Tab', 'SR-04', '2029-04', '25 g', 10, 70.00, 50.00, 700.00, '2026-07-25 03:55:15'),
(1085, 149, 'R-Artho Capsule', 'SR-07', '2029-07', '30 capsule ', 10, 180.00, 50.00, 1800.00, '2026-07-25 03:55:15'),
(1086, 150, 'R-Skin syrup', 'SR-07', '2029-07', '200-ml', 10, 130.00, 50.00, 1300.00, '2026-07-25 04:03:43'),
(1087, 150, 'Rheumo -SR syrup', 'SR-07', '2029-07', '200-ml', 10, 130.00, 50.00, 1300.00, '2026-07-25 04:03:43'),
(1088, 150, 'R-Cid syrup', 'SR-07', '2029-07', '200-ml', 3, 130.00, 50.00, 390.00, '2026-07-25 04:03:43'),
(1089, 150, 'R-Cid syrup', 'SR-07', '2029-07', '200-ml', 7, 130.00, 50.00, 910.00, '2026-07-25 04:03:43'),
(1090, 150, 'Urical-SR syrup', 'SR-07', '2029-07', '200-ml', 9, 130.00, 50.00, 1170.00, '2026-07-25 04:03:43'),
(1091, 150, 'Urical-SR syrup', 'SR-07', '2029-07', '200-ml', 1, 130.00, 50.00, 130.00, '2026-07-25 04:03:43'),
(1092, 150, 'Hart Care syrup', 'SR-06', '2029-06', '200-ml', 4, 130.00, 50.00, 520.00, '2026-07-25 04:03:43'),
(1093, 150, 'Heart care syrup', 'SR-07', '2029-07', '200-ml', 6, 130.00, 50.00, 780.00, '2026-07-25 04:03:43'),
(1094, 150, 'R-Artho Capsule', 'SR-07', '2029-07', '30 capsule ', 9, 180.00, 50.00, 1620.00, '2026-07-25 04:03:43'),
(1095, 150, 'R-Artho Capsule', 'SR-07', '2029-07', '30 capsule ', 11, 180.00, 50.00, 1980.00, '2026-07-25 04:03:43'),
(1096, 150, 'R-wats capsule', 'SR-07', '2029-07', '30 capsule ', 20, 180.00, 50.00, 3600.00, '2026-07-25 04:03:43'),
(1097, 150, 'Brain tablet', 'SR-06', '2029-06', '25 gram', 6, 65.00, 50.00, 390.00, '2026-07-25 04:03:43'),
(1098, 150, 'Pain-x oil', 'SR-07', '2029-07', '30ml', 10, 80.00, 50.00, 800.00, '2026-07-25 04:03:43'),
(1099, 150, 'Chest care oil', 'SR-04', '2029-04', '30 ml', 10, 80.00, 50.00, 800.00, '2026-07-25 04:03:43'),
(1100, 151, 'Rheumo -SR syrup', 'SR-07', '2029-07', '200-ml', 10, 130.00, 50.00, 1300.00, '2026-07-25 04:06:18'),
(1101, 151, 'Urical-SR syrup', 'SR-07', '2029-07', '200-ml', 10, 130.00, 50.00, 1300.00, '2026-07-25 04:06:18'),
(1102, 151, 'Urical Tab', 'SR-04', '2029-04', '25 g', 10, 70.00, 50.00, 700.00, '2026-07-25 04:06:18'),
(1103, 152, 'Rheumo -SR syrup', 'SR-07', '2029-07', '200-ml', 12, 130.00, 50.00, 1560.00, '2026-07-25 04:11:20'),
(1104, 152, 'Urical-SR syrup', 'SR-07', '2029-07', '200-ml', 12, 130.00, 50.00, 1560.00, '2026-07-25 04:11:20'),
(1105, 152, 'R-Cid syrup', 'SR-07', '2029-07', '200-ml', 12, 130.00, 50.00, 1560.00, '2026-07-25 04:11:20'),
(1106, 152, 'R-Skin syrup', 'SR-07', '2029-07', '200-ml', 12, 130.00, 50.00, 1560.00, '2026-07-25 04:11:20'),
(1107, 152, 'Brain tablet', 'SR-06', '2029-06', '25 gram', 10, 65.00, 50.00, 650.00, '2026-07-25 04:11:20'),
(1108, 152, 'Helco plas Tab', 'SR-03', '2029-03', '25 gram', 8, 65.00, 50.00, 520.00, '2026-07-25 04:11:20'),
(1109, 152, 'Antispos tablet', 'SR-06', '2029-06', '25 gram', 10, 65.00, 50.00, 650.00, '2026-07-25 04:11:20'),
(1110, 152, 'Anty Viral Tab', 'SR-03', '2029-02', '25 gm', 10, 70.00, 50.00, 700.00, '2026-07-25 04:11:20'),
(1111, 152, 'R-Typho syrup', 'SR-07', '2029-07', '100 ml', 10, 80.00, 50.00, 800.00, '2026-07-25 04:11:20'),
(1112, 152, 'Pile -x drop', 'SR-03', '2028-03', '50ml', 6, 150.00, 50.00, 900.00, '2026-07-25 04:11:20'),
(1113, 153, 'Livo care drop', 'SR-05', '2028-05', '50ml', 20, 150.00, 50.00, 3000.00, '2026-07-25 04:20:06'),
(1114, 153, 'R-Skin syrup', 'SR-07', '2029-07', '200-ml', 20, 130.00, 50.00, 2600.00, '2026-07-25 04:20:06'),
(1115, 153, 'R-Typho syrup', 'SR-07', '2029-07', '100 ml', 20, 80.00, 50.00, 1600.00, '2026-07-25 04:20:06'),
(1116, 153, 'Pile -x drop', 'SR-03', '2028-03', '50ml', 10, 150.00, 50.00, 1500.00, '2026-07-25 04:20:06'),
(1117, 153, 'Dant care', 'SR-06', '2029-06', '60gram ', 20, 60.00, 50.00, 1200.00, '2026-07-25 04:20:06'),
(1118, 153, 'R-Cid syrup', 'SR-07', '2029-07', '200-ml', 20, 130.00, 50.00, 2600.00, '2026-07-25 04:20:06'),
(1119, 154, 'Diabotone syrup', 'SR-07', '2029-07', '200-ml', 8, 130.00, 50.00, 1040.00, '2026-07-27 06:47:59'),
(1120, 154, 'Rheumo -SR syrup', 'SR-07', '2029-07', '200-ml', 6, 130.00, 50.00, 780.00, '2026-07-27 06:47:59'),
(1121, 154, 'Brain tablet', 'SR-06', '2029-06', '25 gram', 10, 65.00, 50.00, 650.00, '2026-07-27 06:47:59'),
(1122, 154, 'R-Liv syrup', 'SR-05', '2029-06', '200-ml', 4, 130.00, 50.00, 520.00, '2026-07-27 06:47:59'),
(1123, 154, 'Livo care drop', 'SR-05', '2028-05', '50ml', 2, 150.00, 50.00, 300.00, '2026-07-27 06:47:59'),
(1124, 154, 'Hemacuf syrup', 'SR-07', '2029-07', '100 ml', 6, 80.00, 50.00, 480.00, '2026-07-27 06:47:59'),
(1125, 154, 'R-Cid syrup', 'SR-07', '2029-07', '200-ml', 4, 130.00, 50.00, 520.00, '2026-07-27 06:47:59'),
(1126, 155, 'Rheumo -SR syrup', 'SR-07', '2029-07', '200-ml', 3, 130.00, 50.00, 390.00, '2026-07-28 11:02:34'),
(1127, 155, 'R-Cid syrup', 'SR-07', '2029-07', '200-ml', 4, 130.00, 50.00, 520.00, '2026-07-28 11:02:34'),
(1128, 155, 'Hemagyne Syr', 'SR-07', '2029-07', '200ml', 4, 130.00, 50.00, 520.00, '2026-07-28 11:02:34'),
(1129, 155, 'R-Tone syrup', 'SR-07', '2029-07', '200-ml', 4, 130.00, 50.00, 520.00, '2026-07-28 11:02:34'),
(1130, 155, 'R-Skin syrup', 'SR-07', '2029-07', '200-ml', 4, 130.00, 50.00, 520.00, '2026-07-28 11:02:34'),
(1131, 155, 'Diabotone syrup', 'SR-07', '2029-07', '200-ml', 4, 130.00, 50.00, 520.00, '2026-07-28 11:02:34'),
(1132, 155, 'Urical-SR syrup', 'SR-07', '2029-07', '200-ml', 2, 130.00, 50.00, 260.00, '2026-07-28 11:02:34'),
(1133, 155, 'Urical-SR syrup', 'SR-07', '2029-07', '200-ml', 2, 130.00, 50.00, 260.00, '2026-07-28 11:02:34'),
(1134, 155, 'R-Liv syrup', 'SR-05', '2029-06', '200-ml', 4, 130.00, 50.00, 520.00, '2026-07-28 11:02:34'),
(1135, 155, 'R-Lax syrup', 'SR-07', '2029-07', '100 ml', 4, 80.00, 50.00, 320.00, '2026-07-28 11:02:34'),
(1136, 155, 'R-Typho syrup', 'SR-07', '2029-07', '100 ml', 4, 80.00, 50.00, 320.00, '2026-07-28 11:02:34'),
(1137, 155, 'Hemacuf syrup', 'SR-07', '2029-07', '100 ml', 4, 80.00, 50.00, 320.00, '2026-07-28 11:02:34'),
(1138, 155, 'Heart care syrup', 'SR-07', '2029-07', '200-ml', 4, 130.00, 50.00, 520.00, '2026-07-28 11:02:34'),
(1139, 155, 'Face care cream', 'SR-07', '2029-07', '50 gram', 5, 110.00, 50.00, 550.00, '2026-07-28 11:02:34'),
(1140, 156, 'R-Tone syrup', 'SR-07', '2029-07', '200-ml', 12, 130.00, 60.00, 1560.00, '2026-07-28 12:47:54'),
(1141, 156, 'Pile -x drop', 'SR-03', '2028-03', '50ml', 26, 150.00, 60.00, 3900.00, '2026-07-28 12:47:54'),
(1142, 156, 'R-Cid syrup', 'SR-07', '2029-07', '200-ml', 3, 130.00, 60.00, 390.00, '2026-07-28 12:47:54'),
(1143, 156, 'R-Cid syrup', 'SR-07', '2029-07', '200-ml', 7, 130.00, 60.00, 910.00, '2026-07-28 12:47:54'),
(1144, 156, 'R-Artho Capsule', 'SR-07', '2029-07', '30 capsule ', 10, 180.00, 60.00, 1800.00, '2026-07-28 12:47:54'),
(1145, 156, 'Hemacuf syrup', 'SR-07', '2029-07', '100 ml', 15, 80.00, 60.00, 1200.00, '2026-07-28 12:47:54'),
(1146, 157, 'R-Typho syrup', 'SR-07', '2029-07', '100 ml', 25, 80.00, 50.00, 2000.00, '2026-07-30 04:46:22'),
(1147, 157, 'Hemacuf syrup', 'SR-07', '2029-07', '100 ml', 20, 80.00, 50.00, 1600.00, '2026-07-30 04:46:22'),
(1148, 157, 'Spondy Drop', 'SR-01', '2028-01', '50 ml', 20, 150.00, 50.00, 3000.00, '2026-07-30 04:46:22'),
(1149, 158, 'Rheumo -SR syrup', 'SR-08', '2029-08', '200-ml', 15, 130.00, 50.00, 1950.00, '2026-07-31 14:04:56'),
(1150, 158, 'Urical-SR syrup', 'SR-07', '2029-07', '200-ml', 10, 130.00, 50.00, 1300.00, '2026-07-31 14:04:56'),
(1151, 158, 'R-neuro drop', 'SR-08', '2026-08', '50ml', 20, 150.00, 50.00, 3000.00, '2026-07-31 14:04:56'),
(1152, 158, 'Spondy Drop', 'SR-01', '2028-01', '50 ml', 6, 150.00, 50.00, 900.00, '2026-07-31 14:04:56'),
(1153, 158, 'Chest care oil', 'SR-04', '2029-04', '30 ml', 10, 80.00, 50.00, 800.00, '2026-07-31 14:04:56'),
(1154, 159, 'Rheumo -SR syrup', 'SR-08', '2029-08', '200-ml', 20, 130.00, 50.00, 2600.00, '2026-08-04 03:50:53'),
(1155, 159, 'Spondy Drop', 'SR-01', '2028-01', '50 ml', 20, 150.00, 50.00, 3000.00, '2026-08-04 03:50:53'),
(1156, 159, 'R-neuro drop', 'SR-08', '2026-08', '50ml', 20, 150.00, 50.00, 3000.00, '2026-08-04 03:50:53'),
(1157, 159, 'Pile -x drop', 'SR-03', '2028-03', '50ml', 20, 150.00, 50.00, 3000.00, '2026-08-04 03:50:53'),
(1158, 160, 'R-neuro drop', 'SR-08', '2026-08', '50ml', 50, 150.00, 50.00, 7500.00, '2026-08-04 03:55:06'),
(1159, 161, 'Pile-X capsule', 'SR-07', '2029-07', '30 capsule ', 12, 180.00, 50.00, 2160.00, '2026-08-04 03:59:09'),
(1160, 161, 'R-Typho syrup', 'SR-07', '2029-07', '100 ml', 8, 80.00, 50.00, 640.00, '2026-08-04 03:59:09'),
(1161, 161, 'R-Typho syrup', 'SR-08', '2029-08', '100 ml', 4, 80.00, 50.00, 320.00, '2026-08-04 03:59:09'),
(1162, 162, 'Livo care drop', 'SR-05', '2028-05', '50ml', 10, 150.00, 50.00, 1500.00, '2026-08-04 04:07:02'),
(1163, 162, 'R-Cid syrup', 'SR-07', '2029-07', '200-ml', 10, 130.00, 50.00, 1300.00, '2026-08-04 04:07:02'),
(1164, 162, 'Hemagyne Syr', 'SR-07', '2029-07', '200ml', 10, 130.00, 50.00, 1300.00, '2026-08-04 04:07:02'),
(1165, 162, 'Dant care', 'SR-06', '2029-06', '60gram ', 20, 60.00, 50.00, 1200.00, '2026-08-04 04:07:02'),
(1166, 162, 'R-Typho syrup', 'SR-08', '2029-08', '100 ml', 20, 80.00, 50.00, 1600.00, '2026-08-04 04:07:02'),
(1167, 162, 'Heart care syrup', 'SR-07', '2029-07', '200-ml', 20, 130.00, 50.00, 2600.00, '2026-08-04 04:07:02'),
(1168, 162, 'Spondy Drop', 'SR-01', '2028-01', '50 ml', 15, 150.00, 50.00, 2250.00, '2026-08-04 04:07:02'),
(1169, 162, 'R-neuro drop', 'SR-08', '2026-08', '50ml', 20, 150.00, 50.00, 3000.00, '2026-08-04 04:07:02'),
(1170, 162, 'Pile -x drop', 'SR-03', '2028-03', '50ml', 6, 150.00, 50.00, 900.00, '2026-08-04 04:07:02'),
(1171, 163, 'R-neuro drop', 'SR-08', '2026-08', '50ml', 10, 150.00, 50.00, 1500.00, '2026-08-04 04:13:31'),
(1172, 163, 'Hemagyne Syr', 'SR-07', '2029-07', '200ml', 10, 130.00, 50.00, 1300.00, '2026-08-04 04:13:31'),
(1173, 163, 'Rheumo -SR syrup', 'SR-08', '2029-08', '200-ml', 6, 130.00, 50.00, 780.00, '2026-08-04 04:13:31'),
(1174, 164, 'Hemacuf syrup', 'SR-07', '2029-07', '100 ml', 32, 80.00, 50.00, 2560.00, '2026-08-04 04:41:26'),
(1175, 164, 'Hemacuf syrup', 'SR-08', '2029-08', '100 ml', 18, 80.00, 50.00, 1440.00, '2026-08-04 04:41:26'),
(1176, 165, 'Rheumo -SR syrup', 'SR-08', '2029-08', '200-ml', 11, 130.00, 50.00, 1430.00, '2026-08-05 10:50:09'),
(1177, 165, 'R-Liv syrup', 'SR-07', '2029-07', '200-ml', 12, 130.00, 50.00, 1560.00, '2026-08-05 10:50:09'),
(1178, 165, 'R-Skin syrup', 'SR-07', '2029-07', '200-ml', 6, 130.00, 50.00, 780.00, '2026-08-05 10:50:09'),
(1179, 165, 'R-Skin syrup', 'SR-08', '2029-08', '200-ml', 6, 130.00, 50.00, 780.00, '2026-08-05 10:50:09'),
(1180, 165, 'R-Artho Capsule', 'SR-07', '2029-07', '30 capsule ', 12, 180.00, 50.00, 2160.00, '2026-08-05 10:50:09'),
(1181, 165, 'R-Typho syrup', 'SR-08', '2029-08', '100 ml', 12, 80.00, 50.00, 960.00, '2026-08-05 10:50:09'),
(1182, 165, 'Urical-SR syrup', 'SR-07', '2029-07', '200-ml', 6, 130.00, 50.00, 780.00, '2026-08-05 10:50:09'),
(1183, 165, 'R-Tone syrup', 'SR-07', '2029-07', '200-ml', 12, 130.00, 50.00, 1560.00, '2026-08-05 10:50:09'),
(1184, 165, 'Power -x capsule', 'SR-04', '2029-04', '25 capsule ', 5, 150.00, 50.00, 750.00, '2026-08-05 10:50:09'),
(1185, 165, 'Power-X Capsule', 'SR-03', '2028-03', '30 capsul', 1, 180.00, 50.00, 180.00, '2026-08-05 10:50:09'),
(1186, 166, 'Pile -x drop', 'SR-03', '2028-03', '50ml', 10, 150.00, 50.00, 1500.00, '2026-08-07 04:17:03'),
(1187, 166, 'Pile-X capsule', 'SR-07', '2029-07', '30 capsule ', 10, 180.00, 50.00, 1800.00, '2026-08-07 04:17:03'),
(1188, 166, 'R-wats capsule', 'SR-07', '2029-07', '30 capsule ', 10, 180.00, 50.00, 1800.00, '2026-08-07 04:17:03'),
(1189, 167, 'Pain-x oil', 'SR-07', '2029-07', '30ml', 10, 80.00, 50.00, 800.00, '2026-08-07 07:47:23'),
(1190, 167, 'Face care cream', 'SR-07', '2029-07', '50 gram', 2, 110.00, 50.00, 220.00, '2026-08-07 07:47:23'),
(1191, 168, 'Pain-x oil', 'SR-07', '2029-07', '30ml', 10, 80.00, 50.00, 800.00, '2026-08-07 07:50:54'),
(1192, 169, 'R-Skine Tab', 'SR-05', '2029-05', '25 g', 50, 70.00, 50.00, 3500.00, '2026-08-09 03:52:29'),
(1193, 169, 'R-Skin drop', 'SR-07', '2028-07', '50ml', 50, 150.00, 50.00, 7500.00, '2026-08-09 03:52:29'),
(1194, 169, 'R-Skin syrup', 'SR-08', '2029-08', '200-ml', 46, 130.00, 50.00, 5980.00, '2026-08-09 03:52:29'),
(1195, 170, 'Urical Tab', 'SR-04', '2029-04', '25 g', 10, 70.00, 50.00, 700.00, '2026-08-09 04:24:36'),
(1196, 170, 'Urical tablet', 'SR-08', '2029-08', '25 gram', 10, 70.00, 50.00, 700.00, '2026-08-09 04:24:36'),
(1197, 171, 'Anty Viral Tab', 'SR-03', '2029-02', '25 gm', 9, 70.00, 40.00, 630.00, '2026-08-10 07:20:14'),
(1198, 171, 'Anty Viral tablet', 'SR-08', '2029-08', '25 gram', 11, 70.00, 40.00, 770.00, '2026-08-10 07:20:14'),
(1199, 171, 'Urical tablet', 'SR-04', '2029-05', '25 gram', 3, 70.00, 40.00, 210.00, '2026-08-10 07:20:14'),
(1200, 171, 'Urical tablet', 'SR-08', '2029-08', '25 gram', 17, 70.00, 40.00, 1190.00, '2026-08-10 07:20:14'),
(1201, 171, 'R-Skine Tab', 'SR-05', '2029-05', '25 g', 13, 70.00, 40.00, 910.00, '2026-08-10 07:20:14'),
(1202, 171, 'R-skin tablet', 'SR-08', '2029-08', '25 gram', 7, 70.00, 40.00, 490.00, '2026-08-10 07:20:14'),
(1203, 171, 'Ostolife tablet', 'SR-08', '2029-08', '25 gram', 20, 70.00, 40.00, 1400.00, '2026-08-10 07:20:14'),
(1204, 171, 'Feveron Tablet', 'SR-06', '2029-06', '25 gram', 20, 65.00, 40.00, 1300.00, '2026-08-10 07:20:14'),
(1205, 171, 'R-power tablet', 'SR-04', '2029-05', '25 gram', 19, 70.00, 40.00, 1330.00, '2026-08-10 07:20:14'),
(1206, 171, 'Diarrhoea Tab', 'SR-02', '2029-02', '25 gm', 20, 70.00, 40.00, 1400.00, '2026-08-10 07:20:14'),
(1207, 171, 'Helco plas Tab', 'SR-03', '2029-03', '25 gram', 15, 70.00, 40.00, 1050.00, '2026-08-10 07:20:14'),
(1208, 171, 'Helco plas Tab', 'SR-08', '2029-08', '25 gram', 5, 70.00, 40.00, 350.00, '2026-08-10 07:20:14'),
(1209, 171, 'Hemacuf tablet', 'SR-03', '2029-03', '25 gram', 20, 70.00, 40.00, 1400.00, '2026-08-10 07:20:14'),
(1210, 171, 'Hemo tablet', 'SR-06', '2029-06', '25 gram', 20, 70.00, 40.00, 1400.00, '2026-08-10 07:20:14'),
(1211, 171, 'Brain tablet', 'SR-06', '2029-06', '25 gram', 20, 65.00, 40.00, 1300.00, '2026-08-10 07:20:14'),
(1212, 171, 'Anty cold tablet', 'SR-06', '2029-06', '25 gram', 20, 65.00, 40.00, 1300.00, '2026-08-10 07:20:14'),
(1213, 171, 'Antispos tablet', 'SR-06', '2029-06', '25 gram', 20, 65.00, 40.00, 1300.00, '2026-08-10 07:20:14'),
(1214, 171, 'Rheumo -SR syrup', 'SR-08', '2029-08', '200-ml', 20, 130.00, 40.00, 2600.00, '2026-08-10 07:20:14'),
(1215, 172, 'Helco plas Tab', 'SR-03', '2029-03', '25 gram', 20, 70.00, 60.00, 1400.00, '2026-08-10 15:45:40'),
(1216, 172, 'Hemacuf syrup', 'SR-08', '2029-08', '100 ml', 36, 80.00, 60.00, 2880.00, '2026-08-10 15:45:40'),
(1217, 172, 'R-Cid syrup', 'SR-08', '2029-08', '200-ml', 12, 130.00, 60.00, 1560.00, '2026-08-10 15:45:40'),
(1218, 172, 'R-neuro drop', 'SR-08', '2026-08', '50ml', 20, 150.00, 60.00, 3000.00, '2026-08-10 15:45:40'),
(1219, 172, 'R-Lax syrup', 'SR-07', '2029-07', '100 ml', 13, 80.00, 60.00, 1040.00, '2026-08-10 15:45:40'),
(1220, 172, 'Face care cream', 'SR-07', '2029-07', '50 gram', 10, 110.00, 60.00, 1100.00, '2026-08-10 15:45:40'),
(1221, 172, 'Pile -x drop', 'SR-03', '2028-03', '50ml', 15, 150.00, 60.00, 2250.00, '2026-08-10 15:45:40'),
(1222, 172, 'R-Skin drop', 'SR-07', '2028-07', '50ml', 15, 150.00, 60.00, 2250.00, '2026-08-10 15:45:40'),
(1223, 173, 'Dant care', 'SR-08', '2029-08', '60 gram ', 60, 80.00, 50.00, 4800.00, '2026-08-11 05:51:30'),
(1224, 174, 'R-Liv syrup', 'SR-05', '2029-06', '200-ml', 2, 130.00, 50.00, 260.00, '2026-08-12 08:11:59'),
(1225, 174, 'R-Tone syrup', 'SR-07', '2029-07', '200-ml', 2, 130.00, 50.00, 260.00, '2026-08-12 08:11:59'),
(1226, 174, 'Rheumo -SR syrup', 'SR-08', '2029-08', '200-ml', 1, 130.00, 50.00, 130.00, '2026-08-12 08:11:59'),
(1227, 174, 'Urical-SR syrup', 'SR-07', '2029-07', '200-ml', 1, 130.00, 50.00, 130.00, '2026-08-12 08:11:59'),
(1228, 174, 'Dant care', 'SR-06', '2029-06', '60gram ', 2, 60.00, 50.00, 120.00, '2026-08-12 08:11:59'),
(1229, 174, 'Hemacuf syrup', 'SR-08', '2029-08', '100 ml', 2, 80.00, 0.00, 160.00, '2026-08-12 08:11:59'),
(1230, 174, 'R-Typho syrup', 'SR-06', '2029-07', '100 ml', 1, 80.00, 50.00, 80.00, '2026-08-12 08:11:59'),
(1231, 174, 'Pile -x capsule', 'SR-06', '2029-06', '30 capsule ', 1, 180.00, 50.00, 180.00, '2026-08-12 08:11:59'),
(1232, 174, 'R-wats capsule', 'SR-07', '2029-07', '30 capsule ', 1, 180.00, 50.00, 180.00, '2026-08-12 08:11:59'),
(1233, 174, 'Pain-x ointment', 'SR-08', '2028-08', '25 gram', 2, 80.00, 50.00, 160.00, '2026-08-12 08:11:59'),
(1234, 174, 'Ostolife tablet', 'SR-08', '2029-08', '25 gram', 1, 70.00, 50.00, 70.00, '2026-08-12 08:11:59'),
(1235, 174, 'Pain-x oil', 'SR-07', '2029-07', '30ml', 2, 80.00, 50.00, 160.00, '2026-08-12 08:11:59'),
(1236, 174, 'Urical tablet', 'SR-08', '2029-08', '25 gram', 1, 70.00, 50.00, 70.00, '2026-08-12 08:11:59'),
(1237, 174, 'Anty cold tablet', 'SR-06', '2029-06', '25 gram', 2, 65.00, 50.00, 130.00, '2026-08-12 08:11:59'),
(1238, 174, 'Hemo tablet', 'SR-06', '2029-06', '25 gram', 1, 70.00, 50.00, 70.00, '2026-08-12 08:11:59'),
(1239, 174, 'Chest care oil', 'SR-04', '2029-04', '30 ml', 1, 80.00, 50.00, 80.00, '2026-08-12 08:11:59'),
(1240, 174, 'Feveron Tablet', 'SR-06', '2029-06', '25 gram', 2, 65.00, 50.00, 130.00, '2026-08-12 08:11:59'),
(1241, 174, 'Anty Viral tablet', 'SR-08', '2029-08', '25 gram', 1, 70.00, 50.00, 70.00, '2026-08-12 08:11:59'),
(1242, 174, 'Diarrhoea Tab', 'SR-02', '2029-02', '25 gm', 1, 70.00, 50.00, 70.00, '2026-08-12 08:11:59'),
(1243, 174, 'Brain tablet', 'SR-06', '2029-06', '25 gram', 1, 65.00, 50.00, 65.00, '2026-08-12 08:11:59'),
(1244, 174, 'R-skin tablet', 'SR-08', '2029-08', '25 gram', 1, 70.00, 50.00, 70.00, '2026-08-12 08:11:59'),
(1245, 174, 'R-Artho Capsule', 'SR-07', '2029-07', '30 capsule ', 1, 180.00, 50.00, 180.00, '2026-08-12 08:11:59'),
(1246, 175, 'Feveron Tablet', 'SR-06', '2029-06', '25 gram', 2, 65.00, 50.00, 130.00, '2026-08-12 08:19:14'),
(1247, 175, 'Anty cold tablet', 'SR-06', '2029-06', '25 gram', 2, 65.00, 50.00, 130.00, '2026-08-12 08:19:14'),
(1248, 175, 'Chest care oil', 'SR-04', '2029-04', '30 ml', 1, 80.00, 50.00, 80.00, '2026-08-12 08:19:14'),
(1249, 175, 'Hemacuf tablet', 'SR-03', '2029-03', '25 gram', 1, 70.00, 50.00, 70.00, '2026-08-12 08:19:14'),
(1250, 175, 'Hemacuf syrup', 'SR-08', '2029-08', '100 ml', 1, 80.00, 50.00, 80.00, '2026-08-12 08:19:14'),
(1251, 175, 'R-Cid syrup', 'SR-08', '2029-08', '200-ml', 1, 130.00, 50.00, 130.00, '2026-08-12 08:19:14'),
(1252, 175, 'R-Liv syrup', 'SR-07', '2029-07', '200-ml', 1, 130.00, 50.00, 130.00, '2026-08-12 08:19:14'),
(1253, 175, 'Pile-x capsule', 'SR-06', '2029-06', '30 capsule ', 1, 180.00, 50.00, 180.00, '2026-08-12 08:19:14'),
(1254, 175, 'R-wats capsule', 'SR-07', '2029-07', '30 capsule ', 1, 180.00, 50.00, 180.00, '2026-08-12 08:19:14'),
(1255, 175, 'R-Artho Capsule', 'SR-07', '2029-07', '30 capsule ', 1, 180.00, 50.00, 180.00, '2026-08-12 08:19:14'),
(1256, 175, 'Diarrhoea Tab', 'SR-02', '2029-02', '25 gm', 1, 70.00, 50.00, 70.00, '2026-08-12 08:19:14'),
(1257, 176, 'Heart care syrup', 'SR-07', '2029-07', '200-ml', 2, 130.00, 50.00, 260.00, '2026-08-12 08:35:30'),
(1258, 176, 'R-Cid syrup', 'SR-08', '2029-08', '200-ml', 2, 130.00, 50.00, 260.00, '2026-08-12 08:35:30'),
(1259, 176, 'R-Tone syrup', 'SR-07', '2029-07', '200-ml', 2, 130.00, 50.00, 260.00, '2026-08-12 08:35:30'),
(1260, 176, 'R-Skin syrup', 'SR-08', '2029-08', '200-ml', 2, 130.00, 50.00, 260.00, '2026-08-12 08:35:30'),
(1261, 176, 'R-Typho syrup', 'SR-08', '2029-08', '100 ml', 2, 80.00, 50.00, 160.00, '2026-08-12 08:35:30'),
(1262, 176, 'Hemacuf syrup', 'SR-08', '2029-08', '100 ml', 1, 80.00, 50.00, 80.00, '2026-08-12 08:35:30'),
(1263, 176, 'Dant care', 'SR-06', '2029-06', '60gram ', 2, 60.00, 50.00, 120.00, '2026-08-12 08:35:30'),
(1264, 176, 'Urical-SR syrup', 'SR-07', '2029-07', '200-ml', 2, 130.00, 50.00, 260.00, '2026-08-12 08:35:30'),
(1265, 176, 'Pile-X capsule', 'SR-07', '2029-07', '30 capsule ', 2, 180.00, 50.00, 360.00, '2026-08-12 08:35:30'),
(1266, 176, 'Pain-x ointment', 'SR-08', '2028-08', '25 gram', 2, 80.00, 50.00, 160.00, '2026-08-12 08:35:30'),
(1267, 176, 'Hemo tablet', 'SR-06', '2029-06', '25 gram', 2, 70.00, 50.00, 140.00, '2026-08-12 08:35:30'),
(1268, 176, 'Brain tablet', 'SR-06', '2029-06', '25 gram', 2, 65.00, 50.00, 130.00, '2026-08-12 08:35:30'),
(1269, 176, 'Brain tablet', 'SR-06', '2029-06', '25 gram', 2, 65.00, 50.00, 130.00, '2026-08-12 08:35:30'),
(1270, 176, 'Ostolife tablet', 'SR-08', '2029-08', '25 gram', 2, 70.00, 50.00, 140.00, '2026-08-12 08:35:30'),
(1271, 176, 'Chest care oil', 'SR-04', '2029-04', '30 ml', 2, 80.00, 50.00, 160.00, '2026-08-12 08:35:30'),
(1272, 176, 'Diarrhoea Tab', 'SR-02', '2029-02', '25 gm', 2, 70.00, 50.00, 140.00, '2026-08-12 08:35:30'),
(1273, 176, 'Feveron Tablet', 'SR-06', '2029-06', '25 gram', 2, 65.00, 50.00, 130.00, '2026-08-12 08:35:30'),
(1274, 176, 'Livo care drop', 'SR-05', '2028-05', '50ml', 2, 150.00, 50.00, 300.00, '2026-08-12 08:35:30'),
(1275, 176, 'Antispos tablet', 'SR-06', '2029-06', '25 gram', 2, 65.00, 50.00, 130.00, '2026-08-12 08:35:30'),
(1276, 176, 'Anty Viral tablet', 'SR-08', '2029-08', '25 gram', 2, 70.00, 50.00, 140.00, '2026-08-12 08:35:30'),
(1277, 176, 'R-skin tablet', 'SR-08', '2029-08', '25 gram', 2, 70.00, 50.00, 140.00, '2026-08-12 08:35:30'),
(1278, 177, 'Rheumo -SR syrup', 'SR-08', '2029-08', '200-ml', 24, 130.00, 50.00, 3120.00, '2026-08-12 11:55:25'),
(1279, 177, 'R-Typho syrup', 'SR-08', '2029-08', '100 ml', 24, 80.00, 50.00, 1920.00, '2026-08-12 11:55:25'),
(1280, 178, 'Dant care', 'SR-08', '2029-08', '60 gram ', 60, 60.00, 50.00, 3600.00, '2026-08-13 07:45:05'),
(1281, 179, 'Hemacuf syrup', 'SR-08', '2029-08', '100 ml', 5, 80.00, 50.00, 400.00, '2026-08-13 08:42:45'),
(1282, 179, 'R-Cid syrup', 'SR-08', '2029-08', '200-ml', 5, 130.00, 50.00, 650.00, '2026-08-13 08:42:45'),
(1283, 179, 'R-Typho syrup', 'SR-08', '2029-08', '100 ml', 5, 80.00, 50.00, 400.00, '2026-08-13 08:42:45'),
(1284, 179, 'Rheumo -SR syrup', 'SR-08', '2029-08', '200-ml', 5, 130.00, 50.00, 650.00, '2026-08-13 08:42:45'),
(1285, 179, 'Pain-x ointment', 'SR-08', '2028-08', '25 gram', 5, 80.00, 50.00, 400.00, '2026-08-13 08:42:45'),
(1286, 179, 'Diarrhoea Tab', 'SR-02', '2029-02', '25 gm', 5, 70.00, 50.00, 350.00, '2026-08-13 08:42:45'),
(1287, 180, 'Dant care', 'SR-08', '2029-08', '60 gram ', 20, 60.00, 50.00, 1200.00, '2026-08-13 08:44:52'),
(1288, 181, 'Hemacuf tablet', 'SR-03', '2029-03', '25 gram', 1, 70.00, 50.00, 70.00, '2026-08-15 03:50:21'),
(1289, 181, 'Antispos tablet', 'SR-06', '2029-06', '25 gram', 1, 65.00, 50.00, 65.00, '2026-08-15 03:50:21'),
(1290, 181, 'Anty Viral tablet', 'SR-08', '2029-08', '25 gram', 2, 70.00, 50.00, 140.00, '2026-08-15 03:50:21'),
(1291, 181, 'Hemo tablet', 'SR-06', '2029-06', '25 gram', 1, 70.00, 50.00, 70.00, '2026-08-15 03:50:21'),
(1292, 181, 'R-skin tablet', 'SR-08', '2029-08', '25 gram', 1, 70.00, 50.00, 70.00, '2026-08-15 03:50:21'),
(1293, 181, 'R-power tablet', 'SR-04', '2029-04', '25 gram', 1, 70.00, 50.00, 70.00, '2026-08-15 03:50:21'),
(1294, 181, 'Urical tablet', 'SR-08', '2029-08', '25 gram', 1, 70.00, 50.00, 70.00, '2026-08-15 03:50:21'),
(1295, 181, 'Anty cold tablet', 'SR-06', '2029-06', '25 gram', 1, 65.00, 50.00, 65.00, '2026-08-15 03:50:21'),
(1296, 181, 'Feveron Tablet', 'SR-06', '2029-06', '25 gram', 1, 65.00, 50.00, 65.00, '2026-08-15 03:50:21'),
(1297, 181, 'Helco plas Tab', 'SR-08', '2029-08', '25 gram', 1, 70.00, 50.00, 70.00, '2026-08-15 03:50:21'),
(1298, 181, 'Ostolife tablet', 'SR-08', '2029-08', '25 gram', 1, 70.00, 50.00, 70.00, '2026-08-15 03:50:21'),
(1299, 182, 'Hemacuf tablet', 'SR-03', '2029-03', '25 gram', 2, 70.00, 50.00, 140.00, '2026-08-15 03:58:38'),
(1300, 182, 'Anty cold tablet', 'SR-06', '2029-06', '25 gram', 2, 65.00, 50.00, 130.00, '2026-08-15 03:58:38'),
(1301, 182, 'Diarrhoea Tab', 'SR-02', '2029-02', '25 gm', 2, 70.00, 50.00, 140.00, '2026-08-15 03:58:38'),
(1302, 182, 'Feveron Tablet', 'SR-06', '2029-06', '25 gram', 2, 65.00, 50.00, 130.00, '2026-08-15 03:58:38'),
(1303, 182, 'Urical tablet', 'SR-08', '2029-08', '25 gram', 2, 70.00, 50.00, 140.00, '2026-08-15 03:58:38'),
(1304, 182, 'Antispos tablet', 'SR-06', '2029-06', '25 gram', 2, 65.00, 50.00, 130.00, '2026-08-15 03:58:38'),
(1305, 183, 'R-Lax syrup', 'SR-07', '2029-07', '100 ml', 30, 80.00, 60.00, 2400.00, '2026-08-15 14:33:20'),
(1306, 183, 'R-neuro drop', 'SR-08', '2028-08', '50ml', 25, 150.00, 60.00, 3750.00, '2026-08-15 14:33:20'),
(1307, 183, 'R-Artho Capsule', 'SR-07', '2029-07', '30 capsule ', 25, 180.00, 60.00, 4500.00, '2026-08-15 14:33:20'),
(1308, 183, 'R-Cid syrup', 'SR-08', '2029-08', '200-ml', 15, 130.00, 60.00, 1950.00, '2026-08-15 14:33:20'),
(1309, 184, 'Rheumo- SR', 'SR-08', '2029-08', '200ml', 6, 130.00, 50.00, 780.00, '2026-08-16 07:53:29'),
(1310, 184, 'R-Cid syrup', 'SR-08', '2029-08', '200-ml', 6, 130.00, 50.00, 780.00, '2026-08-16 07:53:29'),
(1311, 184, 'R-Skin syrup', 'SR-08', '2029-08', '200-ml', 6, 130.00, 50.00, 780.00, '2026-08-16 07:53:29'),
(1312, 184, 'Hemacuf syrup', 'SR-08', '2029-08', '100 ml', 6, 80.00, 50.00, 480.00, '2026-08-16 07:53:29'),
(1313, 184, 'Hemacuf tablet', 'SR-03', '2029-03', '25 gram', 6, 70.00, 50.00, 420.00, '2026-08-16 07:53:29'),
(1314, 184, 'Antispos tablet', 'SR-06', '2029-06', '25 gram', 6, 65.00, 50.00, 390.00, '2026-08-16 07:53:29'),
(1315, 184, 'Anty Viral tablet', 'SR-08', '2029-08', '25 gram', 6, 70.00, 50.00, 420.00, '2026-08-16 07:53:29'),
(1316, 184, 'Anty cold tablet', 'SR-06', '2029-06', '25 gram', 6, 65.00, 50.00, 390.00, '2026-08-16 07:53:29'),
(1317, 184, 'Urical tablet', 'SR-08', '2029-08', '25 gram', 6, 70.00, 50.00, 420.00, '2026-08-16 07:53:29'),
(1318, 184, 'Feveron Tablet', 'SR-06', '2029-06', '25 gram', 6, 65.00, 50.00, 390.00, '2026-08-16 07:53:29'),
(1319, 184, 'R-skin tablet', 'SR-08', '2029-08', '25 gram', 6, 70.00, 50.00, 420.00, '2026-08-16 07:53:29'),
(1320, 184, 'Diarrhoea Tab', 'SR-02', '2029-02', '25 gm', 6, 70.00, 50.00, 420.00, '2026-08-16 07:53:29'),
(1321, 184, 'Ostolife tablet', 'SR-08', '2029-08', '25 gram', 6, 70.00, 50.00, 420.00, '2026-08-16 07:53:29'),
(1322, 185, 'Hemo tablet', 'SR-06', '2029-06', '25 gram', 5, 70.00, 60.00, 350.00, '2026-08-18 14:10:11'),
(1323, 185, 'R-skin tablet', 'SR-08', '2029-08', '25 gram', 5, 70.00, 60.00, 350.00, '2026-08-18 14:10:11'),
(1324, 185, 'Urical tablet', 'SR-08', '2029-08', '25 gram', 5, 70.00, 60.00, 350.00, '2026-08-18 14:10:11'),
(1325, 185, 'Feveron Tablet', 'SR-06', '2029-06', '25 gram', 5, 65.00, 60.00, 325.00, '2026-08-18 14:10:11'),
(1326, 185, 'Anty Viral tablet', 'SR-08', '2029-08', '25 gram', 5, 70.00, 60.00, 350.00, '2026-08-18 14:10:11'),
(1327, 185, 'Brain tablet', 'SR-06', '2029-06', '25 gram', 5, 65.00, 60.00, 325.00, '2026-08-18 14:10:11'),
(1328, 185, 'R-Cid syrup', 'SR-08', '2029-08', '200-ml', 10, 130.00, 60.00, 1300.00, '2026-08-18 14:10:11'),
(1329, 185, 'Hemagyne syrup', 'SR-08', '2029-08', '200-ml', 5, 130.00, 60.00, 650.00, '2026-08-18 14:10:11'),
(1330, 185, 'R-Skin syrup', 'SR-08', '2029-08', '200-ml', 5, 130.00, 60.00, 650.00, '2026-08-18 14:10:11'),
(1331, 185, 'Urical-SR syrup', 'SR-08', '2029-08', '200-ml', 5, 130.00, 60.00, 650.00, '2026-08-18 14:10:11'),
(1332, 185, 'R-Liv syrup', 'SR-07', '2029-07', '200-ml', 5, 130.00, 60.00, 650.00, '2026-08-18 14:10:11'),
(1333, 185, 'R-Typho syrup', 'SR-08', '2029-08', '100 ml', 5, 80.00, 60.00, 400.00, '2026-08-18 14:10:11'),
(1334, 185, 'Hemagyne Drop', 'SR-02', '2028-02', '50 ml', 5, 150.00, 60.00, 750.00, '2026-08-18 14:10:11'),
(1335, 185, 'Livo care drop', 'SR-05', '2028-05', '50ml', 5, 150.00, 60.00, 750.00, '2026-08-18 14:10:11'),
(1336, 185, 'R-neuro drop', 'SR-08', '2028-08', '50ml', 5, 150.00, 60.00, 750.00, '2026-08-18 14:10:11'),
(1337, 185, 'Pile-X capsule', 'SR-07', '2029-07', '30 capsule ', 10, 180.00, 60.00, 1800.00, '2026-08-18 14:10:11'),
(1338, 185, 'Face care cream', 'SR-07', '2029-07', '50 gram', 20, 110.00, 60.00, 2200.00, '2026-08-18 14:10:11'),
(1339, 185, 'Chest care oil', 'SR-04', '2029-04', '30 ml', 10, 80.00, 60.00, 800.00, '2026-08-18 14:10:11'),
(1340, 185, 'Dant care', 'SR-08', '2029-08', '60 gram ', 10, 60.00, 60.00, 600.00, '2026-08-18 14:10:11'),
(1341, 186, 'Brain tablet', 'SR-06', '2029-06', '25 gram', 3, 65.00, 50.00, 195.00, '2026-08-19 04:53:49'),
(1342, 186, 'Anty Viral tablet', 'SR-08', '2029-08', '25 gram', 6, 70.00, 50.00, 420.00, '2026-08-19 04:53:49'),
(1343, 186, 'Anty cold tablet', 'SR-06', '2029-06', '25 gram', 6, 65.00, 50.00, 390.00, '2026-08-19 04:53:49'),
(1344, 186, 'Feveron Tablet', 'SR-06', '2029-06', '25 gram', 3, 65.00, 50.00, 195.00, '2026-08-19 04:53:49'),
(1345, 186, 'Urical tablet', 'SR-08', '2029-08', '25 gram', 6, 70.00, 50.00, 420.00, '2026-08-19 04:53:49'),
(1346, 186, 'R-power tablet', 'SR-04', '2029-04', '25 gram', 3, 70.00, 50.00, 210.00, '2026-08-19 04:53:49'),
(1347, 186, 'Ostolife tablet', 'SR-08', '2029-08', '25 gram', 6, 70.00, 50.00, 420.00, '2026-08-19 04:53:49'),
(1348, 186, 'Helco plas Tab', 'SR-08', '2029-08', '25 gram', 3, 70.00, 50.00, 210.00, '2026-08-19 04:53:49'),
(1349, 186, 'Hemacuf tablet', 'SR-03', '2029-03', '25 gram', 6, 70.00, 50.00, 420.00, '2026-08-19 04:53:49'),
(1350, 186, 'Antispos tablet', 'SR-06', '2029-06', '25 gram', 3, 65.00, 50.00, 195.00, '2026-08-19 04:53:49'),
(1351, 186, 'R-skin tablet', 'SR-08', '2029-08', '25 gram', 6, 70.00, 50.00, 420.00, '2026-08-19 04:53:49'),
(1352, 186, 'Rheumo- SR', 'SR-08', '2029-08', '200ml', 6, 130.00, 50.00, 780.00, '2026-08-19 04:53:49'),
(1353, 186, 'R-Tone syrup', 'SR-07', '2029-07', '200-ml', 6, 130.00, 50.00, 780.00, '2026-08-19 04:53:49'),
(1354, 186, 'Hemagyne syrup', 'SR-08', '2029-08', '200-ml', 6, 130.00, 50.00, 780.00, '2026-08-19 04:53:49'),
(1355, 186, 'Urical-SR syrup', 'SR-08', '2029-08', '200-ml', 6, 130.00, 50.00, 780.00, '2026-08-19 04:53:49'),
(1356, 186, 'Diabotone syrup', 'SR-07', '2029-07', '200-ml', 3, 130.00, 50.00, 390.00, '2026-08-19 04:53:49'),
(1357, 186, 'R-Liv syrup', 'SR-07', '2029-07', '200-ml', 6, 130.00, 50.00, 780.00, '2026-08-19 04:53:49'),
(1358, 186, 'R-Skin syrup', 'SR-08', '2029-08', '200-ml', 6, 130.00, 50.00, 780.00, '2026-08-19 04:53:49'),
(1359, 186, 'R-Cid syrup', 'SR-08', '2029-08', '200-ml', 6, 130.00, 50.00, 780.00, '2026-08-19 04:53:49'),
(1360, 186, 'Heart care syrup', 'SR-07', '2029-07', '200-ml', 6, 130.00, 50.00, 780.00, '2026-08-19 04:53:49'),
(1361, 186, 'R-Lax syrup', 'SR-08', '2029-08', '100 ml', 6, 80.00, 50.00, 480.00, '2026-08-19 04:53:49'),
(1362, 186, 'R-Typho syrup', 'SR-08', '2029-08', '100 ml', 6, 80.00, 50.00, 480.00, '2026-08-19 04:53:49'),
(1363, 186, 'Hemacuf syrup', 'SR-08', '2029-08', '100 ml', 6, 80.00, 50.00, 480.00, '2026-08-19 04:53:49'),
(1364, 186, 'R-wats capsule', 'SR-07', '2029-07', '30 capsule ', 6, 180.00, 50.00, 1080.00, '2026-08-19 04:53:49'),
(1365, 186, 'Pile-x capsule', 'SR-07', '2029-07', '30 capsule ', 6, 180.00, 50.00, 1080.00, '2026-08-19 04:53:49'),
(1366, 186, 'R-Artho Capsule', 'SR-08', '2029-08', '30 capsule ', 6, 180.00, 50.00, 1080.00, '2026-08-19 04:53:49'),
(1367, 186, 'Hemagyne Drop', 'SR-07', '2028-07', '50ml', 6, 150.00, 50.00, 900.00, '2026-08-19 04:53:49'),
(1368, 186, 'R-neuro drop', 'SR-08', '2028-08', '50ml', 6, 150.00, 50.00, 900.00, '2026-08-19 04:53:49'),
(1369, 186, 'Spondy Droup', 'SR-08', '2028-08', '50 ml', 6, 150.00, 50.00, 900.00, '2026-08-19 04:53:49'),
(1370, 186, 'Livo care drop', 'SR-05', '2028-05', '50ml', 6, 150.00, 50.00, 900.00, '2026-08-19 04:53:49'),
(1371, 186, 'R-Skin drop', 'SR-07', '2028-07', '50ml', 6, 150.00, 50.00, 900.00, '2026-08-19 04:53:49'),
(1372, 186, 'Pain-x oil', 'SR-07', '2029-07', '30ml', 6, 80.00, 50.00, 480.00, '2026-08-19 04:53:49'),
(1373, 186, 'Pain-x ointment', 'SR-08', '2028-08', '25 gram', 6, 80.00, 50.00, 480.00, '2026-08-19 04:53:49'),
(1374, 186, 'Face care cream', 'SR-07', '2029-07', '50 gram', 6, 110.00, 50.00, 660.00, '2026-08-19 04:53:49'),
(1375, 186, 'Dant Care', 'SR-08', '2029-08', '60g', 6, 60.00, 50.00, 360.00, '2026-08-19 04:53:49'),
(1376, 187, 'R-Skin syrup', 'SR-08', '2029-08', '200-ml', 20, 130.00, 50.00, 2600.00, '2026-08-20 04:22:58'),
(1377, 187, 'Heart care syrup', 'SR-07', '2029-07', '200-ml', 16, 130.00, 50.00, 2080.00, '2026-08-20 04:22:58'),
(1378, 187, 'Heart care syrup', 'SR-08', '2029-08', '200-ml', 4, 130.00, 50.00, 520.00, '2026-08-20 04:22:58'),
(1379, 187, 'Pile-x capsule', 'SR-07', '2028-07', '30 capsule ', 20, 180.00, 50.00, 3600.00, '2026-08-20 04:22:58'),
(1380, 187, 'R-Artho Capsule', 'SR-08', '2029-08', '30 capsule ', 20, 180.00, 50.00, 3600.00, '2026-08-20 04:22:58'),
(1381, 187, 'R-wats capsule', 'SR-07', '2029-07', '30 capsule ', 20, 180.00, 50.00, 3600.00, '2026-08-20 04:22:58'),
(1382, 187, 'R-neuro drop', 'SR-08', '2028-08', '50ml', 20, 150.00, 50.00, 3000.00, '2026-08-20 04:22:58'),
(1383, 187, 'R-Skin drop', 'SR-07', '2028-07', '50ml', 20, 150.00, 50.00, 3000.00, '2026-08-20 04:22:58'),
(1384, 188, 'Rheumo- SR', 'SR-08', '2029-08', '200ml', 10, 130.00, 50.00, 1300.00, '2026-08-21 04:34:01'),
(1385, 188, 'R-Cid syrup', 'SR-08', '2029-08', '200-ml', 10, 130.00, 50.00, 1300.00, '2026-08-21 04:34:01'),
(1386, 189, 'Dant Care', 'SR-08', '2029-08', '60g', 10, 60.00, 50.00, 600.00, '2026-08-21 04:36:28'),
(1387, 190, 'Helco plas Tab', 'SR-08', '2029-08', '25 gram', 10, 70.00, 50.00, 700.00, '2026-08-21 04:40:53'),
(1388, 190, 'Pain-x oil', 'SR-07', '2029-07', '30ml', 10, 80.00, 50.00, 800.00, '2026-08-21 04:40:53'),
(1389, 190, 'R-warts capsule', 'SR-07', '2029-07', '30 capsule ', 6, 180.00, 50.00, 1080.00, '2026-08-21 04:40:53'),
(1390, 190, 'R-skin tablet', 'SR-08', '2029-08', '25 gram', 10, 70.00, 50.00, 700.00, '2026-08-21 04:40:53'),
(1391, 190, 'Urical tablet', 'SR-08', '2029-08', '25 gram', 10, 70.00, 50.00, 700.00, '2026-08-21 04:40:53'),
(1392, 190, 'Ostolife tablet', 'SR-08', '2029-08', '25 gram', 10, 70.00, 50.00, 700.00, '2026-08-21 04:40:53'),
(1393, 190, 'Anty Viral tablet', 'SR-08', '2029-08', '25 gram', 10, 70.00, 50.00, 700.00, '2026-08-21 04:40:53'),
(1394, 190, 'Brain tablet', 'SR-06', '2029-06', '25 gram', 10, 65.00, 50.00, 650.00, '2026-08-21 04:40:53'),
(1395, 190, 'Antispos tablet', 'SR-06', '2029-06', '25 gram', 10, 65.00, 50.00, 650.00, '2026-08-21 04:40:53'),
(1396, 190, 'R-Typho syrup', 'SR-08', '2029-08', '100 ml', 10, 80.00, 50.00, 800.00, '2026-08-21 04:40:53'),
(1397, 191, 'R-Artho Capsule', 'SR-08', '2029-08', '30 capsule ', 20, 180.00, 50.00, 3600.00, '2026-08-21 04:44:03'),
(1398, 192, 'Feveron Tablet', 'SR-06', '2029-06', '25 gram', 20, 65.00, 50.00, 1300.00, '2026-08-21 04:46:25'),
(1399, 193, 'Alleres -SR drop', 'SR-03 ', '2027-06', '50ml', 6, 150.00, 50.00, 900.00, '2026-08-21 04:51:49'),
(1400, 194, 'Rheumo- SR', 'SR-08', '2029-08', '200ml', 4, 130.00, 50.00, 520.00, '2026-08-21 04:55:58'),
(1401, 194, 'R-Cid syrup', 'SR-08', '2029-08', '200-ml', 4, 130.00, 50.00, 520.00, '2026-08-21 04:55:58'),
(1402, 194, 'R-Tone syrup', 'SR-07', '2029-07', '200-ml', 4, 130.00, 50.00, 520.00, '2026-08-21 04:55:58'),
(1403, 194, 'Heart care syrup', 'SR-08', '2029-08', '200-ml', 4, 130.00, 50.00, 520.00, '2026-08-21 04:55:58'),
(1404, 194, 'Hemacuf syrup', 'SR-08', '2029-08', '100 ml', 8, 80.00, 50.00, 640.00, '2026-08-21 04:55:58'),
(1405, 195, 'Pain-x oil', 'SR-07', '2029-07', '30ml', 40, 80.00, 60.00, 3200.00, '2026-08-22 05:31:22'),
(1406, 195, 'Pain-x ointment', 'SR-08', '2028-08', '25 gram', 20, 80.00, 60.00, 1600.00, '2026-08-22 05:31:22'),
(1447, 201, 'Diabotone syrup', 'SR-07', '2029-07', '200-ml', 12, 130.00, 50.00, 1560.00, '2026-08-24 05:11:37'),
(1448, 201, 'Diaboton syrup', 'SR-08', '2029-08', '200ml', 28, 130.00, 50.00, 3640.00, '2026-08-24 05:11:37'),
(1449, 201, 'R-Liv syrup', 'SR-07', '2029-07', '200-ml', 29, 130.00, 50.00, 3770.00, '2026-08-24 05:11:37'),
(1450, 201, 'R-Liv syrup', 'SR-08', '2029-08', '200ml', 11, 130.00, 50.00, 1430.00, '2026-08-24 05:11:37'),
(1451, 201, 'R-Typho syrup', 'SR-08', '2029-08', '100 ml', 40, 80.00, 50.00, 3200.00, '2026-08-24 05:11:37'),
(1452, 201, 'R-cid syrup', 'SR-08', '2029-08', '200ml', 36, 130.00, 50.00, 4680.00, '2026-08-24 05:11:37'),
(1453, 201, 'R-Cid syrup', 'SR-08', '2029-08', '200-ml', 2, 130.00, 50.00, 260.00, '2026-08-24 05:11:37'),
(1454, 201, 'Heart care syrup', 'SR-08', '2029-08', '200ml', 20, 130.00, 50.00, 2600.00, '2026-08-24 05:11:37'),
(1455, 201, 'Hemagyne syrup', 'SR-08', '2029-08', '200-ml', 20, 130.00, 50.00, 2600.00, '2026-08-24 05:11:37'),
(1456, 201, 'R-Tone syrup', 'SR-08', '2029-08', '200-ml', 40, 130.00, 50.00, 5200.00, '2026-08-24 05:11:37'),
(1457, 201, 'Rheumo- SR', 'SR-08', '2029-08', '200ml', 36, 130.00, 50.00, 4680.00, '2026-08-24 05:11:37'),
(1458, 201, 'Rheumo- SR', 'SR-08', '2029-08', '200ml', 4, 130.00, 50.00, 520.00, '2026-08-24 05:11:37'),
(1459, 201, 'Diarrhoea Tab', 'SR-02', '2029-02', '25 gm', 15, 70.00, 50.00, 1050.00, '2026-08-24 05:11:37'),
(1460, 201, 'Antispos tablet', 'SR-06', '2029-06', '25 gram', 20, 65.00, 50.00, 1300.00, '2026-08-24 05:11:37'),
(1461, 201, 'Ostolife tablet', 'SR-08', '2029-08', '25 gram', 40, 70.00, 50.00, 2800.00, '2026-08-24 05:11:37'),
(1462, 201, 'R-skin tablet', 'SR-08', '2029-08', '25 gram', 40, 70.00, 50.00, 2800.00, '2026-08-24 05:11:37'),
(1463, 201, 'Urical tablet', 'SR-08', '2029-08', '25 gram', 40, 70.00, 50.00, 2800.00, '2026-08-24 05:11:37'),
(1464, 201, 'Helco plas Tab', 'SR-08', '2029-08', '25 gram', 40, 70.00, 50.00, 2800.00, '2026-08-24 05:11:37'),
(1465, 201, 'Hemacuf tablet', 'SR-03', '2029-03', '25 gram', 20, 70.00, 50.00, 1400.00, '2026-08-24 05:11:37'),
(1466, 201, 'Pain-x oil', 'SR-07', '2029-07', '30ml', 20, 80.00, 50.00, 1600.00, '2026-08-24 05:11:37'),
(1467, 201, 'Pain-x oil', 'SR-08', '2029-08', '30 ml', 20, 80.00, 50.00, 1600.00, '2026-08-24 05:11:37'),
(1468, 201, 'R-warts capsule', 'SR-07', '2029-07', '30 capsule ', 26, 180.00, 50.00, 4680.00, '2026-08-24 05:11:37'),
(1469, 201, 'R-wats capsule', 'SR-08', '2029-08', '30 capsule ', 14, 180.00, 50.00, 2520.00, '2026-08-24 05:11:37'),
(1470, 201, 'R-Artho Capsule', 'SR-08', '2029-08', '30 capsule ', 40, 180.00, 50.00, 7200.00, '2026-08-24 05:11:37'),
(1471, 201, 'Pile-x capsule', 'SR-07', '2028-07', '30 capsule ', 26, 180.00, 50.00, 4680.00, '2026-08-24 05:11:37'),
(1472, 201, 'Pile-X capsule', 'SR-08', '2029-08', '30 capsule ', 14, 180.00, 50.00, 2520.00, '2026-08-24 05:11:37'),
(1473, 202, 'Hemagyne Drop', 'SR-07', '2028-07', '50ml', 15, 150.00, 50.00, 2250.00, '2026-08-24 11:15:16'),
(1474, 202, 'Livo care drop', 'SR-05', '2028-05', '50ml', 20, 150.00, 50.00, 3000.00, '2026-08-24 11:15:16'),
(1475, 202, 'Urical-SR syrup', 'SR-08', '2029-08', '200-ml', 20, 130.00, 50.00, 2600.00, '2026-08-24 11:15:16'),
(1476, 202, 'Face care cream', 'SR-07', '2029-07', '50 gram', 20, 110.00, 50.00, 2200.00, '2026-08-24 11:15:16'),
(1477, 202, 'Ostolife tablet', 'SR-08', '2029-08', '25 gram', 50, 70.00, 50.00, 3500.00, '2026-08-24 11:15:16'),
(1478, 202, 'Hemacuf syrup', 'SR-08', '2029-08', '100 ml', 15, 80.00, 50.00, 1200.00, '2026-08-24 11:15:16'),
(1479, 202, 'R-Lax syrup', 'SR-08', '2029-08', '100 ml', 20, 80.00, 50.00, 1600.00, '2026-08-24 11:15:16'),
(1480, 203, 'Hemacuf syrup', 'SR-08', '2029-08', '100 ml', 50, 80.00, 50.00, 4000.00, '2026-08-25 05:53:34'),
(1481, 203, 'R-Skin syrup', 'SR-08', '2029-08', '200-ml', 10, 130.00, 50.00, 1300.00, '2026-08-25 05:53:34'),
(1482, 203, 'R-skin tablet', 'SR-08', '2029-08', '25 gram', 10, 70.00, 50.00, 700.00, '2026-08-25 05:53:34'),
(1483, 203, 'R-Cid syrup', 'SR-08', '2029-08', '200-ml', 10, 130.00, 50.00, 1300.00, '2026-08-25 05:53:34'),
(1484, 203, 'Helco plas Tab', 'SR-08', '2029-08', '25 gram', 10, 70.00, 50.00, 700.00, '2026-08-25 05:53:34'),
(1485, 203, 'R-wats capsule', 'SR-08', '2029-08', '30 capsule ', 10, 180.00, 50.00, 1800.00, '2026-08-25 05:53:34'),
(1486, 203, 'Pile-X capsule', 'SR-08', '2029-08', '30 capsule ', 10, 180.00, 50.00, 1800.00, '2026-08-25 05:53:34'),
(1487, 203, 'R-neuro drop', 'SR-08', '2028-08', '50ml', 10, 150.00, 50.00, 1500.00, '2026-08-25 05:53:34'),
(1488, 204, 'R-Skin drop', 'SR-07', '2028-07', '50ml', 4, 150.00, 50.00, 600.00, '2026-08-25 06:05:15'),
(1489, 204, 'Hemagyne Drop', 'SR-02', '2028-02', '50 ml', 4, 150.00, 50.00, 600.00, '2026-08-25 06:05:15'),
(1490, 204, 'R-Skin syrup', 'SR-08', '2029-08', '200-ml', 4, 130.00, 50.00, 520.00, '2026-08-25 06:05:15'),
(1491, 204, 'R-Cid syrup', 'SR-08', '2029-08', '200-ml', 4, 130.00, 50.00, 520.00, '2026-08-25 06:05:15'),
(1492, 204, 'Power -x capsule', 'SR-08', '2029-08', '25 capsule ', 4, 150.00, 50.00, 600.00, '2026-08-25 06:05:15'),
(1493, 204, 'R-Artho Capsule', 'SR-08', '2029-08', '30 capsule ', 4, 180.00, 50.00, 720.00, '2026-08-25 06:05:15'),
(1494, 204, 'Rheumo- SR', 'SR-08', '2029-08', '200ml', 5, 130.00, 50.00, 650.00, '2026-08-25 06:05:15');
INSERT INTO `invoice_items` (`id`, `invoice_id`, `product_name`, `batch`, `expiry`, `capacity`, `qty`, `rate`, `discount`, `gross_amount`, `created_at`) VALUES
(1495, 204, 'Helco plas Tab', 'SR-08', '2029-08', '25 gram', 10, 70.00, 50.00, 700.00, '2026-08-25 06:05:15'),
(1496, 204, 'Urical-SR syrup', 'SR-08', '2029-08', '200-ml', 4, 130.00, 50.00, 520.00, '2026-08-25 06:05:15'),
(1497, 204, 'Hemagyne syrup', 'SR-08', '2029-08', '200-ml', 4, 130.00, 50.00, 520.00, '2026-08-25 06:05:15'),
(1498, 204, 'R-Lax syrup', 'SR-07', '2029-07', '100 ml', 4, 80.00, 50.00, 320.00, '2026-08-25 06:05:15'),
(1499, 204, 'R-Typho syrup', 'SR-08', '2029-08', '100 ml', 4, 80.00, 50.00, 320.00, '2026-08-25 06:05:15'),
(1500, 204, 'Brain tablet', 'SR-06', '2029-06', '25 gram', 4, 65.00, 50.00, 260.00, '2026-08-25 06:05:15'),
(1501, 204, 'Anty Viral tablet', 'SR-08', '2029-08', '25 gram', 4, 70.00, 50.00, 280.00, '2026-08-25 06:05:15'),
(1502, 204, 'Ostolife tablet', 'SR-08', '2029-08', '25 gram', 4, 70.00, 50.00, 280.00, '2026-08-25 06:05:15'),
(1503, 205, 'Hemagyne syrup', 'SR-08', '2029-08', '200-ml', 5, 130.00, 50.00, 650.00, '2026-08-25 06:10:31'),
(1504, 205, 'Hemacuf syrup', 'SR-08', '2029-08', '100 ml', 5, 80.00, 50.00, 400.00, '2026-08-25 06:10:31'),
(1505, 205, 'R-Tone syrup', 'SR-08', '2029-08', '200-ml', 5, 130.00, 50.00, 650.00, '2026-08-25 06:10:31'),
(1506, 205, 'R-Cid syrup', 'SR-08', '2029-08', '200-ml', 5, 130.00, 50.00, 650.00, '2026-08-25 06:10:31'),
(1507, 205, 'R-Lax syrup', 'SR-08', '2029-08', '100 ml', 5, 80.00, 50.00, 400.00, '2026-08-25 06:10:31'),
(1508, 205, 'R-skin syrup', 'SR-08', '2029-08', '200ml', 5, 130.00, 50.00, 650.00, '2026-08-25 06:10:31'),
(1509, 205, 'Rheumo- SR', 'SR-08', '2029-08', '200ml', 5, 130.00, 50.00, 650.00, '2026-08-25 06:10:31'),
(1510, 205, 'Brain tablet', 'SR-06', '2029-06', '25 gram', 5, 65.00, 50.00, 325.00, '2026-08-25 06:10:31'),
(1511, 205, 'Ostolife tablet', 'SR-08', '2029-08', '25 gram', 5, 70.00, 50.00, 350.00, '2026-08-25 06:10:31'),
(1512, 205, 'Helco plas Tab', 'SR-08', '2029-08', '25 gram', 5, 70.00, 50.00, 350.00, '2026-08-25 06:10:31'),
(1513, 205, 'R-Artho Capsule', 'SR-08', '2029-08', '30 capsule ', 5, 180.00, 50.00, 900.00, '2026-08-25 06:10:31'),
(1514, 205, 'Face care cream', 'SR-06', '2029-06', '50 gram', 5, 110.00, 50.00, 550.00, '2026-08-25 06:10:31'),
(1515, 206, 'Heart care syrup', 'SR-08', '2029-08', '200-ml', 24, 130.00, 50.00, 3120.00, '2026-08-27 04:27:17'),
(1516, 206, 'Heart care syrup', 'SR-08', '2029-08', '200ml', 6, 130.00, 50.00, 780.00, '2026-08-27 04:27:17'),
(1517, 206, 'R-Lax syrup', 'SR-08', '2029-08', '100 ml', 6, 80.00, 50.00, 480.00, '2026-08-27 04:27:17'),
(1518, 206, 'R-Cid syrup', 'SR-08', '2029-08', '200-ml', 10, 130.00, 50.00, 1300.00, '2026-08-27 04:27:17'),
(1519, 206, 'R-Typho syrup', 'SR-08', '2029-08', '100 ml', 10, 80.00, 50.00, 800.00, '2026-08-27 04:27:17'),
(1520, 206, 'R-Artho Capsule', 'SR-08', '2029-08', '30 capsule ', 6, 180.00, 50.00, 1080.00, '2026-08-27 04:27:17'),
(1521, 206, 'BP care drop', 'SR-01', '2027-06', '50ml', 6, 150.00, 50.00, 900.00, '2026-08-27 04:27:17'),
(1522, 206, 'Brain tablet', 'SR-06', '2029-06', '25 gram', 6, 65.00, 50.00, 390.00, '2026-08-27 04:27:17'),
(1523, 206, 'Pain-x oil', 'SR-08', '2029-08', '30 ml', 10, 80.00, 50.00, 800.00, '2026-08-27 04:27:17'),
(1524, 206, 'Pain-x ointment', 'SR-08', '2028-08', '25 gram', 20, 80.00, 50.00, 1600.00, '2026-08-27 04:27:17'),
(1525, 207, 'Heart care syrup', 'SR-08', '2029-08', '200ml', 3, 130.00, 50.00, 390.00, '2026-08-27 04:34:08'),
(1526, 207, 'R-Liv syrup', 'SR-08', '2029-08', '200ml', 3, 130.00, 50.00, 390.00, '2026-08-27 04:34:08'),
(1527, 208, 'R-Tone syrup', 'SR-08', '2029-08', '200-ml', 10, 130.00, 50.00, 1300.00, '2026-08-27 05:13:07'),
(1528, 208, 'R-Liv syrup', 'SR-08', '2029-08', '200ml', 10, 130.00, 50.00, 1300.00, '2026-08-27 05:13:07'),
(1529, 209, 'Pain-x oil', 'SR-08', '2029-08', '30 ml', 30, 80.00, 50.00, 2400.00, '2026-08-27 05:16:39'),
(1530, 210, 'Rheumo- SR', 'SR-08', '2029-08', '200ml', 10, 130.00, 50.00, 1300.00, '2026-08-27 05:22:21'),
(1531, 210, 'R-Artho Capsule', 'SR-08', '2029-08', '30 capsule ', 10, 180.00, 50.00, 1800.00, '2026-08-27 05:22:21'),
(1532, 210, 'Spondy Droup', 'SR-08', '2028-08', '50 ml', 10, 150.00, 50.00, 1500.00, '2026-08-27 05:22:21'),
(1533, 210, 'Pain-x oil', 'SR-08', '2029-08', '30 ml', 10, 80.00, 50.00, 800.00, '2026-08-27 05:22:21'),
(1534, 210, 'R-Tone syrup', 'SR-08', '2029-08', '200-ml', 6, 130.00, 50.00, 780.00, '2026-08-27 05:22:21'),
(1535, 211, 'Pain-x oil', 'SR-08', '2029-08', '30 ml', 10, 80.00, 50.00, 800.00, '2026-08-27 05:24:17'),
(1536, 211, 'R-neuro drop', 'SR-08', '2028-08', '50ml', 10, 150.00, 50.00, 1500.00, '2026-08-27 05:24:17'),
(1537, 212, 'Hemagyne Drop', 'SR-07', '2028-07', '50ml', 12, 150.00, 50.00, 1800.00, '2026-08-27 05:42:35'),
(1546, 215, 'Pile-x capsule', 'SR-07', '2029-07', '30 capsule ', 6, 180.00, 50.00, 1080.00, '2026-08-30 08:34:19'),
(1547, 215, 'Pile-X capsule', 'SR-08', '2029-08', '30 capsule ', 4, 180.00, 50.00, 720.00, '2026-08-30 08:34:19'),
(1548, 215, 'R-Artho Capsule', 'SR-08', '2029-08', '30 capsule ', 10, 180.00, 50.00, 1800.00, '2026-08-30 08:34:19'),
(1549, 215, 'R-wats capsule', 'SR-07', '2029-07', '30 capsule ', 2, 180.00, 50.00, 360.00, '2026-08-30 08:34:19'),
(1550, 215, 'Dant care', 'SR-06', '2029-06', '60gram ', 7, 60.00, 50.00, 420.00, '2026-08-30 08:34:19'),
(1551, 215, 'Dant care', 'SR-08', '2029-08', '60 gram ', 3, 60.00, 50.00, 180.00, '2026-08-30 08:34:19'),
(1552, 215, 'R-wats capsule', 'SR-08', '2029-08', '30 capsule ', 8, 180.00, 50.00, 1440.00, '2026-08-30 08:34:19'),
(1553, 216, 'Face care cream', 'SR-08', '2029-08', '50g', 30, 110.00, 50.00, 3300.00, '2026-08-31 06:37:35'),
(1554, 217, 'R-neuro drop', 'SR-08', '2028-08', '50ml', 2, 150.00, 50.00, 300.00, '2026-09-01 13:53:40'),
(1555, 217, 'Hemagyne Drop', 'SR-07', '2028-07', '50ml', 2, 150.00, 50.00, 300.00, '2026-09-01 13:53:40'),
(1556, 217, 'R-Artho Capsule', 'SR-08', '2029-08', '30 capsule ', 1, 180.00, 50.00, 180.00, '2026-09-01 13:53:40'),
(1557, 217, 'Pile-X capsule', 'SR-08', '2029-08', '30 capsule ', 1, 180.00, 50.00, 180.00, '2026-09-01 13:53:40'),
(1558, 217, 'R-wats capsule', 'SR-08', '2029-08', '30 capsule ', 1, 180.00, 50.00, 180.00, '2026-09-01 13:53:40'),
(1559, 217, 'Helco plas Tab', 'SR-08', '2029-08', '25 gram', 2, 70.00, 50.00, 140.00, '2026-09-01 13:53:40'),
(1560, 217, 'R-skin tablet', 'SR-08', '2029-08', '25 gram', 2, 70.00, 50.00, 140.00, '2026-09-01 13:53:40'),
(1561, 217, 'Feveron Tablet', 'SR-06', '2029-06', '25 gram', 2, 65.00, 50.00, 130.00, '2026-09-01 13:53:40'),
(1562, 217, 'Hemacuf tablet', 'SR-03', '2029-03', '25 gram', 2, 70.00, 50.00, 140.00, '2026-09-01 13:53:40'),
(1563, 217, 'Urical tablet', 'SR-08', '2029-08', '25 gram', 3, 70.00, 50.00, 210.00, '2026-09-01 13:53:40'),
(1564, 217, 'Anty Viral tablet', 'SR-08', '2029-08', '25 gram', 2, 70.00, 50.00, 140.00, '2026-09-01 13:53:40'),
(1565, 217, 'Ostolife tablet', 'SR-08', '2029-08', '25 gram', 2, 70.00, 50.00, 140.00, '2026-09-01 13:53:40'),
(1566, 217, 'Antispos tablet', 'SR-06', '2029-06', '25 gram', 2, 65.00, 50.00, 130.00, '2026-09-01 13:53:40'),
(1567, 217, 'Pain-x oil', 'SR-08', '2029-08', '30 ml', 3, 80.00, 50.00, 240.00, '2026-09-01 13:53:40'),
(1568, 217, 'Urical-SR syrup', 'SR-07', '2029-07', '200-ml', 2, 130.00, 50.00, 260.00, '2026-09-01 13:53:40'),
(1569, 217, 'Heart care syrup', 'SR-08', '2029-08', '200-ml', 2, 130.00, 50.00, 260.00, '2026-09-01 13:53:40'),
(1570, 217, 'R-Cid syrup', 'SR-08', '2029-08', '200-ml', 2, 130.00, 50.00, 260.00, '2026-09-01 13:53:40'),
(1571, 217, 'Rheumo -SR syrup', 'SR-08', '2029-08', '200-ml', 2, 130.00, 50.00, 260.00, '2026-09-01 13:53:40'),
(1572, 217, 'R-Typho syrup', 'SR-08', '2029-08', '100 ml', 2, 80.00, 50.00, 160.00, '2026-09-01 13:53:40'),
(1573, 217, 'Hemacuf syrup', 'SR-08', '2029-08', '100 ml', 2, 80.00, 50.00, 160.00, '2026-09-01 13:53:40'),
(1574, 217, 'R-Lax syrup', 'SR-07', '2029-07', '100 ml', 1, 80.00, 50.00, 80.00, '2026-09-01 13:53:40'),
(1575, 218, 'R-Cid syrup', 'SR-08', '2029-08', '200-ml', 10, 130.00, 60.00, 1300.00, '2026-09-01 14:36:15'),
(1576, 218, 'Rheumo -SR syrup', 'SR-08', '2029-09', '200-ml', 10, 130.00, 60.00, 1300.00, '2026-09-01 14:36:15'),
(1577, 218, 'Urical-SR syrup', 'SR-08', '2029-08', '200-ml', 17, 130.00, 60.00, 2210.00, '2026-09-01 14:36:15'),
(1578, 218, 'Urical-SR syrup', 'SR-08', '2028-08', '200-ml', 3, 130.00, 60.00, 390.00, '2026-09-01 14:36:15'),
(1579, 218, 'Pain-x ointment', 'SR-08', '2028-08', '25 gram', 20, 80.00, 60.00, 1600.00, '2026-09-01 14:36:15'),
(1580, 218, 'R-warts capsule', 'SR-08', '2029-09', '30 capsule ', 17, 180.00, 60.00, 3060.00, '2026-09-01 14:36:15'),
(1581, 218, 'R-wats capsule', 'SR-08', '2029-08', '30 capsule ', 3, 180.00, 60.00, 540.00, '2026-09-01 14:36:15'),
(1582, 218, 'Power -x capsule', 'SR-08', '2029-08', '25 capsule ', 10, 150.00, 60.00, 1500.00, '2026-09-01 14:36:15'),
(1583, 218, 'R-Typho syrup', 'SR-08', '2029-08', '100 ml', 15, 80.00, 60.00, 1200.00, '2026-09-01 14:36:15'),
(1584, 218, 'R-Tone syrup', 'SR-08', '2029-09', '200-ml', 14, 130.00, 60.00, 1820.00, '2026-09-01 14:36:15'),
(1585, 218, 'R-Tone syrup', 'SR-08', '2029-08', '200-ml', 1, 130.00, 60.00, 130.00, '2026-09-01 14:36:15'),
(1586, 218, 'R-Artho Capsule', 'SR-08', '2029-08', '30 capsule ', 15, 180.00, 60.00, 2700.00, '2026-09-01 14:36:15'),
(1587, 218, 'R-Skin drop', 'SR-07', '2028-07', '50ml', 15, 150.00, 60.00, 2250.00, '2026-09-01 14:36:15'),
(1588, 218, 'Pile-x capsule', 'SR-08', '2029-09', '30 capsule ', 10, 180.00, 60.00, 1800.00, '2026-09-01 14:36:15'),
(1589, 218, 'Gastrzen Cap', 'SR-08', '2029-08', '30 capsule ', 20, 180.00, 60.00, 3600.00, '2026-09-01 14:36:15'),
(1590, 220, 'Rheumo -SR syrup', 'SR-08', '2029-09', '200-ml', 2, 130.00, 50.00, 260.00, '2026-09-02 05:22:23'),
(1591, 220, 'R-Cid syrup', 'SR-08', '2029-08', '200-ml', 2, 130.00, 50.00, 260.00, '2026-09-02 05:22:23'),
(1592, 220, 'Hemacuf syrup', 'SR-08', '2029-08', '100 ml', 2, 80.00, 50.00, 160.00, '2026-09-02 05:22:23'),
(1593, 220, 'R-Liv syrup', 'SR-08', '2029-08', '200ml', 2, 130.00, 50.00, 260.00, '2026-09-02 05:22:23'),
(1594, 220, 'Dant care', 'SR-08', '2029-08', '60 gram ', 2, 60.00, 50.00, 120.00, '2026-09-02 05:22:23'),
(1595, 220, 'Anty Viral tablet', 'SR-08', '2029-08', '25 gram', 2, 70.00, 50.00, 140.00, '2026-09-02 05:22:23'),
(1596, 220, 'Urical tablet', 'SR-08', '2029-08', '25 gram', 2, 70.00, 50.00, 140.00, '2026-09-02 05:22:23'),
(1597, 220, 'Ostolife tablet', 'SR-08', '2029-08', '25 gram', 2, 70.00, 50.00, 140.00, '2026-09-02 05:22:23'),
(1598, 220, 'Feveron Tablet', 'SR-06', '2029-06', '25 gram', 2, 65.00, 50.00, 130.00, '2026-09-02 05:22:23'),
(1599, 220, 'Hemacuf tablet', 'SR-03', '2029-03', '25 gram', 2, 70.00, 50.00, 140.00, '2026-09-02 05:22:23'),
(1600, 220, 'Pain-x oil', 'SR-08', '2029-08', '30 ml', 2, 80.00, 50.00, 160.00, '2026-09-02 05:22:23'),
(1601, 220, 'Chest care oil', 'SR-04', '2029-04', '30 ml', 2, 80.00, 50.00, 160.00, '2026-09-02 05:22:23'),
(1602, 220, 'Spondy Droup', 'SR-08', '2028-08', '50 ml', 3, 150.00, 50.00, 450.00, '2026-09-02 05:22:23'),
(1603, 220, 'R-neuro drop', 'SR-08', '2028-08', '50ml', 2, 150.00, 50.00, 300.00, '2026-09-02 05:22:23'),
(1604, 220, 'R-Artho Capsule', 'SR-08', '2029-08', '30 capsule ', 1, 180.00, 50.00, 180.00, '2026-09-02 05:22:23'),
(1605, 220, 'R-wats capsule', 'SR-08', '2029-08', '30 capsule ', 1, 180.00, 50.00, 180.00, '2026-09-02 05:22:23'),
(1606, 221, 'R-Cid syrup', 'SR-08', '2029-08', '200-ml', 3, 130.00, 50.00, 390.00, '2026-09-02 05:41:45'),
(1607, 221, 'Hemagyne syrup', 'SR-08', '2029-08', '200-ml', 3, 130.00, 50.00, 390.00, '2026-09-02 05:41:45'),
(1608, 221, 'R-Typho syrup', 'SR-08', '2029-08', '100 ml', 3, 80.00, 50.00, 240.00, '2026-09-02 05:41:45'),
(1609, 221, 'Rheumo -SR syrup', 'SR-08', '2029-09', '200-ml', 3, 130.00, 50.00, 390.00, '2026-09-02 05:41:45'),
(1610, 221, 'Urical-SR syrup', 'SR-08', '2029-09', '200-ml', 3, 130.00, 50.00, 390.00, '2026-09-02 05:41:45'),
(1611, 221, 'R-Liv syrup', 'SR-08', '2029-08', '200ml', 3, 130.00, 50.00, 390.00, '2026-09-02 05:41:45'),
(1612, 221, 'R-Tone syrup', 'SR-08', '2029-08', '200-ml', 3, 130.00, 50.00, 390.00, '2026-09-02 05:41:45'),
(1613, 221, 'Diaboton syrup', 'SR-08', '2029-08', '200ml', 3, 130.00, 50.00, 390.00, '2026-09-02 05:41:45'),
(1614, 221, 'R-Lax syrup', 'SR-08', '2029-08', '100 ml', 3, 80.00, 50.00, 240.00, '2026-09-02 05:41:45'),
(1615, 221, 'Hemacuf syrup', 'SR-08', '2029-08', '100 ml', 3, 80.00, 50.00, 240.00, '2026-09-02 05:41:45'),
(1616, 221, 'Heart care syrup', 'SR-08', '2029-08', '200-ml', 3, 130.00, 50.00, 390.00, '2026-09-02 05:41:45'),
(1617, 221, 'Livo care drop', 'SR-05', '2028-05', '50ml', 2, 150.00, 50.00, 300.00, '2026-09-02 05:41:45'),
(1618, 221, 'BP Care Drop', 'SR-04', '2027-09', '50 ml', 2, 150.00, 50.00, 300.00, '2026-09-02 05:41:45'),
(1619, 221, 'Hemagyne Drop', 'SR-07', '2028-07', '50ml', 2, 150.00, 50.00, 300.00, '2026-09-02 05:41:45'),
(1620, 221, 'R-skin tablet', 'SR-08', '2029-08', '25 gram', 5, 70.00, 50.00, 350.00, '2026-09-02 05:41:45'),
(1621, 221, 'Hemacuf tablet', 'SR-03', '2029-03', '25 gram', 5, 70.00, 50.00, 350.00, '2026-09-02 05:41:45'),
(1622, 221, 'Urical tablet', 'SR-08', '2029-08', '25 gram', 5, 70.00, 50.00, 350.00, '2026-09-02 05:41:45'),
(1623, 221, 'Ostolife tablet', 'SR-08', '2029-08', '25 gram', 5, 70.00, 50.00, 350.00, '2026-09-02 05:41:45'),
(1624, 221, 'Feveron Tablet', 'SR-06', '2029-06', '25 gram', 5, 65.00, 50.00, 325.00, '2026-09-02 05:41:45'),
(1625, 221, 'Antispos tablet', 'SR-06', '2029-06', '25 gram', 5, 65.00, 50.00, 325.00, '2026-09-02 05:41:45'),
(1626, 221, 'Anty Viral tablet', 'SR-08', '2029-08', '25 gram', 5, 70.00, 50.00, 350.00, '2026-09-02 05:41:45'),
(1627, 221, 'Helco plas Tab', 'SR-08', '2029-08', '25 gram', 5, 70.00, 50.00, 350.00, '2026-09-02 05:41:45'),
(1628, 221, 'Hemo tablet', 'SR-06', '2029-06', '25 gram', 5, 70.00, 50.00, 350.00, '2026-09-02 05:41:45'),
(1629, 221, 'Brain tablet', 'SR-06', '2029-06', '25 gram', 5, 65.00, 50.00, 325.00, '2026-09-02 05:41:45'),
(1630, 221, 'Anty cold tablet', 'SR-06', '2029-06', '25 gram', 5, 65.00, 50.00, 325.00, '2026-09-02 05:41:45'),
(1631, 221, 'R-power tablet', 'SR-04', '2029-04', '25 gram', 5, 70.00, 50.00, 350.00, '2026-09-02 05:41:45'),
(1632, 221, 'Diarrhoea Tablet', 'SR-08', '2029-09', '25 gram', 5, 70.00, 50.00, 350.00, '2026-09-02 05:41:45'),
(1633, 222, 'Spondy Droup', 'SR-08', '2028-08', '50 ml', 10, 150.00, 50.00, 1500.00, '2026-09-03 09:53:11'),
(1634, 222, 'R-Cid syrup', 'SR-08', '2029-08', '200-ml', 4, 130.00, 50.00, 520.00, '2026-09-03 09:53:11'),
(1635, 222, 'R-Cid syrup', 'SR-08', '2029-08', '200-ml', 2, 130.00, 50.00, 260.00, '2026-09-03 09:53:11'),
(1636, 222, 'R-Artho Capsule', 'SR-08', '2029-09', '30 capsule ', 6, 180.00, 50.00, 1080.00, '2026-09-03 09:53:11'),
(1637, 222, 'Anty Viral tablet', 'SR-08', '2029-08', '25 gram', 6, 70.00, 50.00, 420.00, '2026-09-03 09:53:11'),
(1638, 222, 'Helco plas Tab', 'SR-08', '2029-08', '25 gram', 6, 70.00, 50.00, 420.00, '2026-09-03 09:53:11'),
(1639, 222, 'Urical tablet', 'SR-08', '2029-08', '25 gram', 6, 70.00, 50.00, 420.00, '2026-09-03 09:53:11'),
(1640, 222, 'Ostolife tablet', 'SR-08', '2029-08', '25 gram', 7, 70.00, 50.00, 490.00, '2026-09-03 09:53:11'),
(1641, 222, 'Rheumo -SR syrup', 'SR-08', '2029-09', '200-ml', 10, 130.00, 50.00, 1300.00, '2026-09-03 09:53:11'),
(1642, 222, 'Face care cream', 'SR-08', '2029-08', '50g', 4, 110.00, 50.00, 440.00, '2026-09-03 09:53:11'),
(1643, 222, 'R-warts capsule', 'SR-08', '2029-09', '30 capsule ', 6, 180.00, 50.00, 1080.00, '2026-09-03 09:53:11'),
(1644, 222, 'R-neuro drop', 'SR-08', '2028-08', '50ml', 6, 150.00, 50.00, 900.00, '2026-09-03 09:53:11'),
(1645, 222, 'Hemagyne Drop', 'SR-07', '2028-07', '50ml', 10, 150.00, 50.00, 1500.00, '2026-09-03 09:53:11'),
(1646, 223, 'Rheumo -SR syrup', 'SR-08', '2029-09', '200-ml', 5, 130.00, 50.00, 650.00, '2026-09-04 12:55:19'),
(1647, 223, 'R-Tone syrup', 'SR-07', '2029-07', '200-ml', 2, 130.00, 50.00, 260.00, '2026-09-04 12:55:19'),
(1648, 223, 'R-Tone syrup', 'SR-08', '2029-08', '200-ml', 3, 130.00, 50.00, 390.00, '2026-09-04 12:55:19'),
(1649, 223, 'Hemacuf syrup', 'SR-08', '2029-08', '100 ml', 5, 80.00, 50.00, 400.00, '2026-09-04 12:55:19'),
(1650, 223, 'Pain-x oil', 'SR-08', '2029-08', '30 ml', 5, 80.00, 50.00, 400.00, '2026-09-04 12:55:19'),
(1651, 223, 'Pain-x ointment', 'SR-08', '2028-08', '25 gram', 5, 80.00, 50.00, 400.00, '2026-09-04 12:55:19'),
(1652, 223, 'Urical-SR syrup', 'SR-08', '2028-08', '200-ml', 2, 130.00, 50.00, 260.00, '2026-09-04 12:55:19'),
(1653, 224, 'Heart care syrup', 'SR-08', '2029-08', '200-ml', 5, 130.00, 50.00, 650.00, '2026-09-05 08:30:28'),
(1654, 224, 'Hemagyne syrup', 'SR-08', '2029-08', '200-ml', 5, 130.00, 50.00, 650.00, '2026-09-05 08:30:28'),
(1655, 224, 'R-Lax syrup', 'SR-08', '2029-08', '100 ml', 5, 80.00, 50.00, 400.00, '2026-09-05 08:30:28'),
(1656, 224, 'R-Typho syrup', 'SR-08', '2029-08', '100 ml', 5, 80.00, 50.00, 400.00, '2026-09-05 08:30:28'),
(1657, 224, 'R-skin syrup', 'SR-08', '2029-08', '200ml', 5, 130.00, 50.00, 650.00, '2026-09-05 08:30:28'),
(1658, 224, 'R-Liv syrup', 'SR-08', '2029-08', '200ml', 5, 130.00, 50.00, 650.00, '2026-09-05 08:30:28'),
(1659, 224, 'Brain tablet', 'SR-06', '2029-06', '25 gram', 5, 65.00, 50.00, 325.00, '2026-09-05 08:30:28'),
(1660, 224, 'Hemacuf tablet', 'SR-03', '2029-03', '25 gram', 5, 70.00, 50.00, 350.00, '2026-09-05 08:30:28'),
(1661, 224, 'Anty Viral tablet', 'SR-08', '2029-08', '25 gram', 5, 70.00, 50.00, 350.00, '2026-09-05 08:30:28'),
(1662, 224, 'Urical tablet', 'SR-08', '2029-08', '25 gram', 5, 70.00, 50.00, 350.00, '2026-09-05 08:30:28'),
(1663, 224, 'R-power tablet', 'SR-04', '2029-04', '25 gram', 5, 70.00, 80.00, 350.00, '2026-09-05 08:30:28'),
(1664, 224, 'Antispos tablet', 'SR-06', '2029-06', '25 gram', 5, 65.00, 50.00, 325.00, '2026-09-05 08:30:28'),
(1665, 224, 'R-Artho Capsule', 'SR-09 ', '2029-09', '30 capsule ', 5, 180.00, 50.00, 900.00, '2026-09-05 08:30:28'),
(1666, 224, 'Pile-x capsule', 'SR-08', '2029-09', '30 capsule ', 5, 180.00, 50.00, 900.00, '2026-09-05 08:30:28'),
(1667, 224, 'R-warts capsule', 'SR-08', '2029-09', '30 capsule ', 5, 180.00, 50.00, 900.00, '2026-09-05 08:30:28'),
(1668, 224, 'R-Skin drop', 'SR-07', '2028-07', '50ml', 5, 150.00, 50.00, 750.00, '2026-09-05 08:30:28'),
(1669, 224, 'Livo care drop', 'SR-05', '2028-05', '50ml', 5, 150.00, 50.00, 750.00, '2026-09-05 08:30:28'),
(1670, 224, 'R-neuro drop', 'SR-08', '2028-08', '50ml', 5, 150.00, 50.00, 750.00, '2026-09-05 08:30:28'),
(1671, 224, 'Face care cream', 'SR-08', '2029-08', '50g', 5, 110.00, 50.00, 550.00, '2026-09-05 08:30:28'),
(1672, 224, 'Pain-x oil', 'SR-08', '2029-08', '30 ml', 5, 80.00, 50.00, 400.00, '2026-09-05 08:30:28'),
(1673, 225, 'Antispos tablet', 'SR-06', '2029-06', '25 gram', 1, 65.00, 50.00, 65.00, '2026-09-05 10:55:17'),
(1674, 225, 'R-skin tablet', 'SR-08', '2029-08', '25 gram', 1, 70.00, 50.00, 70.00, '2026-09-05 10:55:17'),
(1675, 225, 'Urical tablet', 'SR-08', '2029-08', '25 gram', 1, 70.00, 50.00, 70.00, '2026-09-05 10:55:17'),
(1676, 225, 'Dant care', 'SR-08', '2029-08', '60 gram ', 1, 60.00, 50.00, 60.00, '2026-09-05 10:55:17'),
(1677, 225, 'Pain-x ointment', 'SR-08', '2028-08', '25 gram', 1, 80.00, 50.00, 80.00, '2026-09-05 10:55:17'),
(1678, 226, 'R-Cid syrup', 'SR-08', '2029-08', '200-ml', 3, 130.00, 50.00, 390.00, '2026-09-05 14:45:55'),
(1679, 226, 'R-Tone syrup', 'SR-08', '2029-08', '200-ml', 3, 130.00, 50.00, 390.00, '2026-09-05 14:45:55'),
(1680, 226, 'Hemagyne syrup', 'SR-08', '2029-08', '200-ml', 3, 130.00, 50.00, 390.00, '2026-09-05 14:45:55'),
(1681, 226, 'Hemacuf syrup', 'SR-08', '2029-08', '100 ml', 5, 80.00, 50.00, 400.00, '2026-09-05 14:45:55'),
(1682, 226, 'Face care cream', 'SR-06', '2029-06', '50 gram', 2, 110.00, 50.00, 220.00, '2026-09-05 14:45:55'),
(1683, 226, 'Face care cream', 'SR-08', '2029-08', '50g', 1, 110.00, 50.00, 110.00, '2026-09-05 14:45:55'),
(1684, 226, 'R-Artho Capsule', 'SR-09 ', '2029-09', '30 capsule ', 2, 180.00, 50.00, 360.00, '2026-09-05 14:45:55'),
(1685, 226, 'Pile-x capsule', 'SR-08', '2029-09', '30 capsule ', 2, 180.00, 50.00, 360.00, '2026-09-05 14:45:55'),
(1686, 226, 'Pain-x ointment', 'SR-08', '2028-08', '25 gram', 5, 80.00, 50.00, 400.00, '2026-09-05 14:45:55'),
(1687, 226, 'Dant care', 'SR-08', '2029-08', '60 gram ', 3, 60.00, 50.00, 180.00, '2026-09-05 14:45:55'),
(1688, 226, 'R-power tablet', 'SR-04', '2029-04', '25 gram', 3, 70.00, 50.00, 210.00, '2026-09-05 14:45:55'),
(1689, 227, 'Rheumo -SR syrup', 'SR-08', '2029-09', '200-ml', 2, 130.00, 50.00, 260.00, '2026-09-05 14:57:56'),
(1690, 227, 'R-Cid syrup', 'SR-08', '2029-08', '200-ml', 2, 130.00, 50.00, 260.00, '2026-09-05 14:57:56'),
(1691, 227, 'R-Tone syrup', 'SR-08', '2029-08', '200-ml', 2, 130.00, 50.00, 260.00, '2026-09-05 14:57:56'),
(1692, 227, 'Hemacuf syrup', 'SR-08', '2029-08', '100 ml', 2, 80.00, 50.00, 160.00, '2026-09-05 14:57:56'),
(1693, 227, 'R-Typho syrup', 'SR-08', '2029-08', '100 ml', 2, 80.00, 50.00, 160.00, '2026-09-05 14:57:56'),
(1694, 227, 'Dant care', 'SR-08', '2029-08', '60 gram ', 1, 60.00, 50.00, 60.00, '2026-09-05 14:57:56'),
(1695, 227, 'R-neuro drop', 'SR-08', '2028-08', '50ml', 2, 150.00, 50.00, 300.00, '2026-09-05 14:57:56'),
(1696, 227, 'Livo care drop', 'SR-05', '2028-05', '50ml', 2, 150.00, 50.00, 300.00, '2026-09-05 14:57:56'),
(1697, 227, 'R-Artho Capsule', 'SR-09 ', '2029-09', '30 capsule ', 4, 180.00, 50.00, 720.00, '2026-09-05 14:57:56'),
(1698, 227, 'R-wats capsule', 'SR-08', '2029-08', '30 capsule ', 2, 180.00, 50.00, 360.00, '2026-09-05 14:57:56'),
(1699, 227, 'Pile-X capsule', 'SR-07', '2029-07', '30 capsule ', 2, 180.00, 50.00, 360.00, '2026-09-05 14:57:56'),
(1700, 227, 'Diarrhoea Tab', 'SR-02', '2029-02', '25 gm', 2, 70.00, 50.00, 140.00, '2026-09-05 14:57:56'),
(1701, 227, 'Anty Viral tablet', 'SR-08', '2029-08', '25 gram', 2, 70.00, 50.00, 140.00, '2026-09-05 14:57:56'),
(1702, 227, 'Helco plas Tab', 'SR-08', '2029-08', '25 gram', 2, 70.00, 50.00, 140.00, '2026-09-05 14:57:56'),
(1703, 227, 'Anty cold tablet', 'SR-06', '2029-06', '25 gram', 2, 65.00, 50.00, 130.00, '2026-09-05 14:57:56'),
(1704, 227, 'Feveron Tablet', 'SR-06', '2029-06', '25 gram', 2, 65.00, 50.00, 130.00, '2026-09-05 14:57:56'),
(1705, 227, 'Chest care oil', 'SR-04', '2029-04', '30 ml', 2, 80.00, 50.00, 160.00, '2026-09-05 14:57:56'),
(1706, 227, 'Pain-x oil', 'SR-08', '2029-08', '30 ml', 2, 80.00, 50.00, 160.00, '2026-09-05 14:57:56'),
(1707, 227, 'Spondy Drop', 'SR-01', '2028-01', '50 ml', 2, 150.00, 50.00, 300.00, '2026-09-05 14:57:56'),
(1708, 228, 'Urical tablet', 'SR-08', '2029-08', '25 gram', 20, 70.00, 40.00, 1400.00, '2026-09-10 04:26:14'),
(1709, 228, 'Ostolife tab', 'SR-09', '2029-09', '25 g', 20, 70.00, 40.00, 1400.00, '2026-09-10 04:26:14'),
(1710, 228, 'R-skin tablet', 'SR-08', '2029-08', '25 gram', 20, 70.00, 40.00, 1400.00, '2026-09-10 04:26:14'),
(1711, 228, 'Feveron Tablet', 'SR-06', '2029-06', '25 gram', 20, 65.00, 40.00, 1300.00, '2026-09-10 04:26:14'),
(1712, 228, 'Anty Viral tablet', 'SR-08', '2029-08', '25 gram', 20, 70.00, 40.00, 1400.00, '2026-09-10 04:26:14'),
(1713, 228, 'Anty cold tablet', 'SR-06', '2029-06', '25 gram', 20, 65.00, 40.00, 1300.00, '2026-09-10 04:26:14'),
(1714, 228, 'Helco plas Tab', 'SR-08', '2029-08', '25 gram', 20, 70.00, 40.00, 1400.00, '2026-09-10 04:26:14'),
(1715, 228, 'Hemacuf tablet', 'SR-03', '2029-03', '25 gram', 16, 70.00, 40.00, 1120.00, '2026-09-10 04:26:14'),
(1716, 228, 'Brain tablet', 'SR-06', '2029-06', '25 gram', 13, 65.00, 40.00, 845.00, '2026-09-10 04:26:14'),
(1717, 228, 'Diarrhoea Tablet', 'SR-08', '2029-09', '25 gram', 20, 70.00, 40.00, 1400.00, '2026-09-10 04:26:14'),
(1718, 228, 'Antispos tablet', 'SR-06', '2029-06', '25 gram', 20, 65.00, 40.00, 1300.00, '2026-09-10 04:26:14'),
(1719, 228, 'R-power tablet', 'SR-04', '2029-04', '25 gram', 20, 70.00, 40.00, 1400.00, '2026-09-10 04:26:14'),
(1720, 228, 'Hemo tablet', 'SR-06', '2029-06', '25 gram', 20, 70.00, 40.00, 1400.00, '2026-09-10 04:26:14'),
(1721, 228, 'Rheumo- SR syrup', 'SR-09', '2029-09', '200ml', 20, 130.00, 40.00, 2600.00, '2026-09-10 04:26:14'),
(1722, 228, 'Hemacuf tab', 'SR-09', '2029-09', '25 g', 4, 70.00, 40.00, 280.00, '2026-09-10 04:26:14');

-- --------------------------------------------------------

--
-- Table structure for table `MediaGallery`
--

CREATE TABLE `MediaGallery` (
  `MediaID` int(11) NOT NULL,
  `Title` varchar(255) DEFAULT NULL,
  `FilePath` varchar(500) DEFAULT NULL,
  `FileType` enum('image','video') DEFAULT 'image',
  `Category` varchar(100) DEFAULT NULL,
  `Description` text DEFAULT NULL,
  `DisplayOrder` int(11) DEFAULT 0,
  `IsActive` tinyint(4) DEFAULT 1,
  `UploadDate` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Partners`
--

CREATE TABLE `Partners` (
  `PartnerID` int(11) NOT NULL,
  `PartnerName` varchar(255) DEFAULT NULL,
  `ContactInfo` varchar(255) DEFAULT NULL,
  `CSRDetails` text DEFAULT NULL,
  `LogoPath` varchar(500) DEFAULT NULL,
  `InstagramURL` varchar(500) DEFAULT NULL,
  `WhatsAppNumber` varchar(50) DEFAULT NULL,
  `DisplayOrder` int(11) DEFAULT 0,
  `CreatedDate` timestamp NULL DEFAULT current_timestamp(),
  `IsActive` tinyint(4) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `Partners`
--

INSERT INTO `Partners` (`PartnerID`, `PartnerName`, `ContactInfo`, `CSRDetails`, `LogoPath`, `InstagramURL`, `WhatsAppNumber`, `DisplayOrder`, `CreatedDate`, `IsActive`) VALUES
(1, 'Surya Ojha', 'Nagpur 440013', NULL, NULL, NULL, '930902XXXX', 1, '2025-12-18 19:21:40', 0),
(2, 'Surya Ojha', 'Nagpur', NULL, 'uploads/partner_1766431352_69499a78922a1.jpg', NULL, '930902XXXX', 0, '2025-12-22 19:05:15', 0),
(3, 'Ritik Anil Zode', 'Sendurwafa ', NULL, 'uploads/partner_1766554147_694b7a230d34f.jpg', NULL, '8888105148', 0, '2025-12-24 05:29:07', 1),
(4, 'Kartik Kapgate ', 'Sendurwafa ', NULL, 'uploads/partner_1766562692_694b9b842a147.jpg', NULL, '9049371575', 0, '2025-12-24 07:50:35', 1),
(5, 'Er.Shrikant Bopche', 'Goregaon', NULL, 'uploads/partner_1766644615_694cdb87dcfa0.jpg', NULL, '7030220773', 0, '2025-12-25 06:36:55', 1),
(6, 'Tushar Shende ', 'Sendurwafa ', NULL, 'uploads/partner_1766644857_694cdc79d2966.jpg', NULL, '8767290216', 0, '2025-12-25 06:40:57', 1),
(7, 'Rushikesh Dudhankar', 'Sendurwafa ', NULL, 'uploads/partner_1766644904_694cdca82c39e.jpg', NULL, '7066152320', 0, '2025-12-25 06:41:44', 1),
(8, 'Sai Mungulmare ', 'Sendurwafa ', NULL, 'uploads/partner_1766646262_694ce1f6612e5.jpeg', NULL, '0000000000', 0, '2025-12-25 07:04:22', 1),
(9, 'Drx.Ashwini Chunne', 'Barwha', NULL, 'uploads/partner_1766657223_694d0cc727a2c.jpg', NULL, ' 70301 20548', 0, '2025-12-25 10:07:03', 1),
(10, 'Drx.Charuta Zode', 'Sakoli', NULL, 'uploads/partner_1766658038_694d0ff634744.jpg', NULL, '+91 88058 73887', 0, '2025-12-25 10:20:38', 1),
(11, 'Dr. Shrushti Choudhari', 'Sakoli', NULL, 'uploads/partner_1766658650_694d125a9d742.jpg', NULL, '+91 86986 67108', 0, '2025-12-25 10:30:50', 1),
(12, 'Mayur Doye', 'Sendurwafa ', NULL, 'uploads/partner_1766658972_694d139cda763.jpg', NULL, '99239 59941', 0, '2025-12-25 10:36:12', 1),
(13, 'Chiranjiv Tembhare', 'Mokhe', NULL, 'uploads/partner_1766671382_694d4416baf22.jpg', NULL, '90495 92751', 0, '2025-12-25 14:03:02', 1),
(14, 'Prashant Khandait', 'Sendurwafa ', NULL, 'uploads/partner_1766682090_694d6dea7eea8.jpg', NULL, '7768801824', 0, '2025-12-25 17:01:30', 1),
(15, 'Anchal Kadrewar', 'Sendurwafa ', NULL, 'uploads/partner_1766734232_694e399876406.jpg', NULL, '9834608435', 0, '2025-12-26 07:30:33', 1),
(16, 'Mukul Bopche', 'Hiratola', NULL, 'uploads/partner_1766768375_694ebef77313f.jpg', NULL, ' 96571 87261', 0, '2025-12-26 16:59:35', 1),
(17, 'Rahul Lanjewar ', 'Sendurwafa ', NULL, 'uploads/partner_1766768437_694ebf35470bf.jpg', NULL, '80079 58692', 0, '2025-12-26 17:00:37', 1),
(18, 'Prajwal Gabhane', 'Sendurwafa ', NULL, 'uploads/partner_1767195807_6955449fd9a65.jpg', NULL, '0000000', 0, '2025-12-31 15:43:27', 1);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `batch` varchar(50) DEFAULT NULL,
  `expiry` varchar(50) DEFAULT NULL,
  `capacity` varchar(50) DEFAULT NULL,
  `rate` decimal(10,2) DEFAULT NULL,
  `stock_qty` int(11) DEFAULT 0,
  `gst_percent` decimal(5,2) DEFAULT 5.00,
  `admin_id` int(11) DEFAULT NULL COMMENT 'Which admin owns this product'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `batch`, `expiry`, `capacity`, `rate`, `stock_qty`, `gst_percent`, `admin_id`) VALUES
(19, 'R-Lax', 'SR-04', '2027-09', '100 ml', 80.00, 3, 5.00, 1),
(36, 'Hemagyne Drop', 'SR-02', '2028-02', '50 ml', 150.00, 0, 5.00, 1),
(38, 'BP Care Drop', 'SR-04', '2027-09', '50 ml', 150.00, 61, 5.00, 1),
(42, 'Spondy Drop', 'SR-01', '2028-01', '50 ml', 150.00, 28, 5.00, 1),
(48, 'Diarrhoea Tab', 'SR-02', '2029-02', '25 gm', 70.00, 0, 5.00, 1),
(59, 'Pile -x drop', 'SR-03', '2028-03', '50ml', 150.00, 3, 5.00, 1),
(70, 'Hemacuf tablet', 'SR-03', '2029-03', '25 gram', 70.00, 0, 5.00, 1),
(85, 'BP care drop ', 'SR-01', '2027-06', '50ml', 150.00, 0, 5.00, 1),
(86, 'Alleres -SR drop ', 'SR-03 ', '2027-06', '50ml', 150.00, 1, 5.00, 1),
(95, 'Chest care oil', 'SR-04', '2029-04', '30 ml', 80.00, 96, 5.00, 1),
(118, 'Livo care drop ', 'SR-05', '2028-05', '50ml', 150.00, 87, 5.00, 1),
(135, 'Feveron Tablet ', 'SR-06', '2029-06', '25 gram', 65.00, 12, 5.00, 1),
(136, 'Antispos tablet ', 'SR-06', '2029-06', '25 gram', 65.00, 22, 5.00, 1),
(138, 'Anty cold tablet', 'SR-06', '2029-06', '25 gram', 65.00, 60, 5.00, 1),
(152, 'Hemo tablet ', 'SR-06', '2029-06', '25 gram', 70.00, 46, 5.00, 1),
(153, 'Face care cream ', 'SR-06', '2029-06', '50 gram', 110.00, 0, 5.00, 1),
(156, 'Dant care ', 'SR-06', '2029-06', '60gram ', 60.00, 0, 5.00, 1),
(165, 'Brain tablet ', 'SR-06', '2029-06', '25 gram', 65.00, 0, 5.00, 1),
(172, 'Pain-x oil', 'SR-07', '2029-07', '30ml', 80.00, 0, 5.00, 1),
(173, 'Face care cream ', 'SR-07', '2029-07', '50 gram', 110.00, 0, 5.00, 1),
(177, 'R-Lax syrup ', 'SR-07', '2029-07', '100 ml', 80.00, 0, 5.00, 1),
(178, 'R-Skin drop ', 'SR-07', '2028-07', '50ml', 150.00, 65, 5.00, 1),
(179, 'Hemagyne Drop', 'SR-07', '2028-07', '50ml', 150.00, 158, 5.00, 1),
(185, 'Pile-X capsule ', 'SR-07', '2029-07', '30 capsule ', 180.00, 1, 5.00, 1),
(187, 'Hemagyne Syr', 'SR-07', '2029-07', '200ml', 130.00, 1, 5.00, 1),
(190, 'R-wats capsule ', 'SR-07', '2029-07', '30 capsule ', 180.00, 0, 5.00, 1),
(195, 'R-Tone syrup', 'SR-07', '2029-07', '200-ml', 130.00, 0, 5.00, 1),
(197, 'R-Liv syrup ', 'SR-07', '2029-07', '200-ml', 130.00, 0, 5.00, 1),
(199, 'Heart care syrup', 'SR-07', '2029-07', '200-ml', 130.00, 0, 5.00, 1),
(203, 'Diabotone syrup ', 'SR-07', '2029-07', '200-ml', 130.00, 0, 5.00, 1),
(205, 'Urical-SR syrup ', 'SR-07', '2029-07', '200-ml', 130.00, 0, 5.00, 1),
(206, 'Urical-SR syrup ', 'SR-08', '2029-08', '200-ml', 130.00, 0, 5.00, 1),
(210, 'Pile-x capsule ', 'SR-07', '2029-07', '30 capsule ', 180.00, 0, 5.00, 1),
(211, 'R-warts capsule ', 'SR-07', '2029-07', '30 capsule ', 180.00, 0, 5.00, 1),
(212, 'R-Typho syrup', 'SR-08', '2029-08', '100 ml', 80.00, 1, 5.00, 1),
(213, 'Ostolife tablet', 'SR-08', '2029-08', '25 gram', 70.00, 8, 5.00, 1),
(215, 'Urical tablet ', 'SR-08', '2029-08', '25 gram', 70.00, 29, 5.00, 1),
(216, 'Helco plas Tab', 'SR-08', '2029-08', '25 gram', 70.00, 51, 5.00, 1),
(218, 'Anty Viral tablet ', 'SR-08', '2029-08', '25 gram', 70.00, 83, 5.00, 1),
(219, 'R-skin tablet ', 'SR-08', '2029-08', '25 gram', 70.00, 55, 5.00, 1),
(220, 'Pain-x ointment ', 'SR-08', '2028-08', '25 gram', 80.00, 162, 5.00, 1),
(221, 'Rheumo -SR syrup', 'SR-08', '2029-08', '200-ml', 130.00, 0, 5.00, 1),
(222, 'R-Cid syrup ', 'SR-08', '2029-08', '200-ml', 130.00, 0, 5.00, 1),
(223, 'R-Skin syrup', 'SR-08', '2029-08', '200-ml', 130.00, 0, 5.00, 1),
(225, 'Dant care ', 'SR-08', '2029-08', '60 gram ', 60.00, 0, 5.00, 1),
(226, 'R-neuro drop ', 'SR-08', '2028-08', '50ml', 150.00, 168, 5.00, 1),
(227, 'Hemacuf syrup ', 'SR-08', '2029-08', '100 ml', 80.00, 1, 5.00, 1),
(228, 'R-Artho Capsule ', 'SR-07', '2029-07', '30 capsule ', 180.00, 0, 5.00, 1),
(229, 'R-power tablet ', 'SR-04', '2029-04', '25 gram', 70.00, 33, 5.00, 1),
(230, 'Rheumo- SR', 'SR-08', '2029-08', '200ml', 130.00, 0, 5.00, 1),
(231, 'Dant Care ', 'SR-08', '2029-08', '60g', 60.00, 0, 5.00, 1),
(232, 'Spondy Droup', 'SR-08', '2028-08', '50 ml', 150.00, 178, 5.00, 1),
(233, 'R-Artho Capsule ', 'SR-08', '2029-08', '30 capsule ', 180.00, 1, 5.00, 1),
(234, 'Hemagyne syrup', 'SR-08', '2029-08', '200-ml', 130.00, 2, 5.00, 1),
(235, 'R-Lax syrup ', 'SR-08', '2029-08', '100 ml', 80.00, 8, 5.00, 1),
(236, 'R-Cid syrup ', 'SR-08', '2029-08', '200-ml', 130.00, 0, 5.00, 1),
(237, 'Heart care syrup', 'SR-08', '2029-08', '200-ml', 130.00, 0, 5.00, 1),
(238, 'Pain-x oil', 'SR-08', '2029-08', '30 ml', 80.00, 70, 5.00, 1),
(239, 'R-Tone syrup', 'SR-07', '2029-07', '200-ml', 130.00, 0, 5.00, 1),
(240, 'R-Tone syrup', 'SR-08', '2029-08', '200-ml', 130.00, 0, 5.00, 1),
(241, 'Pile-x capsule ', 'SR-07', '2028-07', '30 capsule ', 180.00, 0, 5.00, 1),
(242, 'R-wats capsule ', 'SR-08', '2029-08', '30 capsule ', 180.00, 2, 5.00, 1),
(243, 'Gastrzen Cap', 'SR-08', '2029-08', '30 capsule ', 180.00, 1, 5.00, 1),
(244, 'R-Typho syrup ', 'SR-08', '2029-08', '100 ml', 80.00, 23, 5.00, 1),
(245, 'R-Liv syrup ', 'SR-08', '2029-08', '200ml', 130.00, 19, 5.00, 1),
(246, 'Hemacuf syrup ', 'SR-08', '2029-08', '100 ml', 80.00, 15, 5.00, 1),
(247, 'Face care cream ', 'SR-08', '2029-08', '50g', 110.00, 30, 5.00, 1),
(248, 'Rheumo- SR', 'SR-08', '2029-08', '200ml', 130.00, 0, 5.00, 1),
(249, 'Heart care syrup ', 'SR-08', '2029-08', '200ml', 130.00, 1, 5.00, 1),
(250, 'R-cid syrup ', 'SR-08', '2029-08', '200ml', 130.00, 0, 5.00, 1),
(251, 'R-skin syrup ', 'SR-08', '2029-08', '200ml', 130.00, 2, 5.00, 1),
(252, 'Diaboton syrup ', 'SR-08', '2029-08', '200ml', 130.00, 22, 5.00, 1),
(253, 'R-Artho Capsule ', 'SR-08', '2029-08', '30 capsule ', 180.00, 0, 5.00, 1),
(254, 'Pile-X capsule ', 'SR-08', '2029-08', '30 capsule ', 180.00, 0, 5.00, 1),
(255, 'R-Cid syrup ', 'SR-08', '2029-08', '200-ml', 130.00, 0, 5.00, 1),
(256, 'R-Artho Capsule ', 'SR-08', '2029-08', '30 capsule ', 180.00, 0, 5.00, 1),
(257, 'Power -x capsule ', 'SR-08', '2029-08', '25 capsule ', 150.00, 9, 5.00, 1),
(258, 'Dant care ', 'SR-08', '2029-08', '60 gram ', 60.00, 140, 5.00, 1),
(259, 'R-Tone syrup', 'SR-08', '2029-08', '200-ml', 130.00, 10, 5.00, 1),
(260, 'R-Cid syrup ', 'SR-08', '2029-08', '200-ml', 130.00, 24, 5.00, 1),
(261, 'Urical-SR syrup ', 'SR-08', '2028-08', '200-ml', 130.00, 28, 5.00, 1),
(262, 'Heart care syrup', 'SR-08', '2029-08', '200-ml', 130.00, 10, 5.00, 1),
(263, 'Rheumo -SR syrup', 'SR-08', '2029-09', '200-ml', 130.00, 21, 5.00, 1),
(264, 'Diarrhoea Tablet', 'SR-08', '2029-09', '25 gram', 70.00, 152, 5.00, 1),
(265, 'Pile-x capsule ', 'SR-08', '2029-09', '30 capsule ', 180.00, 39, 5.00, 1),
(266, 'R-warts capsule ', 'SR-08', '2029-09', '30 capsule ', 180.00, 0, 5.00, 1),
(267, 'R-Skin syrup', 'SR-08', '2029-09', '200-ml', 130.00, 7, 5.00, 1),
(268, 'R-Tone syrup', 'SR-08', '2029-09', '200-ml', 130.00, 0, 5.00, 1),
(269, 'Urical-SR syrup ', 'SR-08', '2029-09', '200-ml', 130.00, 0, 5.00, 1),
(270, 'Pile-x capsule ', 'SR-08', '2029-09', '30 capsule ', 180.00, 0, 5.00, 1),
(271, 'R-Artho Capsule ', 'SR-08', '2029-09', '30 capsule ', 180.00, 0, 5.00, 1),
(272, 'R-warts capsule ', 'SR-08', '2029-09', '30 capsule ', 180.00, 30, 5.00, 1),
(273, 'R-Artho Capsule ', 'SR-09 ', '2029-09', '30 capsule ', 180.00, 10, 5.00, 1),
(274, 'R-Liv syrup ', 'SR-09', '2029-09', '200ml', 130.00, 54, 5.00, 1),
(275, 'R-CID syrup ', 'SR-09', '2029-09', '200ml', 130.00, 55, 5.00, 1),
(276, 'Rheumo- SR syrup ', 'SR-09', '2029-09', '200ml', 130.00, 0, 5.00, 1),
(277, 'Hemacuf tab', 'SR-09', '2029-09', '25 g', 70.00, 198, 5.00, 1),
(278, 'Ostolife tab', 'SR-09', '2029-09', '25 g', 70.00, 0, 5.00, 1),
(279, 'R-Lax syrup', 'SR-09', '2029-09', '100 ml', 80.00, 53, 5.00, 1);

-- --------------------------------------------------------

--
-- Table structure for table `TeamMembers`
--

CREATE TABLE `TeamMembers` (
  `TeamID` int(11) NOT NULL,
  `Name` varchar(255) DEFAULT NULL,
  `Position` varchar(255) DEFAULT NULL,
  `Photo` varchar(500) DEFAULT NULL,
  `Bio` text DEFAULT NULL,
  `DisplayOrder` int(11) DEFAULT 0,
  `IsActive` tinyint(4) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Testimonials`
--

CREATE TABLE `Testimonials` (
  `TestimonialID` int(11) NOT NULL,
  `Name` varchar(255) DEFAULT NULL,
  `Message` text DEFAULT NULL,
  `Photo` varchar(500) DEFAULT NULL,
  `Position` varchar(255) DEFAULT NULL,
  `DisplayOrder` int(11) DEFAULT 0,
  `IsActive` tinyint(4) DEFAULT 1,
  `CreatedDate` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `Testimonials`
--

INSERT INTO `Testimonials` (`TestimonialID`, `Name`, `Message`, `Photo`, `Position`, `DisplayOrder`, `IsActive`, `CreatedDate`) VALUES
(1, 'Ritik Anil Zode', 'Xx', 'uploads/testimonial_1766554000.jpg', 'Charman ', 0, 0, '2025-12-24 05:26:40');

-- --------------------------------------------------------

--
-- Table structure for table `ThemeSettings`
--

CREATE TABLE `ThemeSettings` (
  `SettingID` int(11) NOT NULL,
  `BackgroundColor` varchar(20) DEFAULT '#667eea',
  `BackgroundImage` varchar(500) DEFAULT NULL,
  `HeroTitle` varchar(255) DEFAULT 'Together for Hope, Together for Change',
  `HeroSubtitle` varchar(255) DEFAULT 'Empowering communities across India',
  `ShowAboutSection` tinyint(4) DEFAULT 1,
  `ShowStatsSection` tinyint(4) DEFAULT 1,
  `ShowProgramsSection` tinyint(4) DEFAULT 1,
  `ShowGallerySection` tinyint(4) DEFAULT 1,
  `ShowPartnersSection` tinyint(4) DEFAULT 1,
  `UpdatedDate` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `ThemeSettings`
--

INSERT INTO `ThemeSettings` (`SettingID`, `BackgroundColor`, `BackgroundImage`, `HeroTitle`, `HeroSubtitle`, `ShowAboutSection`, `ShowStatsSection`, `ShowProgramsSection`, `ShowGallerySection`, `ShowPartnersSection`, `UpdatedDate`) VALUES
(1, '#2b991a', NULL, 'Jahan Zarurat Ho, Wahi Reliefwave Foundation Ho  ', 'Reliefwave Foundation ek Section 8 registered NGO  hai,  12A aur 80G certified,  Good response  Social Work  Environment  Animal  Rescue | Education  Helping Needy  imaandari aur transparency ke sath kaam karta hai.', 1, 1, 1, 1, 1, '2025-12-24 01:27:34');

-- --------------------------------------------------------

--
-- Table structure for table `Users`
--

CREATE TABLE `Users` (
  `UserID` int(11) NOT NULL,
  `Email` varchar(255) DEFAULT NULL,
  `Password` varchar(255) DEFAULT NULL,
  `IsActive` tinyint(4) DEFAULT 1,
  `CreatedDate` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `Users`
--

INSERT INTO `Users` (`UserID`, `Email`, `Password`, `IsActive`, `CreatedDate`) VALUES
(1, 'ritik@gmail.com', 'Ritik@123', 1, '2025-12-18 19:00:09');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `role` enum('superadmin','admin','staff') DEFAULT 'staff',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `admin_id` int(11) DEFAULT NULL COMMENT 'For staff: which admin they belong to',
  `IsActive` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `role`, `created_at`, `admin_id`, `IsActive`) VALUES
(1, 'admin', '542000', 'Admin One', 'admin', '2026-03-02 19:06:48', NULL, 1),
(2, 'Pharma', 'Himani8', 'Billing Clerk', 'staff', '2026-03-02 19:06:48', NULL, 1),
(3, 'superadmin', 'Super@123', 'Super Admin', 'superadmin', '2026-05-23 18:50:57', NULL, 1),
(11, 'Anjani', 'Savan11', 'ANJANI E.H Parma', 'admin', '2026-07-27 03:43:55', NULL, 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `AboutInfo`
--
ALTER TABLE `AboutInfo`
  ADD PRIMARY KEY (`AboutID`);

--
-- Indexes for table `AdminLogs`
--
ALTER TABLE `AdminLogs`
  ADD PRIMARY KEY (`LogID`);

--
-- Indexes for table `AdminSettings`
--
ALTER TABLE `AdminSettings`
  ADD PRIMARY KEY (`SettingID`),
  ADD UNIQUE KEY `SettingKey` (`SettingKey`);

--
-- Indexes for table `Benefits`
--
ALTER TABLE `Benefits`
  ADD PRIMARY KEY (`BenefitID`);

--
-- Indexes for table `Campaigns`
--
ALTER TABLE `Campaigns`
  ADD PRIMARY KEY (`CampaignID`);

--
-- Indexes for table `ContactInfo`
--
ALTER TABLE `ContactInfo`
  ADD PRIMARY KEY (`ContactID`);

--
-- Indexes for table `Donations`
--
ALTER TABLE `Donations`
  ADD PRIMARY KEY (`DonationID`);

--
-- Indexes for table `Events`
--
ALTER TABLE `Events`
  ADD PRIMARY KEY (`EventID`);

--
-- Indexes for table `Gallery`
--
ALTER TABLE `Gallery`
  ADD PRIMARY KEY (`GalleryID`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_no` (`invoice_no`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_id` (`invoice_id`);

--
-- Indexes for table `MediaGallery`
--
ALTER TABLE `MediaGallery`
  ADD PRIMARY KEY (`MediaID`);

--
-- Indexes for table `Partners`
--
ALTER TABLE `Partners`
  ADD PRIMARY KEY (`PartnerID`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `TeamMembers`
--
ALTER TABLE `TeamMembers`
  ADD PRIMARY KEY (`TeamID`);

--
-- Indexes for table `Testimonials`
--
ALTER TABLE `Testimonials`
  ADD PRIMARY KEY (`TestimonialID`);

--
-- Indexes for table `ThemeSettings`
--
ALTER TABLE `ThemeSettings`
  ADD PRIMARY KEY (`SettingID`);

--
-- Indexes for table `Users`
--
ALTER TABLE `Users`
  ADD PRIMARY KEY (`UserID`),
  ADD UNIQUE KEY `Email` (`Email`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `uq_username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `AboutInfo`
--
ALTER TABLE `AboutInfo`
  MODIFY `AboutID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `AdminLogs`
--
ALTER TABLE `AdminLogs`
  MODIFY `LogID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `AdminSettings`
--
ALTER TABLE `AdminSettings`
  MODIFY `SettingID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=84;

--
-- AUTO_INCREMENT for table `Benefits`
--
ALTER TABLE `Benefits`
  MODIFY `BenefitID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `Campaigns`
--
ALTER TABLE `Campaigns`
  MODIFY `CampaignID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `ContactInfo`
--
ALTER TABLE `ContactInfo`
  MODIFY `ContactID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `Donations`
--
ALTER TABLE `Donations`
  MODIFY `DonationID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `Events`
--
ALTER TABLE `Events`
  MODIFY `EventID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `Gallery`
--
ALTER TABLE `Gallery`
  MODIFY `GalleryID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=229;

--
-- AUTO_INCREMENT for table `invoice_items`
--
ALTER TABLE `invoice_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1723;

--
-- AUTO_INCREMENT for table `MediaGallery`
--
ALTER TABLE `MediaGallery`
  MODIFY `MediaID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `Partners`
--
ALTER TABLE `Partners`
  MODIFY `PartnerID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=280;

--
-- AUTO_INCREMENT for table `TeamMembers`
--
ALTER TABLE `TeamMembers`
  MODIFY `TeamID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `Testimonials`
--
ALTER TABLE `Testimonials`
  MODIFY `TestimonialID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `ThemeSettings`
--
ALTER TABLE `ThemeSettings`
  MODIFY `SettingID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `Users`
--
ALTER TABLE `Users`
  MODIFY `UserID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD CONSTRAINT `invoice_items_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
