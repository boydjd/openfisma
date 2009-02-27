--
-- Author:    Ryan yang <ryanyang@reyosoft.com>
-- Copyright: (c) 2008 Endeavor Systems, Inc.
-- License:   http://www.openfisma.org/mw/index.php?title=License
-- Version:   $Id$
--

ALTER TABLE `account_logs` 
    ADD `priority` tinyint(3) unsigned NOT NULL AFTER `timestamp`,
    ADD `priority_name` varchar(10) NOT NULL AFTER `priority`;

ALTER TABLE `account_logs` DROP `ip`;
                                

