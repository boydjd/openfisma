--
-- Author:    Woody <woody.li@reyosoft.com>
-- Copyright: (c) 2008 Endeavor Systems, Inc.
-- License:   http://www.openfisma.org/mw/index.php?title=License
-- Version:   $Id$
--

-- add a table 'uploads' and add a column for table 'poams'

CREATE TABLE `uploads` (
  `id` int(10) NOT NULL auto_increment,
  `upload_ts` timestamp NULL default NULL,
  `user_id` int(10) NOT NULL,
  `filename` varchar(200) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `poams` ADD `upload_id` INT( 10 ) NOT NULL AFTER `duplicate_poam_id` ;
