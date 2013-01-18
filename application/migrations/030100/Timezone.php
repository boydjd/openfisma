<?php
/**
 * Copyright (c) 2012 Endeavor Systems, Inc.
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
 * Add User Timezone and update Incident Timezones as defined in OFJ-1976
 *
 * @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Migration
 */

class Application_Migration_030100_Timezone extends Fisma_Migration_Abstract
{
    /**
     * Migrate.
     */
    public function migrate()
    {
        $this->message("Adding user timezone...");
        $this->getHelper()->addColumn(
            'user',
            'timezone',
            'varchar(255)',
            'homeUrl'
        );
        $this->getHelper()->addColumn(
            'user',
            'timezoneauto',
            'tinyint(1) DEFAULT 1',
            'timezone'
        );

        $this->message("Update incidents...");
        $this->getHelper()->modifyColumn(
            'incident',
            'incidenttimezone',
            "enum('Pacific/Kwajalein','Pacific/Midway','Pacific/Honolulu','America/Anchorage','America/Los_Angeles','" .
            "America/Dawson_Creek','America/Denver','America/Chicago','America/Cancun','America/Belize','America/Bogo" .
            "ta','America/New_York','America/Indianapolis','America/Glace_Bay','America/Caracas','America/St_Johns','" .
            "America/Argentina/Buenos_Aires','America/Sao_Paulo','America/Noronha','Atlantic/Cape_Verde','Europe/Lond" .
            "on','Africa/Abidjan','Europe/Amsterdam','Europe/Belgrade','Europe/Brussels','Africa/Algiers','Asia/Beiru" .
            "t','Africa/Cairo','Europe/Minsk','Africa/Blantyre','Asia/Jerusalem','Africa/Addis_Ababa','Europe/Moscow'" .
            ",'Asia/Tehran','Asia/Dubai','Asia/Kabul','Asia/Tashkent','Asia/Dhaka','Asia/Bangkok','Asia/Hong_Kong','A" .
            "ustralia/Perth','Asia/Tokyo','Australia/Adelaide','Australia/Brisbane','Asia/Vladivostok','Australia/Hob" .
            "art','Asia/Magadan','Pacific/Fiji','Pacific/Auckland','AST','ADT','EST','EDT','CST','CDT','MST','MDT','P" .
            "ST','PDT','AKST','AKDT','HAST','HADT')",
            'incidenttime'
        );
        $this->getHelper()->modifyColumn(
            'incident',
            'reporttz',
            "enum('Pacific/Kwajalein','Pacific/Midway','Pacific/Honolulu','America/Anchorage','America/Los_Angeles','" .
            "America/Dawson_Creek','America/Denver','America/Chicago','America/Cancun','America/Belize','America/Bogo" .
            "ta','America/New_York','America/Indianapolis','America/Glace_Bay','America/Caracas','America/St_Johns','" .
            "America/Argentina/Buenos_Aires','America/Sao_Paulo','America/Noronha','Atlantic/Cape_Verde','Europe/Lond" .
            "on','Africa/Abidjan','Europe/Amsterdam','Europe/Belgrade','Europe/Brussels','Africa/Algiers','Asia/Beiru" .
            "t','Africa/Cairo','Europe/Minsk','Africa/Blantyre','Asia/Jerusalem','Africa/Addis_Ababa','Europe/Moscow'" .
            ",'Asia/Tehran','Asia/Dubai','Asia/Kabul','Asia/Tashkent','Asia/Dhaka','Asia/Bangkok','Asia/Hong_Kong','A" .
            "ustralia/Perth','Asia/Tokyo','Australia/Adelaide','Australia/Brisbane','Asia/Vladivostok','Australia/Hob" .
            "art','Asia/Magadan','Pacific/Fiji','Pacific/Auckland','AST','ADT','EST','EDT','CST','CDT','MST','MDT','P" .
            "ST','PDT','AKST','AKDT','HAST','HADT')",
            'reportts'
        );

        foreach (self::getOldTimezones() as $oldTimezone) {
            $this->getHelper()->update(
                'incident',
                array('incidenttimezone' => self::toNewTimezone($oldTimezone)),
                array('incidenttimezone' => $oldTimezone)
            );
            $this->getHelper()->update(
                'incident',
                array('reporttz' => self::toNewTimezone($oldTimezone)),
                array('reporttz' => $oldTimezone)
            );
        }

        $this->getHelper()->modifyColumn(
            'incident',
            'incidenttimezone',
            "enum('Pacific/Kwajalein','Pacific/Midway','Pacific/Honolulu','America/Anchorage','America/Los_Angeles','" .
            "America/Dawson_Creek','America/Denver','America/Chicago','America/Cancun','America/Belize','America/Bogo" .
            "ta','America/New_York','America/Indianapolis','America/Glace_Bay','America/Caracas','America/St_Johns','" .
            "America/Argentina/Buenos_Aires','America/Sao_Paulo','America/Noronha','Atlantic/Cape_Verde','Europe/Lond" .
            "on','Africa/Abidjan','Europe/Amsterdam','Europe/Belgrade','Europe/Brussels','Africa/Algiers','Asia/Beiru" .
            "t','Africa/Cairo','Europe/Minsk','Africa/Blantyre','Asia/Jerusalem','Africa/Addis_Ababa','Europe/Moscow'" .
            ",'Asia/Tehran','Asia/Dubai','Asia/Kabul','Asia/Tashkent','Asia/Dhaka','Asia/Bangkok','Asia/Hong_Kong','A" .
            "ustralia/Perth','Asia/Tokyo','Australia/Adelaide','Australia/Brisbane','Asia/Vladivostok','Australia/Hob" .
            "art','Asia/Magadan','Pacific/Fiji','Pacific/Auckland')",
            'incidenttime'
        );
        $this->getHelper()->modifyColumn(
            'incident',
            'reporttz',
            "enum('Pacific/Kwajalein','Pacific/Midway','Pacific/Honolulu','America/Anchorage','America/Los_Angeles','" .
            "America/Dawson_Creek','America/Denver','America/Chicago','America/Cancun','America/Belize','America/Bogo" .
            "ta','America/New_York','America/Indianapolis','America/Glace_Bay','America/Caracas','America/St_Johns','" .
            "America/Argentina/Buenos_Aires','America/Sao_Paulo','America/Noronha','Atlantic/Cape_Verde','Europe/Lond" .
            "on','Africa/Abidjan','Europe/Amsterdam','Europe/Belgrade','Europe/Brussels','Africa/Algiers','Asia/Beiru" .
            "t','Africa/Cairo','Europe/Minsk','Africa/Blantyre','Asia/Jerusalem','Africa/Addis_Ababa','Europe/Moscow'" .
            ",'Asia/Tehran','Asia/Dubai','Asia/Kabul','Asia/Tashkent','Asia/Dhaka','Asia/Bangkok','Asia/Hong_Kong','A" .
            "ustralia/Perth','Asia/Tokyo','Australia/Adelaide','Australia/Brisbane','Asia/Vladivostok','Australia/Hob" .
            "art','Asia/Magadan','Pacific/Fiji','Pacific/Auckland')",
            'reportts'
        );
    }

    public static function getOldTimezones()
    {
        return
            array('ADT', 'AST', 'EDT', 'EST', 'CDT', 'CST', 'MDT', 'MST', 'PDT', 'PST', 'AKDT', 'AKST', 'HADT', 'HAST');
    }

    public static function toNewTimezone($timezone)
    {
        $aTimeZones = array(
            'ADT'   =>  'America/Glace_Bay',
            'AST'   =>  'America/Glace_Bay',
            'EDT'   =>  'America/New_York',
            'EST'   =>  'America/New_York',
            'CDT'   =>  'America/Chicago',
            'CST'   =>  'America/Chicago',
            'MDT'   =>  'America/Denver',
            'MST'   =>  'America/Denver',
            'PDT'   =>  'America/Los_Angeles',
            'PST'   =>  'America/Los_Angeles',
            'AKDT'  =>  'America/Anchorage',
            'AKST'  =>  'America/Anchorage',
            'HADT'  =>  'Pacific/Honolulu',
            'HAST'  =>  'Pacific/Honolulu'
        );
        return $aTimeZones[$timezone];
    }
}
