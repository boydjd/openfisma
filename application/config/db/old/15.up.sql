--
-- Author:    Ryan yang
-- Copyright: (c) 2008 Endeavor Systems, Inc.
-- License:   http://www.openfisma.org/mw/index.php?title=License
-- Version:   $Id:$
--

INSERT INTO `configurations`
    (`id`, `key`, `value`, `description`)
VALUES
    ('22', 'rob_duration', '15', 'the duration between which the user has to accept the ROB.(Day)');

ALTER TABLE `users` ADD `last_rob` DATETIME NOT NULL ;

INSERT INTO `events`
    (`id`, `name`, `function_id`)
VALUES
    (50, 'ROB ACCEPT', 1);
