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
        
        $this->_helper->fismaContextSwitch()
                      ->addActionContext('chartoverdue', 'json')
                      ->addActionContext('chartfindingstatus', 'json')
                      ->addActionContext('total-type', 'json')
                      ->addActionContext('findingforecast', 'json')
                      ->addActionContext('chartfindnomitstrat', 'json')
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
            = new Fisma_Chart(420, 275, 'chartTotalStatus', '/dashboard/chart-finding/format/json');
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
        $securityFamilies = $this->_getSecurityControleFamilies();
        foreach ($securityFamilies as &$familyName) {
            $familyName = 'Family: ' . $familyName;
        }
        array_unshift($securityFamilies, 'Family Summary');
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
                    'Family Summary',
                    $securityFamilies
                );

        $this->view->controlDeficienciesChart = $controlDeficienciesChart->export();
    }
    
    /**
     * Gets a list of all Security Controle Families that have
     * findings associated with them, and can be seen from the
     * current user (ACL).
     *
     * @return array
     */
    private function _getSecurityControleFamilies()
    {
        // Dont query if there are no organizations this user can see
        $visibleOrgs = FindingTable::getOrganizationIds();
        if (empty($visibleOrgs)) {
            return array();
        }

        $families = Doctrine_Query::create()
            ->select('SUBSTRING_INDEX(sc.code, "-", 1) fam')
            ->from('SecurityControl sc')
            ->innerJoin('sc.Findings f')
            ->innerJoin('f.ResponsibleOrganization o')
            ->andWhere('f.status <> ?', 'CLOSED')
            ->whereIn('o.id', $visibleOrgs)
            ->groupBy('fam')
            ->orderBy('fam')
            ->setHydrationMode(Doctrine::HYDRATE_SCALAR)
            ->execute();
        
        $familyArray = array();        
        foreach ($families as $famResult)
            $familyArray[] = $famResult['sc_fam'];

        return $familyArray;
    }

    /**
     * Calculate Organization statistics based on params.
     * Params expected by $this->getRequest()->getParam(...)
     * Expected params: displayBy
     * Returns exported Fisma_Chart
     *
     * @return array
     */
    public function chartfindingbyorgdetailAction()
    {
        $displayBy = urldecode($this->getRequest()->getParam('displayBy'));
        $displayBy = strtolower($displayBy);

        $threatLevel = urldecode($this->getRequest()->getParam('threatLevel'));
        $threatLevel = strtolower($threatLevel);
        
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
                    'Null',
                    'HIGH',
                    'MODERATE',
                    'LOW'
                )
            );
            
        // Dont query if there are no organizations this user can see
        $visibleOrgs = FindingTable::getOrganizationIds();
        if (empty($visibleOrgs)) {
            $this->view->chart = $rtnChart->export('array');
            return;
        }
        
        $basicLink =
            '/finding/remediation/list?q=' . 
            '/denormalizedStatus/textDoesNotContain/CLOSED' . 
            '/organization/organizationSubtree/';
        
        if ($displayBy === 'system') {
            
            /* Because of the number of systems this query involves, and the fact
                that Systems shouldnt have children (unlike Bureaus for example) 
                a different query will be used here */
                
                $systemCountsQuery = Doctrine_Query::create();
                $systemCountsQuery->addSelect('COUNT(f.id), o.nickname, f.threatLevel')
                    ->from('Finding f')
                    ->leftJoin('f.ResponsibleOrganization o')
                    ->where('o.orgtype = "system"')
                    ->whereIn('o.id ', FindingTable::getOrganizationIds())
                    ->groupBy('o.nickname, f.threatLevel')
                    ->orderBy('o.nickname')
                    ->setHydrationMode(Doctrine::HYDRATE_SCALAR);
                $systemCounts = $systemCountsQuery->execute();
                
                $findingCounts = array('NULL' => 0, 'HIGH' => 0, 'MODERATE' => 0, 'LOW' => 0);
                foreach ($systemCounts as $systemCountInfo) {
                    
                    $orgName = $systemCountInfo['o_nickname'];
                    
                    if (!empty($lastResultOrg) && $systemCountInfo['o_nickname'] !== $lastResultOrg) {
                        // then all high/mod/low counts for the lastResultOrg organization have been scanned through
                        
                        $rtnChart->addColumn(
                            $orgName,
                            array_values($findingCounts),
                            array(
                                '',
                                $basicLink . $orgName . '/threatLevel/enumIs/HIGH',
                                $basicLink . $orgName . '/threatLevel/enumIs/MODERATE',
                                $basicLink . $orgName . '/threatLevel/enumIs/LOW'
                            )
                        );
                        
                        $findingCounts = array('Null' => 0, 'HIGH' => 0, 'MODERATE' => 0, 'LOW' => 0);
                        $lastOrgChartted = $orgName;
                    }
                    
                    if (in_array($systemCountInfo['f_threatLevel'], $findingCounts)) {
                        // findingCounts [ of this threatLevel ] = number of findings of this threatLevel
                        $findingCounts[$systemCountInfo['f_threatLevel']] = $systemCountInfo['f_COUNT'];
                    } else {
                        $findingCounts['NULL'] = $systemCountInfo['f_COUNT'];
                    }
                    
                    $lastResultOrg = $orgName;
                    
                }

                // Was the last organization in the systemCounts array added to the chart?
                if ($orgName !== $lastOrgChartted) {
                    $rtnChart->addColumn(
                        $orgName,
                        $findingCounts,
                        array(
                            '',
                            $basicLink . $thisParentOrg['o_nickname'] . '/threatLevel/enumIs/HIGH',
                            $basicLink . $thisParentOrg['o_nickname'] . '/threatLevel/enumIs/MODERATE',
                            $basicLink . $thisParentOrg['o_nickname'] . '/threatLevel/enumIs/LOW'
                        )
                    );
                }                
                                
        } else {

            // Get a list of requested organization-parent types (Agency-organizations, Bureau-organizations, gss, etc)
            $parents = $this->_getOrganizationsByOrgType($displayBy);

            // For each parent (foreach agency, or bBureau, etc)
            foreach ($parents as $thisParentOrg) {

                $childrenTotaled = $this->_getSumsOfOrgChildren($thisParentOrg['id']);

                // Do not use association, high/mod/low is defined on the chart with Fisma_Chart->setLayerLabels()
                $childrenTotaled = array_values($childrenTotaled);

                $rtnChart->addColumn(
                    $thisParentOrg['nickname'],
                    $childrenTotaled,
                    array(
                        '',
                        $basicLink . $thisParentOrg['nickname'] . '/threatLevel/enumIs/HIGH',
                        $basicLink . $thisParentOrg['nickname'] . '/threatLevel/enumIs/MODERATE',
                        $basicLink . $thisParentOrg['nickname'] . '/threatLevel/enumIs/LOW'
                    )
                );

            }
        }

        switch ($threatLevel) {

            case 'high, moderate, and low':
                // Remove null-count layer/stack in this stacked bar chart
                $rtnChart->deleteLayer(0);
                break;
                
            case 'totals':
                $rtnChart
                    ->convertFromStackedToRegular()
                    ->setColors(array('#3366FF'))
                    ->setThreatLegendVisibility(false)
                    ->setLinks(
                        '/finding/remediation/list?q=' . 
                        '/denormalizedStatus/textDoesNotContain/CLOSED' . 
                        '/organization/organizationSubtree/#ColumnLabel#'
                    );

                break;
            case 'high':
                // Remove null-count layer/stack in this stacked bar chart
                $rtnChart->deleteLayer(0);

                $rtnChart
                    ->deleteLayer(2)
                    ->deleteLayer(1)
                    ->setColors(array('#FF0000'));
                break;                        
            case 'moderate':
                // Remove null-count layer/stack in this stacked bar chart
                $rtnChart->deleteLayer(0);

                $rtnChart
                    ->deleteLayer(2)
                    ->deleteLayer(0)
                    ->setColors(array('#FF6600'));
                break;
            case 'low';
                // Remove null-count layer/stack in this stacked bar chart
                $rtnChart->deleteLayer(0);

                $rtnChart
                    ->deleteLayer(1)
                    ->deleteLayer(0)
                    ->setColors(array('#FFC000'));
                break;
        }

        // The context switch will turn this array into a json reply (the responce to the external source)
        $this->view->chart = $rtnChart->export('array');
    }

    /**
     * Computes the sums of HIGH/MODERATE/LOW/NULL of all children reported from _getAllChildrenOfOrg($orgId)
     *
     * @return array
     */
    private function _getSumsOfOrgChildren($orgId)
    {
    
        // Get all children of the given organization id
        $childList = $this->_getAllChildrenOfOrg($orgId);
    
        $totalNull = 0;
        $totalHigh = 0;
        $totalMod = 0;
        $totalLow = 0;
    
        // For each organization (that is a child of $orgId)
        foreach ($childList as $thisChildOrg) {
            
            // For each threat level total (of findings) of this organization (high.mod,low)
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
                    case NULL:
                        $totalNull += $thisThreatLvl['COUNT'];
                        break;
                    case '':
                        $totalNull += $thisThreatLvl['COUNT'];
                        break;
                }
                
            }
            
        }
        
        return array('NULL' => $totalNull, 'HIGH' => $totalHigh, 'MODERATE' => $totalMod, 'LOW' => $totalLow);
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
        // Dont query if there are no organizations this user can see
        $visibleOrgs = FindingTable::getOrganizationIds();
        if (empty($visibleOrgs)) {
            return array();
        }
    
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
            ->where('f.responsibleorganizationid=o.id')
            ->whereIn('f.responsibleOrganizationId ', FindingTable::getOrganizationIds())
            ->andWhere($parLft . ' < o.lft')
            ->andWhere('f.status <> "CLOSED"')
            ->andWhere($parRgt . ' > o.rgt')
            ->groupBy('o.nickname, f.threatlevel')
            ->setHydrationMode(Doctrine::HYDRATE_ARRAY);

        $rtn = $q->execute();
        
        if ($includeParent === true && array_search($orgId, FindingTable::getOrganizationIds()) !== false) {

            $q = Doctrine_Query::create();
            $q
                ->addSelect('COUNT(f.id), o.id, o.nickname, f.threatlevel')
                ->from('Organization o')
                ->leftJoin('o.Findings f')
                ->whereIn('f.responsibleorganizationid', FindingTable::getOrganizationIds())
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
                ->whereIn('o.id ', FindingTable::getOrganizationIds())
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
                ->whereIn('o.id ', FindingTable::getOrganizationIds())
                ->setHydrationMode(Doctrine::HYDRATE_ARRAY)
                ->orderBy('o.nickname');

            return $q->execute();
        
        } else {
        
            $q = Doctrine_Query::create();
            $q
                ->addSelect('id, nickname')
                ->from('Organization o')
                ->where('orgtype = ?', $orgType)
                ->whereIn('o.id ', FindingTable::getOrganizationIds())
                ->setHydrationMode(Doctrine::HYDRATE_ARRAY)
                ->orderBy('o.nickname');

            return $q->execute();
        }
    }

    public function chartoverdueAction()
    {
        $dayRanges = str_replace(' ', '', urldecode($this->getRequest()->getParam('dayRanges')));
        $dayRanges = explode(',', $dayRanges);
        $dayRanges[] = 365 * 10;    // The last ##+ column

        $findingType = urldecode($this->getRequest()->getParam('pastThreatLvl'));

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
                    'Null',
                    'HIGH',
                    'MODERATE',
                    'LOW'
                )
            );

        // Dont query if there are no organizations this user can see
        $visibleOrgs = FindingTable::getOrganizationIds();
        if (empty($visibleOrgs)) {
            $this->view->chart = $thisChart->export('array');
            return;
        }

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
                ->where('f.currentecd BETWEEN "' . $fromDayStr . '" AND "' . $toDayStr . '"')
                ->andWhere('f.status <> "CLOSED"')
                ->whereIn('f.responsibleOrganizationId ', FindingTable::getOrganizationIds())
                ->groupBy('threatlevel')
                ->setHydrationMode(Doctrine::HYDRATE_ARRAY);
            $rslts = $q->execute();

            // We will get three results, each for a count of High Mod, Low
            $thisNull = 0;
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
                    case NULL:
                        $thisNull += $thisRslt['COUNT'];
                        break;
                    case '':
                        $thisNull += $thisRslt['COUNT'];
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
            $nonStackedLinks[] = '/finding/remediation/list?q=' .
                '/denormalizedStatus/textDoesNotContain/CLOSED' . 
                '/currentEcd/dateBetween/' . $thisFromDate . '/' . $thisToDate;            

            $thisChart->addColumn(
                $thisColLabel,
                array(
                    $thisNull,
                    $thisHigh,
                    $thisMod,
                    $thisLow
                ),
                array('',
                    '/finding/remediation/list?q=' . 
                    '/denormalizedStatus/textDoesNotContain/CLOSED' . 
                    '/currentEcd/dateBetween/' . $thisFromDate . '/' . $thisToDate .
                    '/threatLevel/enumIs/HIGH',
                    '/finding/remediation/list?q=' . 
                    '/denormalizedStatus/textDoesNotContain/CLOSED' . 
                    '/currentEcd/dateBetween/' . $thisFromDate . '/' . $thisToDate .
                    '/threatLevel/enumIs/MODERATE',
                    '/finding/remediation/list?q=' . 
                    '/denormalizedStatus/textDoesNotContain/CLOSED' .
                    '/currentEcd/dateBetween/' . $thisFromDate . '/' . $thisToDate .
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
                // Remove null-count layer
                $thisChart->deleteLayer(0);
                break;
            case "high":
                // Remove null-count layer
                $thisChart->deleteLayer(0);
                // Remove the Low and Moderate columns/layers
                $thisChart->deleteLayer(2);
                $thisChart->deleteLayer(1);
                $thisChart->setColors(array('#FF0000'));
                break;
            case "moderate":
                // Remove null-count layer
                $thisChart->deleteLayer(0);
                // Remove the Low and High columns/layers
                $thisChart->deleteLayer(2);
                $thisChart->deleteLayer(0);
                $thisChart->setColors(array('#FF6600'));
                break;
            case "low":
                // Remove null-count layer
                $thisChart->deleteLayer(0);
                // Remove the Moderate and High columns/layers
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
        $findingType = urldecode($this->getRequest()->getParam('threatLevel'));

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
                    '/finding/remediation/list?q=' .
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
                        'Null',
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
                        '/finding/remediation/list?q=' .
                        '/denormalizedStatus/textDoesNotContain/CLOSED' .
                        'organization/textExactMatch/' . $thisOrg['nickname'] .
                        '/threatLevel/enumIs/HIGH',
                        '/finding/remediation/list?q=' .
                        '/denormalizedStatus/textDoesNotContain/CLOSED' .
                        'organization/textExactMatch/' . $thisOrg['nickname'] .
                        '/threatLevel/enumIs/MODERATE',
                        '/finding/remediation/list?q=' .
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
                    '/finding/remediation/list?q=' . 
                    '/denormalizedStatus/textDoesNotContain/CLOSED' . 
                    '/organization/textExactMatch/' . $thisThreatCount['nickname'] .
                    '/threatLevel/enumIs/' . strtoupper($findingType)
                );
            }

            return $thisChart;
        }
    }

    /**
     * Calculate "finding forcast" data for a chart based on finding.currentecd in the database
     *
     * @return void
     */
    public function chartfindnomitstratAction()
    {
        $dayRange = $this->getRequest()->getParam('dayRangesMitChart');
        $dayRange = str_replace(' ', '', $dayRange);
        $dayRange = explode(',', $dayRange);

        $threatLvl = $this->getRequest()->getParam('noMitThreatLvl');

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
                    'Null',
                    'High',
                    'Moderate',
                    'Low'
                )
            );

        // Dont query if there are no organizations this user can see
        $visibleOrgs = FindingTable::getOrganizationIds();
        if (empty($visibleOrgs)) {
            $this->view->chart = $noMitChart->export('array');
            return;
        }

        $nonStackedLinks = array();

        for ($x = 0; $x < count($dayRange) - 1; $x++) {
            
            $fromDayInt = $dayRange[$x+1];
            $fromDay = new Zend_Date();
            $fromDay = $fromDay->addDay(-$fromDayInt);
            $fromDayStr = $fromDay->toString('YYY-MM-dd');
            
            $toDayInt = $dayRange[$x];
            $toDay = new Zend_Date();
            $toDay = $toDay->addDay(-$toDayInt);
            $toDayStr = $toDay->toString('YYY-MM-dd');
            
            if ($x !== count($dayRange) - 2) {
                $fromDay->addday(-1);
                $fromDayStr = $fromDay->toString('YYY-MM-dd');
                $fromDayInt--;
            }
            $thisColumnLabel = $toDayInt . '-' . $fromDayInt;

            // Get the count of findings
            $q = Doctrine_Query::create()
                ->select('count(f.id), f.threatlevel')
                ->from('Finding f')
                ->where('f.status="NEW" OR f.status="DRAFT"')
                ->andWhere('f.status <> "CLOSED"')
                ->andWhere('f.createdts BETWEEN "' . $fromDayStr . '" AND "' . $toDayStr . '"')
                ->groupBy('f.threatlevel')
                ->whereIn('f.responsibleOrganizationId ', FindingTable::getOrganizationIds())
                ->setHydrationMode(Doctrine::HYDRATE_ARRAY);
            $rslts = $q->execute();
            
            // Initalize to 0 (query may not return values for 0 counts)
            $thisNull = 0;
            $thisHigh = 0;
            $thisMod = 0;
            $thisLow = 0;
            
            foreach ($rslts as $thisLevel) {
                switch ($thisLevel['threatLevel']) {
                    case 'LOW':
                        $thisLow = $thisLevel['count'];
                        break;
                    case 'MODERATE':
                        $thisMod = $thisLevel['count'];
                        break;
                    case 'HIGH':
                        $thisHigh = $thisLevel['count'];
                        break;
                    case NULL:
                        $thisNull += $thisRslt['COUNT'];
                        break;
                    default:
                        $thisNull += $thisRslt['COUNT'];
                        break;
                }
            }
            
            // Make URL to the search page with date params
            $basicSearchLink = '/finding/remediation/list?q=' . 
                '/createdTs/dateBetween/' . $fromDayStr . '/' . $toDayStr;
                
            // Rake this url filter out CLOSED, EN, and anything on evaluation.nickname (MS ISSO, EV ISSO, etc)
            $basicSearchLink .= '/denormalizedStatus/textNotExactMatch/CLOSED';
            $basicSearchLink .= '/denormalizedStatus/textNotExactMatch/EN';
            foreach ($this->_getEvaluationNames() as $thisStatus) {
                $basicSearchLink .= '/denormalizedStatus/textNotExactMatch/' . $thisStatus;
            }
            
            // Remembers links for a non-stacked bar chart in the even the user is querying "totals"
            $nonStackedLinks[] = $basicSearchLink;
            
            $noMitChart->addColumn(
                $thisColumnLabel,
                array(
                    $thisNull,
                    $thisHigh,
                    $thisMod,
                    $thisLow
                ),
                array(
                    $basicSearchLink . '/threatLevel/enumIs/NULL',
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
                // Remove null-counts (findings without threatLevels)
                $noMitChart->deleteLayer(0);
                break;
            case "high":
                // Remove null-counts (findings without threatLevels)
                $noMitChart->deleteLayer(0);
                // Remove the Low and Moderate columns/layers
                $noMitChart->deleteLayer(2);
                $noMitChart->deleteLayer(1);
                $noMitChart->setColors(array('#FF0000'));
                break;
            case "moderate":
                // Remove null-counts (findings without threatLevels)
                $noMitChart->deleteLayer(0);
                // Remove the Low and High columns/layers
                $noMitChart->deleteLayer(2);
                $noMitChart->deleteLayer(0);
                $noMitChart->setColors(array('#FF6600'));
                break;
            case "low":
                // Remove null-counts (findings without threatLevels)
                $noMitChart->deleteLayer(0);
                // Remove the Moderate and High columns/layers
                $noMitChart->deleteLayer(1);
                $noMitChart->deleteLayer(0);
                $noMitChart->setColors(array('#FFC000'));
                break;
        }

        // Export as array, the context switch will translate it to a JSON responce
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

        $dayRange = $this->getRequest()->getParam('dayRangesStatChart');
        $dayRange = str_replace(' ', '', $dayRange);
        $dayRange = explode(',', $dayRange);
        
        $threatLvl = $this->getRequest()->getParam('forcastThreatLvl');

        $highCount = 0;
        $modCount = 0;
        $lowCount = 0;
        $nullCount = 0;
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
                    'Null',
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

        // Dont query if there are no organizations this user can see
        $visibleOrgs = FindingTable::getOrganizationIds();
        if (empty($visibleOrgs)) {
            $this->view->chart = $thisChart->export('array');
            return;
        }

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
                ->whereIn('f.responsibleOrganizationId ', FindingTable::getOrganizationIds())
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
                    case NULL:
                        $nullCount += $thisRslt['COUNT'];
                        break;
                    case '':
                        $nullCount += $thisRslt['COUNT'];
                        break;
                }
            }

            // Add column assuming this is a stacked-bar chart with High, Mod, and Low findings
            $thisChart
                ->addColumn(
                    $thisColumnLabel,
                    array(
                        $nullCount,
                        $highCount,
                        $modCount,
                        $lowCount
                    ),
                    array('',
                        '/finding/remediation/list?q=' .
                        '/denormalizedStatus/textDoesNotContain/CLOSED' .
                        '/currentEcd/dateBetween/' . 
                        $fromDay->toString('YYYY-MM-dd').'/'.$toDay->toString('YYYY-MM-dd') .
                        '/threatLevel/enumIs/HIGH',
                        '/finding/remediation/list?q=' .
                        '/denormalizedStatus/textDoesNotContain/CLOSED' . 
                        '/currentEcd/dateBetween/' . 
                        $fromDay->toString('YYYY-MM-dd').'/'.$toDay->toString('YYYY-MM-dd') .
                        '/threatLevel/enumIs/MODERATE',
                        '/finding/remediation/list?q=' . 
                        '/denormalizedStatus/textDoesNotContain/CLOSED' . 
                        '/currentEcd/dateBetween/' . 
                        $fromDay->toString('YYYY-MM-dd').'/'.$toDay->toString('YYYY-MM-dd') .
                        '/threatLevel/enumIs/LOW'
                    )
                );
                
            // Note the links to set in the even this is a totals (basic-bar) chart
            $totalChartLinks[] = '/finding/remediation/list?q=' .
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
                // Remove the nullCount layer
                $thisChart->deleteLayer(0);
                break;
            case "high":
                // Remove the nullCount layer
                $thisChart->deleteLayer(0);
                // Remove the Low and Moderate columns/layers
                $thisChart->deleteLayer(2);
                $thisChart->deleteLayer(1);
                $thisChart->setColors(array('#FF0000'));
                break;
            case "moderate":
                // Remove the nullCount layer
                $thisChart->deleteLayer(0);
                // Remove the Low and High columns/layers
                $thisChart->deleteLayer(2);
                $thisChart->deleteLayer(0);
                $thisChart->setColors(array('#FF6600'));
                break;
            case "low":
                // Remove the nullCount layer
                $thisChart->deleteLayer(0);
                // Remove the Moderate and High columns/layers
                $thisChart->deleteLayer(1);
                $thisChart->deleteLayer(0);
                $thisChart->setColors(array('#FFC000'));
                break;
        }

        // Export as array, the context switch will translate it to a JSON responce
        $this->view->chart = $thisChart->export('array');
    }

}

