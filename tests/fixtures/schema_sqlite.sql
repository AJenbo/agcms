-- bind
CREATE TABLE IF NOT EXISTS `bind` (
  `side` smallint(5) NOT NULL DEFAULT '0',
  `kat` smallint(5) NOT NULL DEFAULT '-1',
  PRIMARY KEY (`side`,`kat`),
  UNIQUE (`kat`,`side`),
  FOREIGN KEY (`side`) REFERENCES `sider` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`kat`) REFERENCES `kat` (`id`)
);

-- email
CREATE TABLE IF NOT EXISTS `email` (
  `id` INTEGER NOT NULL,
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
  PRIMARY KEY (`id`)
);
CREATE INDEX tlf1 ON email (tlf1);
CREATE INDEX tlf2 ON email (tlf2);

-- emails
CREATE TABLE IF NOT EXISTS `emails` (
  `id` INTEGER NOT NULL,
  `subject` varchar(255) NOT NULL,
  `from` varchar(255) NOT NULL,
  `to` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`)
);

-- fakturas
CREATE TABLE IF NOT EXISTS `fakturas` (
  `id` INTEGER NOT NULL,
  `payment_id` int(11) DEFAULT NULL,
  `status` varchar(8) NOT NULL DEFAULT 'new',
  `quantities` varchar(255) NOT NULL,
  `products` text NOT NULL,
  `values` varchar(255) NOT NULL,
  `discount` decimal(6,2) NOT NULL DEFAULT '0.00',
  `fragt` decimal(6,2) NOT NULL DEFAULT '0.00',
  `amount` decimal(9,2) NOT NULL DEFAULT '0.00',
  `momssats` varchar(4) NOT NULL DEFAULT '0.25',
  `premoms` tinyint(1) NOT NULL DEFAULT '1',
  `date` date NOT NULL DEFAULT '0000-00-00',
  `paydate` date NOT NULL,
  `transferred` tinyint(1) NOT NULL DEFAULT '0',
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
  PRIMARY KEY (`id`)
);
CREATE INDEX `tlf` ON fakturas (`tlf1`,`tlf2`,`posttlf`);
CREATE INDEX `transferred` ON fakturas (`transferred`);
CREATE INDEX `Verified` ON fakturas (`status`,`transferred`,`paydate`);
CREATE INDEX `date` ON fakturas (`date`);
CREATE INDEX `clerk` ON fakturas (`clerk`,`date`);
CREATE INDEX `department` ON fakturas (`department`,`date`);
CREATE INDEX `status` ON fakturas (`status`,`date`);

-- files
CREATE TABLE IF NOT EXISTS `files` (
  `id`INTEGER NOT NULL,
  `path` varchar(255) NOT NULL,
  `mime` varchar(64) NOT NULL,
  `alt` varchar(128) NOT NULL,
  `width` smallint(5) NOT NULL DEFAULT '0',
  `height` smallint(5) NOT NULL DEFAULT '0',
  `size` int(10) NOT NULL DEFAULT '0',
  `aspect` varchar(4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE (`path`)
);
CREATE INDEX `mime` ON files (`mime`);

-- kat
CREATE TABLE IF NOT EXISTS `kat` (
  `id` INTEGER NOT NULL,
  `navn` varchar(64) NOT NULL DEFAULT '',
  `bind` smallint(5) DEFAULT '-1',
  `icon_id` int(10) DEFAULT NULL,
  `vis` tinyint(1) NOT NULL DEFAULT '1',
  `email` varchar(64) NOT NULL DEFAULT 'mail',
  `custom_sort_subs` tinyint(1) NOT NULL DEFAULT '0',
  `order` tinyint(3) NOT NULL DEFAULT '0',
  `access` varchar(64) NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`bind`) REFERENCES `kat` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`icon_id`) REFERENCES `files` (`id`)
);
CREATE INDEX `katbind` ON kat (`bind`,`vis`);
CREATE INDEX `order` ON kat (`order`);
CREATE INDEX `icon_id` ON kat (`icon_id`);

-- krav
CREATE TABLE IF NOT EXISTS `krav` (
  `id` INTEGER NOT NULL,
  `navn` varchar(32) NOT NULL DEFAULT '',
  `text` text NOT NULL,
  PRIMARY KEY (`id`)
);

-- lists
CREATE TABLE IF NOT EXISTS `lists` (
  `id` INTEGER NOT NULL,
  `page_id` smallint(5) NOT NULL,
  `title` varchar(63) NOT NULL,
  `cells` varchar(511) NOT NULL,
  `cell_names` varchar(511) NOT NULL,
  `sort` tinyint(3) NOT NULL,
  `sorts` varchar(63) NOT NULL,
  `link` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`page_id`) REFERENCES `sider` (`id`) ON DELETE CASCADE
);
CREATE INDEX `page_id` ON lists (`page_id`);
CREATE INDEX `listslink` ON lists (`link`);

-- list_rows
CREATE TABLE IF NOT EXISTS `list_rows` (
  `id` INTEGER NOT NULL,
  `list_id` smallint(5) NOT NULL,
  `cells` varchar(512) NOT NULL,
  `link` smallint(5) DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`list_id`) REFERENCES `lists` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`link`) REFERENCES `sider` (`id`) ON DELETE CASCADE
);
CREATE INDEX `list_id` ON list_rows (`list_id`);
CREATE INDEX `link` ON list_rows (`link`);

-- maerke
CREATE TABLE IF NOT EXISTS `maerke` (
  `id` INTEGER NOT NULL,
  `navn` varchar(32) NOT NULL DEFAULT '',
  `link` varchar(255) NOT NULL DEFAULT '',
  `icon_id` int(10) DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`icon_id`) REFERENCES `files` (`id`)
);
CREATE INDEX `maerkeicon_id` ON maerke (`icon_id`);

-- newsmails
CREATE TABLE IF NOT EXISTS `newsmails` (
  `id` INTEGER NOT NULL,
  `from` varchar(128) NOT NULL,
  `subject` varchar(256) NOT NULL,
  `interests` varchar(256) NOT NULL,
  `text` text NOT NULL,
  `sendt` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE (`sendt`,`id`)
);

-- post
CREATE TABLE IF NOT EXISTS `post` (
  `id` INTEGER NOT NULL,
  `recipientID` varchar(64) NOT NULL,
  `recName1` varchar(64) NOT NULL,
  `recAddress1` varchar(64) NOT NULL,
  `recZipCode` varchar(64) NOT NULL,
  PRIMARY KEY (`id`)
);
CREATE INDEX `recipientID` ON post (`recipientID`);

-- sider
CREATE TABLE IF NOT EXISTS `sider` (
  `id` INTEGER NOT NULL,
  `dato` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `navn` varchar(127) NOT NULL DEFAULT '',
  `keywords` varchar(255) NOT NULL DEFAULT '',
  `pris` mediumint(8) NOT NULL DEFAULT '0',
  `text` mediumtext NOT NULL,
  `varenr` varchar(63) NOT NULL DEFAULT '',
  `for` mediumint(8) NOT NULL DEFAULT '0',
  `beskrivelse` text NOT NULL,
  `krav` smallint(5) DEFAULT NULL,
  `maerke` smallint(5) DEFAULT NULL,
  `icon_id` int(10) DEFAULT NULL,
  `fra` tinyint(1) NOT NULL DEFAULT '0',
  `burde` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  FOREIGN KEY (`krav`) REFERENCES `krav` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`icon_id`) REFERENCES `files` (`id`),
  FOREIGN KEY (`maerke`) REFERENCES `maerke` (`id`)
);
CREATE INDEX `pris` ON sider (`pris`);
CREATE INDEX `sidermaerke` ON sider (`maerke`);
CREATE INDEX `varenr` ON sider (`varenr`);
CREATE INDEX `siderkrav` ON sider (`krav`);
CREATE INDEX `sidericon_id` ON sider (`icon_id`);

-- special
CREATE TABLE IF NOT EXISTS `special` (
  `id` INTEGER NOT NULL,
  `navn` varchar(127) NOT NULL,
  `text` text NOT NULL,
  `dato` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
);

-- tablesort
CREATE TABLE IF NOT EXISTS `tablesort` (
  `id` INTEGER NOT NULL,
  `navn` varchar(64) NOT NULL DEFAULT '',
  `text` text NOT NULL,
  PRIMARY KEY (`id`)
);

-- template
CREATE TABLE IF NOT EXISTS `template` (
  `id` INTEGER NOT NULL,
  `varenr` varchar(63) NOT NULL DEFAULT '',
  `navn` varchar(127) NOT NULL DEFAULT '',
  `keywords` varchar(255) NOT NULL DEFAULT '',
  `beskrivelse` text NOT NULL,
  `text` mediumtext NOT NULL,
  `icon_id` int(10) DEFAULT NULL,
  `pris` mediumint(8) NOT NULL DEFAULT '0',
  `burde` tinyint(1) NOT NULL DEFAULT '0',
  `for` mediumint(8) NOT NULL DEFAULT '0',
  `fra` tinyint(1) NOT NULL DEFAULT '0',
  `maerke` smallint(5) DEFAULT NULL,
  `krav` smallint(5) DEFAULT NULL,
  `bind` smallint(5) DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`maerke`) REFERENCES `maerke` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`bind`) REFERENCES `kat` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`icon_id`) REFERENCES `files` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`krav`) REFERENCES `krav` (`id`) ON DELETE SET NULL
);
CREATE INDEX `templatekrav` ON template (`krav`);
CREATE INDEX `templatemaerke` ON template (`maerke`);
CREATE INDEX `templatebind` ON template (`bind`);
CREATE INDEX `templateicon_id` ON template (`icon_id`);

-- tilbehor
CREATE TABLE IF NOT EXISTS `tilbehor` (
  `side` smallint(5) NOT NULL DEFAULT '0',
  `tilbehor` smallint(5) NOT NULL DEFAULT '0',
  PRIMARY KEY (`side`,`tilbehor`),
  UNIQUE (`tilbehor`,`side`),
  FOREIGN KEY (`side`) REFERENCES `sider` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`tilbehor`) REFERENCES `sider` (`id`) ON DELETE CASCADE
);

-- users
CREATE TABLE IF NOT EXISTS `users` (
  `id` INTEGER NOT NULL,
  `fullname` varchar(128) NOT NULL,
  `name` varchar(64) NOT NULL,
  `password` varchar(64) NOT NULL,
  `access` tinyint(1) NOT NULL DEFAULT '0',
  `lastlogin` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE (`name`)
);
CREATE INDEX `access` ON users (`access`);
CREATE INDEX `fullname` ON users (`fullname`);
