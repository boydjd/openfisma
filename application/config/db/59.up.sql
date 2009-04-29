--
-- Author:    Ryan <ryan.yang@reyosoft.com>
-- Copyright: (c) 2008 Endeavor Systems, Inc.
-- License:   http://www.openfisma.org/mw/index.php?title=License
-- Version:   $Id$
--

-- Convert "date" and "datetime" fields to "timestamp" 

ALTER TABLE `account_logs` CHANGE `timestamp` `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `assets` CHANGE `create_ts` `create_ts` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `audit_logs` CHANGE `timestamp` `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `comments` CHANGE `date` `date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `evidences` CHANGE `submit_ts` `submit_ts` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `poam_evaluations` CHANGE `date` `date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `poams` CHANGE `create_ts` `create_ts` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `poams` CHANGE `modify_ts` `modify_ts` TIMESTAMP NOT NULL DEFAULT 0;
ALTER TABLE `poams` CHANGE `mss_ts` `mss_ts` TIMESTAMP NOT NULL DEFAULT 0;
ALTER TABLE `users` CHANGE `created_ts` `created_ts` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `users` CHANGE `password_ts` `password_ts` TIMESTAMP NOT NULL DEFAULT 0;
ALTER TABLE `users` CHANGE `last_login_ts` `last_login_ts` TIMESTAMP NOT NULL DEFAULT 0;
ALTER TABLE `vulnerabilities` CHANGE `modify_ts` `modify_ts` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
