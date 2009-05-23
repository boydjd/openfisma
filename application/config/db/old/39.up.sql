--
-- Author:    Ryan yang<ryan.yang@reyosoft.com>
-- Copyright: (c) 2008 Endeavor Systems, Inc.
-- License:   http://www.openfisma.org/mw/index.php?title=License
-- Version:   $Id:$
--

UPDATE `configurations` SET `value` = '300', `description` = 'Automated Account Unlock Duration (In Seconds)'
     WHERE `key` = 'unlock_duration';

UPDATE `configurations` SET `value` = '1' WHERE `key` = 'unlock_enabled';
