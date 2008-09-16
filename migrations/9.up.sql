--------------------------------------------------------------------------------
-- Author:    Ryan yang
-- Copyright: (c) 2008 Endeavor Systems, Inc.
-- License:   http://www.openfisma.org/mw/index.php?title=License
-- Version:   $Id:$
--------------------------------------------------------------------------------

-- Add notify_email to the users table

ALTER TABLE users
 ADD COLUMN notify_email varchar(64) NOT NULL;
