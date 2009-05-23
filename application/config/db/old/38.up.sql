--
-- Author:    Mark E. Haase <mhaase@endeavorsystems.com>
-- Copyright: (c) 2008 Endeavor Systems, Inc.
-- License:   http://www.openfisma.org/mw/index.php?title=License
-- Version:   $Id:$
--

-- Create indexes for evidences and poam_evaluations. This supports remediation summary and
-- search queries which were running slowly.

ALTER TABLE evidences ADD INDEX (poam_id);
ALTER TABLE poam_evaluations ADD INDEX (group_id);

