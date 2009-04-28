-- Author:    Woody <woody.li@reyosoft.com>
-- Copyright: (c) 2008 Endeavor Systems, Inc.
-- License:   http://www.openfisma.org/mw/index.php?title=License
-- Version:   $Id$
--

-- remove 'close_ts' in table poams

ALTER TABLE `poams` ADD `close_ts` date NOT NULL AFTER `mss_ts`;
