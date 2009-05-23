--
-- Author:    Mark E. Haase <mhaase@endeavorsystems.com>
-- Copyright: (c) 2008 Endeavor Systems, Inc.
-- License:   http://www.openfisma.org/mw/index.php?title=License
-- Version:   $Id:$
--

DELETE FROM functions WHERE id=95;

DELETE FROM role_functions 
      WHERE role_id in (7, 8, 10)
        AND function_id = 95;

ALTER TABLE poams DROP COLUMN duplicate_poam_id;

