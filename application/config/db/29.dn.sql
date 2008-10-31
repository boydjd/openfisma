--
-- Author:    Ryan yang
-- Copyright: (c) 2008 Endeavor Systems, Inc.
-- License:   http://www.openfisma.org/mw/index.php?title=License
-- Version:   $Id:$
--

ALTER TABLE `evaluations` CHANGE `name` `name` VARCHAR( 32 ) NOT NULL;
UPDATE `evaluations` SET `name` = 'Mitigation Strategy Provided to ' WHERE id = '1';
UPDATE `evaluations` SET `name` = 'Mitigation Strategy Provided to ' WHERE id = '2';

ALTER TABLE `poams` ADD `action_status` ENUM( 'NONE', 'APPROVED', 'DENIED' ) NOT NULL DEFAULT 'NONE' AFTER `action_planned` ,
ADD `action_approved_by` INT( 10 ) unsigned default NULL AFTER `action_status` ;

-- mitigation strategy submit date
ALTER TABLE `poams` DROP `mss_ts`;

ALTER TABLE `poams` CHANGE `status` `status` enum('PEND','NEW','OPEN','EN','EP','ES','CLOSED','DELETED') NOT NULL DEFAULT 'NEW';

UPDATE `functions` SET `action` = 'mitigation_strategy_operate' WHERE id = '93';
DELETE FROM `functions` WHERE id = '94';
