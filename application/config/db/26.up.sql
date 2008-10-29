--
-- Author:    Ryan yang
-- Copyright: (c) 2008 Endeavor Systems, Inc.
-- License:   http://www.openfisma.org/mw/index.php?title=License
-- Version:   $Id:$
--

ALTER TABLE `poams` CHANGE `status` `status` ENUM( 'NEW', 'OPEN', 'MSA', 'EN', 'EP', 'CLOSED', 'DELETED' ) NOT NULL DEFAULT 'NEW';

ALTER TABLE `evaluations` ADD `nickname` VARCHAR( 32 ) NOT NULL AFTER `name`,
ADD `event_id` INT( 10 ) NOT NULL AFTER `function_id` ;

UPDATE `evaluations` SET `name`='Mitigation Strategy Provided to SSO', `nickname`='MP_SSO',
    `function_id`='24', `event_id`='15', `group`='ACTION' WHERE id = '1';
UPDATE `evaluations` SET `name`='Mitigation Strategy Provided to IVV', `nickname`='MP_IVV', 
    `function_id`='92', `event_id`='93', `group`='ACTION' WHERE id = '2';
UPDATE `evaluations` SET `name`='Evidence Provided to SSO', `nickname`='EP_SSO',
    `precedence_id`='0',  `function_id`='25', `event_id`='18' WHERE id = '3';
UPDATE `evaluations` SET `name`='Evidence Provided to SP', `nickname`='EP_SP', `precedence_id`='1',
    `function_id`='26', `event_id`='19', `group`='EVIDENCE' WHERE id = '4';
UPDATE `evaluations` SET `name`='Evidence Provided to IVV', `nickname`='EP_IVV', `precedence_id`='2',
    `function_id`='27', `event_id`='20', `group`='EVIDENCE' WHERE id = '5';

UPDATE `functions` SET `name`='Mitigation Strategy Provided to SSO', `action`='update_mitigation_strategy_approval_1' WHERE id='24';
INSERT INTO `functions` (`id`, `name`, `screen`, `action`, `desc`, `open`) VALUES 
(92, 'Mitigation Strategy Provided to IVV', 'remediation', 'update_mitigation_strategy_approval_2', '', '1'),
(93, 'Mitigation Strategy submit', 'remediation', 'mitigation_strategy_operate', '', '1');

UPDATE `events` SET `name` = 'Mitigation Strategy Approved TO SSO' WHERE id = '15';
INSERT INTO `events` (`id`, `name`, `function_id`) VALUES 
(53, 'Mitigation Strategy Approved to IVV', 91);
