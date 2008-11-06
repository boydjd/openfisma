--
-- Author:    Mark E. Haase <mhaase@endeavorsystems.com>
-- Copyright: (c) 2008 Endeavor Systems, Inc.
-- License:   http://www.openfisma.org/mw/index.php?title=License
-- Version:   $Id:$
--

-- Add a function for injecting findings. This is distinct from the function for creating findings.

--UPDATE `users` SET `created_ts` = NOW() AND `password_ts` = NOW() WHERE `id` = '1';
DELETE FROM `users` WHERE `id` = '1';
INSERT INTO `users` VALUES (1,'root','4a95bac3e19b28ee0acf3cc1137b4d1e66720a49','admin','Application',NULL,'Admin',NOW(),NOW(),'','0000-00-00 00:00:00','','0000-00-00 00:00:00',1,0,'',NULL,'',0,'root_r',720,'0000-00-00 00:00:00','','','0000-00-00 00:00:00');

