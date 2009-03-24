--
-- Author:    Ryan yang <ryanyang@reyosoft.com>
-- Copyright: (c) 2008 Endeavor Systems, Inc.
-- License:   http://www.openfisma.org/mw/index.php?title=License
-- Version:   $Id$
--

ALTER TABLE users MODIFY COLUMN search_columns_pref INTEGER DEFAULT 65783;
UPDATE users SET search_columns_pref = 65783 WHERE account LIKE 'root';

