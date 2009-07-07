--
-- Author:    Ryan Yang <ryan@users.sourceforge.net>
-- Copyright: (c) 2008 Endeavor Systems, Inc.
-- License:   http://www.openfisma.org/mw/index.php?title=License
-- Version:   $Id$
--

ALTER TABLE `account_logs` DROP `priority`, DROP `priority_name`;

ALTER TABLE `account_logs` ADD `ip` VARCHAR( 32 ) NOT NULL AFTER `timestamp` ;

