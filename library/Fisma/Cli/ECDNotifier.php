<?php
/**
 * Copyright (c) 2008 Endeavor Systems, Inc.
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
 * This class scans for any findings which have ECDs expiring today, in 7 days,
 * 14 days, or 21 days, and creates notifications for each of these events.
 *
 * This script is designed to be run every day in the early morning. If it runs
 * multiple times in the same day, then it will send multiple notifications.
 * 
 * @author     Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Cron_Job
 */
class Fisma_Cli_ECDNotifier
{
    /**
     * Build the query
     * 
     * @return Doctrine_Query
     */
    public function getQuery()
    {
        $expirationDates = array(
            Zend_Date::now()->toString(Fisma_Date::FORMAT_DATE),
            Zend_Date::now()->addDay(7)->toString(Fisma_Date::FORMAT_DATE),
            Zend_Date::now()->addDay(14)->toString(Fisma_Date::FORMAT_DATE),
            Zend_Date::now()->addDay(21)->toString(Fisma_Date::FORMAT_DATE)
        );

        // Get all findings which expire today, or 7/14/21 days from now
        $query = Doctrine_Query::create()
                    ->select('f.id, f.currentEcd, f.responsibleOrganizationId')
                    ->from('Finding f')
                    ->where('f.status != ?', 'CLOSED')
                    ->andWhereIn('f.currentEcd', $expirationDates);

        return $query;
    }

    /**
     * Iterate through all findings in the system and create
     * notifications for those which have ECDs expiring today,
     * 
     * @return void
     */
    function run()
    {
        $query = $this->getQuery();
        $expiringFindings = $query->execute();
        // Now iterate through the findings and create the appropriate
        // notifications
        $notification = new Notification();
        foreach ($expiringFindings as $finding) {
            $daysRemaining = ceil((strtotime($finding->currentEcd) - time()) / (3600 * 24));
            switch($daysRemaining) {
                case 0:
                    $notificationType = 'ECD_EXPIRES_TODAY';
                    break;
                case 7:
                    $notificationType = 'ECD_EXPIRES_7_DAYS';
                    break;
                case 14:
                    $notificationType = 'ECD_EXPIRES_14_DAYS';
                    break;
                case 21:
                    $notificationType = 'ECD_EXPIRES_21_DAYS';
                    break;
                default:
                    // This should never happen, because the query is written
                    // to exclude it.
                    throw new Fisma_Zend_Exception("ECD Notifier has an internal error.");
            }
            $this->notify($notificationType, $finding);
        }
    }

    /**
     * A wrapper for Notification::notify, extracted to make run() testable
     * 
     * @param String $notificationType 
     * @param Doctrine_Record $finding          
     * 
     * @return void
     */
    function notify($notificationType, $finding)
    {
        Notification::notify($notificationType, $finding, null);
    }
}
