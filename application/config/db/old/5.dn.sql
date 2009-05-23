--
-- Author:    Ryan Yang
-- Copyright: (c) 2008 Endeavor Systems, Inc.
-- License:   http://www.openfisma.org/mw/index.php?title=License
-- Version:   $Id:$
--

DELETE FROM functions
      WHERE id BETWEEN 80 AND 88;

DELETE FROM role_functions
      WHERE function_id BETWEEN 80 AND 88;
