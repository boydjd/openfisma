--
-- Author:    Mark Haase
-- Copyright: (c) 2008 Endeavor Systems, Inc.
-- License:   http://www.openfisma.org/mw/index.php?title=License
-- Version:   $Id:$
--

-- Add new a new event for Account Lock

INSERT INTO events
    (id, name, function_id)
VALUES
    (52, 'Account Locked', 89);
