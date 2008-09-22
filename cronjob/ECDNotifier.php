<?php
/**
 * Copyright (c) 2008 Endeavor Systems, Inc.
 *
 * This file is part of OpenFISMA.
 *
 * OpenFISMA is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * OpenFISMA is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OpenFISMA.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Mark Haase <mhaase@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id$
 */

/**
 * Indicates that we're running a command line tool, not responding to an http
 * request. This prevents the interface from being rendered.
 */
define('COMMAND_LINE', true);

require_once dirname(__FILE__) . '/../apps/bootstrap.php';

require_once (CONFIGS . '/setting.php');

// Kick off the main routine:
ECDNotifier::run();

/**
 * This class scans for any findings which have ECDs expiring today, in 7 days,
 * 14 days, or 21 days, and creates notifications for each of these events.
 *
 * This script is designed to be run every day in the early morning. If it runs
 * multiple times in the same day, then it will send multiple notifications.
 *
 * @package    Cron_Job
 * @subpackage Controller_Subpackage
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 */
class ECDNotifier
{
    /**
     * run() - Iterate through all findings in the system and create
     * notifications for those which have ECDs expiring today,
     */
    static function run() {
        $db = Zend_Db::factory(Zend_Registry::get('datasource'));
        Zend_Db_Table::setDefaultAdapter($db);
        Zend_Registry::set('db', $db);
        
        // Get all findings which expire today, or 7/14/21 days from now
        $query = "SELECT p.id,
                         DATE_FORMAT(p.action_est_date, '%m/%e/%y') ecd,
                         DATEDIFF(p.action_est_date, CURDATE()) days_remaining
                    FROM poams p
                   WHERE DATE(p.action_est_date) = CURDATE()
                      OR DATE(p.action_est_date) =
                         DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                      OR DATE(p.action_est_date) =
                         DATE_ADD(CURDATE(), INTERVAL 14 DAY)
                      OR DATE(p.action_est_date) =
                         DATE_ADD(CURDATE(), INTERVAL 21 DAY)";
        $statement = $db->query($query);
        $expiringPoams = $statement->fetchAll();

        // Now iterate through the poams and create the appropriate
        // notifications
        $notification = new Notification();
        foreach($expiringPoams as $poam) {
            switch($poam['days_remaining']) {
                case 0:
                    $notification->add(Notification::ECD_EXPIRES_TODAY,
                                       null,
                                       "PoamId:{$poam['id']}, ECD:{$poam['ecd']}");
                    break;
                case 7:
                    $notification->add(Notification::ECD_EXPIRES_7_DAYS,
                                       null,
                                       "PoamId:{$poam['id']}, ECD:{$poam['ecd']}");
                    break;
                case 14:
                    $notification->add(Notification::ECD_EXPIRES_14_DAYS,
                                       null,
                                       "PoamId:{$poam['id']}, ECD:{$poam['ecd']}");
                    break;
                case 21:
                    $notification->add(Notification::ECD_EXPIRES_21_DAYS,
                                       null,
                                       "PoamId:{$poam['id']}, ECD:{$poam['ecd']}");
                    break;
                default:
                    // This should never happen, because the query is written
                    // to exclude it.
                    throw new Exception("ECD Notifier has an internal error.");
            }
        }
    }


}
