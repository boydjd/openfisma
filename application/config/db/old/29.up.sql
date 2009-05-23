--
-- Author:    Ryan yang
-- Copyright: (c) 2008 Endeavor Systems, Inc.
-- License:   http://www.openfisma.org/mw/index.php?title=License
-- Version:   $Id:$
--

ALTER TABLE `evaluations` CHANGE `name` `name` VARCHAR( 100 ) NOT NULL;
UPDATE `evaluations` SET `name` = 'Mitigation Strategy Provided to SSO' WHERE id = '1';
UPDATE `evaluations` SET `name` = 'Mitigation Strategy Provided to IVV' WHERE id = '2';

ALTER TABLE `poams`
  DROP `action_status`,
  DROP `action_approved_by`;

-- mitigation strategy submit date
ALTER TABLE `poams` ADD `mss_ts` DATETIME NOT NULL default '0000-00-00 00:00:00' AFTER `modify_ts` ;

ALTER TABLE `poams` CHANGE `status` `status` enum('PEND', 'NEW', 'OPEN', 'MSA', 'EN', 'EP', 'CLOSED', 'DELETED') NOT NULL DEFAULT 'NEW';

UPDATE `functions` SET `action` = 'mitigation_strategy_submit' WHERE id = '93';
INSERT INTO `functions` (`id`, `name` , `screen` , `action` , `desc` , `open` ) VALUES (
'94', 'Mitigation Strategy revise', 'remediation', 'mitigation_strategy_revise', '', '1'
);
