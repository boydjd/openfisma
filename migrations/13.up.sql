--
-- Author:    Mark Haase
-- Copyright: (c) 2008 Endeavor Systems, Inc.
-- License:   http://www.openfisma.org/mw/index.php?title=License
-- Version:   $Id:$
--

-- Create some new notification event types for ECDs which are expiring.

INSERT INTO events
    (id, name, function_id)
VALUES
    (46, 'ECD Expires Today', 1),
    (47, 'ECD Expires in 7 Days', 1),
    (48, 'ECD Expires in 14 days', 1),
    (49, 'ECD Expires in 21 days', 1);
    