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
 * Indicates that we're running a command line tool, not responding to an http
 * request. This prevents the interface from being rendered.
 */
try {
    $ecdNotifier = new ECDNotifier();
    $ecdNotifier->run();
    print ("ECDNotifier finished at " . Fisma::now() . "\n");
} catch (Exception $e) {
    print $e->getMessage();
}

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
 * @version    $Id$
 */
class ECDNotifier
{
    public function __construct()
    {
        require_once(realpath(dirname(__FILE__) . '/../../library/Fisma.php'));

        Fisma::initialize(Fisma::RUN_MODE_COMMAND_LINE);
        Fisma::connectDb();
    }

    /**
     * Iterate through all findings in the system and create
     * notifications for those which have ECDs expiring today,
     */
    static function run() 
    {
        // Get all findings which expire today, or 7/14/21 days from now
        $query = Doctrine_Query::create()
                    ->select('f.id, f.currentEcd, f.responsibleOrganizationId')
                    ->from('Finding f')
                    ->where('f.status != ?', 'CLOSED')
                    ->addWhere('f.currentEcd = ?', date('Y-m-d'))
                    ->orWhere('f.currentEcd = ?', date('Y-m-d', time() + 7 * 3600 * 24))
                    ->orWhere('f.currentEcd = ?', date('Y-m-d', time() + 14 * 3600 * 24))
                    ->orWhere('f.currentEcd = ?', date('Y-m-d', time() + 21 * 3600 * 24));
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
                    throw new Exception("ECD Notifier has an internal error.");
            }
            Notification::notify($notificationType, $finding, null, $finding->responsibleOrganizationId);
        }
    }
}
