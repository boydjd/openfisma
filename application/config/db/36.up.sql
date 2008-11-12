--
-- Author:    Mark E. Haase <mhaase@endeavorsystems.com>
-- Copyright: (c) 2008 Endeavor Systems, Inc.
-- License:   http://www.openfisma.org/mw/index.php?title=License
-- Version:   $Id:$
--

ALTER TABLE `account_logs` CHANGE `event` `event` ENUM( 'ACCOUNT_CREATED', 'ACCOUNT_MODIFICATION', 'ACCOUNT_DELETED','ACCOUNT_LOCKOUT', 'DISABLING', 'LOGINFAILURE', 'LOGIN', 'LOGOUT', 'ROB_ACCEPT' ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL;
