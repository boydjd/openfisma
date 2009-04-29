--
-- Author:    Ryan <ryan.yang@reyosoft.com>
-- Copyright: (c) 2008 Endeavor Systems, Inc.
-- License:   http://www.openfisma.org/mw/index.php?title=License
-- Version:   $Id$
--

ALTER TABLE `account_logs` CHANGE `timestamp` `timestamp` datetime NOT NULL;
ALTER TABLE `assets` CHANGE `create_ts` `create_ts` datetime NOT NULL default '0000-00-00 00:00:00';
ALTER TABLE `audit_logs` CHANGE `timestamp` `timestamp` datetime NOT NULL;
ALTER TABLE `comments` CHANGE `date` `date` datetime NOT NULL default '0000-00-00 00:00:00';
ALTER TABLE `evidences` CHANGE `submit_ts` `submit_ts` date NOT NULL default '0000-00-00';
ALTER TABLE `poam_evaluations` CHANGE `date` `date` date NOT NULL default '0000-00-00';
ALTER TABLE `poams` CHANGE `create_ts` `create_ts` date NOT NULL;
ALTER TABLE `poams` CHANGE `modify_ts` `modify_ts` date NOT NULL;
ALTER TABLE `poams` CHANGE `mss_ts` `mss_ts` datetime NOT NULL default '0000-00-00 00:00:00';
ALTER TABLE `users` CHANGE `created_ts` `created_ts` datetime NOT NULL default '0000-00-00 00:00:00';
ALTER TABLE `users` CHANGE `password_ts` `password_ts` datetime NOT NULL default '0000-00-00 00:00:00';
ALTER TABLE `users` CHANGE `last_login_ts` `last_login_ts` datetime NOT NULL default '0000-00-00 00:00:00';
ALTER TABLE `vulnerabilities` CHANGE `modify_ts` `modify_ts` date NOT NULL default '0000-00-00';
