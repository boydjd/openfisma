--
-- Author:    Mark E. Haase <mhaase@endeavorsystems.com>
-- Copyright: (c) 2008 Endeavor Systems, Inc.
-- License:   http://www.openfisma.org/mw/index.php?title=License
-- Version:   $Id:$
--

UPDATE plugins SET class = 'AppDetective' WHERE class = 'Inject_AppDetective';

ALTER TABLE `plugins` CHANGE `class` `classname` VARCHAR(12) NOT NULL DEFAULT '';
