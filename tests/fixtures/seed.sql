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
INSERT INTO `sider` (`id`, `navn`, `text`, `beskrivelse`, `maerke`) VALUES
('1', 'Root Page', '', '', null),
('2', 'Page 1', '', '', null),
('3', 'Product 1', '', '', 1),
('4', 'Category Index Page', '', '', null);
INSERT INTO `bind` (`side`, `kat`) VALUES
('1', '0'),
('2', '1'),
('3', '1'),
('2', '2'),
('3', '2'),
('4', '6');

INSERT INTO `maerke` (`id`, `navn`) VALUES
('1', 'Test'),
('2', 'Empty Brand');
INSERT INTO `krav` (`id`, `navn`, `text`) VALUES
('1', 'Test', '');
