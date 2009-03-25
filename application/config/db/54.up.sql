--
-- Author:    Mark E. Haase <mhaase@endeavorsystems.com>
-- Copyright: (c) 2008 Endeavor Systems, Inc.
-- License:   http://www.openfisma.org/mw/index.php?title=License
-- Version:   $Id$
--

ALTER TABLE users MODIFY COLUMN search_columns_pref INTEGER DEFAULT 66037;
UPDATE users SET search_columns_pref = 66037 WHERE account LIKE 'root';

