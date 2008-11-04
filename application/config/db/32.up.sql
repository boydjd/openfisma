--
-- Author:    Mark E. Haase <mhaase@endeavorsystems.com>
-- Copyright: (c) 2008 Endeavor Systems, Inc.
-- License:   http://www.openfisma.org/mw/index.php?title=License
-- Version:   $Id:$
--

-- Add a Common Platform Enumeration (CPE) field to the products table
ALTER TABLE products ADD COLUMN cpe_name VARCHAR(256) DEFAULT NULL UNIQUE KEY;