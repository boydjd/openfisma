--
-- Author:    Ryan yang
-- Copyright: (c) 2008 Endeavor Systems, Inc.
-- License:   http://www.openfisma.org/mw/index.php?title=License
-- Version:   $Id:$
--

DELETE FROM `events`
    WHERE `id` = 50;

ALTER TABLE `account_logs` CHANGE `event` `event` ENUM( 'CREATION', 'MODIFICATION', 'TERMINATION', 'DISABLING', 'LOGINFAILURE', 'LOGIN', 'LOGOUT', 'ROB_ACCEPT' ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL  
