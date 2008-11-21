--
-- Author:    Ryan yang<ryan.yang@reyosoft.com>
-- Copyright: (c) 2008 Endeavor Systems, Inc.
-- License:   http://www.openfisma.org/mw/index.php?title=License
-- Version:   $Id:$
--

UPDATE `configurations` SET `value` = '15', `description` = 'Automated Account Unlock Duration (hour)'
     WHERE `key` = 'unlock_duration';

UPDATE `configurations` SET `value` = '0' WHERE `key` = 'unlock_enabled';
