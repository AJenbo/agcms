INSERT INTO `kat` (`id`, `navn`, `bind`, `vis`, `email`) VALUES ('-1', 'Trash', NULL, '0', 'mail@example.com'), ('0', 'Frontpage', NULL, '1', 'mail@example.com');
UPDATE `kat` SET `id`='0' WHERE `id`='1';
INSERT INTO `special` (`id`, `navn`, `dato`, `text`) VALUES ('0', 'Cron', NOW(), '');
UPDATE `special` SET `id`='0' WHERE `id`='1';

