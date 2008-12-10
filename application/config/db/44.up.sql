--
-- Author:    Ryan yang<ryan.yang@reyosoft.com>
-- Copyright: (c) 2008 Endeavor Systems, Inc.
-- License:   http://www.openfisma.org/mw/index.php?title=License
-- Version:   $Id:$
--

 ALTER TABLE `poams` CHANGE `status` `status` ENUM( 'PEND', 'NEW', 'DRAFT', 'MSA', 'EN', 'EP', 'CLOSED', 'DELETED' ) NOT NULL DEFAULT 'NEW';
