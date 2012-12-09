SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

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
  `navn` varchar(128) COLLATE utf8_danish_ci NOT NULL,
  `email` varchar(64) COLLATE utf8_danish_ci NOT NULL,
  `adresse` tinytext COLLATE utf8_danish_ci NOT NULL,
  `land` varchar(64) COLLATE utf8_danish_ci NOT NULL DEFAULT 'Danmark',
  `post` varchar(8) COLLATE utf8_danish_ci NOT NULL,
  `by` varchar(128) COLLATE utf8_danish_ci NOT NULL,
  `tlf1` varchar(16) COLLATE utf8_danish_ci NOT NULL,
  `tlf2` varchar(16) COLLATE utf8_danish_ci NOT NULL,
  `kartotek` enum('0','1') COLLATE utf8_danish_ci NOT NULL DEFAULT '0',
  `interests` varchar(256) COLLATE utf8_danish_ci NOT NULL,
  `dato` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `downloaded` enum('0','1') COLLATE utf8_danish_ci NOT NULL DEFAULT '1',
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
  `status` enum('new','locked','pbsok','accepted','giro','cash','pbserror','canceled','rejected') COLLATE utf8_danish_ci NOT NULL DEFAULT 'new',
  `transferred` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `quantities` varchar(64) COLLATE utf8_danish_ci NOT NULL,
  `products` text COLLATE utf8_danish_ci NOT NULL,
  `values` varchar(128) COLLATE utf8_danish_ci NOT NULL,
  `discount` decimal(6,2) unsigned NOT NULL DEFAULT '0.00',
  `fragt` decimal(6,2) NOT NULL DEFAULT '0.00',
  `amount` decimal(9,2) NOT NULL DEFAULT '0.00',
  `momssats` enum('0.25','0') COLLATE utf8_danish_ci NOT NULL DEFAULT '0.25',
  `premoms` tinyint(1) NOT NULL DEFAULT '1',
  `date` date NOT NULL DEFAULT '0000-00-00',
  `paydate` date NOT NULL,
  `cardtype` varchar(20) COLLATE utf8_danish_ci NOT NULL,
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
  KEY `cardtype` (`cardtype`),
  KEY `transfered` (`transferred`)
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
  `width` smallint(3) unsigned NOT NULL DEFAULT '0',
  `height` smallint(4) unsigned NOT NULL DEFAULT '0',
  `size` int(10) unsigned NOT NULL DEFAULT '0',
  `aspect` enum('4-3','16-9') COLLATE utf8_danish_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`path`),
  FULLTEXT KEY `alt` (`alt`),
  FULLTEXT KEY `path` (`path`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci ;

-- --------------------------------------------------------

--
-- Table structure for table `kat`
--

CREATE TABLE IF NOT EXISTS `kat` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `navn` varchar(64) COLLATE utf8_danish_ci NOT NULL,
  `bind` smallint(6) NOT NULL DEFAULT '-1',
  `icon` varchar(128) COLLATE utf8_danish_ci NOT NULL,
  `vis` enum('0','1','2') COLLATE utf8_danish_ci NOT NULL DEFAULT '1',
  `email` varchar(64) COLLATE utf8_danish_ci NOT NULL DEFAULT 'mail',
  `custom_sort_subs` enum('0','1') COLLATE utf8_danish_ci NOT NULL DEFAULT '0',
  `order` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `access` varchar(64) COLLATE utf8_danish_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `bind` (`bind`),
  FULLTEXT KEY `navn` (`navn`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci ;

-- --------------------------------------------------------

--
-- Table structure for table `krav`
--

CREATE TABLE IF NOT EXISTS `krav` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `navn` varchar(32) COLLATE utf8_danish_ci NOT NULL,
  `text` text COLLATE utf8_danish_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci ;

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
  `link` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `page_id` (`page_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci ;

-- --------------------------------------------------------

--
-- Table structure for table `list_rows`
--

CREATE TABLE IF NOT EXISTS `list_rows` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `list_id` smallint(5) unsigned NOT NULL,
  `cells` varchar(512) COLLATE utf8_danish_ci NOT NULL,
  `link` smallint(5) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `cells` (`cells`(333))
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci ;

-- --------------------------------------------------------

--
-- Table structure for table `maerke`
--

CREATE TABLE IF NOT EXISTS `maerke` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `navn` varchar(32) COLLATE utf8_danish_ci NOT NULL,
  `link` varchar(255) COLLATE utf8_danish_ci NOT NULL,
  `ico` varchar(128) COLLATE utf8_danish_ci NOT NULL,
  PRIMARY KEY (`id`),
  FULLTEXT KEY `navn` (`navn`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci ;

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
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci ;

-- --------------------------------------------------------

--
-- Table structure for table `sider`
--

CREATE TABLE IF NOT EXISTS `sider` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `dato` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `navn` varchar(127) COLLATE utf8_danish_ci NOT NULL,
  `keywords` varchar(255) COLLATE utf8_danish_ci NOT NULL,
  `pris` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `text` mediumtext COLLATE utf8_danish_ci NOT NULL,
  `varenr` varchar(63) COLLATE utf8_danish_ci NOT NULL,
  `for` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `beskrivelse` text COLLATE utf8_danish_ci NOT NULL,
  `krav` smallint(5) unsigned NOT NULL DEFAULT '0',
  `maerke` varchar(127) COLLATE utf8_danish_ci NOT NULL,
  `billed` varchar(127) COLLATE utf8_danish_ci NOT NULL,
  `fra` tinyint(1) NOT NULL DEFAULT '0',
  `burde` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `pris` (`pris`),
  KEY `varenr` (`varenr`),
  FULLTEXT KEY `navn` (`navn`,`text`,`beskrivelse`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci ;

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

INSERT INTO `special` (`id`, `navn`) VALUES
(0, 'Cron status'),
(1, 'Forsiden'),
(3, 'Handelsbetingelser');

-- --------------------------------------------------------

--
-- Table structure for table `tablesort`
--

CREATE TABLE IF NOT EXISTS `tablesort` (
  `id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `navn` varchar(64) COLLATE utf8_danish_ci NOT NULL,
  `text` text COLLATE utf8_danish_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci ;

-- --------------------------------------------------------

--
-- Table structure for table `template`
--

CREATE TABLE IF NOT EXISTS `template` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `varenr` varchar(63) COLLATE utf8_danish_ci NOT NULL,
  `navn` varchar(127) COLLATE utf8_danish_ci NOT NULL,
  `keywords` varchar(255) COLLATE utf8_danish_ci NOT NULL,
  `beskrivelse` text COLLATE utf8_danish_ci NOT NULL,
  `text` mediumtext COLLATE utf8_danish_ci NOT NULL,
  `billed` varchar(255) COLLATE utf8_danish_ci NOT NULL,
  `pris` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `burde` enum('0','1') COLLATE utf8_danish_ci NOT NULL DEFAULT '0',
  `for` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `fra` enum('0','1') COLLATE utf8_danish_ci NOT NULL DEFAULT '0',
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
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci ;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `fullname` varchar(128) COLLATE utf8_danish_ci NOT NULL,
  `name` varchar(64) COLLATE utf8_danish_ci NOT NULL,
  `password` varchar(64) COLLATE utf8_danish_ci NOT NULL,
  `access` enum('0','1','2','3','4') COLLATE utf8_danish_ci NOT NULL DEFAULT '0',
  `lastlogin` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `kode` (`password`),
  KEY `adgang` (`access`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci ;

-- --------------------------------------------------------

