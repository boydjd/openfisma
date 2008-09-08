--------------------------------------------------------------------------------
-- Author:    Ryan Yang
-- Copyright: (c) 2008 Endeavor Systems, Inc.
-- License:   http://www.openfisma.org/mw/index.php?title=License
-- Version:   $Id:$
--------------------------------------------------------------------------------

-- Add the ldap configuration table

CREATE TABLE `ldap_config` (
    `id` int(10) unsigned NOT NULL auto_increment,
    `host` varchar(64) NOT NULL,
    `port` int(16),
    `domain_name` varchar(64) NOT NULL,
    `domain_short` varchar(64),
    `username` varchar(64),
    `password` varchar(64),
    `basedn` varchar(64),
    `account_filter` varchar(64),
    `account_canonical` varchar(64) ,
    `bind_requires_dn` varchar(64) ,
    `use_ssl` boolean,
    PRIMARY KEY (`id`)
);