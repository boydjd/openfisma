--
-- Author:    Mark E. Haase <mhaase@endeavorsystems.com>
-- Copyright: (c) 2008 Endeavor Systems, Inc.
-- License:   http://www.openfisma.org/mw/index.php?title=License
-- Version:   $Id:$
--

-- Increase the length of the class column in the plugins table.

ALTER TABLE `plugins` CHANGE `classname` `class` VARCHAR(256) NOT NULL;

-- Modify the default setting for AppDetective

UPDATE plugins SET class = 'Inject_AppDetective' WHERE class = 'AppDetective';
