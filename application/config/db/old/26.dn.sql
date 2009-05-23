--
-- Author:    Ryan yang
-- Copyright: (c) 2008 Endeavor Systems, Inc.
-- License:   http://www.openfisma.org/mw/index.php?title=License
-- Version:   $Id:$
--

ALTER TABLE `poams` CHANGE `status` `status` ENUM( 'NEW', 'OPEN', 'EN', 'EP', 'ES', 'CLOSED', 'DELETED' ) NOT NULL DEFAULT 'NEW';

ALTER TABLE `evaluations`
  DROP `nickname`,
  DROP `event_id`;

UPDATE `evaluations` SET `name`='EV_SSO', `function_id`='25', `group`='EVIDENCE' WHERE id='1';
UPDATE `evaluations` SET `name`='EV_FSA', `function_id`='26', `group`='EVIDENCE' WHERE id='2';
UPDATE `evaluations` SET `name`='EV_IVV', `precedence_id`='2', `function_id`='27' WHERE id='3';
UPDATE `evaluations` SET `name`='EST', `precedence_id`='3', `function_id`='21', `group`='ACTION' WHERE id='4';
UPDATE `evaluations` SET `name`='SSO', `precedence_id`='4', `function_id`='24', `group`='ACTION' WHERE id='5';


DELETE FROM `functions` WHERE id IN (92, 93);
UPDATE `functions` SET `name`='Approve Mitigation Strategy', `action`='update_mitigation_strategy_approval' WHERE id = '24';

DELETE FROM `events` WHERE id ='53';
UPDATE `events` SET `name`='Mitigation Strategy Approved' WHERE id = '15';
