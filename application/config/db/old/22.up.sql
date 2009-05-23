--
-- Author:    Ryan yang
-- Copyright: (c) 2008 Endeavor Systems, Inc.
-- License:   http://www.openfisma.org/mw/index.php?title=License
-- Version:   $Id:$
--

ALTER TABLE `poams` ADD `action_current_date` date default NULL AFTER `action_est_date` ,
ADD `ecd_justification` TEXT NULL AFTER `action_current_date` ;
