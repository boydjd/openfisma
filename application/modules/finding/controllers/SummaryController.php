<?php
/**
 * Copyright (c) 2011 Endeavor Systems, Inc.
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
 * The Summary controller hands the finding summary views.
 *
 * @author     Andrew Reeves <andrew.reeves@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Controller
 */
class Finding_SummaryController extends Fisma_Zend_Controller_Action_Security
{
    /**
     * Create the additional PDF, XLS and RSS contexts for this class.
     *
     * @return void
     */
    public function init()
    {
        $this->_helper->fismaContextSwitch()
                      ->addActionContext('data', 'json')
                      ->initContext();

        $this->_helper->ajaxContext()
             ->addActionContext('index', 'html')
             ->initContext();

        parent::init();
    }

    /**
     * Presents the view which contains the summary table. The summary table loads summary data
     * asynchronously by invoking the summaryDataAction().
     *
     * @GETAllowed
     * @return void
     */
    public function indexAction()
    {
        $this->_acl->requirePrivilegeForClass('read', 'Finding');

        // Create a list of mitigation types
        $this->view->mitigationTypes = array(
            'none' => ''
        );

        // Get a list of workflow steps
        $allStatuses = array();
        $closedStatuses = array();
        $workflows = Doctrine::getTable('Workflow')->listArray('finding');
        foreach ($workflows as $workflow) {
            $this->view->mitigationTypes[$workflow->id] = $workflow->name;
            foreach ($workflow->WorkflowSteps as $step) {
                if ($step->allottedTime === 'unlimited') {
                    $closedStatuses[] = $step->id;
                }
                $allStatuses[] = array(
                    'stepId' => $step->id,
                    'workflowId' => $workflow->id,
                    'label' => $step->label,
                    'name' => $step->name,
                    'workflowName' => $workflow->name
                );
            }
        }
        $this->view->steps = $allStatuses;
        $this->view->closedSteps = $closedStatuses;

        // Create tooltip texts
        $tooltips = array();
        $tooltips['viewBy'] = $this->view->partial("/summary/view-by-tooltip.phtml");
        array_walk($tooltips,
            function (&$value)
            {
                $value = str_replace("\n", " ", $value);
            }
        );
        $this->view->tooltips = $tooltips;

        // Create a list of finding sources with a default option
        $findingSources = Doctrine::getTable('Source')->findAll()->toKeyValueArray('id', 'nickname');
        $this->view->findingSources = array('none' => '') + $findingSources;
        $this->view->csrfToken = $this->_helper->csrf->getToken();
    }

    /**
     * Invoked asynchronously to load data for the summary table.
     *
     * @GETAllowed
     * @return void
     */
    public function dataAction()
    {
        $this->_acl->requirePrivilegeForClass('read', 'Finding');

        $summaryType = $this->getRequest()->getParam('summaryType');

        $findingParams = array(
            'findingSource' => null,
            'mitigationType' => null
        );

        foreach ($findingParams as $key => &$value) {
            $temp = $this->getRequest()->getParam($key);

            if ($temp !== 'none') {
                $value = Doctrine_Manager::connection()->quote($temp);
            }
        }

        switch ($summaryType) {
            case 'organizationHierarchy':
                $treeNodes = $this->_getOrganizationHierarchyData($findingParams);
                break;

            case 'systemAggregation':
                $treeNodes = $this->_getSystemAggregationData($findingParams);
                break;

            case 'pointOfContact':
                $treeNodes = $this->_getPointOfContactData($findingParams);
                break;

            default:
                throw new Fisma_Zend_Exception("Invalid summary type ($summaryType)");
        }

        if (empty($treeNodes)) {
            $this->view->rootNodes = null;
            return;
        }
        // Convert "numbers" to actual numbers
        array_walk_recursive($treeNodes,
            function (&$scalar)
            {
                if (is_numeric($scalar)) $scalar = (int)$scalar;
            }
        );

        /*
         * Remove the prefixed column alias that HYDRATE_SCALAR adds, and group all key-value pairs under
         * a new key called "nodeData".
         */
        foreach ($treeNodes as &$treeNode) {
            foreach ($treeNode as $k => $v) {
                $underscoreString = strstr($k, '_');
                if ($underscoreString !== FALSE) {
                    $newName = substr($underscoreString, 1);
                    $treeNode['nodeData'][$newName] = $v;
                    unset($treeNode[$k]);
                }
            }

            $treeNode['children'] = array();
        }

        // Create hierarchical structure from flat array
        $temp = array(array());
        foreach ($treeNodes as $n => $a) {
            $d = $a['nodeData']['level'] + 1;
            $temp[$d-1]['children'][] = &$treeNodes[$n];
            $temp[$d] = &$treeNodes[$n];
        }

        $this->view->rootNodes = $temp[0]['children'];
    }

    /**
     * Get statistics about number of findings in each status for each of this user's systems and organizations.
     *
     * Organizations and system are grouped together by their organizational hierarchy.
     *
     * @param $findingParams Array A dictionary of parameters related to findings.
     * @return Array Flat list of organizations and finding data
     */
    private function _getOrganizationHierarchyData($findingParams)
    {
        $sourceJoinCondition = $this->_getFindingSourceJoinConditions($findingParams);
        $typeJoinCondition = $this->_getFindingTypeJoinConditions($findingParams);

        // First get a list of all organizations, even ones this user is not allowed to see. This is used to
        // fill in any "missing" nodes in tree structure.
        $organizationsQuery = Doctrine_Query::create()
                              ->from('Organization o')
                              ->select('o.id, o.level, o.lft, o.rgt, o.nickname AS rowLabel')
                              ->addSelect("CONCAT(o.nickname, ' - ', o.name) AS label")
                              ->leftJoin('o.OrganizationType ot')
                              ->addSelect("'organization' AS searchKey")
                              ->leftJoin('o.System s')
                              ->leftJoin('s.SystemType st')
                              ->addSelect("IF(ot.nickname = 'system', st.iconId, ot.iconId) iconId")
                              ->addSelect("IF(ot.nickname = 'system', st.name, ot.name) typeLabel")
                              ->groupBy('o.id')
                              ->orderBy('o.lft');
        $organizations = $organizationsQuery->execute(null, Doctrine::HYDRATE_SCALAR);

        // Now get the user's actual organization nodes.
        $userOrgQuery = $this->_me->getOrganizationsByPrivilegeQuery('finding', 'read')
                                  ->select('o.id, o.level, o.lft, o.rgt, o.nickname AS rowLabel')
                                  ->addSelect("CONCAT(o.nickname, ' - ', o.name) AS label")
                                  ->leftJoin('o.OrganizationType ot')
                                  ->addSelect("'organization' AS searchKey")
                                  ->leftJoin('o.System s')
                                  ->leftJoin('s.SystemType st')
                                  ->addSelect("IF(ot.nickname = 'system', st.iconId, ot.iconId) iconId")
                                  ->addSelect("IF(ot.nickname = 'system', st.name, ot.name) typeLabel")
                                  ->leftJoin("o.Findings f ON o.id = f.responsibleorganizationid $sourceJoinCondition")
                                  ->groupBy('o.id')
                                  ->orderBy('o.lft');

        if ($this->_me->username != 'root') {
            $userOrgQuery->distinct()
                         ->addGroupBy('r.id');
        }

        $this->_addFindingStatusFields($userOrgQuery);
        if (!empty($typeJoinCondition)) {
            $userOrgQuery->innerJoin("f.CurrentStep ws")->andWhere($typeJoinCondition);
        }

        //die(print_r($this->_prepareSummaryQueryParameters()) . '');

        $userOrgs = $userOrgQuery->execute($this->_prepareSummaryQueryParameters(), Doctrine::HYDRATE_SCALAR);
        if (empty($userOrgs)) {
            return $userOrgs;
        }

        // Stitch together the two organization lists.
        $orgMax = count($organizations) - 1;
        $previousOrg = null;
        $currentUserOrgIndex = 0;
        $currentUserOrg = $userOrgs[$currentUserOrgIndex];
        $parents = array();

        for ($currentOrgIndex = 0; $currentOrgIndex <= $orgMax; $currentOrgIndex++) {
            $currentOrg = $organizations[$currentOrgIndex];

            // Keep track of parents for current node
            if (isset($previousOrg)) {
                if ($previousOrg['o_level'] < $currentOrg['o_level']) {
                    array_push($parents, $currentOrgIndex - 1);
                } elseif ($previousOrg['o_level'] > $currentOrg['o_level']) {
                    array_pop($parents);
                }
            }

            if ($currentOrg['o_id'] == $currentUserOrg['o_id']) {
                $currentUserOrg['visited'] = true;
                array_splice($organizations, $currentOrgIndex, 1, array($currentUserOrg));
                $currentUserOrgIndex++;

                $currentUserOrg = isset($userOrgs[$currentUserOrgIndex]) ? $userOrgs[$currentUserOrgIndex] : null;

                foreach ($parents as $parent) {
                    // Mark visited parents so we can prune unvisited subtrees later
                    $organizations[$parent]['visited'] = true;
                }
            }

            $previousOrg = $currentOrg;
        }

        // Prune unvisited subtrees
        for ($currentOrgIndex = 0; $currentOrgIndex <= $orgMax; $currentOrgIndex++) {
            $currentOrg = $organizations[$currentOrgIndex];

            if (!isset($currentOrg['visited'])) {
                unset($organizations[$currentOrgIndex]);
            }
        }

        return $organizations;
    }

    /**
     * Get statistics about number of findings in each status for Point Of Contact.
     *
     * Every user can see *all* points of contact across *all* organizations.
     *
     * @param $findingParams Array A dictionary of parameters related to findings.
     * @return Array Flat list of points of contact and organizations.
     */
    private function _getPointOfContactData($findingParams)
    {
        $sourceJoinCondition = $this->_getFindingSourceJoinConditions($findingParams);
        $typeJoinCondition = $this->_getFindingTypeJoinConditions($findingParams);

        // Get the list of organizations (not including systems)
        $organizationQuery = Doctrine_Query::create()
                             ->from('Organization o')
                             ->select('o.id, o.name, o.nickname, o.level, "organization" AS type')
                             ->addSelect("CONCAT(o.nickname, ' - ', o.name) AS label")
                             ->addSelect('o.nickname AS rowLabel')
                             ->addSelect("'pocOrg' AS searchKey")
                             ->leftJoin('o.OrganizationType ot')
                             ->addSelect("ot.name typeLabel, ot.iconId iconId")
                             ->andWhere('o.systemId IS NULL')
                             ->groupBy('o.id')
                             ->orderBy('o.lft');

        $organizations = $organizationQuery->execute(null, Doctrine::HYDRATE_SCALAR);

        $userOrgs = $this->_me->getOrganizationsByPrivilegeQuery('finding', 'read')
                              ->select('o.id')
                              ->execute(null, Doctrine::HYDRATE_SCALAR);

        $userOrgIds = array();
        foreach ($userOrgs as $userOrg) {
            $userOrgIds[] = $userOrg['o_id'];
        }

        // Get list of point of contacts
        $pocLabel = $this->view->column('pocId', Doctrine::getTable('Finding'), false);
        $pointOfContactQuery = Doctrine_Query::create()
                               ->from('User u')
                               ->addSelect('u.id, u.reportingOrganizationId, "poc" AS type')
                               ->addSelect("CONCAT(u.nameFirst, ' ', u.nameLast) AS label")
                               ->addSelect("'$pocLabel' AS typeLabel")
                               ->addSelect("'poc' AS icon, u.username AS rowLabel")
                               ->addSelect("'pocUser' AS searchKey")
                               ->where('u.reportingOrganizationId IS NOT NULL')
                               ->andWhere('(u.lockType IS NULL OR u.lockType <> ?)', 'manual')
                               ->groupBy('u.id')
                               ->orderBy('u.reportingOrganizationId, u.nameFirst, u.nameLast');

        $pocList = $pointOfContactQuery->execute(null, Doctrine::HYDRATE_SCALAR);

        // Create an array of points of contact grouped together by their reporting organization.
        $pointsOfContact = array();

        foreach ($pocList as $poc) {
            $organizationId = $poc['u_reportingOrganizationId'];

            if (!isset($pointsOfContact[$organizationId])) {
                $pointsOfContact[$organizationId] = array();
            }

            $pointsOfContact[$organizationId][] = $poc;
        }

        // Get a list of finding statistics for each POC
        $findingQuery = $this->_me->getOrganizationsByPrivilegeQuery('finding', 'read')
                                  ->select('o.id, f.id, poc.id')
                                  ->leftJoin("o.Findings f ON o.id = f.responsibleorganizationid $sourceJoinCondition")
                                  ->innerJoin('f.PointOfContact poc')
                                  ->groupBy('poc.id')
                                  ->orderBy('poc.id');

        if ($this->_me->username != 'root') {
            $findingQuery->distinct()
                         ->addGroupBy('r.id');
        }

        $this->_addFindingStatusFields($findingQuery);
        if (!empty($typeJoinCondition)) {
            $findingQuery->innerJoin("f.CurrentStep ws")->andWhere($typeJoinCondition);
        }
        $tempFindings = $findingQuery->execute($this->_prepareSummaryQueryParameters(), Doctrine::HYDRATE_SCALAR);
        $findings = array();
        foreach ($tempFindings as $finding) {
            $findings[(int)$finding['poc_id']] = $finding;
        }

        // Stitch together the organizations, POCs, and findings
        $currentOrganization = 0;

        while (isset($organizations[$currentOrganization])) {
            $currentOrganizationId = $organizations[$currentOrganization]['o_id'];

            if ($organizations[$currentOrganization]['o_level'] > 0) {
                $org = Doctrine::getTable('organization')->findOneById($currentOrganizationId);

                /**
                 * If an organization's parent is a system, it needs to update the organization and its descendants
                 * levels so that the organization and its descendants can show on the summary tree.
                 */
                if ($org->getNode()->getParent()->systemId) {
                    $parent = $this->_getNearestOrgParent($org->getNode());

                    /**
                     * If an organization's nearest parent with organization type is found, then update the
                     * organization's level to its parent level + 1 because the level difference between parent and
                     * its direct children needs to be 1 to build the tree structure correctly.
                     */
                    if ($parent) {
                        $organizations[$currentOrganization]['o_level']
                            = $this->_getOrgLevel($organizations, $parent->nickname) + 1;
                    } else {

                        // If no parent with organization type, it means the root such as BGA becomes system.
                        $organizations[$currentOrganization]['o_level'] = 0;
                    }

                    $levelDifference = $org->level - $organizations[$currentOrganization]['o_level'];
                    $this->_updateLevel($organizations, $org, $levelDifference);
                }
            }

            if (isset($pointsOfContact[$currentOrganizationId])) {
                $level = $organizations[$currentOrganization]['o_level'];

                foreach ($pointsOfContact[$currentOrganizationId] as &$poc) {
                    if (isset($findings[$poc['u_id']])) {
                        $poc = array_merge($poc, $findings[$poc['u_id']]);
                    }
                    $poc['p_level'] = $level + 1;
                }

                array_splice($organizations, $currentOrganization + 1, 0, $pointsOfContact[$currentOrganizationId]);
                $currentOrganization += count($pointsOfContact[$currentOrganizationId]) + 1;
            } else {
                $currentOrganization++;
            }
        }

        return $organizations;
    }

    /**
     * Recursively update the level of an organization's children except the child with system type and its descendants
     *
     * @param array $organizations The array data of organization for tree structure.
     * @param object $organization An organization to update
     * @param integer $difference Use to update an organization's level
     */
    private function _updateLevel(&$organizations, $organization, $difference)
    {
        if ($organization->getNode()->hasChildren()) {
            $children = $organization->getNode()->getChildren();
            for ($j = 0; $j < count($children); $j++) {
                if (is_null($children[$j]->systemId)) {
                    for ($i = 0; $i < count($organizations); $i++) {
                        if (isset($organizations[$i]['o_nickname'])
                            && $organizations[$i]['o_nickname'] == $children[$j]->nickname) {

                            $organizations[$i]['o_level'] = $organizations[$i]['o_level'] - $difference;
                        }
                    }
                    $this->_updateLevel($organizations, $children[$j], $difference);
                }
            }
        }
    }

    /**
     * Return a node's nearest parent with organization type if any, otherwise, false
     *
     * @param $node  An organization node.
     */
    private function _getNearestOrgParent($node)
    {
        $ancestors = $node->getAncestors();
        if ($ancestors) {
            for ($i = count($ancestors) - 1; $i >= 0; $i--) {
                if (is_null($ancestors[$i]->systemId)) {
                    return $ancestors[$i];
                }
            }
        }
        return false;
    }

    /**
     * Return an organization level if found, otherwise, false
     *
     * @param array $organizations The array data of organization
     * @param string $nickname The nickname of an organization to update
     */
    private function _getOrgLevel($organizations, $nickname)
    {
        for ($i = 0; $i < count($organizations); $i++) {
            if (isset($organizations[$i]['o_nickname']) && $organizations[$i]['o_nickname'] == $nickname) {
                return $organizations[$i]['o_level'];
            }
        }

        return false;
    }

    /**
     * Returns DQL string that can be used as finding join conditions (i.e. part of "ON" clause)
     *
     * @param $findingParams Array Optional parameters to join condition.
     * @return string
     */
    public function _getFindingSourceJoinConditions($findingParams)
    {
        $dql = '';

        if (isset($findingParams['findingSource'])) {
            $dql .= " AND f.sourceId = " . $findingParams['findingSource'];
        }

        return $dql;
    }

    /**
     * Returns DQL string that can be used as finding join conditions (i.e. part of "ON" clause)
     *
     * @param $findingParams Array Optional parameters to join condition.
     * @return string
     */
    public function _getFindingTypeJoinConditions($findingParams)
    {
        $dql = '';

        // These are escaped in the dataAction method and are safe to interpolate.
        if (isset($findingParams['mitigationType'])) {
            $dql .= "ws.workflowId = " . $findingParams['mitigationType'];
        }
        return $dql;
    }

    /**
     * Add fields to a query that get the number of findings in each status for each system or organization.
     *
     * This modifies the query that is passed to it, it does not return a new query.
     *
     * NOTE: The query that's passed in must have a table alias called "f" and it must be an alias for the Finding
     * table.
     *
     * @param $query
     */
    public function _addFindingStatusFields(Doctrine_Query $query)
    {
        $allStatuses = array();
        $workflows = Doctrine::getTable('Workflow')->listArray('finding');
        foreach ($workflows as $workflow) {
            foreach ($workflow->WorkflowSteps as $step) {
                $allStatuses[] = $step->id;
            }
        }

        // Get ontime and overdue statistics for each status where we track overdues
        foreach ($allStatuses as $status) {
            $statusName = urlencode($status);

            $query->addSelect(
                "SUM(
                    IF(f.currentStepId LIKE ? AND (DATEDIFF(NOW(), f.nextduedate) <= 0 OR f.nextduedate is NULL), 1, 0)
                ) ontime_$statusName"
            );

            $query->addSelect(
                "SUM(
                    IF(f.currentStepId LIKE ? AND DATEDIFF(NOW(), f.nextduedate) > 0, 1, 0)
                ) overdue_$statusName"
            );
        }

        // Add the last 3 columns: OPEN, CLOSED, TOTAL
        $query->addSelect(
            "SUM(
                IF(f.isResolved <> 1 AND (DATEDIFF(NOW(), f.nextduedate) <= 0 OR f.nextduedate is NULL), 1, 0)
            ) ontime_ALL+OPEN"
        );

        $query->addSelect(
            "SUM(
                IF(f.isResolved <> 1 AND DATEDIFF(NOW(), f.nextduedate) > 0, 1, 0)
            ) overdue_ALL+OPEN"
        );

        $query->addSelect("SUM(IF(f.isResolved = 1, 1, 0)) closed");
        $query->addSelect("SUM(IF(f.id IS NOT NULL, 1, 0)) total");
    }

    /**
     * Set each finding status except 'CLOSED' to an array
     *
     * @return array The list of finding status.
     */
    private function _prepareSummaryQueryParameters()
    {
        $allStatuses = array();
        $workflows = Doctrine::getTable('Workflow')->listArray('finding');
        foreach ($workflows as $workflow) {
            foreach ($workflow->WorkflowSteps as $step) {
                if ($step->isResolved) {
                    //continue;
                }
                $allStatuses[] = $step->id;
            }
        }
        $findingStatus = array();

        foreach ($allStatuses as $status) {
            // Since there are two finding status in a query constructed at _addFindingStatusFields(),
            // it needs to add the status twice accordingly.
            array_push($findingStatus, $status);
            array_push($findingStatus, $status);
        }

        return $findingStatus;
    }
}
