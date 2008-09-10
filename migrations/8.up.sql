--------------------------------------------------------------------------------
-- Author:    Mark Haase
-- Copyright: (c) 2008 Endeavor Systems, Inc.
-- License:   http://www.openfisma.org/mw/index.php?title=License
-- Version:   $Id:$
--------------------------------------------------------------------------------

-- Add ldap_dn to the users table

ALTER TABLE users
 ADD COLUMN ldap_dn varchar(64) NOT NULL;
