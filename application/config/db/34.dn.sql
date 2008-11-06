--
-- Author:    Mark E. Haase <mhaase@endeavorsystems.com>
-- Copyright: (c) 2008 Endeavor Systems, Inc.
-- License:   http://www.openfisma.org/mw/index.php?title=License
-- Version:   $Id:$
--

UPDATE `users` SET `created_ts` = '0000-00-00 00:00:00',
`password_ts` = '0000-00-00 00:00:00' WHERE `account` = 'root';
