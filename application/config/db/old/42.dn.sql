--
-- Author:    Ryan yang<ryan.yang@reyosoft.com>
-- Copyright: (c) 2008 Endeavor Systems, Inc.
-- License:   http://www.openfisma.org/mw/index.php?title=License
-- Version:   $Id:$
--

ALTER TABLE `systems` 
    ADD `security_categorization` enum('NONE','LOW','MODERATE','HIGH') NOT NULL AFTER `availability`;
