-- phpMyAdmin SQL Dump
-- version 3.4.3.2
-- http://www.phpmyadmin.net
--
-- Client: 127.0.0.1
-- Generation date: Mar 03 Juillet 2012 à 15:52
-- Server version: 5.5.15
-- PHP version: 5.3.8

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Base de données: `annalythic`
--

-- --------------------------------------------------------

--
-- Structure de la table `al_sessions`
--

CREATE TABLE IF NOT EXISTS `al_sessions` (
  `session_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `ip` int(11) NOT NULL,
  `history` longtext NOT NULL,
  `pagesCount` int(11) NOT NULL,
  `duration` int(11) NOT NULL,
  PRIMARY KEY (`session_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `al_visits`
--

CREATE TABLE IF NOT EXISTS `al_visits` (
  `visit_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `ip` int(11) NOT NULL,
  `url` text NOT NULL,
  `datetime` datetime NOT NULL,
  `records` longtext NOT NULL,
  PRIMARY KEY (`visit_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
