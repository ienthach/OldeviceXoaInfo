-- phpMyAdmin SQL Dump
-- version 4.9.5
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jan 31, 2021 at 10:35 PM
-- Server version: 10.2.36-MariaDB-log-cll-lve
-- PHP Version: 7.3.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `vidcasha_oldDevices`
--

-- --------------------------------------------------------

--
-- Table structure for table `oldDeviceAccount`
--

CREATE TABLE `oldDeviceAccount` (
  `id` int(11) NOT NULL,
  `user` varchar(25) NOT NULL,
  `typeDB` tinyint(4) NOT NULL DEFAULT 1,
  `hash` varchar(50) DEFAULT NULL,
  `dateCreate` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `PUBoldDevice`
--

CREATE TABLE `PUBoldDevice` (
  `id` int(11) UNSIGNED NOT NULL,
  `ownerID` int(11) NOT NULL,
  `date` datetime DEFAULT NULL,
  `country` varchar(2) NOT NULL,
  `idfa` varchar(40) NOT NULL,
  `deviceName` varchar(50) NOT NULL,
  `deviceModel` varchar(15) NOT NULL,
  `deviceVersion` varchar(10) NOT NULL,
  `carrierName` varchar(10) NOT NULL,
  `carrierCountry` varchar(5) NOT NULL,
  `carrierCountryISO` varchar(2) NOT NULL,
  `carrierNetwork` varchar(5) NOT NULL,
  `bundleIDs` text NOT NULL,
  `dateModify` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `requestCount` int(5) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `PUBoldDeviceBundles`
--

CREATE TABLE `PUBoldDeviceBundles` (
  `id` int(11) UNSIGNED NOT NULL,
  `oldDeviceID` int(11) UNSIGNED NOT NULL,
  `createDate` datetime NOT NULL,
  `bundleID` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `PUBoldDeviceCountryDeploy`
--

CREATE TABLE `PUBoldDeviceCountryDeploy` (
  `id` int(11) NOT NULL,
  `country` varchar(3) NOT NULL,
  `minRequest` int(11) NOT NULL,
  `ownerID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `oldDeviceAccount`
--
ALTER TABLE `oldDeviceAccount`
  ADD PRIMARY KEY (`id`),
  ADD KEY `typeDB` (`typeDB`),
  ADD KEY `hash` (`hash`),
  ADD KEY `user` (`user`);

--
-- Indexes for table `PUBoldDevice`
--
ALTER TABLE `PUBoldDevice`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idfa` (`idfa`),
  ADD KEY `date` (`date`,`country`,`deviceModel`,`deviceVersion`),
  ADD KEY `requestCount` (`requestCount`),
  ADD KEY `country` (`country`),
  ADD KEY `ownerID` (`ownerID`);

--
-- Indexes for table `PUBoldDeviceBundles`
--
ALTER TABLE `PUBoldDeviceBundles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idOldDevice` (`oldDeviceID`,`createDate`),
  ADD KEY `bundleID` (`bundleID`);

--
-- Indexes for table `PUBoldDeviceCountryDeploy`
--
ALTER TABLE `PUBoldDeviceCountryDeploy`
  ADD PRIMARY KEY (`id`),
  ADD KEY `minRequest` (`minRequest`),
  ADD KEY `ownerID` (`ownerID`),
  ADD KEY `country` (`country`) USING BTREE;

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `oldDeviceAccount`
--
ALTER TABLE `oldDeviceAccount`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `PUBoldDevice`
--
ALTER TABLE `PUBoldDevice`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `PUBoldDeviceBundles`
--
ALTER TABLE `PUBoldDeviceBundles`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `PUBoldDeviceCountryDeploy`
--
ALTER TABLE `PUBoldDeviceCountryDeploy`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `PUBoldDevice`
--
ALTER TABLE `PUBoldDevice`
  ADD CONSTRAINT `PUBoldDevice_ibfk_1` FOREIGN KEY (`ownerID`) REFERENCES `oldDeviceAccount` (`id`);

--
-- Constraints for table `PUBoldDeviceBundles`
--
ALTER TABLE `PUBoldDeviceBundles`
  ADD CONSTRAINT `PUBoldDeviceBundles_ibfk_1` FOREIGN KEY (`oldDeviceID`) REFERENCES `PUBoldDevice` (`id`);

--
-- Constraints for table `PUBoldDeviceCountryDeploy`
--
ALTER TABLE `PUBoldDeviceCountryDeploy`
  ADD CONSTRAINT `ownerID_cts1` FOREIGN KEY (`ownerID`) REFERENCES `oldDeviceAccount` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
