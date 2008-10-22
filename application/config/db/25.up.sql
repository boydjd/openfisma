--
-- Author:    Ryan yang
-- Copyright: (c) 2008 Endeavor Systems, Inc.
-- License:   http://www.openfisma.org/mw/index.php?title=License
-- Version:   $Id:$
--

ALTER TABLE `users` CHANGE `password` `password` VARCHAR(256);

ALTER TABLE `users` CHANGE `history_password` `history_password` TEXT;

UPDATE `users` SET `password` = '4a95bac3e19b28ee0acf3cc1137b4d1e66720a49' WHERE `account` = 'root';

