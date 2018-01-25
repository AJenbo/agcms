INSERT INTO `kat` (`id`, `navn`, `bind`, `vis`, `email`, `access`) VALUES
(-1, 'Trash', NULL, 0, 'mail@example.com', ''),
(0, 'Frontpage', NULL, 1, 'mail@example.com', '');
INSERT INTO `special` (`id`, `navn`, `dato`, `text`) VALUES
(1, 'Frontpage', 'now', ''),
(3, 'Terms & Conditions', 'now', ''),
(0, 'Cron', 'now', '');
-- password is 123456
INSERT INTO `users` (`fullname`, `name`, `password`, `access`, `lastlogin`) VALUES
('test', 'test', '$2y$10$LmBhlJ6QHgLUKOSoqMSpp.V33uO9SXfTRigeTFA3I/ogXEvheR0gG', 1, 'now');

