--
-- Author:    Gary Alexander <galexander@endeavorsystems.com>
-- Copyright: (c) 2008 Endeavor Systems, Inc.
-- License:   http://www.openfisma.org/mw/index.php?title=License
-- Version:   $Id$
--

-- add a column for table 'systems'


ALTER TABLE `systems` ADD `visibility` enum('visible', 'hidden')  AFTER `tier` ;
