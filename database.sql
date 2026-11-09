-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Dec 20, 2025 at 09:21 PM
-- Server version: 10.11.11-MariaDB-cll-lve
-- PHP Version: 8.4.14

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
  `SettingKey` varchar(100) DEFAULT NULL,
  `SettingValue` text DEFAULT NULL,
  `UpdatedDate` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `AdminSettings`
--

INSERT INTO `AdminSettings` (`SettingID`, `SettingKey`, `SettingValue`, `UpdatedDate`) VALUES
(2, 'HeroVideoPath', '', '2025-12-18 19:00:09'),
(3, 'ThemeColor', 'gray', '2025-12-18 19:00:09'),
(4, 'SecondaryColor', '#38ef7d', '2025-12-19 04:59:05'),
(5, 'GradientType', 'linear', '2025-12-19 05:01:50'),
(6, 'GradientDirection', '135deg', '2025-12-19 05:01:50'),
(7, 'LogoPath', 'uploads/logo.jpg', '2025-12-18 19:07:52'),
(8, 'BackgroundPath', '', '2025-12-19 04:50:57'),
(9, 'SiteHeading', 'Reliefwave Foundation ( NGO)', '2025-12-19 04:56:39'),
(10, 'FontColor', '#30a103', '2025-12-19 04:56:39'),
(11, 'PrimaryColor', '#11998e', '2025-12-19 04:59:05'),
(44, 'QRCodePath', 'uploads/qr-code.png', '2025-12-20 07:19:50'),
(45, 'UpiId', 'reliefwave@paytm', '2025-12-20 09:21:00'),
(46, 'AboutSubtitle', 'Transforming lives through compassion, dedication, and sustainable impact across India', '2025-12-20 09:25:00'),
(47, 'MissionText', 'To empower underprivileged communities through education, healthcare, and sustainable development programs that create lasting positive change.', '2025-12-20 09:25:00'),
(48, 'VisionText', 'A world where everyone has access to opportunities, dignity, and the chance to build a better future for themselves and their families.', '2025-12-20 09:25:00'),
(49, 'LivesImpacted', '1000', '2025-12-20 09:25:00'),
(50, 'ProjectsCompleted', '50', '2025-12-20 09:25:00'),
(51, 'CommunitiesServed', '25', '2025-12-20 09:25:00'),
(52, 'YearsOfService', '5', '2025-12-20 09:25:00');

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
(1, 'Education', '?? A child’s classroom today shapes the nation’s tomorrow.\r\n\r\nAt ReliefWave Foundation, we believe education is not charity—it is empowerment.\r\nYour donation helps provide school supplies, scholarships, and learning opportunities to underprivileged children across India.\r\n\r\nTogether, we can turn dreams into degrees and hope into reality.\r\n?? Donate now. Be the force behind education.\r\n\r\n#ReliefWaveFoundation #EducationForAll #DonateForEducation #India #ChildrensFuture', 10000.00, 0.00, '', 1, 1, '2025-12-18 19:29:35');

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
(1, 'gau sewa', 'sakoli', '2025-12-28', 'sakoli', 1, 1, '2025-12-18 19:22:06');

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
(3, 'uploads/gallery_1766139051.jpg', 'Food', '', 'Community', '2025-12-19 10:10:54', 1);

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
(1, 'Surya Ojha', 'Nagpur 440013', NULL, NULL, NULL, '930902XXXX', 1, '2025-12-18 19:21:40', 1);

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
(1, '#11998e', NULL, 'Jahan Zarurat Ho, Wahi Reliefwave Foundation Ho  ', 'Reliefwave Foundation ek Section 8 registered NGO  hai,  12A aur 80G certified,  Good response  Social Work  Environment  Animal  Rescue | Education  Helping Needy  imaandari aur transparency ke sath kaam karta hai.', 1, 1, 1, 1, 1, '2025-12-19 05:38:05');

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
  MODIFY `SettingID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `Benefits`
--
ALTER TABLE `Benefits`
  MODIFY `BenefitID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `Campaigns`
--
ALTER TABLE `Campaigns`
  MODIFY `CampaignID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
  MODIFY `EventID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `Gallery`
--
ALTER TABLE `Gallery`
  MODIFY `GalleryID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `MediaGallery`
--
ALTER TABLE `MediaGallery`
  MODIFY `MediaID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `Partners`
--
ALTER TABLE `Partners`
  MODIFY `PartnerID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `TeamMembers`
--
ALTER TABLE `TeamMembers`
  MODIFY `TeamID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `Testimonials`
--
ALTER TABLE `Testimonials`
  MODIFY `TestimonialID` int(11) NOT NULL AUTO_INCREMENT;

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
