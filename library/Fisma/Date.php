<?php
/**
 * Copyright (c) 2010 Endeavor Systems, Inc.
 *
 * This file is part of OpenFISMA.
 *
 * OpenFISMA is free software: you can redistribute it and/or modify it under the terms of the GNU General Public 
 * License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later
 * version.
 *
 * OpenFISMA is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied 
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more 
 * details.
 *
 * You should have received a copy of the GNU General Public License along with OpenFISMA.  If not, see 
 * {@link http://www.gnu.org/licenses/}.
 */

/**
 * Set ISO format date constants for OpenFisma
 * 
 * @author     Ben Zheng <ben.zheng@reyosoft.com>
 * @copyright  (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Date
 */
class Fisma_Date
{
    /**
     * set format like '2010-10-27'
     */
    const FORMAT_DATE = 'yyyy-MM-dd';

    /**
     * set format like '23:18:10'
     */
    const FORMAT_TIME = 'HH:mm:ss';

    /**
     * set format like '2010-10-27 23:19:10'
     */
    const FORMAT_DATETIME = 'yyyy-MM-dd HH:mm:ss';

    /**
     * set format like 'Wednesday, Oct 27, 11:14 PM'
     */
    const FORMAT_WEEKDAY_MONTH_NAME_SHORT_DAY_TIME = 'EEEE, MMM d, h:mm a';

    /**
     * set format like 'Wed, 27 Oct 2010 23:21:54 '
     */
    const FORMAT_WEEKDAY_SHORT_DAY_MONTH_NAME_SHORT_YEAR_TIME = 'EEE, dd MMM yyyy HH:mm:ss';

    /**
     * set format like '2010-10-27 11:25:02 PM EDT'
     */
    const FORMAT_DATETIME_MERIDIEM_TIMEZONE = 'yyyy-MM-dd hh:mm:ss a z';

    /**
     * set format like '20101027-112502'
     */
    const FORMAT_FILENAME_DATETIMESTAMP = 'yyyyMMdd-HHmmss';
    
    /**
     * The format of dates returned by Solr
     */
    const FORMAT_SOLR_DATETIME_TIMEZONE = 'YYYY-MM-ddTHH:mm:ssZ';
    
    /**
     * When storing dates in Solr, use this format and append a literal 'Z' to the timestamp value.
     * 
     * The literal 'Z' means a timezone offset of zero, i.e. Grenwich Mean Time, which is required in Solr.
     */
    const FORMAT_SOLR_DATETIME = 'yyyy-MM-ddTHH:mm:ss';
}
