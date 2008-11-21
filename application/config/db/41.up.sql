--
-- Author:    Ryan yang<ryan.yang@reyosoft.com>
-- Copyright: (c) 2008 Endeavor Systems, Inc.
-- License:   http://www.openfisma.org/mw/index.php?title=License
-- Version:   $Id:$
--

ALTER TABLE `users` DROP `hash`;

ALTER TABLE `users` ADD `hash` ENUM( 'md5', 'sha1', 'sha256' ) NOT NULL DEFAULT 'sha1' AFTER `password`;

UPDATE `users` SET `hash`='md5' WHERE length(`password`) = 32;
