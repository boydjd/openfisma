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

    /**
     * set format like 'Oct 10, 2012'
     */
    const FORMAT_MONTH_DAY_YEAR = 'F';

    /**
     * set format like '2:45 AM'
     */
    const FORMAT_AM_PM_TIME = 'h:mm a';

    /**
     * Timezones
     *
     * @todo this doesn't belong here
     */
    public static function getTimezones()
    {
        return array(
            'Pacific/Kwajalein' => '(GMT-12:00) Enewetak, Kwajalein',
            'Pacific/Midway' => '(GMT-11:00) Midway Island, Samoa',
            'Pacific/Honolulu' => '(GMT-10:00) Hawaii',
            'America/Anchorage' => '(GMT-09:00) Alaska',
            'America/Los_Angeles' => '(GMT-08:00) Pacific Time (US & Canada)',
            'America/Dawson_Creek' => '(GMT-07:00) Arizona',
            'America/Denver' => '(GMT-07:00) Mountain Time (US & Canada)',
            'America/Chicago' => '(GMT-06:00) Central Time (US & Canada)',
            'America/Cancun' => '(GMT-06:00) Guadalajara, Mexico City, Monterrey',
            'America/Belize' => '(GMT-06:00) Saskatchewan, Central America',
            'America/Bogota' => '(GMT-05:00) Bogota, Lima, Quito, Rio Branco',
            'America/New_York' => '(GMT-05:00) Eastern Time (US & Canada)',
            'America/Indianapolis' => '(GMT-05:00) Indiana (East)',
            'America/Glace_Bay' => '(GMT-04:00) Atlantic Time (Canada)',
            'America/Caracas' => '(GMT-04:00) Caracas, La Paz',
            'America/St_Johns' => '(GMT-03:30) Newfoundland',
            'America/Argentina/Buenos_Aires' => '(GMT-03:00) Buenos Aires',
            'America/Sao_Paulo' => '(GMT-03:00) Rio de Janeiro',
            'America/Noronha' => '(GMT-02:00) Mid-Atlantic',
            'Atlantic/Cape_Verde' => '(GMT-01:00) Cape Verde Is., Azores',
            'Europe/London' => '(GMT) Greenwich Mean Time; Dublin, Edinburgh, London',
            'Africa/Abidjan' => '(GMT) Monrovia, Reykjavik',
            'Europe/Amsterdam' => '(GMT+01:00) Berlin, Stockholm, Rome, Bern, Brussels, Vienna',
            'Europe/Belgrade' => '(GMT+01:00) Lisbon, Warsaw',
            'Europe/Brussels' => '(GMT+01:00) Paris, Madrid',
            'Africa/Algiers' => '(GMT+01:00) Prague',
            'Asia/Beirut' => '(GMT+02:00) Athens, Helsinki, Istanbul',
            'Africa/Cairo' => '(GMT+02:00) Cairo',
            'Europe/Minsk' => '(GMT+02:00) Eastern Europe',
            'Africa/Blantyre' => '(GMT+02:00) Harare, Pretoria',
            'Asia/Jerusalem' => '(GMT+02:00) Israel',
            'Africa/Addis_Ababa' => '(GMT+03:00) Baghdad, Kuwait, Nairobi, Riyadh',
            'Europe/Moscow' => '(GMT+03:00) Moscow, St. Petersburg, Volgograd',
            'Asia/Tehran' => '(GMT+03:30) Tehran',
            'Asia/Dubai' => '(GMT+04:00) Abu Dhabi, Muscat, Tbilisi, Kazan, Volgograd',
            'Asia/Kabul' => '(GMT+04:30) Kabul',
            'Asia/Tashkent' => '(GMT+05:00) Islamabad, Karachi, Sverdlovsk, Tashkent',
            'Asia/Dhaka' => '(GMT+06:00) Alma Ata, Dhaka',
            'Asia/Bangkok' => '(GMT+07:00) Bangkok, Hanoi, Jakarta',
            'Asia/Hong_Kong' => '(GMT+08:00) Beijing, Chongqing, Urumqi',
            'Australia/Perth' => '(GMT+08:00) Hong Kong, Perth, Singapore, Taipei',
            'Asia/Tokyo' => '(GMT+09:00) Tokyo, Osaka, Sapporo, Seoul, Yakutsk',
            'Australia/Adelaide' => '(GMT+09:30) Adelaide',
            'Australia/Brisbane' => '(GMT+10:00) Brisbane, Melbourne, Sydney',
            'Asia/Vladivostok' => '(GMT+10:00) Guam, Port Moresby, Vladivostok',
            'Australia/Hobart' => '(GMT+10:00) Hobart',
            'Asia/Magadan' => '(GMT+11:00) Magadan, Soloman Is., New Caledonia',
            'Pacific/Fiji' => '(GMT+12:00) Fiji, Kamchatka, Marshall Is.',
            'Pacific/Auckland' => '(GMT+12:00) Wellington'
        );
    }
}
