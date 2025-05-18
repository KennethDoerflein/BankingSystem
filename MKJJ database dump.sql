-- phpMyAdmin SQL Dump
-- version 5.2.1deb1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: May 18, 2025 at 06:20 AM
-- Server version: 10.11.11-MariaDB-0+deb12u1
-- PHP Version: 8.2.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `mkjj`
--
CREATE DATABASE IF NOT EXISTS `mkjj` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `mkjj`;

GRANT SELECT, INSERT, DELETE, UPDATE ON mkjj.* TO mkjj_user@localhost IDENTIFIED BY 'PASSWORD_PLACEHOLDER';


-- --------------------------------------------------------

--
-- Table structure for table `accounts`
--

CREATE TABLE `accounts` (
  `bankAccountNumber` char(12) NOT NULL,
  `accountType` varchar(10) NOT NULL,
  `balance` double NOT NULL,
  `ownerID` char(6) NOT NULL,
  `dateOpened` date NOT NULL,
  `numOfTransactions` int(11) NOT NULL,
  `status` varchar(128) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `accounts`
--

INSERT INTO `accounts` (`bankAccountNumber`, `accountType`, `balance`, `ownerID`, `dateOpened`, `numOfTransactions`, `status`) VALUES
('460226058339', 'checking', 9227998479.25, '984628', '2025-04-16', 2, 'approved'),
('407639968276', 'checking', 500000.01, '984628', '2025-04-17', 0, 'approved'),
('461445043778', 'checking', 6000, '984628', '2025-04-16', 1, 'denied'),
('478393637340', 'savings', 490781298103.94, '984628', '2025-04-16', 7, 'approved'),
('407766904261', 'checking', 55, '984628', '2025-04-27', 0, 'pending approval');

-- --------------------------------------------------------

--
-- Table structure for table `customer`
--

CREATE TABLE `customer` (
  `customerID` char(6) NOT NULL,
  `cUsername` varchar(50) DEFAULT NULL,
  `cPassword` varchar(255) NOT NULL,
  `cEmail` varchar(50) DEFAULT NULL,
  `cFname` varchar(50) NOT NULL,
  `cLname` varchar(50) NOT NULL,
  `cAddress` varchar(100) NOT NULL,
  `phoneNumber` varchar(255) DEFAULT NULL,
  `numOfAccounts` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `customer`
--

INSERT INTO `customer` (`customerID`, `cUsername`, `cPassword`, `cEmail`, `cFname`, `cLname`, `cAddress`, `phoneNumber`, `numOfAccounts`) VALUES
('984628', 'test', '$2y$10$duX10ezAkGFmt5fJM6F2fuPBETYTdv4pRbRwE2vyu0YHzdNV/XnKS', 'test@test.com', 'John', 'Doe', '710 Cool Street Oak, NJ 07043', '888-888-8888', 6);

-- --------------------------------------------------------

--
-- Table structure for table `employee`
--

CREATE TABLE `employee` (
  `employeeID` char(6) NOT NULL,
  `eUsername` varchar(500) DEFAULT NULL,
  `ePassword` varchar(255) NOT NULL,
  `eEmail` varchar(500) DEFAULT NULL,
  `eFname` varchar(500) NOT NULL,
  `eLname` varchar(500) NOT NULL,
  `eAddress` varchar(1000) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `employee`
--

INSERT INTO `employee` (`employeeID`, `eUsername`, `ePassword`, `eEmail`, `eFname`, `eLname`, `eAddress`) VALUES
('625505', 'admin', '$2y$10$ajOnRNZsK5xJA/1scHQ79u1FABd53BXwnK3vMFPmmZpUksbITU.eS', 'admin@mkjj.com', 'Ada', 'Lovelace', '1 Normal Ave Montclair NJ 07043');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `dateOfTransaction` datetime NOT NULL,
  `transactionType` varchar(128) NOT NULL,
  `changeInBalance` double NOT NULL,
  `bankAccountNumber` char(12) NOT NULL,
  `transactionID` char(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`dateOfTransaction`, `transactionType`, `changeInBalance`, `bankAccountNumber`, `transactionID`) VALUES
('2025-04-17 20:41:10', 'transfer received', 5220202, '478393637340', '13644698910'),
('2025-04-17 20:18:17', 'external transfer sent', -5, '460226058339', '75060611247'),
('2025-04-17 20:41:10', 'transfer sent', -5376808.06, '478393637340', '12868534277'),
('2025-04-17 20:41:01', 'withdrawal', -25000, '478393637340', '14604454903'),
('2025-04-17 20:40:50', 'deposit', 500000000000, '478393637340', '17827390318'),
('2025-04-17 20:24:38', 'deposit', 5, '460226058339', '78976870394'),
('2025-04-17 20:24:31', 'withdrawal', -5, '460226058339', '86975441009'),
('2025-04-17 19:53:20', 'initial deposit', 500000.01, '407639968276', '15956551352'),
('2025-04-17 20:20:51', 'external transfer sent', -1.25, '460226058339', '20409823776'),
('2025-04-16 16:57:15', 'transfer received', 2, '460226058339', '11281869625'),
('2025-04-16 16:57:15', 'transfer sent', -2.06, '478393637340', '15979938610'),
('2025-04-16 16:55:50', 'initial deposit', 25, '460226058339', '11207069918'),
('2025-04-16 16:55:20', 'withdrawal', -3, '478393637340', '11595767937'),
('2025-04-16 16:55:14', 'deposit', 2, '478393637340', '18484608856'),
('2025-04-16 16:55:05', 'deposit', 20, '478393637340', '17611116623'),
('2025-04-16 16:53:36', 'initial deposit', 1, '491543368126', '12416085012'),
('2025-04-16 16:53:24', 'initial deposit', 6000, '461445043778', '17842785101'),
('2025-04-16 16:50:01', 'withdrawal', -20, '478393637340', '11416830449'),
('2025-04-16 16:30:02', 'withdrawal', -5, '478393637340', '10505117847'),
('2025-04-16 16:21:05', 'deposit', 25, '478393637340', '19522667350'),
('2025-04-16 16:01:59', 'initial deposit', 500, '478393637340', '13390916985'),
('2025-04-27 04:10:30', 'initial deposit', 55, '407766904261', '17409968863'),
('2025-04-29 12:11:38', 'deposit', 25, '460226058339', '81048529990'),
('2025-04-29 12:11:53', 'withdrawal', -899000, '460226058339', '55112650706'),
('2025-04-29 12:12:55', 'internal transfer sent', -469900000, '478393637340', '42391969896'),
('2025-04-29 12:12:55', 'internal transfer received', 469900000, '460226058339', '52080346142'),
('2025-04-29 12:13:19', 'internal transfer sent', -8753997533, '478393637340', '13495512347'),
('2025-04-29 12:13:19', 'internal transfer received', 8753997533, '460226058339', '46129821207');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accounts`
--
ALTER TABLE `accounts`
  ADD PRIMARY KEY (`bankAccountNumber`),
  ADD KEY `ownerID` (`ownerID`);

--
-- Indexes for table `customer`
--
ALTER TABLE `customer`
  ADD PRIMARY KEY (`customerID`),
  ADD UNIQUE KEY `cUsername` (`cUsername`),
  ADD UNIQUE KEY `cEmail` (`cEmail`);

--
-- Indexes for table `employee`
--
ALTER TABLE `employee`
  ADD PRIMARY KEY (`employeeID`),
  ADD UNIQUE KEY `eUsername` (`eUsername`),
  ADD UNIQUE KEY `eEmail` (`eEmail`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`transactionID`),
  ADD KEY `bankAccountNumber` (`bankAccountNumber`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
