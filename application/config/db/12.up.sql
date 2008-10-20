--
-- Author:    Mark Haase
-- Copyright: (c) 2008 Endeavor Systems, Inc.
-- License:   http://www.openfisma.org/mw/index.php?title=License
-- Version:   $Id:$
--

-- Set the default privacy policy value if the current system has not already
-- overriden the default;

UPDATE configurations
   SET description = '* This is a U.S. Federal government computer system that is FOR OFFICIAL USE ONLY.\n* This system is subject to monitoring. No expectation of privacy is to be assumed.\n* Individuals found performing unauthorized activities are subject to disciplinary action including criminal prosecution.'
 WHERE `key` LIKE 'privacy_policy'
   AND description like 'To be (good) or not to be?';
