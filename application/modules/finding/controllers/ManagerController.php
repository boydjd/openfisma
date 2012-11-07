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
 * @copyright  (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Controllers
 */
class Finding_ManagerController extends Fisma_Zend_Controller_Action_Security
{
    /**
     * Mapping from enum strings used in the DB to User-Friendly strings used in the UI
     *
     * @var array
     */
    protected $_threatTypes = array('threat_level' => 'Threat Level', 'residual_risk' => 'Residual Risk');

    /**
     * List of threat/risk levels
     *
     * @var array
     */
    protected $_threatLevels = array( 'Totals', 'High, Moderate, and Low', 'High', 'Moderate', 'Low');

    /**
     * Low, Moderate and High Colors
     *
     * @var array
     */
    protected $_highModLowColors = array(Fisma_Chart::COLOR_HIGH, Fisma_Chart::COLOR_MODERATE, Fisma_Chart::COLOR_LOW);
    protected $_lowModHighColors = array(Fisma_Chart::COLOR_LOW, Fisma_Chart::COLOR_MODERATE, Fisma_Chart::COLOR_HIGH);

    /**
     * Set ajaxContect on analystAction and chartsAction
     */
    public function init()
    {
        $this->_helper->ajaxContext()
            ->addActionContext('index-tab', 'html')
            ->addActionContext('analyst', 'html')
            ->addActionContext('charts', 'html')
            ->initContext();
        parent::init();
    }

    /**
     * Set up headers/footers
     */
    public function preDispatch()
    {
        parent::preDispatch();

        $this->_acl->requireArea('finding');

        $this->_helper->fismaContextSwitch()
            ->addActionContext('chart-by-poc', 'json')
            ->addActionContext('chart-by-system', 'json')
            ->addActionContext('chartoverdue', 'json')
            ->addActionContext('chartfindingstatus', 'json')
            ->addActionContext('total-type', 'json')
            ->addActionContext('findingforecast', 'json')
            ->addActionContext('chartfindnomitstrat', 'json')
            ->addActionContext('chartfindingbyorgdetail', 'json')
            ->initContext();

        $this->_visibleOrgs = $this->_me
            ->getOrganizationsByPrivilegeQuery('finding', 'read')
            ->select('o.id')
            ->execute()
            ->toKeyValueArray('id', 'id');
    }

    /**
     * The landing page for Finding Manager View
     *
     * @GETAllowed
     */
    public function indexAction()
    {
        $this->_acl->requirePrivilegeForClass('oversee', 'organization');
        $organizations = $this->_me->getOrganizationsByPrivilege('organization', 'oversee');
        switch (count($organizations->toArray())) {
            case 0:
                throw new Fisma_Zend_User_Exception("You do not have the manager role");
                break;
            case 1:
                $this->_forward('index-tab', 'manager', 'finding', array('orgId' => $organizations->getFirst()->id));
                break;
            default:
                $tabView = new Fisma_Yui_TabView('FindingManager');
                foreach ($organizations as $organization) {
                    $tabView->addTab(
                        $organization->nickname,
                        "/finding/manager/index-tab/format/html/orgId/" . $organization->id
                    );
                }
                $this->view->tabView = $tabView;
                break;
        }
    }

    /**
     * Load the manager view for a specific organization in a tab
     *
     * @GETAllowed
     */
    public function indexTabAction()
    {
        if (!$orgId = $this->getRequest()->getParam('orgId')) {
            throw new Fisma_Zend_User_Exception("No organization id provided.");
        }

        if (!$organization = Doctrine::getTable('Organization')->find($orgId)) {
            throw new Fisma_Zend_User_Exception("No organization found with id #{$orgId}");
        }

        $myOrgSystemIds = array($orgId);
        $orgSystems = $organization->getNode()->getChildren();
        if ($orgSystems) {
            foreach ($orgSystems as $orgSystem) {
                if ($orgSystem->systemId && $orgSystem->System !== 'disposal') {
                    $myOrgSystemIds[] = $orgSystem['id'];
                }
            }
        }

        $this->view->byPoc = Doctrine_Query::create()
            ->select(
                'COUNT(f.id) as count, f.threatlevel, o.id, o.nickname, f.pocid, u.id, u.displayName'
            )
            ->from('Finding f')
            ->leftJoin('f.PointOfContact u')
            ->leftJoin('u.ReportingOrganization o')
            ->groupBy('f.pocid, f.threatlevel')
            ->where('f.deleted_at is NULL AND f.status <> ?', 'CLOSED')
            ->andWhere('o.id = ?', $orgId)
            ->setHydrationMode(Doctrine::HYDRATE_ARRAY)
            ->execute();
        $criteria = array();
        $this->view->totalByPoc = 0;
        foreach ($this->view->byPoc as $index => &$statistic) {
            $this->view->totalByPoc += $statistic['count'];
            if (empty($statistic['pocId'])) {
                $statistic['criteria'] = 'Unassigned';
                $statistic['pocId'] = 'empty';
            } else {
                $statistic['criteria'] = $this->view->userInfo(
                    $statistic['PointOfContact']['displayName'],
                    $statistic['PointOfContact']['id']
                );
            }

            $pocid = $statistic['pocId'];
            $threatlevel = $statistic['threatLevel'];
            if (!isset($criteria[$pocid])) {
                $criteria[$pocid] = $index;
                $this->view->byPoc[$index][$threatlevel] = $statistic['count'];
            } else {
                $currentIndex = $criteria[$pocid];
                $this->view->byPoc[$currentIndex][$threatlevel] = $statistic['count'];
                $this->view->byPoc[$currentIndex]['count'] += $statistic['count'];
                unset($this->view->byPoc[$index]);
            }
        }
        $byPoc = array();
        foreach ($this->view->byPoc as &$statistic) {
            $statistic['LOW'] = (empty($statistic['LOW'])) ? 0 : $statistic['LOW'];
            $statistic['MODERATE'] = (empty($statistic['MODERATE'])) ? 0 : $statistic['MODERATE'];
            $statistic['HIGH'] = (empty($statistic['HIGH'])) ? 0 : $statistic['HIGH'];
            $byPoc[] = array(
                'poc' => $statistic['PointOfContact']['displayName'],
                'displayPoc' => $statistic['criteria'],
                'threatLevel' => json_encode(array(
                    'LOW' => $statistic['LOW'],
                    'MODERATE' => $statistic['MODERATE'],
                    'HIGH' => $statistic['HIGH'],
                    'criteriaQuery' => '/threatLevel/enumIs/',
                    'total' => $this->view->totalByPoc
                )),
                'total' => $statistic['count'],
                'displayTotal' => json_encode(array(
                    'url' => '/finding/remediation/list?q=denormalizedStatus/enumIsNot/CLOSED/'
                           . 'pocUser/textContains/' . $statistic['PointOfContact']['displayName'],
                    'displayText' => $statistic['count']
                ))
            );
        }
        $this->view->byPocTable = new Fisma_Yui_DataTable_Local();
        $this->view->byPocTable->addColumn(
            new Fisma_Yui_DataTable_Column(
                'Point of Contact',
                false,
                null,
                null,
                'poc',
                true
            )
        );
        $this->view->byPocTable->addColumn(
            new Fisma_Yui_DataTable_Column(
                'Point of Contact',
                true,
                'Fisma.TableFormat.formatHtml',
                null,
                'displayPoc',
                false,
                'string',
                'poc'
            )
        );
        $this->view->byPocTable->addColumn(
            new Fisma_Yui_DataTable_Column(
                'Threat Level',
                true,
                'Fisma.TableFormat.formatThreatBar',
                null,
                'threatLevel',
                false,
                'string',
                'total'
            )
        );
        $this->view->byPocTable->addColumn(
            new Fisma_Yui_DataTable_Column(
                'Total',
                false,
                null,
                null,
                'total',
                true,
                'number'
            )
        );
        $this->view->byPocTable->addColumn(
            new Fisma_Yui_DataTable_Column(
                'Total',
                true,
                'Fisma.TableFormat.formatLink',
                null,
                'displayTotal',
                false,
                'string',
                'total'
            )
        );
        $this->view->byPocTable->setData($byPoc);

        $this->view->bySystem = Doctrine_Query::create()
            ->select(
                'COUNT(f.id) as count, o.nickname as criteria, f.threatlevel, o.id, o.lft, o.rgt, o.level, ' .
                'f.responsibleorganizationid, ot.iconId as icon'
            )
            ->from('Organization o')
            ->leftJoin('o.OrganizationType ot')
            ->leftJoin('o.Findings f')
            ->groupBy('f.threatlevel, o.id')
            ->where('f.deleted_at is NULL AND f.status <> ?', 'CLOSED')
            ->andWhereIn('o.id', $myOrgSystemIds)
            ->setHydrationMode(Doctrine::HYDRATE_ARRAY)
            ->execute();
        $this->view->totalBySystem = 0;
        foreach ($this->view->bySystem as &$statistic) {
            $count = 0;
            foreach ($statistic['Findings'] as &$finding) {
                $threatLevel = $finding['threatLevel'];
                $statistic[$threatLevel] = $finding['count'];
                $count += $finding['count'];
            }
            $statistic['LOW'] = (empty($statistic['LOW'])) ? 0 : $statistic['LOW'];
            $statistic['MODERATE'] = (empty($statistic['MODERATE'])) ? 0 : $statistic['MODERATE'];
            $statistic['HIGH'] = (empty($statistic['HIGH'])) ? 0 : $statistic['HIGH'];
            $statistic['count'] = $count;
            $this->view->totalBySystem += $statistic['count'];

            if (empty($statistic['icon'])) { // the OrganizationType "system" doesn't have an icon
                $statistic['icon'] = Doctrine_Query::create()
                    ->select('o.id, s.id, st.iconId as icon')
                    ->from('Organization o')
                    ->leftJoin('o.System s')
                    ->leftJoin('s.SystemType st')
                    ->where('o.id = ?', $statistic['id'])
                    ->setHydrationMode(Doctrine::HYDRATE_ARRAY)
                    ->fetchOne();
                $statistic['icon'] = $statistic['icon']['icon'];
            }
        }
        $bySystem = array();
        foreach ($this->view->bySystem as &$statistic) {
            $bySystem[] = array(
                'organization' => $statistic['criteria'],
                'displayOrganization' => json_encode(array(
                    'iconId' => $statistic['icon'],
                    'iconSize' => 'small',
                    'displayName' => $statistic['criteria'],
                    'orgId' => $statistic['id']
                )),
                'threatLevel' => json_encode(array(
                    'LOW' => $statistic['LOW'],
                    'MODERATE' => $statistic['MODERATE'],
                    'HIGH' => $statistic['HIGH'],
                    'criteriaQuery' => '/threatLevel/enumIs/',
                    'total' => $this->view->totalBySystem
                )),
                'total' => $statistic['count'],
                'displayTotal' => json_encode(array(
                    'url' => '/finding/remediation/list?q=denormalizedStatus/enumIsNot/CLOSED/'
                           . 'organization/textContains/' . $statistic['criteria'],
                    'displayText' => $statistic['count']
                ))
            );
        }
        $this->view->bySystemTable = new Fisma_Yui_DataTable_Local();
        $this->view->bySystemTable->addColumn(
            new Fisma_Yui_DataTable_Column(
                'System',
                false,
                null,
                null,
                'organization',
                true
            )
        );
        $this->view->bySystemTable->addColumn(
            new Fisma_Yui_DataTable_Column(
                'System',
                true,
                'Fisma.TableFormat.formatOrganization',
                null,
                'displayOrganization',
                false,
                'string',
                'organization'
            )
        );
        $this->view->bySystemTable->addColumn(
            new Fisma_Yui_DataTable_Column(
                'Threat Level',
                true,
                'Fisma.TableFormat.formatThreatBar',
                null,
                'threatLevel',
                false,
                'string',
                'total'
            )
        );
        $this->view->bySystemTable->addColumn(
            new Fisma_Yui_DataTable_Column(
                'Total',
                false,
                null,
                null,
                'total',
                true,
                'number'
            )
        );
        $this->view->bySystemTable->addColumn(
            new Fisma_Yui_DataTable_Column(
                'Total',
                true,
                'Fisma.TableFormat.formatLink',
                null,
                'displayTotal',
                false,
                'string',
                'total'
            )
        );
        $this->view->bySystemTable->setData($bySystem);

        if ($this->view->totalByPoc + $this->view->totalBySystem < 1) {
            $this->view->message = "There are no unresolved findings under your responsibility.";
            return;
        }

        $chartByPoc =
            new Fisma_Chart(300, 250, 'chartByPoc', '/finding/manager/chart-by-poc/format/json/orgId/' . $orgId);
        $chartByPoc->setTitle('Unresolved: By Point of Contact');
        $this->view->chartByPoc = $chartByPoc->export('html', true);

        $chartBySystem =
            new Fisma_Chart(300, 250, 'chartBySystem', '/finding/manager/chart-by-system/format/json/orgId/' . $orgId);
        $chartBySystem->setTitle('Unresolved: By Point of Contact');
        $this->view->chartBySystem = $chartBySystem->export('html', true);
    }

    /**
     * Total unresolved by POC
     *
     * @GETAllowed
     */
    public function chartByPocAction()
    {
        if (!$orgId = $this->getRequest()->getParam('orgId')) {
            throw new Fisma_Zend_User_Exception("No organization id provided.");
        }

        if (!$organization = Doctrine::getTable('Organization')->find($orgId)) {
            throw new Fisma_Zend_User_Exception("No organization found with id #{$orgId}");
        }

        $rtnChart = new Fisma_Chart();
        $rtnChart
            ->setAxisLabelY('Number of Findings')
            ->setChartType('bar')
            ->setColors($this->_lowModHighColors);

        // Dont query if there are no organizations this user can see
        if (empty($this->_visibleOrgs)) {
            $this->view->chart = $rtnChart->export('array');
            return;
        }

        $basicLink =
            '/finding/remediation/list?q=' .
            '/denormalizedStatus/enumIsNot/CLOSED' .
            '/pocOrg/textExactMatch/' . $organization->nickname .
            '/threatLevel/enumIs/';

        $data = array(
            'LOW' => 0,
            'MODERATE' => 0,
            'HIGH' => 0
        );

        $results = Doctrine_Query::create()
            ->select(
                'COUNT(f.id) as count, f.threatlevel as criteria, f.id, u.reportingorganizationid, o.id'
            )
            ->from('Finding f')
            ->leftJoin('f.PointOfContact u')
            ->leftJoin('u.ReportingOrganization o')
            ->groupBy('f.threatlevel')
            ->where('f.deleted_at is NULL AND f.status <> ?', 'CLOSED')
            ->andWhere('o.id = ?', $orgId)
            ->setHydrationMode(Doctrine::HYDRATE_ARRAY)
            ->execute();
        foreach ($results as $result) {
            $data[$result['criteria']] = $result['count'];
        }

        foreach ($data as $key => $value) {
            $rtnChart->addColumn($key, $value, $basicLink . $key);
        }

        // The context switch will turn this array into a json reply (the responce to the external source)
        $this->view->chart = $rtnChart->export('array');
    }

    /**
     * Total unresolved by System
     *
     * @GETAllowed
     */
    public function chartBySystemAction()
    {
        if (!$orgId = $this->getRequest()->getParam('orgId')) {
            throw new Fisma_Zend_User_Exception("No organization id provided.");
        }

        if (!$organization = Doctrine::getTable('Organization')->find($orgId)) {
            throw new Fisma_Zend_User_Exception("No organization found with id #{$orgId}");
        }

        $rtnChart = new Fisma_Chart();
        $rtnChart
            ->setAxisLabelY('Number of Findings')
            ->setChartType('bar')
            ->setColors($this->_lowModHighColors);

        // Dont query if there are no organizations this user can see
        if (empty($this->_visibleOrgs)) {
            $this->view->chart = $rtnChart->export('array');
            return;
        }

        $basicLink =
            '/finding/remediation/list?q=' .
            '/denormalizedStatus/enumIsNot/CLOSED' .
            '/organization/organizationChildren/' . $organization->nickname .
            '/threatLevel/enumIs/';

        $data = array(
            'LOW' => 0,
            'MODERATE' => 0,
            'HIGH' => 0
        );

        $myOrgSystemIds = array($orgId);
        $orgSystems = $organization->getNode()->getChildren();
        if ($orgSystems) {
            foreach ($orgSystems as $orgSystem) {
                $myOrgSystemIds[] = $orgSystem['id'];
            }
        }
        $results = Doctrine_Query::create()
            ->select(
                'COUNT(f.id) as count, f.threatlevel as criteria, f.id, o.id'
            )
            ->from('Finding f')
            ->leftJoin('f.Organization o')
            ->groupBy('f.threatlevel')
            ->where('f.deleted_at is NULL AND f.status <> ?', 'CLOSED')
            ->andWhereIn('o.id', $myOrgSystemIds)
            ->setHydrationMode(Doctrine::HYDRATE_ARRAY)
            ->execute();
        foreach ($results as $result) {
            $data[$result['criteria']] = $result['count'];
        }

        foreach ($data as $key => $value) {
            $rtnChart->addColumn($key, $value, $basicLink . $key);
        }

        // The context switch will turn this array into a json reply (the responce to the external source)
        $this->view->chart = $rtnChart->export('array');
    }
}