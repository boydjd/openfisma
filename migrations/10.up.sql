--
-- Author:    Ryan yang
-- Copyright: (c) 2008 Endeavor Systems, Inc.
-- License:   http://www.openfisma.org/mw/index.php?title=License
-- Version:   $Id:$
--

ALTER TABLE `users`
    ADD `last_login_ip` VARCHAR( 32 ) NOT NULL AFTER `last_login_ts` ;
