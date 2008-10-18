--
-- Author:    Ryan yang
-- Copyright: (c) 2008 Endeavor Systems, Inc.
-- License:   http://www.openfisma.org/mw/index.php?title=License
-- Version:   $Id:$
--


DROP TABLE `organizations`;

CREATE TABLE `system_groups` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(64) NOT NULL default '',
  `nickname` varchar(8) NOT NULL default '',
  `is_identity` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`id`)
);

UPDATE `functions` SET `name` = 'View System Groups', `screen` = 'admin_system_groups' WHERE id = '58';
UPDATE `functions` SET `name` = 'Delete System Groups', `screen` = 'admin_system_groups' WHERE id = '59';
UPDATE `functions` SET `name` = 'Edit System Groups', `screen` = 'admin_system_groups' WHERE id = '60';
UPDATE `functions` SET `name` = 'Create System Groups', `screen` = 'admin_system_groups' WHERE id = '61';

CREATE TABLE `systemgroup_systems` (
  `sysgroup_id` int(10) unsigned NOT NULL default '0',
  `system_id` int(10) unsigned NOT NULL default '0',
  KEY `sysgroup_id` (`sysgroup_id`),
  KEY `system_id` (`system_id`)
);

ALTER TABLE `systems` DROP `organization_id`;

ALTER TABLE `systems` ADD `primary_office` INT( 10 ) unsigned NOT NULL default '0' COMMENT 'fk to system_groups' after `type`;

ALTER TABLE `systems` ADD `criticality` ENUM('NONE','SUPPORTIVE','IMPORTANT','CRITICAL') NOT NULL;

ALTER TABLE `systems` CHANGE `confidentiality_justification` `criticality_justification` TEXT CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL;

ALTER TABLE `systems` CHANGE `integrity_justification` `sensitivity_justification` TEXT CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL;

ALTER TABLE `systems` DROP `availability_justification`;

ALTER TABLE `systems` DROP `security_categorization`;


