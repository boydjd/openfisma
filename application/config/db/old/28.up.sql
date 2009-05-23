--
-- Author:    Mark E. Haase <mhaase@endeavorsystems.com>
-- Copyright: (c) 2008 Endeavor Systems, Inc.
-- License:   http://www.openfisma.org/mw/index.php?title=License
-- Version:   $Id:$
--

-- Add the 'PEND' status to the poam status field

ALTER TABLE `poams` CHANGE `status` `status` enum('PEND','NEW','OPEN','EN','EP','ES','CLOSED','DELETED') NOT NULL DEFAULT 'NEW';

