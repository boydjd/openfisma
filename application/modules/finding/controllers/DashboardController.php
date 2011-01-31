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
 * Dashboard for findings
 *
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Controllers
 * @version    $Id$
 */
class Finding_DashboardController extends Fisma_Zend_Controller_Action_Security
{
    /**
     * Set up headers/footers
     */
    public function preDispatch()
    {
        parent::preDispatch();

        $this->_acl->requireArea('finding');
        
        // Get a list of organization IDs that this user can see
        $orgSystems = $this->_me->getOrganizationsByPrivilege('finding', 'read')->toArray();
        $orgSystemIds = array(0);
        foreach ($orgSystems as $orgSystem) {
            $orgSystemIds[] = $orgSystem['id'];
        }
        $this->_myOrgSystemIds = $orgSystemIds;
        
        $this->_helper->fismaContextSwitch()
                      ->addActionContext('chartoverdue', 'json')
                      ->addActionContext('chartfindingstatus', 'json')
                      ->addActionContext('totaltype', 'json')
                      ->addActionContext('findingforecast', 'json')
                      ->addActionContext('chartfindnomitstrat', 'json')
                      ->addActionContext('chartfinding', 'json')
                      ->addActionContext('chartfindingbyorgdetail', 'json')
                      ->initContext();
    }

    public function indexAction()
    {
        // Top-left chart - Finding Forecast
        $chartFindForecast = 
            new Fisma_Chart(380, 275, 'chartFindForecast', 
                '/finding/dashboard/findingforecast/format/json');
        $chartFindForecast
            ->setTitle('Finding Forecast')
            ->addWidget('dayRangesStatChart', 'Day Ranges:', 'text', '0, 15, 30, 60, 90')
            ->addWidget(
                'forcastThreatLvl',
                'Finding Type:',
                'combo',
                'High, Moderate, and Low',
                array(
                    'Totals',
                    'High, Moderate, and Low',
                    'High',
                    'Moderate',
                    'Low'
                )
            );

        $this->view->chartFindForecast = $chartFindForecast->export();

        // Top-right chart - Findings Past Due
        $chartOverdueFinding = 
            new Fisma_Chart(380, 275, 'chartOverdueFinding', '/finding/dashboard/chartoverdue/format/json');
        $chartOverdueFinding
            ->setTitle('Findings Past Due')
            ->addWidget('dayRanges', 'Day Ranges:', 'text', '1, 30, 60, 90, 120')
            ->addWidget(
                'pastThreatLvl',
                'Finding Type:',
                'combo',
                'High, Moderate, and Low',
                array(
                    'Totals',
                    'High, Moderate, and Low',
                    'High',
                    'Moderate',
                    'Low'
                )
            );
        $this->view->chartOverdueFinding = $chartOverdueFinding->export();

        // Mid-left chart - Findings by Worklow Process
        $chartTotalStatus 
            = new Fisma_Chart(420, 275, 'chartTotalStatus', '/finding/dashboard/chartfinding/format/json');
        $chartTotalStatus
                ->setTitle('Findings by Workflow Process')
                ->addWidget(
                    'findingType',
                    'Finding Type:',
                    'combo',
                    'High, Moderate, and Low',
                    array(
                        'Totals',
                        'High, Moderate, and Low',
                        'High',
                        'Moderate',
                        'Low'
                    )
                );

        $this->view->chartTotalStatus = $chartTotalStatus->export();

        // Mid-right chart - Findings Without Corrective Actions
        $chartNoMit = new Fisma_Chart(380, 275);
        $chartNoMit
                ->setTitle('Findings Without Corrective Actions')
                ->setUniqueid('chartNoMit')
                ->setExternalSource('/finding/dashboard/chartfindnomitstrat/format/json')
                ->addWidget('dayRangesMitChart', 'Day Ranges:', 'text', '1, 30, 60, 90, 120')
                ->addWidget(
                    'noMitThreatLvl',
                    'Finding Type:',
                    'combo',
                    'High, Moderate, and Low',
                    array(
                        'Totals',
                        'High, Moderate, and Low',
                        'High',
                        'Moderate',
                        'Low'
                    )
                );
        $this->view->chartNoMit = $chartNoMit->export();

        // Bottom-Upper chart - Open Findings By Organization
        $findingOrgChart = new Fisma_Chart(400, 275, 'findingOrgChart');
        $findingOrgChart
                ->setTitle('Open Findings By Organization')
                ->setExternalSource('/finding/dashboard/chartfindingbyorgdetail/format/json')
                ->addWidget(
                    'displayBy',
                    'Display By:',
                    'combo',
                    'Organization',
                    array(
                        'Agency',
                        'Bureau',
                        'Organization',
                        'System',
                        'GSS and Majors'
                    )
                )
                ->addWidget(
                    'threatLevel',
                    'Threat Level:',
                    'combo',
                    'Totals',
                    array(
                        'Totals',
                        'High, Moderate, and Low',
                        'High',
                        'Moderate',
                        'Low'
                    )
                );
                
        $this->view->findingOrgChart = $findingOrgChart->export();

        // Bottom-Bottom chart - Current Security Control Deficiencies
        $controlDeficienciesChart = new Fisma_Chart();
        $controlDeficienciesChart
                ->setTitle('Current Security Control Deficiencies')
                ->setUniqueid('chartSecurityControlDeficiencies')
                ->setWidth(800)
                ->setHeight(275)
                ->setChartType('bar')
                ->setExternalSource('/security-control-chart/control-deficiencies/format/json')
                ->setAlign('center')
                ->addWidget(
                    'displaySecurityBy',
                    'Display By:',
                    'combo',
                    'Family',
                    array(
                        'Family',
                        'Family and Control Number'
                    )
                );

        $this->view->controlDeficienciesChart = $controlDeficienciesChart->export();
    }

    /**
     * Calculate Organization statistics based on params.
     * Params expected by $this->_request->getParam(...)
     * Expected params: displayBy
     * Returns exported Fisma_Chart
     *
     * @return array
     */
    public function chartfindingbyorgdetailAction()
    {
        $displayBy = urldecode($this->_request->getParam('displayBy'));
        $displayBy = strtolower($displayBy);

        $threatLevel = urldecode($this->_request->getParam('threatLevel'));
        $threatLevel = strtolower($threatLevel);
        
        if ($displayBy === 'everything') {
            
            $rtnChart = $this->_chartfindingorgbasic();
            
        } else {
        
            $rtnChart = new Fisma_Chart();
            $rtnChart
                ->setThreatLegendVisibility(true)
                ->setThreatLegendWidth(450)
                ->setAxisLabelY('Number of Findings')
                ->setChartType('stackedbar')
                ->setColors(
                    array(
                        "#FF0000",
                        "#FF6600",
                        "#FFC000"
                    )
                )
                ->setLayerLabels(
                    array(
                        'HIGH',
                        'MODERATE',
                        'LOW'
                    )
                );

            // get a list of requested organization-parent types (Agency-organizations, Bureau-organizations, gss, etc)
            $parents = $this->_getOrganizationsByOrgType($displayBy);

            // for each parent (foreach agency, or bBureau, etc)
            foreach ($parents as $thisParentOrg) {

                $childrenTotaled = $this->_getSumsOfOrgChildren($thisParentOrg['id']);

                // do not use association, high/mod/low is defined on the chart with Fisma_Chart->setLayerLabels()
                $childrenTotaled = array_values($childrenTotaled);

                $rtnChart->addColumn(
                    $thisParentOrg['nickname'],
                    $childrenTotaled
                );

            }
        }

        if ($rtnChart->isStacked() == true && $threatLevel !== 'High, Moderate, and Low') {
            switch ($threatLevel) {
            
                case 'totals':
                    $rtnChart
                        ->convertFromStackedToRegular()
                        ->setColors(array('#3366FF'))
                        ->setThreatLegendVisibility(false);
                    break;
                case 'high':
                    $rtnChart
                        ->deleteLayer(1)
                        ->deleteLayer(0)
                        ->setColors(array('#FF0000'));
                    break;                        
                case 'moderate':
                    $rtnChart
                        ->deleteLayer(2)
                        ->deleteLayer(0)
                        ->setColors(array('#FF6600'));
                    break;
                case 'low';
                    $rtnChart
                        ->deleteLayer(2)
                        ->deleteLayer(1)
                        ->setColors(array('#FFC000'));
                    break;
            }
        }
        
        // set link
        $rtnChart->setLinks(
            '/finding/remediation/list/queryType/advanced' . 
            '/denormalizedStatus/textDoesNotContain/CLOSED' . 
            '/organization/organizationSubtree/#ColumnLabel#'
        );
        
        // the context switch will turn this array into a json reply (the responce to the external source)
        $this->view->chart = $rtnChart->export('array');
    }

    /**
     * Computes the sums of HIGH/MODERATE/LOW of all children reported from _getAllChildrenOfOrg($orgId)
     *
     * @return array
     */
    private function _getSumsOfOrgChildren($orgId)
    {
    
        // get all children of the given organization id
        $childList = $this->_getAllChildrenOfOrg($orgId);
    
        $totalHigh = 0;
        $totalMod = 0;
        $totalLow = 0;
    
        // for each organization (that is a child of $orgId)
        foreach ($childList as $thisChildOrg) {
            
            // for each threat level total (of findings) of this organization (high.mod,low)
            foreach ($thisChildOrg['Findings'] as $thisThreatLvl) {
            
                switch ($thisThreatLvl['threatLevel']) {
                    case 'HIGH':
                        $totalHigh += $thisThreatLvl['COUNT'];
                        break;
                    case 'MODERATE':
                        $totalMod += $thisThreatLvl['COUNT'];
                        break;
                    case 'LOW':
                        $totalLow += $thisThreatLvl['COUNT'];
                        break;
                }
                
            }
            
        }
        
        return array('HIGH' => $totalHigh, 'MODERATE' => $totalMod, 'LOW' => $totalLow);
    }
    
    /**
     * Gets a list of organizations that are children of the given organization id, and 
     * the count of their findings associated with them (seperate by threat level)
     * returns an array strict of
     * array(
     *   'id'       => this organization id
     *   'nickname' => Organization nickname
     *   'Findings' =>
     *      array(
     *          array(
     *              'threatLevel' => LOW/MODERATE/HIGH
     *              'COUNT' => Number of findings with this threatLevel and in this org
     *          )
     *      )
     *  )
     *
     * @return array
     */
    private function _getAllChildrenOfOrg($orgId, $includeParent = true)
    {
        // get the left and right nodes (lft and rgt) of the target system from the system table
        $q = Doctrine_Query::create();
        $q
            ->addSelect('lft, rgt')
            ->from('Organization o')
            ->where('id = ?', $orgId)
            ->setHydrationMode(Doctrine::HYDRATE_ARRAY);
        $row = $q->execute();
        $row = $row[0];     // we are only expecting 1 row result
        $parLft = $row['lft'];
        $parRgt = $row['rgt'];

        $q = Doctrine_Query::create();
        $q
            ->addSelect('COUNT(f.id), o.id, o.nickname, f.threatlevel')
            ->from('Organization o')
            ->leftJoin('o.Findings f')
            ->whereIn('f.responsibleorganizationid=o.id')
            ->andWhereIn('f.responsibleOrganizationId ', $this->_myOrgSystemIds)
            ->where($parLft . ' < o.lft')
            ->andWhere('f.status <> "CLOSED"')
            ->andWhere($parRgt . ' > o.rgt')
            ->groupBy('o.nickname, f.threatlevel')
            ->setHydrationMode(Doctrine::HYDRATE_ARRAY);

        $rtn = $q->execute();
        
        if ($includeParent === true && array_search($orgId, $this->_myOrgSystemIds) !== false) {

            $q = Doctrine_Query::create();
            $q
                ->addSelect('COUNT(f.id), o.id, o.nickname, f.threatlevel')
                ->from('Organization o')
                ->leftJoin('o.Findings f')
                ->whereIn('f.responsibleorganizationid=o.id')
                ->where('o.id = ?', $orgId)
                ->andWhere('f.status <> "CLOSED"')
                ->groupBy('o.nickname, f.threatlevel')
                ->setHydrationMode(Doctrine::HYDRATE_ARRAY);

            $rtn = array_merge($rtn, $q->execute());
        }
        
        return $rtn;
    }
    
    /**
     * Gets a list of organizations that are at the leven given
     * This is usefull for obtaining Agency and Bureau IDs
     * Returns array('id','nickname') for each result in an array
     *
     * @return array
     */
    private function _getOrganizationsByOrgType($orgType)
    {

        if ($orgType === 'major') {
            
            $q = Doctrine_Query::create();
            $q
                ->addSelect('o.id, o.nickname')
                ->from('Organization o')
                ->leftJoin('o.System s')
                ->where('s.type = ?', $orgType)
                ->whereIn('o.id ', $this->_myOrgSystemIds)
                ->setHydrationMode(Doctrine::HYDRATE_ARRAY)
                ->orderBy('o.nickname');

            return $q->execute();
            
        } elseif ($orgType === 'gss and majors') {
            
            $q = Doctrine_Query::create();
            $q
                ->addSelect('o.id, o.nickname')
                ->from('Organization o')
                ->leftJoin('o.System s')
                ->where('s.type = "gss" OR s.type = "major"')
                ->whereIn('o.id ', $this->_myOrgSystemIds)
                ->setHydrationMode(Doctrine::HYDRATE_ARRAY)
                ->orderBy('o.nickname');

            return $q->execute();
        
        } else {
        
            $q = Doctrine_Query::create();
            $q
                ->addSelect('id, nickname')
                ->from('Organization o')
                ->where('orgtype = ?', $orgType)
                ->whereIn('o.id ', $this->_myOrgSystemIds)
                ->setHydrationMode(Doctrine::HYDRATE_ARRAY)
                ->orderBy('o.nickname');

            return $q->execute();
        }
    }

    public function chartfindingAction()
    {
        $displayBy = urldecode($this->_request->getParam('displayBy'));
        $rtnChart = $this->_chartfindingstatus();

        // export as array, the context switch will translate it to a JSON responce
        $this->view->chart = $rtnChart->export('array');
    }

    public function chartoverdueAction()
    {
        $dayRanges = str_replace(' ', '', urldecode($this->_request->getParam('dayRanges')));
        $dayRanges = explode(',', $dayRanges);
        $dayRanges[] = 365 * 10;    // The last ##+ column

        $findingType = urldecode($this->_request->getParam('pastThreatLvl'));

        $thisChart = new Fisma_Chart();
        $thisChart
            ->setChartType('stackedbar')
            ->setConcatColumnLabels(false)
            ->setAxisLabelX('Number of Days Past Due')
            ->setAxisLabelY('Number of Findings')
            ->setColumnLabelAngle(0)
            ->setThreatLegendVisibility(true)
            ->setColors(
                array(
                    "#FF0000",
                    "#FF6600",
                    "#FFC000"
                )
            )
            ->setLayerLabels(
                array(
                    'HIGH',
                    'MODERATE',
                    'LOW'
                )
            );

        $nonStackedLinks = array();

        // Get counts in between the day ranges given
        for ($x = 0; $x < count($dayRanges) - 1; $x++) {

            $fromDayDiff = $dayRanges[$x];
            $fromDay = new Zend_Date();
            $fromDay->addDay($fromDayDiff);
            $fromDayStr = $fromDay->toString('YYY-MM-dd');
            
            $toDayDiff = $dayRanges[$x+1] - 1;
            $toDay = new Zend_Date();
            $toDay->addDay($toDayDiff);
            $toDayStr = $toDay->toString('YYY-MM-dd');

            $q = Doctrine_Query::create();
            $q
                ->addSelect('threatlevel threat, COUNT(f.id)')
                ->from('Finding f')
                ->where('f.nextduedate BETWEEN "' . $fromDayStr . '" AND "' . $toDayStr . '"')
                ->andWhere('f.status <> "CLOSED"')
                ->whereIn('f.responsibleOrganizationId ', $this->_myOrgSystemIds)
                ->groupBy('threatlevel')
                ->setHydrationMode(Doctrine::HYDRATE_ARRAY);
            $rslts = $q->execute();
            //$this->view->q = $q->getSqlQuery();

            // We will get three results, each for a count of High Mod, Low
            $thisHigh = 0;
            $thisMod = 0;
            $thisLow = 0;
            foreach ($rslts as $thisRslt) {
                switch ($thisRslt['threat']) {
                    case "LOW":
                        $thisLow = $thisRslt['COUNT'];
                        break;
                    case "MODERATE":
                        $thisMod = $thisRslt['COUNT'];
                        break;
                    case "HIGH":
                        $thisHigh = $thisRslt['COUNT'];
                        break;
                }
            }

            $thisFromDate = new Zend_Date();
            $thisFromDate = $thisFromDate->addDay($fromDayDiff)->toString('YYY-MM-dd');
            $thisToDate = new Zend_Date();
            $thisToDate = $thisToDate->addDay($toDayDiff)->toString('YYY-MM-dd');
            
            if ($x === count($dayRanges) - 2) {
                $thisColLabel = $dayRanges[$x] . '+';
            } else {
                $thisColLabel = $fromDayDiff . '-' . $toDayDiff;
            }
            
            // The links to associate with entire columns when this is not a stacked bar chart
            $nonStackedLinks[] = '/finding/remediation/list/queryType/advanced' .
                '/denormalizedStatus/textDoesNotContain/CLOSED' . 
                '/nextDueDate/dateBetween/' . $thisFromDate . '/' . $thisToDate;            

            $thisChart->addColumn(
                $thisColLabel,
                array(
                    $thisHigh,
                    $thisMod,
                    $thisLow
                ),
                array(
                    '/finding/remediation/list/queryType/advanced' . 
                    '/denormalizedStatus/textDoesNotContain/CLOSED' . 
                    '/nextDueDate/dateBetween/' . $thisFromDate . '/' . $thisToDate .
                    '/threatLevel/enumIs/HIGH',
                    '/finding/remediation/list/queryType/advanced' . 
                    '/denormalizedStatus/textDoesNotContain/CLOSED' . 
                    '/nextDueDate/dateBetween/' . $thisFromDate . '/' . $thisToDate .
                    '/threatLevel/enumIs/MODERATE',
                    '/finding/remediation/list/queryType/advanced' . 
                    '/denormalizedStatus/textDoesNotContain/CLOSED' .
                    '/nextDueDate/dateBetween/' . $thisFromDate . '/' . $thisToDate .
                    '/threatLevel/enumIs/LOW'
                )
            );
        }

        // What should we filter/show on the chart? Totals? Migh,Mod,Low? etc...
        
        switch (strtolower($findingType)) {
            case "totals":
                // Crunch numbers
                $thisChart
                    ->convertFromStackedToRegular()
                    ->setThreatLegendVisibility(false)
                    ->setColors(array('#3366FF'))
                    ->setLinks($nonStackedLinks);
                break;
            case "high, moderate, and low":
                // $thisChart is already in this form
                break;
            case "high":
                // remove the Low and Moderate columns/layers
                $thisChart->deleteLayer(2);
                $thisChart->deleteLayer(1);
                $thisChart->setColors(array('#FF0000'));
                break;
            case "moderate":
                // remove the Low and High columns/layers
                $thisChart->deleteLayer(2);
                $thisChart->deleteLayer(0);
                $thisChart->setColors(array('#FF6600'));
                break;
            case "low":
                // remove the Moderate and High columns/layers
                $thisChart->deleteLayer(1);
                $thisChart->deleteLayer(0);
                $thisChart->setColors(array('#FFC000'));
                break;
        }

        $this->view->chart = $thisChart->export('array');
    }

    /**
     * Calculate the finding statistics by Org
     *
     * @return Fisma_Chart
     */
    private function _chartfindingorgbasic()
    {
        $findingType = urldecode($this->_request->getParam('threatLevel'));

        if ($findingType === 'Totals') {

            $thisChart = new Fisma_Chart();
            $thisChart
                ->setChartType('bar')
                ->setConcatColumnLabels(false)
                ->setAxisLabelY('Number of Findings')
                ->setColors(array('#3366FF'));

            $q = Doctrine_Query::create()
                ->select('count(*), nickname')
                ->from('Organization o')
                ->leftJoin('o.Findings f')
                ->groupBy('o.id')
                ->orderBy('o.nickname')
                ->where('f.status <> "CLOSED"')
                ->setHydrationMode(Doctrine::HYDRATE_ARRAY);
            $orgCounts = $q->execute();

            foreach ($orgCounts as $thisOrg) {

                $thisChart->addColumn(
                    $thisOrg['nickname'],
                    $thisOrg['count'],
                    '/finding/remediation/list/queryType/advanced' .
                    '/denormalizedStatus/textDoesNotContain/CLOSED' . 
                    '/organization/textExactMatch/' . $thisOrg['nickname']
                );

            }

            return $thisChart;

        } elseif ($findingType === 'High, Moderate, and Low') {

            $thisChart = new Fisma_Chart();
            $thisChart
                ->setChartType('stackedbar')
                ->setThreatLegendVisibility(true)
                ->setThreatLegendWidth(450)
                ->setConcatColumnLabels(true)
                ->setAxisLabelY('Number of Findings')
                ->setColors(
                    array(
                        "#FF0000",
                        "#FF6600",
                        "#FFC000"
                    )
                )
                ->setLayerLabels(
                    array(
                        'HIGH',
                        'MODERATE',
                        'LOW'
                    )
                );

            $q = Doctrine_Query::create()
                ->select('count(f.threatlevel), nickname, f.threatlevel')
                ->from('Organization o')
                ->leftJoin('o.Findings f')
                ->groupBy('o.id, f.threatlevel')
                ->orderBy('o.nickname, f.threatlevel')
                ->where('f.status <> "CLOSED"')
                ->setHydrationMode(Doctrine::HYDRATE_ARRAY);

            $orgCounts = $q->execute();

            foreach ($orgCounts as $thisOrg) {

                // initalize counts to 0
                $thisHigh = 0;
                $thisMod = 0;
                $thisLow = 0;

                foreach ($thisOrg['Findings'] as $thisLevel) {
                    switch ($thisLevel['threatLevel']) {
                        case 'LOW':
                            $thisHigh = $thisLevel['count'];
                            break;
                        case 'MODERATE':
                            $thisMod = $thisLevel['count'];
                            break;
                        case 'HIGH':
                            $thisLow = $thisLevel['count'];
                            break;
                    }
                }

                $thisChart->addColumn(
                    $thisOrg['nickname'],
                    array(
                        $thisLow,
                        $thisMod,
                        $thisHigh
                    ),
                    array(
                        '/finding/remediation/list/queryType/advanced/' .
                        '/denormalizedStatus/textDoesNotContain/CLOSED' .
                        'organization/textExactMatch/' . $thisOrg['nickname'] .
                        '/threatLevel/enumIs/HIGH',
                        '/finding/remediation/list/queryType/advanced/' .
                        '/denormalizedStatus/textDoesNotContain/CLOSED' .
                        'organization/textExactMatch/' . $thisOrg['nickname'] .
                        '/threatLevel/enumIs/MODERATE',
                        '/finding/remediation/list/queryType/advanced/' .
                        '/denormalizedStatus/textDoesNotContain/CLOSED' . 
                        'organization/textExactMatch/' . $thisOrg['nickname'] .
                        '/threatLevel/enumIs/LOW'
                    )
                );
            }

            return $thisChart;

        } else {
            // findingType is High, Mod, or Low

            $thisChart = new Fisma_Chart();
            $thisChart
                ->setChartType('bar')
                ->setConcatColumnLabels(false)
                ->setAxisLabelY('Number of Findings');

            // Decide color of every bar based on High/Mod/Low
            switch (strtoupper($findingType)) {
            case 'HIGH':
                $thisChart->setColors(array('#FF0000'));    // red
                break;
            case 'MODERATE':
                $thisChart->setColors(array('#FF6600'));    // orange
                break;
            case 'LOW':
                $thisChart->setColors(array('#FFC000'));    // yellow
                break;
            }

            $q = Doctrine_Query::create()
                ->select('count(f.threatlevel), nickname, f.threatlevel')
                ->from('Organization o')
                ->leftJoin('o.Findings f')
                ->groupBy('o.id')
                ->orderBy('o.nickname, f.threatlevel')
                ->where('f.threatlevel = ?', strtoupper($findingType))
                ->andWhere('f.status <> "CLOSED"')
                ->setHydrationMode(Doctrine::HYDRATE_ARRAY);

            $orgThisThreatCounts = $q->execute();

            foreach ($orgThisThreatCounts as $thisThreatCount) {
                $thisChart->addColumn(
                    $thisThreatCount['nickname'],
                    $thisThreatCount['count'],
                    '/finding/remediation/list/queryType/advanced' . 
                    '/denormalizedStatus/textDoesNotContain/CLOSED' . 
                    '/organization/textExactMatch/' . $thisThreatCount['nickname'] .
                    '/threatLevel/enumIs/' . strtoupper($findingType)
                );
            }

            return $thisChart;
        }
    }

    /**
     * Calculate the finding statistics by status
     *
     * @return Fisma_Chart
     */
    private function _chartfindingstatus()
    {
        $findingType = urldecode($this->_request->getParam('findingType'));

        $thisChart = new Fisma_Chart();
        $thisChart
            ->setChartType('stackedbar')
            ->setThreatLegendVisibility(true)
            ->setColors(
                array(
                    "#FF0000",
                    "#FF6600",
                    "#FFC000"
                )
            )
            ->setLayerLabels(
                array(
                    'High',
                    'Moderate',
                    'Low'
                )
            );

        $q = Doctrine_Query::create()
            ->select('count(f.id), threatlevel, denormalizedstatus')
            ->from('Finding f')
            ->groupBy('f.denormalizedstatus, f.threatlevel')
            ->orderBy('f.denormalizedstatus, f.threatlevel')
            ->where('f.status <> "CLOSED"')
            ->whereIn('f.responsibleOrganizationId ', $this->_myOrgSystemIds)
            ->setHydrationMode(Doctrine::HYDRATE_ARRAY);
        $rslts = $q->execute();

        // sort results into $sortedRslts[FindingStatusName][High/Mod/Low], where sortedRslts[][] = TheCount
        $sortedRslts = array();
        foreach ($rslts as $thisRslt) {

            if (empty($sortedRslts[$thisRslt['denormalizedStatus']])) {
                $sortedRslts[$thisRslt['denormalizedStatus']] = array();
            }

            $sortedRslts[$thisRslt['denormalizedStatus']][$thisRslt['threatLevel']] = $thisRslt['count'];
        }
        
        $nonStackedLinks = array();
        
        // Go in order adding columns to chart; New,Draft,MS ISSO, MS IV&V, EN, EV ISSO, EV IV&V
        for ($x = 0; $x < 7; $x++) {

            // Which status are we adding this time? (this will be the column label on the chart)
            switch ($x) {
            case 0:
                $thisStatus = 'NEW';
                break;
            case 1:
                $thisStatus = 'DRAFT';
                break;
            case 2:
                $thisStatus = 'MS ISSO';
                break;
            case 3:
                $thisStatus = 'MS IV&V';
                break;
            case 4:
                $thisStatus = 'EN';
                break;
            case 5:
                $thisStatus = 'EV ISSO';
                break;
            case 6:
                $thisStatus = 'EV IV&V';
                break;
            }

            // get Counts of High,MOd,Low. Also MySQL may not return 0s, assume 0 on empty
            if (!empty($sortedRslts[$thisStatus]['HIGH'])) {
                $highCount = $sortedRslts[$thisStatus]['HIGH'];
            } else {
                $highCount = 0;
            }
            
            if (!empty($sortedRslts[$thisStatus]['MODERATE'])) {
                $modCount = $sortedRslts[$thisStatus]['MODERATE'];
            } else {
                $modCount = 0;
            }
            
            if (!empty($sortedRslts[$thisStatus]['LOW'])) {
                $lowCount = $sortedRslts[$thisStatus]['LOW'];
            } else {
                $lowCount = 0;
            }

            // Prepare for a stacked-bar chart (these are the counts on each stack within the column)
            $addColumnCounts = array($highCount, $modCount, $lowCount);

            // Make each area of the chart link
            $basicLink = '/finding/remediation/list/queryType/advanced' .
                '/denormalizedStatus/textExactMatch/' . strtoupper($thisStatus);
            $nonStackedLinks[] = $basicLink;
            $stackedLinks = array(
                $basicLink . '/threatLevel/enumIs/HIGH',
                $basicLink . '/threatLevel/enumIs/MODERATE',
                $basicLink . '/threatLevel/enumIs/LOW'
            );
            
            // Create this column as a stacked-bar chart for now (filtration later in function)
            $thisChart->addColumn(
                $thisStatus,
                $addColumnCounts,
                $stackedLinks
            );
        }

        // Show, hide and filter chart data as requested
        switch (strtolower($findingType)) {
            case "totals":
                // Crunch numbers
                $thisChart
                    ->convertFromStackedToRegular()
                    ->setLinks($nonStackedLinks)
                    ->setThreatLegendVisibility(false)
                    ->setColors(
                        array(
                            '#CECECE',
                            '#67F967',
                            '#FFCACA',
                            '#FF2424',
                            '#FF9E3D',
                            '#CACAFF',
                            '#2424FF'
                        )
                    );
                break;
            case "high, moderate, and low":
                // $thisChart is already in this form
                break;
            case "high":
                // remove the Low and Moderate columns/layers
                $thisChart->deleteLayer(2);
                $thisChart->deleteLayer(1);
                $thisChart->setColors(array('#FF0000'));
                break;
            case "moderate":
                // remove the Low and High columns/layers
                $thisChart->deleteLayer(2);
                $thisChart->deleteLayer(0);
                $thisChart->setColors(array('#FF6600'));
                break;
            case "low":
                // remove the Moderate and High columns/layers
                $thisChart->deleteLayer(1);
                $thisChart->deleteLayer(0);
                $thisChart->setColors(array('#FFC000'));
                break;
        }

        return $thisChart;

    }

    /**
     * Calculate "finding forcast" data for a chart based on finding.currentecd in the database
     *
     * @return void
     */
    public function chartfindnomitstratAction()
    {
        $dayRange = $this->_request->getParam('dayRangesMitChart');
        $dayRange = str_replace(' ', '', $dayRange);
        $dayRange = explode(',', $dayRange);

        $threatLvl = $this->_request->getParam('noMitThreatLvl');

        $noMitChart = new Fisma_Chart();
        $noMitChart
            ->setAxisLabelX('Number of Days Without Mitigation Strategy')
            ->setAxisLabelY('Number of Findings')
            ->setChartType('stackedbar')
            ->setThreatLegendVisibility(true)
            ->setColumnLabelAngle(0)
            ->setColors(
                array(
                    "#FF0000",
                    "#FF6600",
                    "#FFC000"
                )
            )
            ->setConcatColumnLabels(false)
            ->setLayerLabels(
                array(
                    'High',
                    'Moderate',
                    'Low'
                )
            );
            
        $nonStackedLinks = array();

        for ($x = 0; $x < count($dayRange) - 1; $x++) {
            
            $fromDayInt = $dayRange[$x];
            $fromDay = new Zend_Date();
            $fromDay = $fromDay->addDay($fromDayInt);
            $fromDayStr = $fromDay->toString('YYY-MM-dd');
            
            $toDayInt = $dayRange[$x+1];
            $toDay = new Zend_Date();
            $toDay = $toDay->addDay($toDayInt);
            $toDayStr = $toDay->toString('YYY-MM-dd');
            
            if ($x === count($dayRange) - 2) {
                $thisColumnLabel = $fromDayInt . '-' . $toDayInt;
            } else {
                $toDay->addDay(-1);
                $toDayStr = $toDay->toString('YYY-MM-dd');
                $toDayInt--;
                $thisColumnLabel = $fromDayInt . '-' . $toDayInt;
            }

            // Get the count of High findings
            $q = Doctrine_Query::create()
                ->select('count(f.id), f.threatlevel')
                ->from('Finding f')
                ->where('f.status="NEW" OR f.status="DRAFT"')
                ->andWhere('f.status <> "CLOSED"')
                ->andWhere('f.createdts BETWEEN "' . $fromDayStr . '" AND "' . $toDayStr . '"')
                ->groupBy('f.threatlevel')
                ->whereIn('f.responsibleOrganizationId ', $this->_myOrgSystemIds)
                ->setHydrationMode(Doctrine::HYDRATE_ARRAY);
            $rslts = $q->execute();
            
            // initalize to 0 (query may not return values for 0 counts)
            $thisHigh = 0;
            $thisMod = 0;
            $thisLow = 0;
            
            foreach ($rslts as $thisLevel) {
                switch ($thisLevel['threatLevel']) {
                    case 'LOW':
                        $thisHigh = $thisLevel['count'];
                        break;
                    case 'MODERATE':
                        $thisMod = $thisLevel['count'];
                        break;
                    case 'HIGH':
                        $thisLow = $thisLevel['count'];
                        break;
                }
            }
            
            // make URL to the search page with date params
            $basicSearchLink = '/finding/remediation/list/queryType/advanced' . 
                '/denormalizedStatus/textDoesNotContain/CLOSED' . 
                '/createdTs/dateBetween/' . $fromDayStr . '/' . $toDayStr;
                
            // make this url filter out CLOSED, EN, and anything on evaluation.nickname (MS ISSO, EV ISSO, etc)
            $basicSearchLink .= '/denormalizedStatus/textDoesNotContain/CLOSED';
            $basicSearchLink .= '/denormalizedStatus/textDoesNotContain/EN';
            foreach ($this->_getEvaluationNames() as $thisStatus) {
                $basicSearchLink .= '/denormalizedStatus/textDoesNotContain/' . $thisStatus;
            }
            
            // remembers links for a non-stacked bar chart in the even the user is querying "totals"
            $nonStackedLinks[] = $basicSearchLink;
            
            $noMitChart->addColumn(
                $thisColumnLabel,
                array(
                    $thisHigh,
                    $thisMod,
                    $thisLow
                ),
                array(
                    $basicSearchLink . '/threatLevel/enumIs/HIGH',
                    $basicSearchLink . '/threatLevel/enumIs/MODERATE',
                    $basicSearchLink . '/threatLevel/enumIs/LOW'
                )
            );

        }

        // Show, hide and filter data on the chart as requested
        switch (strtolower($threatLvl)) {
            case "totals":
                // Crunch numbers
                $noMitChart
                    ->convertFromStackedToRegular()
                    ->setThreatLegendVisibility(false)
                    ->setColors(array('#3366FF'))
                    ->setLinks($nonStackedLinks);
                break;
            case "high, moderate, and low":
                // $noMitChart is already in this form
                break;
            case "high":
                // remove the Low and Moderate columns/layers
                $noMitChart->deleteLayer(2);
                $noMitChart->deleteLayer(1);
                $noMitChart->setColors(array('#FF0000'));
                break;
            case "moderate":
                // remove the Low and High columns/layers
                $noMitChart->deleteLayer(2);
                $noMitChart->deleteLayer(0);
                $noMitChart->setColors(array('#FF6600'));
                break;
            case "low":
                // remove the Moderate and High columns/layers
                $noMitChart->deleteLayer(1);
                $noMitChart->deleteLayer(0);
                $noMitChart->setColors(array('#FFC000'));
                break;
        }

        // export as array, the context switch will translate it to a JSON responce
        $this->view->chart = $noMitChart->export('array');
    }

    /**
     * Gets all nicknames from the evaluation table
     *
     * @return array
     */
    private function _getEvaluationNames()
    {
        $q = Doctrine_Query::create()
            ->select('nickname')
            ->from('Evaluation e')
            ->setHydrationMode(Doctrine::HYDRATE_ARRAY);
        $results = $q->execute();        

        $rtn = array();
        foreach ($results as $thisEval) {
            $rtn[] = $thisEval['nickname'];
        }
        
        return $rtn;
    }

    /**
     * Calculate "finding forcast" data for a chart based on finding.currentecd in the database
     *
     * @return void
     */
    public function findingforecastAction()
    {

        $dayRange = $this->_request->getParam('dayRangesStatChart');
        $dayRange = str_replace(' ', '', $dayRange);
        $dayRange = explode(',', $dayRange);
        
        $threatLvl = $this->_request->getParam('forcastThreatLvl');

        $highCount = array();
        $modCount = array();
        $lowCount = array();
        $chartDataText = array();
        $totalChartLinks = array();

        $thisChart = new Fisma_Chart();
        $thisChart
            ->setChartType('stackedbar')
            ->setConcatColumnLabels(false)
            ->setColumnLabelAngle(0)
            ->setThreatLegendVisibility(true)
            ->setAxisLabelX('Number of Days Until Overdue')
            ->setAxisLabelY('Number of Findings')
            ->setLayerLabels(
                array(
                    'High',
                    'Moderate',
                    'Low'
                )
            )
            ->setColors(
                array(
                    "#FF0000",
                    "#FF6600",
                    "#FFC000"
                )
            );

        for ($x = 0; $x < count($dayRange) - 1; $x++) {

            $fromDay = new Zend_Date();
            $fromDay = $fromDay->addDay($dayRange[$x]);
            $fromDayStr = $fromDay->toString('YYY-MM-dd');

            $toDay = new Zend_Date();
            $toDay = $toDay->addDay($dayRange[$x+1]);
            
            if ($x === count($dayRange) - 2) {
                $thisColumnLabel = $dayRange[$x] . '-' . $dayRange[$x + 1];
            } else {
                $toDay->addDay(-1);
                $thisColumnLabel = $dayRange[$x] . '-' . ( $dayRange[$x + 1] - 1 );
            }
            
            $toDayStr = $toDay->toString('YYY-MM-dd');

            // Get the count of High,Mod,Low findings
            $q = Doctrine_Query::create()
                ->select('COUNT(f.id), f.threatlevel')
                ->from('Finding f')
                ->where('f.currentecd BETWEEN "' . $fromDayStr . '" AND "' . $toDayStr . '"')
                ->andWhere('f.status <> "CLOSED"')
                ->whereIn('f.responsibleOrganizationId ', $this->_myOrgSystemIds)
                ->groupBy('f.threatlevel')
                ->setHydrationMode(Doctrine::HYDRATE_ARRAY);
            $results = $q->execute();
            $this->view->rtn = $results;

            $highCount = $modCount = $lowCount = 0;
            foreach ($results as $thisRslt) {
                switch ($thisRslt['threatLevel']) {
                    case 'HIGH':
                        $highCount = $thisRslt['COUNT'];
                        break;
                    case 'MODERATE':
                        $modCount = $thisRslt['COUNT'];
                        break;
                    case 'LOW':
                        $lowCount = $thisRslt['COUNT'];
                        break;
                }
            }

            // Add column assuming this is a stacked-bar chart with High, Mod, and Low findings
            $thisChart
                ->addColumn(
                    $thisColumnLabel,
                    array(
                        $highCount,
                        $modCount,
                        $lowCount
                    ),
                    array('/finding/remediation/list/queryType/advanced' .
                        '/denormalizedStatus/textDoesNotContain/CLOSED' .
                        '/currentEcd/dateBetween/' . 
                        $fromDay->toString('YYYY-MM-dd').'/'.$toDay->toString('YYYY-MM-dd') .
                        '/threatLevel/enumIs/HIGH',
                        '/finding/remediation/list/queryType/advanced' .
                        '/denormalizedStatus/textDoesNotContain/CLOSED' . 
                        '/currentEcd/dateBetween/' . 
                        $fromDay->toString('YYYY-MM-dd').'/'.$toDay->toString('YYYY-MM-dd') .
                        '/threatLevel/enumIs/MODERATE',
                        '/finding/remediation/list/queryType/advanced' . 
                        '/denormalizedStatus/textDoesNotContain/CLOSED' . 
                        '/currentEcd/dateBetween/' . 
                        $fromDay->toString('YYYY-MM-dd').'/'.$toDay->toString('YYYY-MM-dd') .
                        '/threatLevel/enumIs/LOW'
                    )
                );
                
            // Note the links to set in the even this is a totals (basic-bar) chart
            $totalChartLinks[] = '/finding/remediation/list/queryType/advanced' .
                '/denormalizedStatus/textDoesNotContain/CLOSED' . 
                '/currentEcd/dateBetween/' . $fromDay->toString('YYYY-MM-dd').'/'.$toDay->toString('YYYY-MM-dd');
        }

        // Show, hide and filter chart data as requested
        switch (strtolower($threatLvl)) {
            case "totals":
                // Crunch numbers
                $thisChart
                    ->convertFromStackedToRegular()
                    ->setLinks($totalChartLinks)
                    ->setThreatLegendVisibility(false)
                    ->setColors(array('#3366FF'));
                break;
            case "high, moderate, and low":
                // $thisChart is already in this form
                break;
            case "high":
                // remove the Low and Moderate columns/layers
                $thisChart->deleteLayer(2);
                $thisChart->deleteLayer(1);
                $thisChart->setColors(array('#FF0000'));
                break;
            case "moderate":
                // remove the Low and High columns/layers
                $thisChart->deleteLayer(2);
                $thisChart->deleteLayer(0);
                $thisChart->setColors(array('#FF6600'));
                break;
            case "low":
                // remove the Moderate and High columns/layers
                $thisChart->deleteLayer(1);
                $thisChart->deleteLayer(0);
                $thisChart->setColors(array('#FFC000'));
                break;
        }

        // export as array, the context switch will translate it to a JSON responce
        $this->view->chart = $thisChart->export('array');
    }

}

