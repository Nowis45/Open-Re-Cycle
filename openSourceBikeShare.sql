-- phpMyAdmin SQL Dump
-- version 4.1.14.8
-- http://www.phpmyadmin.net
--
-- Host: db744024984.db.1and1.com
-- Generation Time: Oct 01, 2018 at 02:38 PM
-- Server version: 5.5.60-0+deb7u1-log
-- PHP Version: 5.4.45-0+deb7u14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `yourdatabasename`
--

-- --------------------------------------------------------

--
-- Table structure for table `bikes`
--

CREATE TABLE IF NOT EXISTS `bikes` (
  `bikeNum` int(11) NOT NULL,
  `currentUser` int(11) DEFAULT NULL,
  `currentStand` int(11) DEFAULT NULL,
  `currentCode` int(11) NOT NULL,
  `note` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`bikeNum`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `coupons`
--

CREATE TABLE IF NOT EXISTS `coupons` (
  `coupon` varchar(6) NOT NULL,
  `value` float(5,2) DEFAULT NULL,
  `status` int(11) DEFAULT '0',
  UNIQUE KEY `coupon` (`coupon`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `credit`
--

CREATE TABLE IF NOT EXISTS `credit` (
  `userId` int(11) NOT NULL,
  `credit` float(5,2) DEFAULT NULL,
  PRIMARY KEY (`userId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `geolocation`
--

CREATE TABLE IF NOT EXISTS `geolocation` (
  `userId` int(10) unsigned NOT NULL,
  `longitude` double(20,17) NOT NULL,
  `latitude` double(20,17) NOT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `history`
--

CREATE TABLE IF NOT EXISTS `history` (
  `userId` int(11) NOT NULL,
  `bikeNum` int(11) NOT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `action` varchar(20) NOT NULL,
  `parameter` text NOT NULL,
  `standId` int(11) DEFAULT NULL,
  `pairAction` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `limits`
--

CREATE TABLE IF NOT EXISTS `limits` (
  `userId` int(11) NOT NULL AUTO_INCREMENT,
  `userLimit` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`userId`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=204 ;

INSERT INTO `limits`(`userId`, `userLimit`) VALUES (1,10);
--
-- Table structure for table `notes`
--

CREATE TABLE IF NOT EXISTS `notes` (
  `bikeNum` int(11) NOT NULL,
  `userId` int(11) NOT NULL,
  `note` varchar(100) DEFAULT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `received`
--

CREATE TABLE IF NOT EXISTS `received` (
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sms_uuid` varchar(60) NOT NULL,
  `sender` varchar(20) NOT NULL,
  `receive_time` varchar(20) NOT NULL,
  `sms_text` varchar(200) NOT NULL,
  `IP` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `registration`
--

CREATE TABLE IF NOT EXISTS `registration` (
  `userId` int(11) NOT NULL,
  `userKey` varchar(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `sent`
--

CREATE TABLE IF NOT EXISTS `sent` (
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `number` varchar(20) NOT NULL,
  `text` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `sessions`
--

CREATE TABLE IF NOT EXISTS `sessions` (
  `userId` int(10) unsigned NOT NULL,
  `sessionId` varchar(256) CHARACTER SET latin1 NOT NULL,
  `timeStamp` varchar(256) CHARACTER SET latin1 NOT NULL,
  UNIQUE KEY `userId` (`userId`),
  KEY `sessionId` (`sessionId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `signalement`
--

CREATE TABLE IF NOT EXISTS `signalement` (
  `userId` int(11) NOT NULL,
  `standId` int(11) NOT NULL,
  `note` varchar(100) CHARACTER SET utf8 NOT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted` date DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `stands`
--

CREATE TABLE IF NOT EXISTS `stands` (
  `standId` int(11) NOT NULL AUTO_INCREMENT,
  `standName` varchar(50) NOT NULL,
  `standDescription` varchar(255) DEFAULT NULL,
  `standPhoto` varchar(255) DEFAULT NULL,
  `serviceTag` int(10) DEFAULT NULL,
  `placeName` varchar(50) NOT NULL,
  `longitude` double(20,17) DEFAULT NULL,
  `latitude` double(20,17) DEFAULT NULL,
  PRIMARY KEY (`standId`),
  UNIQUE KEY `standName` (`standName`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=7 ;

--
-- Table structure for table `touristes`
--

CREATE TABLE IF NOT EXISTS `touristes` (
  `touristeId` int(11) NOT NULL AUTO_INCREMENT,
  `number` varchar(30) NOT NULL,
  `nation` text NOT NULL,
  `nbBikes` int(11) NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`touristeId`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `userId` int(11) NOT NULL AUTO_INCREMENT,
  `userName` varchar(50) NOT NULL,
  `password` text NOT NULL,
  `mail` varchar(30) NOT NULL,
  `number` varchar(30) NOT NULL,
  `privileges` int(11) NOT NULL DEFAULT '0',
  `PI` text NOT NULL,
  `note` text NOT NULL,
  `recommendations` text NOT NULL,
  PRIMARY KEY (`userId`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=204 ;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`userId`, `userName`, `password`, `mail`, `number`, `privileges`, `PI`, `note`, `recommendations`) VALUES
(1, 'Admin', 'EE26B0DD4AF7E749AA1A8EE3C10AE9923F618980772E473F8819A5D4940E0DB27AC185F8A0E1D5F84F88BC887FD67B143732C304CC5FA9AD8E6F57F50028A8FF', 'example@gmail.com', '33622222222', 7, '', '', '');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
