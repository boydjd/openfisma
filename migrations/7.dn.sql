--------------------------------------------------------------------------------
-- Author:    Ryan Yang
-- Copyright: (c) 2008 Endeavor Systems, Inc.
-- License:   http://www.openfisma.org/mw/index.php?title=License
-- Version:   $Id:$
--------------------------------------------------------------------------------

ALTER TABLE users 
    DROP COLUMN `email_validate`;

DROP TABLE `validate_emails`;

UPDATE events SET function_id = 0 WHERE id = 42;
UPDATE events SET function_id = 0 WHERE id BETWEEN 43 AND 45;
