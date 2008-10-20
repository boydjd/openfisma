--
-- Author:    Mark Haase
-- Copyright: (c) 2008 Endeavor Systems, Inc.
-- License:   http://www.openfisma.org/mw/index.php?title=License
-- Version:   $Id:$
--

-- Add new functions which pertain to the rights to receive notifications.
-- Update the default notifications to use these functions.

INSERT INTO functions
    (id, name, screen, action)
VALUES
    (89, 'Admin Notifications', 'notification', 'admin'),
    (90, 'Asset Notifications', 'notification', 'asset'),
    (91, 'Remediation Notifications', 'notification', 'remediation');
    
UPDATE events
   SET function_id=89
 WHERE id BETWEEN 21 AND 45;
    
UPDATE events
   SET function_id=90
 WHERE id BETWEEN 4 AND 6;
 
UPDATE events
   SET function_id=91
 WHERE id BETWEEN 1 AND 3
    OR id BETWEEN 7 AND 20
    OR id BETWEEN 46 AND 49;
    
-- Add new event for when evidence is denied

INSERT INTO events
    (id, name, function_id)
VALUES
    (51, 'Evidence Denied', 91);
