--
-- Author:    Woody <woody.li@reyosoft.com>
-- Copyright: (c) 2008 Endeavor Systems, Inc.
-- License:   http://www.openfisma.org/mw/index.php?title=License
-- Version:   $Id$
--

-- add a table 'uploads' and add a column for table 'poams'

DROP TABLE `uploads`;

ALTER TABLE `poams` DROP `upload_id`;
