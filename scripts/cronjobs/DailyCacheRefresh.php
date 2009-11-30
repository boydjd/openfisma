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
 * <http://www.gnu.org/licenses/>.
 */

$dailyCacheRefresh = new DailyCacheRefresh();
$dailyCacheRefresh->run();

/**
 * OpenFISMA caches certain information about due dates for action items. When the calendar day advances
 * forward, these caches become invalid and need to be refreshed for the current day.
 * 
 * This script invalidates all cache items which need to be refreshed on a daily basis and then rebuilds
 * the cache. This script should be run early in the day so that no user sees the outdated cache entries.
 * 
 * @author     Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2009 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Cron_Job
 * @version    $Id$
 */
class DailyCacheRefresh
{
    public function __construct()
    {
        require_once(realpath(dirname(__FILE__) . '/../../library/Fisma.php'));

        Fisma::initialize(Fisma::RUN_MODE_COMMAND_LINE);
        Fisma::connectDb();
    }
    
    /**
     * Invalidate the finding summary count cache and then rebuild it. There are other parameters 
     * (such as Mitigation Type and Finding Source) which are not re-built automatically. Those will
     * be build on-demand when a user requests them. Building all of the different permutations here
     * would probably be wasteful.
     */
    function run() 
    {
        $cache = Fisma::getCacheInstance('finding_summary');
        $cache->clean(Zend_Cache::CLEANING_MODE_ALL);
        
        $organizations = Doctrine::getTable('Organization')->findAll();
        foreach ($organizations as $organization) {
            $organization->getSummaryCounts();
        }
    }
}
