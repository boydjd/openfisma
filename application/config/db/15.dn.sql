--
-- Author:    Ryan yang
-- Copyright: (c) 2008 Endeavor Systems, Inc.
-- License:   http://www.openfisma.org/mw/index.php?title=License
-- Version:   $Id:$
--

DELETE FROM `configurations`
    WHERE `id` = 22;

ALTER TABLE `users` DROP `last_rob`;

DELETE FROM `events`
    WHERE `id` = 50;
