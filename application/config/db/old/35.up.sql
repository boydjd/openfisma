--
-- Author:    Mark E. Haase <mhaase@endeavorsystems.com>
-- Copyright: (c) 2008 Endeavor Systems, Inc.
-- License:   http://www.openfisma.org/mw/index.php?title=License
-- Version:   $Id:$
--

-- Set some reasonable defaults for system configuration:

UPDATE configurations SET value = '' WHERE `key` = 'contact_email';
UPDATE configurations SET value = '' WHERE `key` = 'contact_subject';
