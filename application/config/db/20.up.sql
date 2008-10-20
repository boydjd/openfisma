--
-- Author:    Mark Haase
-- Copyright: (c) 2008 Endeavor Systems, Inc.
-- License:   http://www.openfisma.org/mw/index.php?title=License
-- Version:   $Id:$
--

-- Change the default value for users.email_validate to 1. This means all new
-- users will have valid e-mails by default. They would only need to validate
-- their e-mails if the address is changed.

  ALTER TABLE users
MODIFY COLUMN email_validate tinyint(1) NOT NULL default '1';