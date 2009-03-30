--
-- Author:    Ryan <ryan.yang@reyosoft.com>
-- Copyright: (c) 2008 Endeavor Systems, Inc.
-- License:   http://www.openfisma.org/mw/index.php?title=License
-- Version:   $Id$
--

-- Make a configuration item that store the password expire warning days.

INSERT INTO `configurations` (
`id` ,
`key` ,
`value` ,
`description`
)
VALUES (
30 , 'pass_warningdays', '3', 'Password expire warning days'
);
