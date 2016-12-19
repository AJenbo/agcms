SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- --------------------------------------------------------

--
-- Table structure for table `bind`
--

CREATE TABLE IF NOT EXISTS `bind` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `side` smallint(5) unsigned NOT NULL DEFAULT '0',
  `kat` smallint(6) NOT NULL DEFAULT '-1',
  PRIMARY KEY (`id`),
  KEY `kat` (`kat`),
  KEY `side` (`side`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci ;

-- --------------------------------------------------------

--
-- Table structure for table `email`
--

CREATE TABLE IF NOT EXISTS `email` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `navn` varchar(128) NOT NULL DEFAULT '',
  `email` varchar(64) NOT NULL,
  `adresse` varchar(128) NOT NULL DEFAULT '',
  `land` varchar(64) NOT NULL DEFAULT 'Danmark',
  `post` varchar(8) NOT NULL DEFAULT '',
  `by` varchar(128) NOT NULL DEFAULT '',
  `tlf1` varchar(16) NOT NULL DEFAULT '',
  `tlf2` varchar(16) NOT NULL DEFAULT '',
  `kartotek` tinyint(1) NOT NULL DEFAULT '0',
  `interests` varchar(256) NOT NULL,
  `dato` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `downloaded` tinyint(1) NOT NULL DEFAULT '1',
  `ip` varchar(15) NOT NULL DEFAULT '0.0.0.0',
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
  `subject` varchar(255) NOT NULL,
  `from` varchar(255) NOT NULL,
  `to` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci ;

-- --------------------------------------------------------

--
-- Table structure for table `fakturas`
--

CREATE TABLE IF NOT EXISTS `fakturas` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `status` varchar(8) NOT NULL DEFAULT 'new',
  `transferred` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `quantities` varchar(255) NOT NULL,
  `products` text NOT NULL,
  `values` varchar(255) NOT NULL,
  `discount` decimal(6,2) unsigned NOT NULL DEFAULT '0.00',
  `fragt` decimal(6,2) NOT NULL DEFAULT '0.00',
  `amount` decimal(9,2) NOT NULL DEFAULT '0.00',
  `momssats` varchar(4) NOT NULL DEFAULT '0.25',
  `premoms` tinyint(1) NOT NULL DEFAULT '1',
  `date` date NOT NULL DEFAULT '0000-00-00',
  `paydate` date NOT NULL,
  `cardtype` varchar(64) NOT NULL,
  `iref` varchar(32) NOT NULL,
  `eref` varchar(64) NOT NULL,
  `navn` varchar(64) NOT NULL,
  `att` varchar(64) NOT NULL,
  `adresse` varchar(64) NOT NULL,
  `postbox` varchar(32) NOT NULL,
  `postnr` varchar(8) NOT NULL,
  `by` varchar(128) NOT NULL,
  `land` varchar(64) NOT NULL DEFAULT 'DK',
  `email` varchar(64) NOT NULL,
  `sendt` tinyint(1) NOT NULL DEFAULT '0',
  `tlf1` varchar(16) NOT NULL,
  `tlf2` varchar(16) NOT NULL,
  `altpost` tinyint(1) NOT NULL DEFAULT '0',
  `posttlf` varchar(16) NOT NULL,
  `postname` varchar(64) NOT NULL,
  `postatt` varchar(64) NOT NULL,
  `postaddress` varchar(64) NOT NULL,
  `postaddress2` varchar(64) NOT NULL,
  `postpostbox` varchar(32) NOT NULL,
  `postpostalcode` varchar(8) NOT NULL,
  `postcity` varchar(128) NOT NULL,
  `postcountry` varchar(64) NOT NULL DEFAULT 'DK',
  `clerk` varchar(64) NOT NULL,
  `department` varchar(256) NOT NULL,
  `note` text NOT NULL,
  `enote` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `tlf` (`tlf1`,`tlf2`,`posttlf`),
  KEY `paymethode` (`cardtype`),
  KEY `transferred` (`transferred`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci ;

-- --------------------------------------------------------

--
-- Table structure for table `files`
--

CREATE TABLE IF NOT EXISTS `files` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `path` varchar(255) NOT NULL,
  `mime` varchar(64) NOT NULL,
  `alt` varchar(128) NOT NULL,
  `width` smallint(5) unsigned NOT NULL DEFAULT '0',
  `height` smallint(5) unsigned NOT NULL DEFAULT '0',
  `size` int(10) unsigned NOT NULL DEFAULT '0',
  `aspect` varchar(4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `path_2` (`path`),
  FULLTEXT KEY `alt` (`alt`),
  FULLTEXT KEY `path` (`path`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci ;

-- --------------------------------------------------------

--
-- Table structure for table `kat`
--

CREATE TABLE IF NOT EXISTS `kat` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `navn` varchar(64) NOT NULL DEFAULT '',
  `bind` smallint(6) NOT NULL DEFAULT '-1',
  `icon` varchar(128) NOT NULL DEFAULT '',
  `vis` tinyint(1) NOT NULL DEFAULT '1',
  `email` varchar(64) NOT NULL DEFAULT 'mail',
  `custom_sort_subs` tinyint(1) NOT NULL DEFAULT '0',
  `order` tinyint(4) NOT NULL DEFAULT '0',
  `access` varchar(64) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `bind` (`bind`),
  KEY `navn_2` (`navn`),
  FULLTEXT KEY `navn` (`navn`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci ;

-- --------------------------------------------------------

--
-- Table structure for table `krav`
--

CREATE TABLE IF NOT EXISTS `krav` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `navn` varchar(32) NOT NULL DEFAULT '',
  `text` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci ;

-- --------------------------------------------------------

--
-- Table structure for table `lists`
--

CREATE TABLE IF NOT EXISTS `lists` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `page_id` smallint(5) unsigned NOT NULL,
  `title` varchar(63) NOT NULL,
  `cells` varchar(511) NOT NULL,
  `cell_names` varchar(511) NOT NULL,
  `sort` tinyint(3) unsigned NOT NULL,
  `sorts` varchar(63) NOT NULL,
  `link` tinyint(1) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sort` (`sort`),
  KEY `page_id` (`page_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci ;

-- --------------------------------------------------------

--
-- Table structure for table `list_rows`
--

CREATE TABLE IF NOT EXISTS `list_rows` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `list_id` smallint(5) unsigned NOT NULL,
  `cells` varchar(512) NOT NULL,
  `link` smallint(5) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `list_id` (`list_id`),
  KEY `cells` (`cells`(333))
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci ;

-- --------------------------------------------------------

--
-- Table structure for table `maerke`
--

CREATE TABLE IF NOT EXISTS `maerke` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `navn` varchar(32) NOT NULL DEFAULT '',
  `link` varchar(255) NOT NULL DEFAULT '',
  `ico` varchar(128) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  FULLTEXT KEY `navn` (`navn`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci ;

-- --------------------------------------------------------

--
-- Table structure for table `newsmails`
--

CREATE TABLE IF NOT EXISTS `newsmails` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `from` varchar(128) NOT NULL,
  `subject` varchar(256) NOT NULL,
  `interests` varchar(256) NOT NULL,
  `text` text NOT NULL,
  `sendt` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci ;

-- --------------------------------------------------------

--
-- Table structure for table `sider`
--

CREATE TABLE IF NOT EXISTS `sider` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `dato` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `navn` varchar(127) NOT NULL DEFAULT '',
  `keywords` varchar(255) NOT NULL DEFAULT '',
  `pris` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `text` mediumtext NOT NULL,
  `varenr` varchar(63) NOT NULL DEFAULT '',
  `for` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `beskrivelse` text NOT NULL,
  `krav` smallint(5) unsigned NOT NULL DEFAULT '0',
  `maerke` smallint(5) unsigned NOT NULL DEFAULT '0',
  `billed` varchar(255) NOT NULL DEFAULT '',
  `fra` tinyint(1) NOT NULL DEFAULT '0',
  `burde` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `pris` (`pris`),
  KEY `maerke` (`maerke`),
  KEY `billed` (`billed`),
  KEY `varenr` (`varenr`),
  FULLTEXT KEY `navn` (`navn`,`text`,`beskrivelse`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci ;

-- --------------------------------------------------------

--
-- Table structure for table `special`
--

CREATE TABLE IF NOT EXISTS `special` (
  `id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `navn` varchar(127) NOT NULL,
  `text` text NOT NULL,
  `dato` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci ;

INSERT INTO `special` (`id`, `navn`, `dato`) VALUES
(0, 'Cron status', NOW()),
(1, 'Forsiden', NOW()),
(3, 'Handelsbetingelser', NOW());

-- --------------------------------------------------------

--
-- Table structure for table `tablesort`
--

CREATE TABLE IF NOT EXISTS `tablesort` (
  `id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `navn` varchar(64) NOT NULL DEFAULT '',
  `text` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci ;

-- --------------------------------------------------------

--
-- Table structure for table `template`
--

CREATE TABLE IF NOT EXISTS `template` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `varenr` varchar(63) NOT NULL DEFAULT '',
  `navn` varchar(127) NOT NULL DEFAULT '',
  `keywords` varchar(255) NOT NULL DEFAULT '',
  `beskrivelse` text NOT NULL,
  `text` mediumtext NOT NULL,
  `billed` varchar(255) NOT NULL DEFAULT '',
  `pris` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `burde` tinyint(1) NOT NULL DEFAULT '0',
  `for` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `fra` tinyint(1) NOT NULL DEFAULT '0',
  `maerke` smallint(5) unsigned NOT NULL DEFAULT '0',
  `krav` smallint(5) unsigned NOT NULL DEFAULT '0',
  `bind` smallint(6) NOT NULL DEFAULT '-1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci ;

-- --------------------------------------------------------

--
-- Table structure for table `tilbehor`
--

CREATE TABLE IF NOT EXISTS `tilbehor` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `side` smallint(5) unsigned NOT NULL DEFAULT '0',
  `tilbehor` smallint(5) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `side` (`side`),
  KEY `tilbehor` (`tilbehor`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci ;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `fullname` varchar(128) NOT NULL,
  `name` varchar(64) NOT NULL,
  `password` varchar(128) NOT NULL,
  `access` tinyint(1) NOT NULL DEFAULT '0',
  `lastlogin` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `kode` (`password`),
  KEY `adgang` (`access`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci ;

-- --------------------------------------------------------

--
-- Table structure for table `PNL`
--

CREATE TABLE IF NOT EXISTS `PNL` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `fakturaid` smallint(5) unsigned NOT NULL,
  `bookingDate` date NOT NULL,
  `bookingTime` varchar(8) NOT NULL,
  `shipmentId` varchar(17) NOT NULL,
  `packageId` varchar(13) NOT NULL,
  `labelType` varchar(3) NOT NULL,
  `sender` varchar(2) NOT NULL,
  `name` varchar(64) NOT NULL,
  `att` varchar(64) NOT NULL,
  `address` varchar(64) NOT NULL,
  `address2` varchar(64) NOT NULL,
  `postcode` varchar(16) NOT NULL,
  `city` varchar(64) NOT NULL,
  `country` varchar(2) NOT NULL,
  `product` tinyint(3) unsigned NOT NULL,
  `contens` varchar(3) NOT NULL,
  `text` varchar(256) NOT NULL,
  `kg` tinyint(3) unsigned NOT NULL,
  `w` tinyint(3) unsigned NOT NULL,
  `h` tinyint(3) unsigned NOT NULL,
  `l` tinyint(3) unsigned NOT NULL,
  `return` smallint(1) unsigned NOT NULL,
  `ref` varchar(128) NOT NULL,
  `insurance` int(10) unsigned NOT NULL,
  `arrived` tinyint(1) NOT NULL DEFAULT '0',
  `inmotion` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci ;

-- --------------------------------------------------------

--
-- Table structure for table `post`
--

CREATE TABLE IF NOT EXISTS `post` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `fakturaid` smallint(5) unsigned NOT NULL,
  `formSenderID` smallint(5) NOT NULL DEFAULT '11856',
  `recName1` varchar(34) NOT NULL,
  `recAddress1` varchar(34) NOT NULL,
  `recZipCode` smallint(4) unsigned NOT NULL,
  `recPoValue` smallint(5) unsigned NOT NULL DEFAULT '0',
  `recipientID` varchar(10) NOT NULL,
  `formDate` date NOT NULL,
  `optRecipType` varchar(1) NOT NULL DEFAULT 'P',
  `weight` float unsigned NOT NULL DEFAULT '0',
  `ss1` tinyint(1) NOT NULL DEFAULT '0',
  `ss2` tinyint(1) NOT NULL DEFAULT '0',
  `ss46` tinyint(1) NOT NULL DEFAULT '0',
  `ss5amount` smallint(5) unsigned NOT NULL DEFAULT '0',
  `height` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `width` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `length` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `porto` decimal(6,2) unsigned NOT NULL DEFAULT '0.00',
  `token` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `STREGKODE` varchar(13) DEFAULT NULL,
  `pd_weight` float unsigned NOT NULL DEFAULT '0',
  `pd_length` float unsigned NOT NULL DEFAULT '0',
  `pd_height` float unsigned NOT NULL DEFAULT '0',
  `pd_width` float unsigned NOT NULL DEFAULT '0',
  `pd_return` tinyint(1) NOT NULL DEFAULT '0',
  `pd_arrived` tinyint(1) NOT NULL DEFAULT '0',
  `reklmation` tinyint(1) NOT NULL DEFAULT '0',
  `ub` tinyint(1) NOT NULL DEFAULT '1',
  `deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `STREGKODE` (`STREGKODE`),
  KEY `formSenderID` (`formSenderID`),
  KEY `formDate` (`formDate`),
  KEY `recipientID` (`recipientID`),
  KEY `recName1` (`recName1`),
  KEY `optRecipType` (`optRecipType`),
  KEY `pd_return` (`pd_return`),
  KEY `pd_arrived` (`pd_arrived`),
  KEY `deleted` (`deleted`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci ;

