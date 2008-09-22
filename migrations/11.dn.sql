--
-- Author:    Ryan yang
-- Copyright: (c) 2008 Endeavor Systems, Inc.
-- License:   http://www.openfisma.org/mw/index.php?title=License
-- Version:   $Id:$
--

ALTER TABLE `users`
    CHANGE `notify_frequency` `notify_frequency` INT( 10 ) NOT NULL DEFAULT '720' 
