--------------------------------------------------------------------------------
-- Author:    Chris Chen
-- Copyright: (c) 2008 Endeavor Systems, Inc.
-- License:   http://www.openfisma.org/mw/index.php?title=License
-- Version:   $Id:$
--------------------------------------------------------------------------------

-- Add notification data model and notifications metadata

ALTER TABLE users
 ADD COLUMN (`notify_frequency` int(10) NOT NULL default '720',
             `most_recent_notify_ts` datetime NOT NULL);
             
CREATE TABLE `user_events` (
    `user_id` int(10) NOT NULL,
    `event_id` int(10) NOT NULL,
    PRIMARY KEY  (`user_id`,`event_id`)
);

CREATE TABLE `events` (
    `id` int(10) NOT NULL auto_increment,
    `name` varchar(64) NOT NULL,
    `function_id` int(10) NOT NULL,
    PRIMARY KEY  (`id`,`name`)
);

-- Metadata:

INSERT INTO `events`
VALUES  (1, 'Finding Created', 4),
        (2, 'Finding Import', 4),
        (3, 'Finding Inject', 11),
        (4, 'Asset Modified', 7),
        (5, 'Asset Created', 8),
        (6, 'Asset Deleted', 9),
        (7, 'Update Course of Action', 14),
        (8, 'Update Finding Assignment', 15),
        (9, 'Update Control Assignment', 16),
        (10, 'Update Countermeasures', 17),
        (11, 'Update Threat', 18),
        (12, 'Update Finding Recommendation', 19),
        (13, 'Update Finding Resources', 20),
        (14, 'Update Est Completion Date', 21),
        (15, 'Mitigation Strategy Approved', 24),
        (16, 'POA&M Closed', 0),
        (17, 'Evidence Upload', 23),
        (18, 'Evidence Submitted for 1st Approval', 25),
        (19, 'Evidence Submitted for 2nd Approval', 26),
        (20, 'Evidence Submitted for 3rd Approval', 27),
        (21, 'Account Modified', 43),
        (22, 'Account Deleted', 44),
        (23, 'Account Created', 45),
        (24, 'System Groups Deleted', 59),
        (25, 'System Groups Modified ', 60),
        (26, 'System Groups Created', 61),
        (27, 'System Deleted', 67),
        (28, 'System Modified', 68),
        (29, 'System Created', 69),
        (30, 'Product Created', 72),
        (31, 'Product Modified', 73),
        (32, 'Product Deleted', 74),
        (33, 'Role Created', 82),
        (34, 'Role Deleted', 83),
        (35, 'Role Modified', 81),
        (36, 'Finding Source Created', 78),
        (37, 'Finding Source Modified', 75),
        (38, 'Finding Source Deleted', 76),
        (39, 'Network Modified', 86),
        (40, 'Network Created', 87),
        (41, 'Network Deleted', 88),
        (42, 'System Configuration Modified', 0),
        (43, 'Account Login Success', 0),
        (44, 'Account Login Failure', 0),
        (45, 'Account Logout', 0);