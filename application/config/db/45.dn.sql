--
-- Author:    Mark E. Haase <mhaase@endeavorsystems.com>
-- Copyright: (c) 2008 Endeavor Systems, Inc.
-- License:   http://www.openfisma.org/mw/index.php?title=License
-- Version:   $Id$
--

ALTER TABLE poams MODIFY create_ts DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00';
ALTER TABLE poams MODIFY discover_ts DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00';
ALTER TABLE poams MODIFY modify_ts DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00';
ALTER TABLE poams MODIFY close_ts DATETIME DEFAULT '0000-00-00 00:00:00';

