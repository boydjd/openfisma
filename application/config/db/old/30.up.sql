--
-- Author:    Mark E. Haase <mhaase@endeavorsystems.com>
-- Copyright: (c) 2008 Endeavor Systems, Inc.
-- License:   http://www.openfisma.org/mw/index.php?title=License
-- Version:   $Id:$
--

-- Create the function for approving injected findings (moving them from PEND status to NEW status)
INSERT INTO `functions` VALUES (95,'Approve Injected Findings','finding','approve','','1');

-- Assign the function to default roles for ADMIN, SAISO, and IV&V
INSERT INTO role_functions VALUES (7, 95), (8, 95), (10, 95);

-- Add a "duplicate" field to the poam to show which ones are duplicates of existing poams
ALTER TABLE poams ADD COLUMN duplicate_poam_id INT DEFAULT NULL;

