--------------------------------------------------------------------------------
-- Author:    Chris Chen
-- Copyright: (c) 2008 Endeavor Systems, Inc.
-- License:   http://www.openfisma.org/mw/index.php?title=License
-- Version:   $Id:$
--------------------------------------------------------------------------------

ALTER TABLE users
DROP COLUMN `notify_frequency`;

ALTER TABLE users
DROP COLUMN `most_recent_notify_ts`;

DROP TABLE user_events;

DROP TABLE events;