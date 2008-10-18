--
-- Author:    Ryan yang
-- Copyright: (c) 2008 Endeavor Systems, Inc.
-- License:   http://www.openfisma.org/mw/index.php?title=License
-- Version:   $Id:$
--

DROP TABLE `system_groups`;

CREATE TABLE `organizations` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(64) NOT NULL default '',
  `nickname` varchar(8) NOT NULL default '',
  `father` int(10) NOT NULL default '0',
  PRIMARY KEY  (`id`)
);

UPDATE `functions` SET `name` = 'View Organizations', `screen` = 'admin_organizations' WHERE id = '58';
UPDATE `functions` SET `name` = 'Delete Organizations', `screen` = 'admin_organizations' WHERE id = '59';
UPDATE `functions` SET `name` = 'Edit Organizations', `screen` = 'admin_organizations' WHERE id = '60';
UPDATE `functions` SET `name` = 'Create Organizations', `screen` = 'admin_organizations' WHERE id = '61';

DROP TABLE `systemgroup_systems`;


ALTER TABLE `systems` ADD `organization_id` INT( 10 ) NOT NULL AFTER `nickname`;

ALTER TABLE `systems` DROP `primary_office`;

ALTER TABLE `systems` DROP `criticality`;

ALTER TABLE `systems` CHANGE `criticality_justification` `confidentiality_justification` TEXT CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL;

ALTER TABLE `systems` CHANGE `sensitivity_justification` `integrity_justification` TEXT CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL;

ALTER TABLE `systems` ADD `availability_justification` TEXT NOT NULL AFTER `integrity_justification`;

ALTER TABLE `systems` ADD `security_categorization` ENUM( 'NONE', 'LOW', 'MODERATE', 'HIGH' ) NOT NULL AFTER `availability`;
