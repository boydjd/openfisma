--
-- Author:    Mark E. Haase <mhaase@endeavorsystems.com>
-- Copyright: (c) 2008 Endeavor Systems, Inc.
-- License:   http://www.openfisma.org/mw/index.php?title=License
-- Version:   $Id$
--

-- Convert these columns from DATETIME to DATE

ALTER TABLE poams MODIFY create_ts DATE NOT NULL;
ALTER TABLE poams MODIFY discover_ts DATE NOT NULL;
ALTER TABLE poams MODIFY modify_ts DATE NOT NULL;
ALTER TABLE poams MODIFY close_ts DATE NOT NULL;

