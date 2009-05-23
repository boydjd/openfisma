--
-- Author:    Ryan yang
-- Copyright: (c) 2008 Endeavor Systems, Inc.
-- License:   http://www.openfisma.org/mw/index.php?title=License
-- Version:   $Id:$
--

ALTER TABLE `users` CHANGE `password` `password` VARCHAR(32) NOT NULL default '';

ALTER TABLE `users` CHANGE `history_password` `history_password` VARCHAR(100) NOT NULL default '';

UPDATE `users` SET `password` = '9d1fee901b933a42978f2eacbcddff65' WHERE `account` = 'root';

