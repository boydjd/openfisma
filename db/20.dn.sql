--
-- Author:    Mark Haase
-- Copyright: (c) 2008 Endeavor Systems, Inc.
-- License:   http://www.openfisma.org/mw/index.php?title=License
-- Version:   $Id:$
--

  ALTER TABLE users
MODIFY COLUMN email_validate tinyint(1) NOT NULL default '0';