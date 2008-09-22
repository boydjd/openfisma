--
-- Author:    Ryan yang
-- Copyright: (c) 2008 Endeavor Systems, Inc.
-- License:   http://www.openfisma.org/mw/index.php?title=License
-- Version:   $Id:$
--


ALTER TABLE users
 ADD COLUMN `email_validate` tinyint(1) NOT NULL default '0' AFTER `email`;

CREATE TABLE `validate_emails` (
    `id` int(10) NOT NULL auto_increment,
    `user_id` int(10) NOT NULL,
    `email` varchar(64) NOT NULL,
    `validate_code` varchar(32) NOT NULL,
    PRIMARY KEY  (`id`)
);

UPDATE events SET function_id = 79 WHERE id = 42;
UPDATE events SET function_id = 45 WHERE id BETWEEN 43 AND 45;
