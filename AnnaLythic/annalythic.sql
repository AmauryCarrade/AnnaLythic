-- phpMyAdmin SQL Dump
-- version 3.4.3.2
-- http://www.phpmyadmin.net
--
-- Client: 127.0.0.1
-- Généré le : Lun 09 Juillet 2012 à 16:14
-- Version du serveur: 5.5.15
-- Version de PHP: 5.3.8

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
-- Structure de la table `al_navigation`
--

CREATE TABLE IF NOT EXISTS `al_navigation` (
  `session_id` varchar(50) NOT NULL COMMENT 'PHPSESSID',
  `datetime` datetime NOT NULL,
  `page` varchar(800) NOT NULL,
  UNIQUE KEY `index_unique_session_date` (`session_id`,`datetime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `al_navigators`
--

CREATE TABLE IF NOT EXISTS `al_navigators` (
  `navigator_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `navigator_name` varchar(255) CHARACTER SET utf8 NOT NULL,
  `navigator_version` varchar(20) CHARACTER SET utf8 NOT NULL,
  `navigator_type` enum('user','bot') CHARACTER SET utf8 NOT NULL DEFAULT 'user',
  PRIMARY KEY (`navigator_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;

-- --------------------------------------------------------

--
-- Structure de la table `al_os`
--

CREATE TABLE IF NOT EXISTS `al_os` (
  `os_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `os_name` varchar(255) NOT NULL,
  `os_version` varchar(20) NOT NULL,
  PRIMARY KEY (`os_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;

-- --------------------------------------------------------

--
-- Structure de la table `al_places`
--

CREATE TABLE IF NOT EXISTS `al_places` (
  `place_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `place_country` varchar(255) NOT NULL,
  `place_city` varchar(800) NOT NULL,
  PRIMARY KEY (`place_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `al_plugins`
--

CREATE TABLE IF NOT EXISTS `al_plugins` (
  `session_id` varchar(50) NOT NULL,
  `plugin_name` varchar(255) NOT NULL,
  `plugin_enabled` enum('0','1') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `al_sessions`
--

CREATE TABLE IF NOT EXISTS `al_sessions` (
  `session_id` varchar(50) NOT NULL COMMENT 'PHPSESSID',
  `session_start` datetime NOT NULL,
  `session_finish` datetime NOT NULL,
  `ip` varchar(255) NOT NULL,
  `navigator` bigint(20) NOT NULL,
  `os` bigint(20) NOT NULL,
  `geo_lat` float DEFAULT NULL,
  `geo_long` float DEFAULT NULL,
  `geo_place` bigint(20) NOT NULL,
  `screen_definition` varchar(12) NOT NULL,
  `screen_color_depth` int(4) NOT NULL,
  `screen_font_smoothing` enum('0','1') NOT NULL,
  `cookies` enum('0','1') NOT NULL DEFAULT '1',
  `source` varchar(800) NOT NULL,
  `source_type` enum('direct','website','search') NOT NULL,
  `source_search_engine` varchar(255) DEFAULT NULL,
  `source_keywords` text,
  PRIMARY KEY (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
