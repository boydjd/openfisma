--
-- Author:    Ryan yang<ryan.yang@reyosoft.com>
-- Copyright: (c) 2008 Endeavor Systems, Inc.
-- License:   http://www.openfisma.org/mw/index.php?title=License
-- Version:   $Id$
--

 ALTER TABLE `systems`
    CHANGE `confidentiality` `confidentiality` ENUM( 'NA', 'LOW', 'MODERATE', 'HIGH' ) default NULL;

 ALTER TABLE `systems`
    CHANGE `integrity` `integrity` ENUM( 'LOW', 'MODERATE', 'HIGH' ) default NULL ;

 ALTER TABLE `systems` CHANGE `availability` `availability` ENUM( 'LOW', 'MODERATE', 'HIGH' ) default NULL;
