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
 * @author    Woody <woody712@users.sourceforge.net>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id$
 * @package   Controller
 */

/**
 * The report controller creates the multitude of reports available in
 * OpenFISMA.
 *
 * @package   Helper
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 */
class Fisma_Controller_Action_Helper_OverdueStatistic extends Zend_Controller_Action_Helper_Abstract
{
    /**
     * make a statistics for overdue records
     * 
     * @param array $list all overdue records
     * @return array $result
     */
    public function overdueStatistic($list)
    {
        $result = $this->_overdueSort($list);
        foreach ($result as &$v) {
            $v['systemName'] = $v['systemNickname'] . ' - ' . $v['systemName'];
            unset($v['systemNickname']);
            $totalOverdue = $v['lessThan30'] + $v['moreThan30'] + $v['moreThan60'] 
                            + $v['moreThan90'] + $v['moreThan120'];
            $v['total'] = $totalOverdue;
            $v['average'] = round(array_sum($v['diffDay'])/$totalOverdue);
            $v['max'] = max($v['diffDay']);
            unset($v['diffDay']);
        }
        ksort($result);
        return $result;
    }
    
    /**
     * sort overdue records by overdue days and status
     *
     * @param array $list all overdue records
     * @return array $result
     */  
    private function _overdueSort($list)
    {
        $mitigationStrategyStatus = array('NEW', 'DRAFT', 'MSA');
        $correctiveAction = array('EN', 'EA');
        $result = array();
        $date = new Zend_Date(null, Zend_Date::ISO_8601);
        foreach ($list as $row) {
            $now = clone $date;
            $duetime = new Zend_Date($row['duetime'], Zend_Date::ISO_8601);
            $differDay = floor($now->sub($duetime)/(3600*24));
            $row['diffDay'] = $differDay + 1;
            
            if (in_array($row['oStatus'], $mitigationStrategyStatus)) {
                $overdueType = 'MS';
            }
            if (in_array($row['oStatus'], $correctiveAction)) {
                $overdueType = 'CA';
            }
            $key = $row['system_nickname'] . $row['system_id'].'_'.$overdueType;
            if (!isset($result[$key])) {
                $result[$row['system_nickname'] . $row['system_id'].'_'.$overdueType] = array();
            }
            if (!isset($result[$key]['systemName'])) {
                $result[$key]['systemName'] = $row['system_name'];
            }
            if (!isset($result[$key]['systemNickname'])) {
                $result[$key]['systemNickname'] = $row['system_nickname'];
            }
            if (!isset($result[$key]['type'])) {
                if ($overdueType == 'MS') {
                    $result[$key]['type'] = 'Mitigation Strategy';
                }
                if ($overdueType == 'CA') {
                    $result[$key]['type'] = 'Corrective Action';
                }
            }
            if (!isset($result[$key]['lessThan30'])) {
                $result[$key]['lessThan30'] = 0;
            }
            if ($row['diffDay'] < 30) {
                $result[$key]['lessThan30'] ++;
            }
            if (!isset($result[$key]['moreThan30'])) {
                $result[$key]['moreThan30'] = 0;
            }
            if ($row['diffDay'] >= 30 && $row['diffDay'] < 60) {
                $result[$key]['moreThan30'] ++;
            }
            if (!isset($result[$key]['moreThan60'])) {
                $result[$key]['moreThan60'] = 0;
            }
            if ($row['diffDay'] >= 60 && $row['diffDay'] < 90) {
                $result[$key]['moreThan60'] ++;
            }
            if (!isset($result[$key]['moreThan90'])) {
                $result[$key]['moreThan90'] = 0;
            }
            if ($row['diffDay'] >= 90 && $row['diffDay'] < 120) {
                $result[$key]['moreThan90'] ++;
            }
            if (!isset($result[$key]['moreThan120'])) {
                $result[$key]['moreThan120'] = 0;
            }
            if ($row['diffDay'] >= 120) {
                $result[$key]['moreThan120'] ++;
            }
            if (!isset($result[$key]['diffDay'])) {
                $result[$key]['diffDay'] = array($row['diffDay']);
            } else {
                $result[$key]['diffDay'][] = $row['diffDay'];
            }
        }
        return $result;
    }
    
    /**
     * Perform helper when called as $this->_helper->overdueStatistic() from an action controller
     * 
     * @param array $list all overdue records
     */
    public function direct($list)
    {
        return $this->overdueStatistic($list);
    }
    
}
