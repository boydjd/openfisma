--
-- Author:    Ryan yang
-- Copyright: (c) 2008 Endeavor Systems, Inc.
-- License:   http://www.openfisma.org/mw/index.php?title=License
-- Version:   $Id:$
--

INSERT INTO `events`
    (`id`, `name`, `function_id`)
VALUES
    (50, 'ROB ACCEPT', 1);

ALTER TABLE `account_logs` CHANGE `event` `event` ENUM( 'CREATION', 'MODIFICATION', 'TERMINATION', 'DISABLING', 'LOGINFAILURE', 'LOGIN', 'LOGOUT' ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL  
