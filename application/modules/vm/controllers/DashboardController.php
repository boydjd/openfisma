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
 * Dashboard for vulnerabilities
 *
 * @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2013 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Vulnerability
 */
class Vm_DashboardController extends Fisma_Zend_Controller_Action_Security
{
    /**
     * Restrict permissions
     */
    public function preDispatch()
    {
        parent::preDispatch();

        $module = Doctrine::getTable('Module')->findOneByName('Vulnerability Management');

        if (!$module->enabled) {
            throw new Fisma_Zend_Exception('This module is not enabled.');
        }

        $this->_acl->requireArea('vulnerability');
        $this->_visibleOrgs = $this->_me
            ->getOrganizationsByPrivilegeQuery('vulnerability', 'read')
            ->select('o.id')
            ->execute()
            ->toKeyValueArray('id', 'id');
    }

    /**
     * @GETAllowed
     */
    public function indexAction()
    {
        $this->view->toolbarButtons = $this->getToolbarButtons();

        $totalQuery = Doctrine_Query::create()
            ->from('Vulnerability v');
        $this->_addAclConditions($totalQuery);
        $this->view->total = $totalQuery->count();
        if ($this->view->total < 1) {
            $this->view->message = "There are no unresolved vulnerabilities under your responsibility.";
            return;
        }

        $this->view->byCvssAv = array(
            'A' => 0,
            'L' => 0,
            'N' => 0
        );
        $byCvssAv = Doctrine_Query::create()
            ->select('COUNT(v.id) as count, SUBSTRING(v.cvssvector, 4, 1) as criteria')
            ->from('Vulnerability v')
            ->groupBy('criteria')
            ->orderBy('criteria')
            ->setHydrationMode(Doctrine::HYDRATE_ARRAY);
        $this->_addAclConditions($byCvssAv);
        foreach ($byCvssAv->execute() as $statistic) {
            $criteria = $statistic['criteria'];
            $count = $statistic['count'];
            $this->view->byCvssAv[$criteria] = $count;
        }

        $this->view->byCvssAc = array(
            'H' => 0,
            'L' => 0,
            'M' => 0
        );
        $byCvssAc = Doctrine_Query::create()
            ->select('COUNT(v.id) as count, SUBSTRING(v.cvssvector, 9, 1) as criteria')
            ->from('Vulnerability v')
            ->groupBy('criteria')
            ->orderBy('criteria')
            ->setHydrationMode(Doctrine::HYDRATE_ARRAY);
        $this->_addAclConditions($byCvssAc);
        foreach ($byCvssAc->execute() as $statistic) {
            $criteria = $statistic['criteria'];
            $count = $statistic['count'];
            $this->view->byCvssAc[$criteria] = $count;
        }

        $this->view->byCvssAu = array(
            'M' => 0,
            'N' => 0,
            'S' => 0
        );
        $byCvssAu = Doctrine_Query::create()
            ->select('COUNT(v.id) as count, SUBSTRING(v.cvssvector, 14, 1) as criteria')
            ->from('Vulnerability v')
            ->groupBy('criteria')
            ->orderBy('criteria')
            ->setHydrationMode(Doctrine::HYDRATE_ARRAY);
        $this->_addAclConditions($byCvssAu);
        foreach ($byCvssAu->execute() as $statistic) {
            $criteria = $statistic['criteria'];
            $count = $statistic['count'];
            $this->view->byCvssAu[$criteria] = $count;
        }

        $this->view->byCvssC = array(
            'H' => 0,
            'L' => 0,
            'M' => 0
        );
        $byCvssC = Doctrine_Query::create()
            ->select('COUNT(v.id) as count, SUBSTRING(v.cvssvector, 18, 1) as criteria')
            ->from('Vulnerability v')
            ->groupBy('criteria')
            ->orderBy('criteria')
            ->setHydrationMode(Doctrine::HYDRATE_ARRAY);
        $this->_addAclConditions($byCvssC);
        foreach ($byCvssC->execute() as $statistic) {
            $criteria = $statistic['criteria'];
            $count = $statistic['count'];
            $this->view->byCvssC[$criteria] = $count;
        }

        $this->view->byCvssI = array(
            'H' => 0,
            'L' => 0,
            'M' => 0
        );
        $byCvssI = Doctrine_Query::create()
            ->select('COUNT(v.id) as count, SUBSTRING(v.cvssvector, 22, 1) as criteria')
            ->from('Vulnerability v')
            ->groupBy('criteria')
            ->orderBy('criteria')
            ->setHydrationMode(Doctrine::HYDRATE_ARRAY);
        $this->_addAclConditions($byCvssI);
        foreach ($byCvssI->execute() as $statistic) {
            $criteria = $statistic['criteria'];
            $count = $statistic['count'];
            $this->view->byCvssI[$criteria] = $count;
        }

        $this->view->byCvssA = array(
            'H' => 0,
            'L' => 0,
            'M' => 0
        );
        $byCvssA = Doctrine_Query::create()
            ->select('COUNT(v.id) as count, SUBSTRING(v.cvssvector, 26, 1) as criteria')
            ->from('Vulnerability v')
            ->groupBy('criteria')
            ->orderBy('criteria')
            ->setHydrationMode(Doctrine::HYDRATE_ARRAY);
        $this->_addAclConditions($byCvssA);
        foreach ($byCvssA->execute() as $statistic) {
            $criteria = $statistic['criteria'];
            $count = $statistic['count'];
            $this->view->byCvssA[$criteria] = $count;
        }

        $byThreatQuery = Doctrine_Query::create()
            ->select('COUNT(v.id) as count, v.threatlevel as criteria')
            ->from('Vulnerability v')
            ->groupBy('criteria')
            ->setHydrationMode(Doctrine::HYDRATE_ARRAY);
        $this->_addAclConditions($byThreatQuery);
        $this->view->byThreat = $byThreatQuery->execute();

        $byWorkflowQuery = Doctrine_Query::create()
            ->select('COUNT(v.id) as count, IFNULL(w.name, "Unassigned") as criteria, ' .
                     'IFNULL(w.description, "") as tooltip, v.currentStepId, ws.id, w.id')
            ->from('Vulnerability v')
            ->leftJoin('v.CurrentStep ws')
            ->leftJoin('ws.Workflow w')
            ->groupBy('criteria')
            ->setHydrationMode(Doctrine::HYDRATE_ARRAY)
            ->orderBy('w.id ASC');
        $this->_addAclConditions($byWorkflowQuery);
        $this->view->byWorkflow = $byWorkflowQuery->execute();

        $byWorkflowStepQuery = Doctrine_Query::create()
            ->select(
                'COUNT(v.id) as count, IFNULL(ws.name, "Unassigned") as criteria, ' .
                'CONCAT("<b>", IFNULL(w.name, "No "), " - ", ws.name, "</b><br/><br/>", ws.description) as tooltip, ' .
                'v.currentStepId, w.id, ws.id'
            )->from('Vulnerability v')
            ->leftJoin('v.CurrentStep ws')
            ->leftJoin('ws.Workflow w')
            ->groupBy('criteria')
            ->setHydrationMode(Doctrine::HYDRATE_ARRAY)
            ->orderBy('w.id ASC, ws.cardinality');
        $this->_addAclConditions($byWorkflowStepQuery);
        $this->view->byWorkflowStep = $byWorkflowStepQuery->execute();

        $byNetworkQuery = Doctrine_Query::create()
            ->select('COUNT(v.id) as count, a.networkId, n.nickname as criteria, ' .
                     'CONCAT("<b>", n.nickname, " - ", n.name, "</b><br/>", n.description) as tooltip')
            ->from('Vulnerability v')
            ->groupBy('criteria')
            ->setHydrationMode(Doctrine::HYDRATE_ARRAY)
            ->orderBy('a.networkId ASC');
        $this->_addAclConditions($byNetworkQuery);
        $byNetworkQuery->leftJoin('a.Network n'); //(v.Asset a) is joined by _addAclConditions
        $this->view->byNetwork = $byNetworkQuery->execute();

        $byPocQuery = Doctrine_Query::create()
            ->select(
                'COUNT(v.id) as count, v.threatlevel, i.id as icon, o.id, o.nickname, ot.nickname as type, ' .
                'v.pocid, u.id, u.displayName'
            )
            ->from('Vulnerability v')
            ->leftJoin('v.PointOfContact u')
            ->leftJoin('u.ReportingOrganization o')
            ->leftJoin('o.OrganizationType ot')
            ->leftJoin('ot.Icon i')
            ->groupBy('v.pocid, v.threatlevel')
            ->setHydrationMode(Doctrine::HYDRATE_ARRAY);
        $this->_addAclConditions($byPocQuery);
        $this->view->byPoc = $byPocQuery->execute();

        $criteria = array();
        foreach ($this->view->byPoc as $index => &$statistic) {
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
        foreach ($this->view->byPoc as $statistic) {
            $statistic['LOW'] = (empty($statistic['LOW'])) ? 0 : $statistic['LOW'];
            $statistic['MODERATE'] = (empty($statistic['MODERATE'])) ? 0 : $statistic['MODERATE'];
            $statistic['HIGH'] = (empty($statistic['HIGH'])) ? 0 : $statistic['HIGH'];
            $byPoc[] = array(
                'poc' => $statistic['PointOfContact']['displayName'],
                'displayPoc' => $statistic['criteria'],
                'parentOrganization' => $statistic['PointOfContact']['ReportingOrganization']['nickname'],
                'displayParentOrganization' => json_encode(array(
                    'iconId' => $statistic['icon'],
                    'iconSize' => 'small',
                    'displayName' => $statistic['PointOfContact']['ReportingOrganization']['nickname'],
                    'orgId' => $statistic['PointOfContact']['ReportingOrganization']['id'],
                    'iconAlt' => $statistic['type']
                )),
                'threatLevel' => json_encode(array(
                    'LOW' => $statistic['LOW'],
                    'MODERATE' => $statistic['MODERATE'],
                    'HIGH' => $statistic['HIGH'],
                    'criteriaQuery' => '/threatLevel/enumIs/',
                    'total' => $this->view->total
                )),
                'total' => $statistic['count'],
                'displayTotal' => json_encode(array(
                    'url' => '/vm/vulnerability/list?q=isResolved/booleanNo/'
                           . 'pocUser/textContains/'
                           . $this->view->escape($statistic['PointOfContact']['displayName'], 'url'),
                    'displayText' => $statistic['count']
                ))
            );
        }
        $this->view->byPocTable = new Fisma_Yui_DataTable_Local();
        $this->view->byPocTable->setRegistryName('Vulnerability.Dashboard.Analyst.byPocTable');
        $this->view->byPocTable->addEventListener('renderEvent', 'Fisma.Finding.restrictTableLength');
        $this->view->byPocTable->addColumn(
            new Fisma_Yui_DataTable_Column(
                $this->view->translate('Vulnerability_Point_of_Contact'),
                false,
                null,
                null,
                'poc',
                true
            )
        );
        $this->view->byPocTable->addColumn(
            new Fisma_Yui_DataTable_Column(
                $this->view->translate('Vulnerability_Point_of_Contact'),
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
                'Parent',
                false,
                null,
                null,
                'parentOrganization',
                true
            )
        );
        $this->view->byPocTable->addColumn(
            new Fisma_Yui_DataTable_Column(
                'Parent',
                true,
                'Fisma.TableFormat.formatOrganization',
                null,
                'displayParentOrganization',
                false,
                'string',
                'parentOrganization'
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

        $bySummaryQuery = Doctrine_Query::create()
            ->select('COUNT(v.id) as count, v.threatlevel, v.summary ')
            ->from('Vulnerability v')
            ->groupBy('v.summary, v.threatlevel')
            ->orderBy('v.threatlevel DESC')
            ->setHydrationMode(Doctrine::HYDRATE_ARRAY);
        $this->_addAclConditions($bySummaryQuery);
        $this->view->bySummary = $bySummaryQuery->execute();

        $bySummary = array();
        foreach ($this->view->bySummary as $statistic) {
            $bySummary[] = array(
                'summary' => $statistic['summary'],
                'threatLevel' => $statistic['threatLevel'],
                'total' => $statistic['count'],
                'displayTotal' => json_encode(array(
                    'url' => '/vm/vulnerability/list?q=isResolved/booleanNo/'
                           . 'summary/textContains/'
                           . $this->view->escape($statistic['summary'], 'url'),
                    'displayText' => $statistic['count']
                ))
            );
        }
        $this->view->bySummaryTable = new Fisma_Yui_DataTable_Local();
        $this->view->bySummaryTable->setRegistryName('Vulnerability.Dashboard.Analyst.bySummaryTable');
        $this->view->bySummaryTable->addEventListener('renderEvent', 'Fisma.Finding.restrictTableLength');
        $this->view->bySummaryTable->addColumn(
            new Fisma_Yui_DataTable_Column(
                $this->view->translate('Summary'),
                true,
                'Fisma.TableFormat.formatHtml',
                null,
                'summary'
            )
        );
        $this->view->bySummaryTable->addColumn(
            new Fisma_Yui_DataTable_Column(
                'Threat Level',
                true,
                null,
                null,
                'threatLevel'
            )
        );
        $this->view->bySummaryTable->addColumn(
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
        $this->view->bySummaryTable->addColumn(
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
        $this->view->bySummaryTable->setData($bySummary);

        $bySystemQuery = Doctrine_Query::create()
            ->select(
                'COUNT(v.id) as count, o.nickname as criteria, v.threatlevel, o.id, o.lft, o.rgt, o.level, ' .
                'a.orgSystemId, ot.iconId as icon, ot.nickname as type'
            )
            ->from('Asset a')
            ->leftJoin('a.Organization o')
            ->leftJoin('o.OrganizationType ot')
            ->leftJoin('a.Vulnerabilities v')
            ->groupBy('o.id, v.threatlevel')
            ->setHydrationMode(Doctrine::HYDRATE_ARRAY);
        //manually handle ACL conditions due to this query's unique join path (Organization => Asset => Vulnerabilities)
        $myOrgSystemIds = $this->_visibleOrgs;
        $viewUser = ($this->_me->viewAs()) ? $this->_me->viewAs() : $this->_me;
        $bySystemQuery->where('v.deleted_at is NULL AND v.isResolved <> ?', true);

        if (!$this->_acl->hasPrivilegeForClass('unaffiliated', 'Asset')) {
            $query
                ->andWhereIn('a.orgSystemId', $myOrgSystemIds)
                ->orWhere('v.deleted_at is NULL AND v.isResolved <> ?', true)
                ->andWhere('v.pocId = ?', $viewUser->id);
        }

        $this->view->bySystem = $bySystemQuery->execute();
        $bySystem = array();
        foreach ($this->view->bySystem as &$statistic) {
            $count = 0;
            foreach ($statistic['Vulnerabilities'] as &$finding) {
                $threatLevel = $finding['threatLevel'];
                if (!isset($statistic[$threatLevel])) {
                    $statistic[$threatLevel] = 0;
                }
                $statistic[$threatLevel] += $finding['count'];
                $count += $finding['count'];
            }
            $statistic['LOW'] = (empty($statistic['LOW'])) ? 0 : $statistic['LOW'];
            $statistic['MODERATE'] = (empty($statistic['MODERATE'])) ? 0 : $statistic['MODERATE'];
            $statistic['HIGH'] = (empty($statistic['HIGH'])) ? 0 : $statistic['HIGH'];
            $statistic['count'] = $count;

            if (empty($statistic['criteria'])) {
                $statistic['criteria'] = 'Unassigned';
            } else {
                if (empty($statistic['icon'])) { // the OrganizationType "system" doesn't have an icon
                    $statistic['icon'] = Doctrine_Query::create()
                        ->select('o.id, s.id, st.iconId as icon')
                        ->from('Organization o')
                        ->leftJoin('o.System s')
                        ->leftJoin('s.SystemType st')
                        ->where('o.id = ?', $statistic['Organization']['id'])
                        ->setHydrationMode(Doctrine::HYDRATE_ARRAY)
                        ->fetchOne();
                    //die(print_r($statistic));
                    $statistic['icon'] = $statistic['icon']['icon'];
                }
                $statistic['parent'] = Doctrine_Query::create()
                    ->select('o.id, o.nickname, i.id as icon, ot.nickname as type')
                    ->from('Organization o')
                    ->leftJoin('o.OrganizationType ot')
                    ->leftJoin('ot.Icon i')
                    ->where('o.lft < ?', $statistic['Organization']['lft'])
                    ->andWhere('o.rgt > ?', $statistic['Organization']['rgt'])
                    ->andWhere('o.level = ?', $statistic['Organization']['level'] - 1)
                    ->setHydrationMode(Doctrine::HYDRATE_ARRAY)
                    ->fetchOne();
                if (empty($statistic['parent']['icon'])) { // the OrganizationType "system" doesn't have an icon
                    $statistic['parent']['icon'] = Doctrine_Query::create()
                        ->select('o.id, s.id, st.iconId as icon, st.nickname as type')
                        ->from('Organization o')
                        ->leftJoin('o.System s')
                        ->leftJoin('s.SystemType st')
                        ->where('o.id = ?', $statistic['parent']['id'])
                        ->setHydrationMode(Doctrine::HYDRATE_ARRAY)
                        ->fetchOne();
                    $statistic['parent']['type'] = $statistic['parent']['icon']['type'];
                    $statistic['parent']['icon'] = $statistic['parent']['icon']['icon'];
                }
            }
            if (empty($statistic['parent']['nickname'])) {
                $statistic['parent']['nickname'] = "(top level)";
                $statistic['parent']['id'] = null;
                $statistic['parent']['icon'] = null;
                $statistic['parent']['type'] = "";
            }

            $bySystem[] = array(
                'organization' => $statistic['criteria'],
                'displayOrganization' => json_encode(array(
                    'iconId' => $statistic['icon'],
                    'iconSize' => 'small',
                    'displayName' => $statistic['criteria'],
                    'orgId' => $statistic['Organization']['id'],
                    'iconAlt' => $statistic['type']
                )),
                'parentOrganization' => $statistic['parent']['nickname'],
                'displayParentOrganization' => json_encode(array(
                    'iconId' => $statistic['parent']['icon'],
                    'iconSize' => 'small',
                    'displayName' => $statistic['parent']['nickname'],
                    'orgId' => $statistic['parent']['id'],
                    'iconAlt' => $statistic['parent']['type']
                )),
                'threatLevel' => json_encode(array(
                    'LOW' => $statistic['LOW'],
                    'MODERATE' => $statistic['MODERATE'],
                    'HIGH' => $statistic['HIGH'],
                    'criteriaQuery' => '/threatLevel/enumIs/',
                    'total' => $this->view->total
                )),
                'total' => $statistic['count'],
                'displayTotal' => json_encode(array(
                    'url' => '/vm/vulnerability/list?q=isResolved/booleanNo/'
                           . 'organization/textContains/'
                           . $this->view->escape($statistic['criteria'], 'url'),
                    'displayText' => $statistic['count']
                ))
            );
        }
        $this->view->bySystemTable = new Fisma_Yui_DataTable_Local();
        $this->view->bySystemTable->setRegistryName('Vulnerability.Dashboard.Analyst.bySystemTable');
        $this->view->bySystemTable->addEventListener('renderEvent', 'Fisma.Finding.restrictTableLength');
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
                'Parent',
                false,
                null,
                null,
                'parentOrganization',
                true
            )
        );
        $this->view->bySystemTable->addColumn(
            new Fisma_Yui_DataTable_Column(
                'Parent',
                true,
                'Fisma.TableFormat.formatOrganization',
                null,
                'displayParentOrganization',
                false,
                'string',
                'parentOrganization'
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

        $this->view->byAssetTable = $this->_getVulnerabilitiesByAssetTable();

        // Open Vulnerability Trending
        $this->view->vulnTrending = Doctrine_Query::create()
            ->select('period, SUM(open) AS totalOpen')
            ->from('VulnerabilityTrending vt')
            ->whereIn('organizationId', $this->_visibleOrgs)
            ->groupBy('period')
            ->orderBy('period DESC')
            ->limit(30)
            ->setHydrationMode(Doctrine::HYDRATE_SCALAR)
            ->execute();
    }

    protected function _addAclConditions(&$query)
    {
        $myOrgSystemIds = $this->_visibleOrgs;
        $viewUser = ($this->_me->viewAs()) ? $this->_me->viewAs() : $this->_me;

        $query
            ->leftJoin('v.Asset a')
            ->addSelect('v.threatlevel, a.id')
            ->where('v.deleted_at is NULL AND v.isResolved <> ?', true);

        if (!$this->_acl->hasPrivilegeForClass('unaffiliated', 'Asset')) {
            $query
                ->andWhereIn('a.orgSystemId', $myOrgSystemIds)
                ->orWhere('v.deleted_at is NULL AND v.isResolved <> ?', true)
                ->andWhere('v.pocId = ?', $viewUser->id);
        }
    }

    public function getToolbarButtons()
    {
        $buttons = array();

        return $buttons;
    }

    protected function _getVulnerabilitiesByAssetTable()
    {
        $byAssetQuery = Doctrine_Query::create()
            ->select(
                'a.id AS assetId, a.name AS assetName, ' .
                'o.id AS orgId, o.nickname AS orgNickname, ' .
                'IF(s.id IS NULL, ot.iconId, st.iconId) AS icon, ' .
                'IF(s.id IS NULL, ot.nickname, st.nickname) AS type, ' .
                "SUM(IF(v.threatLevel = 'LOW', 1, 0)) AS low, " .
                "SUM(IF(v.threatLevel = 'MODERATE', 1, 0)) AS moderate, " .
                "SUM(IF(v.threatLevel = 'HIGH', 1, 0)) AS high, " .
                'COUNT(v.id) AS count'
            )
            ->from('Asset a, a.Organization o, o.OrganizationType ot, o.System s, s.SystemType st, a.Vulnerabilities v')
            ->groupBy('a.id')
            ->where('v.deleted_at is NULL AND v.isResolved <> ?', true)
            /* Using SCALAR instead of ARRAy because of issues with the ARRAY hydrator */
            ->setHydrationMode(Doctrine::HYDRATE_SCALAR);
        //manually handle ACL conditions due to this query's unique join path (Organization => Asset => Vulnerabilities)
        $myOrgSystemIds = $this->_visibleOrgs;
        $viewUser = ($this->_me->viewAs()) ? $this->_me->viewAs() : $this->_me;
        if (!$this->_acl->hasPrivilegeForClass('unaffiliated', 'Asset')) {
            $byAssetQuery
                ->andWhereIn('o.id', $myOrgSystemIds)
                ->orWhere('v.deleted_at is NULL AND v.isResolved <> ?', true)
                ->andWhere('v.pocId = ?', $viewUser->id);
        }

        $byAsset = $byAssetQuery->execute();
        $total = 0;
        foreach ($byAsset as $record) {
            $total += $record['v_count'];
        }
        $rows = array();
        foreach ($byAsset as $record) {
            $rows[] = array(
                'assetName' => $record['a_assetName'],
                'organization' => $record['o_orgNickname'],
                'displayOrganization' => json_encode(array(
                    'iconId' => $record['s_icon'],
                    'iconSize' => 'small',
                    'displayName' => $record['o_orgNickname'],
                    'orgId' => $record['o_orgId'],
                    'iconAlt' => $record['s_type']
                )),
                'threatLevel' => json_encode(array(
                    'LOW' => $record['v_low'],
                    'MODERATE' => $record['v_moderate'],
                    'HIGH' => $record['v_high'],
                    'criteriaQuery' => '/threatLevel/enumIs/',
                    'total' => $total
                )),
                'total' => $record['v_count'],
                'displayTotal' => json_encode(array(
                    'url' => '/vm/vulnerability/list?q=isResolved/booleanNo/'
                           . 'asset/textContains/'
                           . $this->view->escape($record['a_assetName'], 'url'),
                    'displayText' => $record['v_count']
                ))
            );
        }
        $table = new Fisma_Yui_DataTable_Local();
        $table->setRegistryName('Vulnerability.Dashboard.Analyst.byAssetTable');
        $table->addEventListener('renderEvent', 'Fisma.Finding.restrictTableLength');
        $table->addColumn(
            new Fisma_Yui_DataTable_Column(
                'Asset',
                true,
                null,
                null,
                'assetName'
            )
        );
        $table->addColumn(
            new Fisma_Yui_DataTable_Column(
                'System',
                false,
                null,
                null,
                'organization',
                true
            )
        );
        $table->addColumn(
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
        $table->addColumn(
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
        $table->addColumn(
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
        $table->addColumn(
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
        $table->setData($rows);

        return $table;
    }
}
