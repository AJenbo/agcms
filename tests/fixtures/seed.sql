INSERT INTO `kat` (`id`, `navn`, `bind`, `vis`, `email`, `access`) VALUES
(-1, 'Trash', NULL, 0, 'mail@example.com', ''),
(0, 'Frontpage', NULL, 1, 'mail@example.com', ''),
(1, 'Gallery Category', 0, 1, 'mail@example.com', ''),
(2, 'List Category', 0, 2, 'mail@example.com', ''),
(3, 'Empty Category', 0, 1, 'mail@example.com', ''),
(4, 'Inactive Category', -1, 1, 'mail@example.com', ''),
(5, 'Hidden Category', 0, 1, 'mail@example.com', ''),
(6, 'Indexed Category', 0, 1, 'mail@example.com', '');

INSERT INTO `special` (`id`, `navn`, `dato`, `text`) VALUES
(1, 'Frontpage', 'now', ''),
(3, 'Terms & Conditions', 'now', ''),
(0, 'Cron', 'now', '');

-- password is 123456
INSERT INTO `users` (`fullname`, `name`, `password`, `access`, `lastlogin`) VALUES
('test', 'test', '$2y$10$LmBhlJ6QHgLUKOSoqMSpp.V33uO9SXfTRigeTFA3I/ogXEvheR0gG', 1, 'now');

INSERT INTO `files` (`id`, `path`, `mime`, `alt`, `width`, `height`, `size`) VALUES
(1, '/images/test.jpg', 'image/jpeg', 'Test', 64, 64, 1024);

INSERT INTO `sider` (`id`, `navn`, `text`, `beskrivelse`, `maerke`, `dato`, `icon_id`, `varenr`, `for`, `pris`) VALUES
(1, 'Root Page', '', '', null, '2018-01-01 00:00:00', null, '', 0, 0),
(2, 'Page 1', '', '', null, '2018-01-02 00:00:00', null, '', 0, 0),
(3, 'Product 1', '', '', 1, '2018-01-03 00:00:00', 1, 'sku2', 200, 20),
(4, 'Category Index Page', '', '', null, '2018-01-03 08:00:00', null, '', 0, 0),
(5, 'Inactive page', '', '', null, '2018-01-03 08:00:00', null, '', 0, 0),
(6, 'Product 2', '', '', 1, '2018-01-04 00:00:00', null, 'sku3', 100, 30),
(7, 'Product 3', '', '', 1, '2018-01-04 00:00:00', null, 'sku4', 300, 10),
(8, 'Product 4', '', '', 1, '2018-01-04 00:00:00', null, 'sku1', 400, 40);

INSERT INTO `bind` (`side`, `kat`) VALUES
(1, 0),
(2, 1),
(3, 1),
(3, 2),
(6, 2),
(7, 2),
(8, 2),
(4, 6),
(5, -1);

INSERT INTO `maerke` (`id`, `navn`) VALUES
(1, 'Test'),
(2, 'Empty Brand');

INSERT INTO `krav` (`id`, `navn`, `text`) VALUES
(1, 'Test', '');

INSERT INTO `fakturas` (`quantities`, `products`, `values`, `paydate`, `cardtype`, `iref`, `eref`, `navn`, `att`, `adresse`, `postbox`, `postnr`, `by`, `email`, `tlf1`, `tlf2`, `posttlf`, `postname`, `postatt`, `postaddress`, `postaddress2`, `postpostbox`, `postpostalcode`, `postcity`, `clerk`, `department`, `note`, `enote`) VALUES
('', '', '', '', '', '', '', 'John Doe', 'Jane Doe', '50 Oakland Ave', 'P.O. box #578', '32104', 'A City, Florida', 'john@example.com', '88888888', '88888889', '88888890', 'Jane Doe', 'John Doe', '20 Shipping rd.', 'Collage Green', 'P.O. box #382', '902010', 'Beverly hills', '', '', '', '');

INSERT INTO `email` (`email`, `interests`, `navn`, `adresse`, `post`, `tlf1`, `tlf2`) VALUES
('john-email@excample.com', '', 'John Email', '48 Email street', '31047', '88888891', '88888892');

INSERT INTO `post` (`recipientID`, `recName1`, `recAddress1`, `recZipCode`) VALUES
('88888893', 'John Post', '48 Post street', '80447');
