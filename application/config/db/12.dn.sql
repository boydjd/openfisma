--
-- Author:    Mark Haase
-- Copyright: (c) 2008 Endeavor Systems, Inc.
-- License:   http://www.openfisma.org/mw/index.php?title=License
-- Version:   $Id:$
--

UPDATE configurations
   SET description = 'To be (good) or not to be?'
 WHERE `key` LIKE 'privacy_policy';
