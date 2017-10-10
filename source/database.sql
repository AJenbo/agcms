SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


--
-- Table structure for table `bind`
--

CREATE TABLE IF NOT EXISTS `bind` (
  `side` smallint(5) unsigned NOT NULL DEFAULT '0',
  `kat` smallint(5) NOT NULL DEFAULT '-1',
  UNIQUE KEY `side` (`side`,`kat`),
  UNIQUE KEY `kat` (`kat`,`side`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email`
--

CREATE TABLE IF NOT EXISTS `email` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `navn` varchar(128) COLLATE utf8_danish_ci NOT NULL DEFAULT '',
  `email` varchar(64) COLLATE utf8_danish_ci NOT NULL,
  `adresse` varchar(128) COLLATE utf8_danish_ci NOT NULL DEFAULT '',
  `land` varchar(64) COLLATE utf8_danish_ci NOT NULL DEFAULT 'Danmark',
  `post` varchar(8) COLLATE utf8_danish_ci NOT NULL DEFAULT '',
  `by` varchar(128) COLLATE utf8_danish_ci NOT NULL DEFAULT '',
  `tlf1` varchar(16) COLLATE utf8_danish_ci NOT NULL DEFAULT '',
  `tlf2` varchar(16) COLLATE utf8_danish_ci NOT NULL DEFAULT '',
  `kartotek` tinyint(1) NOT NULL DEFAULT '0',
  `interests` varchar(256) COLLATE utf8_danish_ci NOT NULL,
  `dato` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `downloaded` tinyint(1) NOT NULL DEFAULT '1',
  `ip` varchar(15) COLLATE utf8_danish_ci NOT NULL DEFAULT '0.0.0.0',
  PRIMARY KEY (`id`),
  KEY `tlf1` (`tlf1`),
  KEY `tlf2` (`tlf2`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci PACK_KEYS=0 ;

-- --------------------------------------------------------

--
-- Table structure for table `emails`
--

CREATE TABLE IF NOT EXISTS `emails` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `subject` varchar(255) COLLATE utf8_danish_ci NOT NULL,
  `from` varchar(255) COLLATE utf8_danish_ci NOT NULL,
  `to` varchar(255) COLLATE utf8_danish_ci NOT NULL,
  `body` text COLLATE utf8_danish_ci NOT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci ;

-- --------------------------------------------------------

--
-- Table structure for table `fakturas`
--

CREATE TABLE IF NOT EXISTS `fakturas` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `status` varchar(8) CHARACTER SET utf8 NOT NULL DEFAULT 'new',
  `quantities` varchar(255) COLLATE utf8_danish_ci NOT NULL,
  `products` text COLLATE utf8_danish_ci NOT NULL,
  `values` varchar(255) COLLATE utf8_danish_ci NOT NULL,
  `discount` decimal(6,2) unsigned NOT NULL DEFAULT '0.00',
  `fragt` decimal(6,2) NOT NULL DEFAULT '0.00',
  `amount` decimal(9,2) NOT NULL DEFAULT '0.00',
  `momssats` varchar(4) CHARACTER SET utf8 NOT NULL DEFAULT '0.25',
  `premoms` tinyint(1) NOT NULL DEFAULT '1',
  `date` date NOT NULL DEFAULT '0000-00-00',
  `paydate` date NOT NULL,
  `transferred` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `cardtype` varchar(64) COLLATE utf8_danish_ci NOT NULL,
  `iref` varchar(32) COLLATE utf8_danish_ci NOT NULL,
  `eref` varchar(64) COLLATE utf8_danish_ci NOT NULL,
  `navn` varchar(64) COLLATE utf8_danish_ci NOT NULL,
  `att` varchar(64) COLLATE utf8_danish_ci NOT NULL,
  `adresse` varchar(64) COLLATE utf8_danish_ci NOT NULL,
  `postbox` varchar(32) COLLATE utf8_danish_ci NOT NULL,
  `postnr` varchar(8) COLLATE utf8_danish_ci NOT NULL,
  `by` varchar(128) COLLATE utf8_danish_ci NOT NULL,
  `land` varchar(64) COLLATE utf8_danish_ci NOT NULL DEFAULT 'DK',
  `email` varchar(64) COLLATE utf8_danish_ci NOT NULL,
  `sendt` tinyint(1) NOT NULL DEFAULT '0',
  `tlf1` varchar(16) COLLATE utf8_danish_ci NOT NULL,
  `tlf2` varchar(16) COLLATE utf8_danish_ci NOT NULL,
  `altpost` tinyint(1) NOT NULL DEFAULT '0',
  `posttlf` varchar(16) COLLATE utf8_danish_ci NOT NULL,
  `postname` varchar(64) COLLATE utf8_danish_ci NOT NULL,
  `postatt` varchar(64) COLLATE utf8_danish_ci NOT NULL,
  `postaddress` varchar(64) COLLATE utf8_danish_ci NOT NULL,
  `postaddress2` varchar(64) COLLATE utf8_danish_ci NOT NULL,
  `postpostbox` varchar(32) COLLATE utf8_danish_ci NOT NULL,
  `postpostalcode` varchar(8) COLLATE utf8_danish_ci NOT NULL,
  `postcity` varchar(128) COLLATE utf8_danish_ci NOT NULL,
  `postcountry` varchar(64) COLLATE utf8_danish_ci NOT NULL DEFAULT 'DK',
  `clerk` varchar(64) COLLATE utf8_danish_ci NOT NULL,
  `department` varchar(256) COLLATE utf8_danish_ci NOT NULL,
  `note` text COLLATE utf8_danish_ci NOT NULL,
  `enote` text COLLATE utf8_danish_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `tlf` (`tlf1`,`tlf2`,`posttlf`),
  KEY `transferred` (`transferred`),
  KEY `Verified` (`status`,`transferred`,`paydate`),
  KEY `date` (`date`),
  KEY `clerk` (`clerk`,`date`),
  KEY `department` (`department`,`date`),
  KEY `status` (`status`,`date`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci ;

-- --------------------------------------------------------

--
-- Table structure for table `files`
--

CREATE TABLE IF NOT EXISTS `files` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `path` varchar(255) COLLATE utf8_danish_ci NOT NULL,
  `mime` varchar(64) COLLATE utf8_danish_ci NOT NULL,
  `alt` varchar(128) COLLATE utf8_danish_ci NOT NULL,
  `width` smallint(5) unsigned NOT NULL DEFAULT '0',
  `height` smallint(5) unsigned NOT NULL DEFAULT '0',
  `size` int(10) unsigned NOT NULL DEFAULT '0',
  `aspect` varchar(4) CHARACTER SET utf8 DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `path_2` (`path`),
  KEY `mime` (`mime`),
  FULLTEXT KEY `alt` (`alt`),
  FULLTEXT KEY `path` (`path`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci ;

-- --------------------------------------------------------

--
-- Table structure for table `kat`
--

CREATE TABLE IF NOT EXISTS `kat` (
  `id` smallint(5) NOT NULL AUTO_INCREMENT,
  `navn` varchar(64) COLLATE utf8_danish_ci NOT NULL DEFAULT '',
  `bind` smallint(5) DEFAULT '-1',
  `icon` varchar(128) COLLATE utf8_danish_ci DEFAULT NULL,
  `vis` tinyint(1) NOT NULL DEFAULT '1',
  `email` varchar(64) COLLATE utf8_danish_ci NOT NULL DEFAULT 'mail',
  `custom_sort_subs` tinyint(1) NOT NULL DEFAULT '0',
  `order` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `access` varchar(64) COLLATE utf8_danish_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `icon` (`icon`),
  KEY `bind` (`bind`,`vis`),
  KEY `order` (`order`),
  FULLTEXT KEY `navn` (`navn`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci ;

-- --------------------------------------------------------

--
-- Table structure for table `krav`
--

CREATE TABLE IF NOT EXISTS `krav` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `navn` varchar(32) COLLATE utf8_danish_ci NOT NULL DEFAULT '',
  `text` text COLLATE utf8_danish_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci ;

-- --------------------------------------------------------

--
-- Table structure for table `lists`
--

CREATE TABLE IF NOT EXISTS `lists` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `page_id` smallint(5) unsigned NOT NULL,
  `title` varchar(63) COLLATE utf8_danish_ci NOT NULL,
  `cells` varchar(511) COLLATE utf8_danish_ci NOT NULL,
  `cell_names` varchar(511) COLLATE utf8_danish_ci NOT NULL,
  `sort` tinyint(3) unsigned NOT NULL,
  `sorts` varchar(63) COLLATE utf8_danish_ci NOT NULL,
  `link` tinyint(1) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `page_id` (`page_id`),
  KEY `link` (`link`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci ;

-- --------------------------------------------------------

--
-- Table structure for table `list_rows`
--

CREATE TABLE IF NOT EXISTS `list_rows` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `list_id` smallint(5) unsigned NOT NULL,
  `cells` varchar(512) COLLATE utf8_danish_ci NOT NULL,
  `link` smallint(5) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `list_id` (`list_id`),
  KEY `link` (`link`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci ;

-- --------------------------------------------------------

--
-- Table structure for table `maerke`
--

CREATE TABLE IF NOT EXISTS `maerke` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `navn` varchar(32) COLLATE utf8_danish_ci NOT NULL DEFAULT '',
  `link` varchar(255) COLLATE utf8_danish_ci NOT NULL DEFAULT '',
  `ico` varchar(128) COLLATE utf8_danish_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ico` (`ico`),
  FULLTEXT KEY `navn` (`navn`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci ;

-- --------------------------------------------------------

--
-- Table structure for table `newsmails`
--

CREATE TABLE IF NOT EXISTS `newsmails` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `from` varchar(128) COLLATE utf8_danish_ci NOT NULL,
  `subject` varchar(256) COLLATE utf8_danish_ci NOT NULL,
  `interests` varchar(256) COLLATE utf8_danish_ci NOT NULL,
  `text` text COLLATE utf8_danish_ci NOT NULL,
  `sendt` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `sendt` (`sendt`,`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci ;

-- --------------------------------------------------------

--
-- Table structure for table `sider`
--

CREATE TABLE IF NOT EXISTS `sider` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `dato` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `navn` varchar(127) COLLATE utf8_danish_ci NOT NULL DEFAULT '',
  `keywords` varchar(255) COLLATE utf8_danish_ci NOT NULL DEFAULT '',
  `pris` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `text` mediumtext COLLATE utf8_danish_ci NOT NULL,
  `varenr` varchar(63) COLLATE utf8_danish_ci NOT NULL DEFAULT '',
  `for` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `beskrivelse` text COLLATE utf8_danish_ci NOT NULL,
  `krav` smallint(5) unsigned DEFAULT NULL,
  `maerke` smallint(5) unsigned DEFAULT NULL,
  `billed` varchar(255) COLLATE utf8_danish_ci DEFAULT NULL,
  `fra` tinyint(1) NOT NULL DEFAULT '0',
  `burde` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `pris` (`pris`),
  KEY `maerke` (`maerke`),
  KEY `varenr` (`varenr`),
  KEY `billed` (`billed`),
  KEY `krav` (`krav`),
  FULLTEXT KEY `navn` (`navn`,`text`,`beskrivelse`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci ;

-- --------------------------------------------------------

--
-- Table structure for table `special`
--

CREATE TABLE IF NOT EXISTS `special` (
  `id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `navn` varchar(127) COLLATE utf8_danish_ci NOT NULL,
  `text` text COLLATE utf8_danish_ci NOT NULL,
  `dato` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci ;

-- --------------------------------------------------------

--
-- Table structure for table `tablesort`
--

CREATE TABLE IF NOT EXISTS `tablesort` (
  `id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `navn` varchar(64) COLLATE utf8_danish_ci NOT NULL DEFAULT '',
  `text` text COLLATE utf8_danish_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci ;

-- --------------------------------------------------------

--
-- Table structure for table `template`
--

CREATE TABLE IF NOT EXISTS `template` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `varenr` varchar(63) COLLATE utf8_danish_ci NOT NULL DEFAULT '',
  `navn` varchar(127) COLLATE utf8_danish_ci NOT NULL DEFAULT '',
  `keywords` varchar(255) COLLATE utf8_danish_ci NOT NULL DEFAULT '',
  `beskrivelse` text COLLATE utf8_danish_ci NOT NULL,
  `text` mediumtext COLLATE utf8_danish_ci NOT NULL,
  `billed` varchar(255) COLLATE utf8_danish_ci DEFAULT NULL,
  `pris` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `burde` tinyint(1) NOT NULL DEFAULT '0',
  `for` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `fra` tinyint(1) NOT NULL DEFAULT '0',
  `maerke` smallint(5) unsigned DEFAULT NULL,
  `krav` smallint(5) unsigned DEFAULT NULL,
  `bind` smallint(5) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `krav` (`krav`),
  KEY `maerke` (`maerke`),
  KEY `billed` (`billed`),
  KEY `bind` (`bind`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci ;

-- --------------------------------------------------------

--
-- Table structure for table `tilbehor`
--

CREATE TABLE IF NOT EXISTS `tilbehor` (
  `side` smallint(5) unsigned NOT NULL DEFAULT '0',
  `tilbehor` smallint(5) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`side`,`tilbehor`),
  UNIQUE KEY `tilbehor` (`tilbehor`,`side`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `fullname` varchar(128) COLLATE utf8_danish_ci NOT NULL,
  `name` varchar(64) COLLATE utf8_danish_ci NOT NULL,
  `password` varchar(64) COLLATE utf8_danish_ci NOT NULL,
  `access` tinyint(1) NOT NULL DEFAULT '0',
  `lastlogin` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `navn` (`name`),
  KEY `access` (`access`),
  KEY `fullname` (`fullname`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bind`
--
ALTER TABLE `bind`
  ADD CONSTRAINT `bind_ibfk_1` FOREIGN KEY (`side`) REFERENCES `sider` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bind_ibfk_2` FOREIGN KEY (`kat`) REFERENCES `kat` (`id`);

--
-- Constraints for table `kat`
--
ALTER TABLE `kat`
  ADD CONSTRAINT `kat_ibfk_1` FOREIGN KEY (`icon`) REFERENCES `files` (`path`) ON UPDATE CASCADE,
  ADD CONSTRAINT `kat_ibfk_2` FOREIGN KEY (`bind`) REFERENCES `kat` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `lists`
--
ALTER TABLE `lists`
  ADD CONSTRAINT `lists_ibfk_1` FOREIGN KEY (`page_id`) REFERENCES `sider` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `list_rows`
--
ALTER TABLE `list_rows`
  ADD CONSTRAINT `list_rows_ibfk_1` FOREIGN KEY (`list_id`) REFERENCES `lists` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `list_rows_ibfk_2` FOREIGN KEY (`link`) REFERENCES `sider` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `maerke`
--
ALTER TABLE `maerke`
  ADD CONSTRAINT `maerke_ibfk_1` FOREIGN KEY (`ico`) REFERENCES `files` (`path`);

--
-- Constraints for table `sider`
--
ALTER TABLE `sider`
  ADD CONSTRAINT `sider_ibfk_1` FOREIGN KEY (`krav`) REFERENCES `krav` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `sider_ibfk_2` FOREIGN KEY (`maerke`) REFERENCES `maerke` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `sider_ibfk_3` FOREIGN KEY (`billed`) REFERENCES `files` (`path`);

--
-- Constraints for table `template`
--
ALTER TABLE `template`
  ADD CONSTRAINT `template_ibfk_1` FOREIGN KEY (`billed`) REFERENCES `files` (`path`),
  ADD CONSTRAINT `template_ibfk_2` FOREIGN KEY (`maerke`) REFERENCES `maerke` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `template_ibfk_3` FOREIGN KEY (`krav`) REFERENCES `krav` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `template_ibfk_4` FOREIGN KEY (`bind`) REFERENCES `bind` (`kat`) ON DELETE SET NULL;

--
-- Constraints for table `tilbehor`
--
ALTER TABLE `tilbehor`
  ADD CONSTRAINT `tilbehor_ibfk_1` FOREIGN KEY (`side`) REFERENCES `sider` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tilbehor_ibfk_2` FOREIGN KEY (`tilbehor`) REFERENCES `sider` (`id`) ON DELETE CASCADE;

