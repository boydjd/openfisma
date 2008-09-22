--
-- Author:    Ryan Yang
-- Copyright: (c) 2008 Endeavor Systems, Inc.
-- License:   http://www.openfisma.org/mw/index.php?title=License
-- Version:   $Id:$
--

-- Add the notifications table

CREATE TABLE `notifications` (
    `id` int(10) NOT NULL auto_increment,
    `event_id` int(10) NOT NULL,
    `user_id` int(10) NOT NULL,
    `event_text` text NOT NULL,
    `timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP,
    PRIMARY KEY  (`id`)
);
