<?php
/**
 * Copyright (c) 2013 Endeavor Systems, Inc.
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
 * @author     Andrew Reeves <andrew.reeves@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2013 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Cli
 */
class Fisma_Cli_RecordTrending extends Fisma_Cli_Abstract
{
    /**
     * _run
     */
    protected function _run()
    {
        $today = Zend_Date::now()->toString(Fisma_Date::FORMAT_DATE);
        $records = Doctrine_Query::create()
            ->select(
                'o.id AS organizationId, ' .
                'SUM(IF(v.isResolved, 0, 1)) AS open, ' .
                'SUM(IF(v.isResolved, 1, 0)) AS closed, ' .
                'SUM(IF(v.isResolved, 0, IFNULL(v.cvssBaseScore, 5))) AS openCvss'
            )
            ->from('Organization o, o.Assets a, a.Vulnerabilities v')
            ->where('v.id IS NOT NULL')
            ->groupBy('o.id')
            ->setHydrationMode(Doctrine::HYDRATE_SCALAR)
            ->execute();
        foreach ($records as $record) {
            $obj = new VulnerabilityTrending();
            $obj->period = $today;
            $obj->organizationId = $record['o_organizationId'];
            $obj->open = $record['v_open'];
            $obj->closed = $record['v_closed'];
            $obj->openCvss = (double)$record['v_openCvss'];
            $obj->save();
            $obj->free();
        }
    }
}
