-- --------------------------------------------------------
-- 
-- Base de dades: `bicing`
-- 
-- --------------------------------------------------------

DROP DATABASE IF EXISTS bicing;
CREATE DATABASE bicing DEFAULT CHARACTER SET utf8 COLLATE utf8_spanish_ci;
USE bicing;

-- --------------------------------------------------------
-- 
-- Estructura de la taula `data`
-- 
-- --------------------------------------------------------

DROP TABLE IF EXISTS `data`;
CREATE TABLE IF NOT EXISTS `data` (
  `id` int(8) NOT NULL auto_increment,
  `measure` int(5) NOT NULL,
  `station` int(5) NOT NULL,
  `bycicles` int(5) NOT NULL,
  `freeplaces` int(5) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `STATIONS` (`station`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------
-- 
-- Estructura de la taula `measures`
-- 
-- --------------------------------------------------------

DROP TABLE IF EXISTS `measures`;
CREATE TABLE IF NOT EXISTS `measures` (
  `id` int(5) NOT NULL auto_increment,
  `dt` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------
-- 
-- Estructura de la taula `stations`
-- 
-- --------------------------------------------------------

DROP TABLE IF EXISTS `stations`;
CREATE TABLE IF NOT EXISTS `stations` (
  `id` int(5) NOT NULL auto_increment,
  `hid` varchar(15) collate utf8_spanish_ci NOT NULL,
  `name` varchar(50) collate utf8_spanish_ci NOT NULL,
  `address` varchar(100) collate utf8_spanish_ci default NULL,
  `utmx` int(6) NOT NULL,
  `utmy` int(7) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `hid` (`hid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------
-- 
-- Permissos
-- 
-- --------------------------------------------------------

CREATE USER 'bicing'@'localhost';
SET PASSWORD FOR 'bicing'@'localhost' = PASSWORD('b3c3ng');
GRANT ALL PRIVILEGES ON bicing.* TO 'bicing'@'localhost' WITH GRANT OPTION ;
