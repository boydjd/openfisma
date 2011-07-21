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

        if (in_array($this->getRequest()->getParam('format'), array('pdf', 'xls'))) {
            $this->_helper->reportContextSwitch()
                          ->addActionContext('data', array('pdf', 'xls'))
                          ->initContext();
        }

        parent::init();
    }

    /**
     * Presents the view which contains the summary table. The summary table loads summary data
     * asynchronously by invoking the summaryDataAction().
     * 
     * @return void
     */
    public function indexAction()
    {
        $this->_acl->requirePrivilegeForClass('read', 'Finding');
                
        $mitigationEvaluationQuery = Doctrine_Query::create()
                                     ->from('Evaluation e')
                                     ->where('approvalGroup = \'action\'')
                                     ->orderBy('e.precedence');

        $mitigationEvaluations = $mitigationEvaluationQuery->execute();
        
        $evidenceEvaluationQuery = Doctrine_Query::create()
                                     ->from('Evaluation e')
                                     ->where('approvalGroup = \'evidence\'')
                                     ->orderBy('e.precedence');
        $evidenceEvaluations = $evidenceEvaluationQuery->execute();
        
        // Create a list of the columns displayed on the summary
        $columns = array('NEW', 'DRAFT');

        foreach ($mitigationEvaluations as $evaluation) {
            $columns[] = $evaluation->nickname;
        }
        
        $columns[] = 'EN';

        foreach ($evidenceEvaluations as $evaluation) {
            $columns[] = $evaluation->nickname;
        }
        
        $columns[] = 'CLOSED';
        $columns[] = 'TOTAL';

        $this->view->statusArray = $columns;
        $this->view->mitigationEvaluations = $mitigationEvaluations;
        $this->view->evidenceEvaluations = $evidenceEvaluations;
        $this->view->findingSources = Doctrine::getTable('Source')->findAll();
    }

    /**
     * Invoked asynchronously to load data for the summary table.
     * 
     * @return void
     */
    public function dataAction()
    {
        $this->_acl->requirePrivilegeForClass('read', 'Finding');

        $view = $this->getRequest()->getParam('view', 'OHV');

        $type = $this->getRequest()->getParam('type');
        $source = $this->getRequest()->getParam('sourceNickname');        
        $format = $this->_request->getParam('format');
        // Prepare summary data

        // Get user organizations
        $organizationsQuery = $this->_me->getOrganizationsByPrivilegeQuery('finding', 'read');
        $organizationsQuery->select('o.id');
        $organizationsQuery->setHydrationMode(Doctrine::HYDRATE_NONE);
        $organizations = $organizationsQuery->execute();

        foreach ($organizations as $k => $v) {
            $organizations[] = $v[0];
            unset($organizations[$k]);
        }

        // Get finding summary counts
        switch ($view) {
            case 'POCV':
                $organizations = $this->_summaryDataPocv($type, $source, $organizations);
                break;
            case 'OHV':
                $organizations = $this->_summaryDataOhv($type, $source, $organizations);
                break;
            case 'SAV':
                $organizations = $this->_summaryDataSav($type, $source, $organizations);
                break;
            default:
                throw new Exception ('Unknown summary view requested.');
        }

        // For excel and PDF requests, return a table format. For JSON requests, return a hierarchical
        // format
        if ('pdf' == $format || 'xls' == $format) {
            $report = new Fisma_Report();
            $report->setTitle('Finding Summary')
                   ->addColumn(new Fisma_Report_Column('Organization/Information System'));
            
            $allStatuses = Finding::getAllStatuses();
            foreach ($allStatuses as $status) {
                $report->addColumn(new Fisma_Report_Column($status));
            }

            $report->addColumn(new Fisma_Report_Column('TOTAL'));

            // Create a table of data based on the rows which need to be displayed
            $tableData = array();
            $expandedRows = $this->getRequest()->getParam('e');
            if (!is_array($expandedRows)) {
                $expandedRows = array($expandedRows);
            }
            $collapsedRows = $this->getRequest()->getParam('c');
            if (!is_array($collapsedRows)) {
                $collapsedRows = array($collapsedRows);
            }

            foreach ($organizations as $organization) {
                /** @todo pad left string */
                $indentAmount = $organization['level'] * 3;
                $orgName = str_pad(
                    $organization['label'], $indentAmount + strlen($organization['label']), ' ', STR_PAD_LEFT
                );

                // Decide whether to show rolled up counts or single row counts
                if (in_array($organization['id'], $collapsedRows)) {
                    // Show rolled up row counts
                    $ontimeRow = array_merge(array($orgName), array_values($organization['all_ontime']));
                    $tableData[] = $ontimeRow;

                    // If there are overdues, then create another row for overdues
                    if (array_sum($organization['all_overdue']) > 0) {
                        $overdueRow = array_merge(
                            array("$orgName (Overdue Items)"), 
                            array_values($organization['all_overdue'])
                        );

                        // Add 2 blank columns at the end of the overdue row (for CLOSED and TOTAL)
                        $overdueRow[] = 'n/a';
                        $overdueRow[] = 'n/a';

                        $tableData[] = $overdueRow;
                    }
                } elseif (in_array($organization['id'], $expandedRows)) {
                    // Show single row counts
                    $ontimeRow = array_merge(array($orgName), array_values($organization['single_ontime']));
                    $tableData[] = $ontimeRow;

                    // If there are overdues, then create another row for overdues
                    if (array_sum($organization['single_overdue']) > 0) {
                        $overdueRow = array_merge(
                            array("$orgName (Overdue Items)"), 
                            array_values($organization['single_overdue'])
                        );
                        
                        // Add 2 blank columns at the end of the overdue row (for CLOSED and TOTAL)
                        $overdueRow[] = 'n/a';
                        $overdueRow[] = 'n/a';

                        $tableData[] = $overdueRow;
                    }                    
                }
            }

            $report->setData($tableData);

            $this->_helper->reportContextSwitch()->setReport($report);
        } else {
            $this->view->summaryData = $this->_assembleTree($organizations);
        } 
    }

    /**
     * Build tree from flat list
     *
     * @param array $organizations
     * @return array
     */
    protected function _assembleTree(array $organizations)
    {
        $temp = array(array());
        foreach ($organizations as $n => $a) {
            $d = $a['level']+1;
            $temp[$d-1]['children'][] = &$organizations[$n];
            $temp[$d] = &$organizations[$n];
        }
        return $temp[0]['children'];
    }

    /**
     * Point of Contact View
     *
     * @param string $type Type of findings
     * @param int $source Finding source id
     * @param array $organizations Array of organizations
     * @return array
     */
    protected function _summaryDataPocv($type, $source, $organizations)
    {
        $organizations = $this->_getPocvCounts($organizations, $type, $source);

        // seperate POCs from organizations
        $lastOrgId = null;
        $newOrgArray = array();
        foreach ($organizations as $org) {
            if ($org['o_id'] !== $lastOrgId) {
                $lastOrgId = $org['o_id'];
                $newOrgArray[] = array(
                    'label' => $org['o_label'],
                    'nickname' => $org['o_nickname'],
                    'id' => $org['o_id'],
                    'orgType' => $org['o_orgType'],
                    'orgTypeLabel' => $org['o_orgTypeLabel'],
                    'level' => $org['o_level']
                );
            }
            if (!empty($org['p_pocId'])) {
                $pocArray = array(
                    'label' => $org['p_nameLast'] . ', ' . $org['p_nameFirst'],
                    'nickname' => $org['p_username'],
                    'id' => 'p' . $org['p_pocId'],
                    'orgType' => 'poc',
                    'orgTypeLabel' => 'Point of Contact',
                    'level' => $org['o_level'] + 1
                );
                foreach ($org as $key => $value) {
                    if (substr($key, 0, 2) === 'f_' || substr($key, 0, 11) === 'evaluation_') {
                        $pocArray[$key] = $value;
                    }
                }
                $newOrgArray[] = $pocArray;
            }
        }

        $organizations = $this->_prepareSummaryData($newOrgArray);

        $orgTree = $this->_assembleTree($organizations);
        foreach ($orgTree as &$tree) {
            $this->_trickleValues($tree);
        }
        $flatOrgTree = array();
        foreach ($orgTree as $node) {
            $flatOrgTree = array_merge($flatOrgTree, $this->_flattenTree($node));
        }
        return $flatOrgTree;
    }

    /**
     * Trickle values up the tree
     *
     * @param array &$treeNode
     * @return void
     */
    protected function _trickleValues(array &$treeNode)
    {
        $treeNode['all_ontime'] = $treeNode['single_ontime'];
        $treeNode['all_overdue'] = $treeNode['single_overdue'];

        foreach ($treeNode['children'] as &$childNode) {
            $this->_trickleValues($childNode);
            foreach ($treeNode['all_ontime'] as $status => &$value) {
                $value += $childNode['all_ontime'][$status];
            }
            foreach ($treeNode['all_overdue'] as $status => &$value) {
                $value += $childNode['all_overdue'][$status];
            }
        }
    }

    private static function _flattenTree($node)
    {
        $children = $node['children'];
        $node['children'] = array();
        $result = array($node);
        foreach ($children as $child) {
            $subtree = self::_flattenTree($child);

            $result = array_merge($result, $subtree);
        }
        return $result;
    }

    /**
     * Organization Hiararchy View
     *
     * @param string $type Type of findings
     * @param int $source Finding source id
     * @param array $organizations Array of organizations
     * @return array
     */
    protected function _summaryDataOhv($type, $source, $organizations)
    {
        $organizations = $this->_getSummaryCounts($organizations, $type, $source);
        $organizations = $this->_prepareSummaryData($organizations);

        return $organizations;
    }

    /**
     * System Aggregation View
     *
     * @param string $type Type of findings
     * @param int $source Finding source id
     * @param array $organizations Array of organizations
     * @return array
     */
    protected function _summaryDataSav($type, $source, $organizations)
    {
        $organizations = $this->_getSavCounts($organizations, $type, $source);
        $organizations = $this->_prepareSummaryData($organizations);

        /*
         * Since this data doesn't have left, right and level values, we need to compute level and reorder
         * into tree order, instead of simply alphabetical order.
         * First we need to make the organization array an id => org map.
         */
        $orgMap = array();
        $orgTree = array();
        foreach ($organizations as &$org) {
            $orgMap[$org['id']] = &$org;
        }
        // now build the tree:
        foreach ($organizations as &$org) {
            if (empty($org['aggregateSystemId'])) {
                $orgTree[] = &$org;
            } else {
                $orgMap[$org['aggregateSystemId']]['children'][] = &$org;
            }
        }
        // create a new flat organization list in tree order
        $organizations = array();
        foreach ($orgTree as $node) {
            $organizations = array_merge($organizations, self::_flattenSystemTreeNode($node));
        }

        return $organizations;
    }

    /*
     * Takes a system tree node and flattens it into an array including all children.
     * Additionally it sums up the counts for the node's subtree.
     *
     * @param array $node The node to process
     * @param int $depth The depth of this iteration (for recursion)
     * @return array An array of nodes in the subtree
     */
    private static function _flattenSystemTreeNode($node, $depth = 0)
    {
        $children = $node['children'];
        $node['level'] = $depth;
        $node['children'] = array();
        $result = array();
        foreach ($children as $child) {
            $subtree = self::_flattenSystemTreeNode($child, $depth + 1);
            foreach (array('all_ontime', 'all_overdue') as $row) {
                foreach ($subtree[0][$row] as $field => $value) {
                    $node[$row][$field] += $value;
                }
            }

            $result = array_merge($result, $subtree);
        }
        return array_merge(array($node), $result);
    }

    /**
     * Returns summary counts for organizations
     *
     * @param array $organization Array of organizations to get counts for
     * @param string $type Type of findings to get counts for
     * @param int $source Finding source ids to get counts for
     * @return array
     */
    private function _getSummaryCounts($organization, $type, $sourceNickname)
    {
        // Doctrine won't let me paramaterize within a somewhat complex statement, so we'll just protect against
        // injection by using sprintf.
        if (!empty($sourceNickname)) {
            $source = Doctrine::getTable('Source')->findOneByNickname($sourceNickname);
            $sourceId = $source->id;            
        }

        $sourceCondition = isset($source) ? "AND finding.sourceId = $sourceId" : "";

        $allStatuses = Finding::getAllStatuses();

        $summary = Doctrine_Query::create()
            ->select("CONCAT_WS(' - ', parent.nickname, parent.name) label")
            ->addSelect('parent.nickname nickname');

        foreach ($allStatuses as $status) {
            $s = $status;
            $status = urlencode($status);
            // These statuses are constant, and should never change
            if (array_search($status, array('PEND', 'NEW', 'DRAFT', 'EN', 'CLOSED'))) {
                $summary->addSelect(
                    "SUM(IF(finding.status = '$s' AND finding.responsibleorganizationid = parent.id"
                    . " $sourceCondition, IF(DATEDIFF(NOW(), finding.nextduedate) > 0, 0, 1), 0)) singleOntime$status"
                );
                $summary->addSelect(
                    "SUM(IF(finding.status = '$s' AND finding.responsibleorganizationid = parent.id"
                    . " $sourceCondition, IF(DATEDIFF(NOW(), finding.nextduedate) > 0, 1, 0), 0)) singleOverdue$status"
                );
                $summary->addSelect(
                    "SUM(IF(finding.status = '$s' $sourceCondition, IF(DATEDIFF(NOW(), finding.nextduedate) > 0,"
                    . "0, 1), 0)) ontime$status"
                );
                $summary->addSelect(
                    "SUM(IF(finding.status = '$s' $sourceCondition, IF(DATEDIFF(NOW(), finding.nextduedate) > 0,"
                    . "1, 0), 0)) overdue$status"
                );
            } else { // These are statuses relating to workflow when finding status is EA or MSA, which are dynamic
                $summary->addSelect(
                    "SUM(IF(evaluation.nickname = '$s' AND finding.responsibleorganizationid = parent.id"
                    . " $sourceCondition, IF(DATEDIFF(NOW(), finding.nextduedate) > 0, 0, 1), 0)) singleOntime$status"
                );
                $summary->addSelect(
                    "SUM(IF(evaluation.nickname = '$s' AND finding.responsibleorganizationid = parent.id"
                    . " $sourceCondition, IF(DATEDIFF(NOW(), finding.nextduedate) > 0, 1, 0), 0)) singleOverdue$status"
                );
                $summary->addSelect(
                    "SUM(IF(evaluation.nickname = '$s' $sourceCondition, IF(DATEDIFF(NOW(), finding.nextduedate)"
                    . "> 0, 0, 1), 0)) ontime$status"
                );
                $summary->addSelect(
                    "SUM(IF(evaluation.nickname = '$s' $sourceCondition, IF(DATEDIFF(NOW(), finding.nextduedate)"
                    . "> 0, 1, 0), 0)) overdue$status"
                );
            }
        }
        $summary->addSelect(
            "SUM(IF(finding.responsibleorganizationid = parent.id $sourceCondition, 1, 0)) singleTotal"
        );
        $summary->addSelect("SUM(IF(finding.status = 'CLOSED' $sourceCondition, 1, 0)) closed");

        if (isset($source)) {
            $summary->addSelect("SUM(IF(finding.sourceId = $sourceId, 1, 0)) total");
        } else {
            $summary->addSelect("COUNT(finding.id) total");
        }

        $summary->addSelect("IF(parent.orgtype = 'system', system.type, parent.orgtype) orgType")
            ->addSelect('parent.lft as lft')
            ->addSelect('parent.rgt as rgt')
            ->addSelect('parent.id as id')
            ->addSelect(
                "IF(parent.orgtype <> 'system', CONCAT(UPPER(SUBSTRING(parent.orgtype, 1, 1)), SUBSTRING"
                . "(parent.orgtype, 2)), CASE WHEN system.type = 'gss' then 'General Support System' WHEN "
                . "system.type = 'major' THEN 'Major Application' WHEN system.type = 'minor' THEN "
                . "'Minor Application' END) orgTypeLabel"
            )
            ->addSelect('parent.level level')
            ->from('Organization node');

        if (!empty($type))
            $summary->leftJoin("node.Findings finding WITH finding.status <> 'PEND' AND finding.type = ?", $type);
        else
            $summary->leftJoin("node.Findings finding WITH finding.status <> 'PEND'");

        $summary->leftJoin('node.System nodeSystem')
            ->leftJoin('finding.CurrentEvaluation evaluation')
            ->leftJoin('Organization parent')
            ->leftJoin('parent.System system')
            ->where('node.lft BETWEEN parent.lft and parent.rgt')
            ->andWhere('node.orgType <> ? OR nodeSystem.sdlcPhase <> ?', array('system', 'disposal'))
            ->groupBy('parent.nickname')
            ->orderBy('parent.lft')
            ->setHydrationMode(Doctrine::HYDRATE_SCALAR);

        if (!empty($organization))
            $summary->andWhereIn('node.id', $organization);
        return $summary->execute();
    }

    /**
     * Returns summary counts for organizations
     *
     * @param array $organization Array of organizations to get counts for
     * @param string $type Type of findings to get counts for
     * @param int $source Finding source ids to get counts for
     * @return array
     */
    private function _getSavCounts($organization, $type, $sourceNickname)
    {
        if (!empty($sourceNickname)) {
            $source = Doctrine::getTable('Source')->findOneByNickname($sourceNickname);
            $sourceId = $source->id;            
        }

        $sourceCondition = isset($source) ? "AND finding.sourceId = $sourceId" : "";

        $allStatuses = Finding::getAllStatuses();

        $summary = Doctrine_Query::create()
            ->select("CONCAT_WS(' - ', o.nickname, o.name) label")
            ->addSelect('o.nickname nickname');

        foreach ($allStatuses as $status) {
            $s = $status;
            $status = urlencode($status);
            // These statuses are constant, and should never change
            if (array_search($status, array('PEND', 'NEW', 'DRAFT', 'EN', 'CLOSED'))) {
                $summary->addSelect(
                    "SUM(IF(finding.status = '$s' AND finding.responsibleorganizationid = o.id"
                    . " $sourceCondition, IF(DATEDIFF(NOW(), finding.nextduedate) > 0, 0, 1), 0)) singleOntime$status"
                );
                $summary->addSelect(
                    "SUM(IF(finding.status = '$s' AND finding.responsibleorganizationid = o.id"
                    . " $sourceCondition, IF(DATEDIFF(NOW(), finding.nextduedate) > 0, 1, 0), 0)) singleOverdue$status"
                );
                $summary->addSelect(
                    "SUM(IF(finding.status = '$s' $sourceCondition, IF(DATEDIFF(NOW(), finding.nextduedate) > 0,"
                    . "0, 1), 0)) ontime$status"
                );
                $summary->addSelect(
                    "SUM(IF(finding.status = '$s' $sourceCondition, IF(DATEDIFF(NOW(), finding.nextduedate) > 0,"
                    . "1, 0), 0)) overdue$status"
                );
            } else { // These are statuses relating to workflow when finding status is EA or MSA, which are dynamic
                $summary->addSelect(
                    "SUM(IF(evaluation.nickname = '$s' AND finding.responsibleorganizationid = o.id"
                    . " $sourceCondition, IF(DATEDIFF(NOW(), finding.nextduedate) > 0, 0, 1), 0)) singleOntime$status"
                );
                $summary->addSelect(
                    "SUM(IF(evaluation.nickname = '$s' AND finding.responsibleorganizationid = o.id"
                    . " $sourceCondition, IF(DATEDIFF(NOW(), finding.nextduedate) > 0, 1, 0), 0)) singleOverdue$status"
                );
                $summary->addSelect(
                    "SUM(IF(evaluation.nickname = '$s' $sourceCondition, IF(DATEDIFF(NOW(), finding.nextduedate)"
                    . "> 0, 0, 1), 0)) ontime$status"
                );
                $summary->addSelect(
                    "SUM(IF(evaluation.nickname = '$s' $sourceCondition, IF(DATEDIFF(NOW(), finding.nextduedate)"
                    . "> 0, 1, 0), 0)) overdue$status"
                );
            }
        }
        $summary->addSelect(
            "SUM(IF(finding.responsibleorganizationid = o.id $sourceCondition, 1, 0)) singleTotal"
        );
        $summary->addSelect("SUM(IF(finding.status = 'CLOSED' $sourceCondition, 1, 0)) closed");

        if (isset($source)) {
            $summary->addSelect("SUM(IF(finding.sourceId = $sourceId, 1, 0)) total");
        } else {
            $summary->addSelect("COUNT(finding.id) total");
        }

        $systemTypeSwitch = 'CASE ';
        foreach (System::getAllTypeLabels() as $k => $v) {
            $systemTypeSwitch .= "WHEN s.type = '$k' THEN '$v' ";
        }
        $systemTypeSwitch .= 'END';
        $summary->addSelect("s.type orgType")
            ->addSelect('s.id as id')
            ->addSelect('s.aggregateSystemId as aggregateSystemId')
            // CASE wrapped in CONCAT to not confuse doctrine
            ->addSelect("CONCAT('', $systemTypeSwitch) orgTypeLabel")
            ->from('Organization o');

        if (!empty($type))
            $summary->leftJoin("o.Findings finding WITH finding.status <> 'PEND' AND finding.type = ?", $type);
        else
            $summary->leftJoin("o.Findings finding WITH finding.status <> 'PEND'");

        $summary->leftJoin('o.System s')
            ->leftJoin('finding.CurrentEvaluation evaluation')
            ->where('o.orgType = ? AND s.sdlcPhase <> ?', array('system', 'disposal'))
            ->groupBy('o.nickname')
            ->orderBy('o.nickname')
            ->setHydrationMode(Doctrine::HYDRATE_SCALAR);

        if (!empty($organization))
            $summary->andWhereIn('o.id', $organization);
        return $summary->execute();
    }

    /**
     * Returns summary counts of POCs
     *
     * @param array $organization Array of organizations to get counts for
     * @param string $type Type of findings to get counts for
     * @param int $source Finding source ids to get counts for
     * @return array
     */
    private function _getPocvCounts($organization, $type, $sourceNickname)
    {
        // Doctrine won't let me paramaterize within a somewhat complex statement, so we'll just protect against
        // injection by using sprintf.
        if (!empty($sourceNickname)) {
            $source = Doctrine::getTable('Source')->findOneByNickname($sourceNickname);
            $sourceId = $source->id;            
        }

        $sourceCondition = isset($source) ? "AND finding.sourceId = $sourceId" : "";

        $allStatuses = Finding::getAllStatuses();

        $findingJoinParams = array();
        $findingJoinStr = '';
        if (!empty($organization)) {
            $findingJoinStr .= ' AND f.responsibleOrganizationId IN ('
                . implode(',', array_fill(0, count($organization), '?')) . ')';
            $findingJoinParams = $organization;
        }
        if (!empty($type)) {
            $findingJoinStr .= ' AND f.type = ?';
            $findingJoinParams[] = $type;
        }

        $summary = Doctrine_Query::create()
            ->select('o.id')
            ->addSelect("CONCAT_WS(' - ', o.nickname, o.name) label")
            ->addSelect('o.nickname nickname')
            ->addSelect("IF(o.orgtype = 'system', system.type, o.orgtype) orgType")
            ->addSelect('o.lft as lft')
            ->addSelect('o.rgt as rgt')
            ->addSelect(
                "IF(o.orgtype <> 'system', CONCAT(UPPER(SUBSTRING(o.orgtype, 1, 1)), SUBSTRING"
                . "(o.orgtype, 2)), CASE WHEN system.type = 'gss' then 'General Support System' WHEN "
                . "system.type = 'major' THEN 'Major Application' WHEN system.type = 'minor' THEN "
                . "'Minor Application' END) orgTypeLabel"
            )
            ->addSelect('o.level level')
            ->addSelect('p.id pocId')
            ->addSelect('p.username')
            ->addSelect('p.nameFirst')
            ->addSelect('p.nameLast')
            ->from('Organization o')
            ->leftJoin('o.System system')
            ->leftJoin('o.Pocs p')
            ->leftJoin("p.Findings f WITH f.status <> 'PEND'" . $findingJoinStr, $findingJoinParams)
            ->leftJoin('f.CurrentEvaluation evaluation')
            ->where('o.orgType <> ? OR system.sdlcPhase <> ?', array('system', 'disposal'))
            ->groupBy('o.id, p.id')
            ->orderBy('o.lft, p.nameLast, p.nameFirst')
            ->setHydrationMode(Doctrine::HYDRATE_SCALAR);
        if (!empty($organization)) {
            $summary->andWhereIn('o.id', $organization);
        }

        $ontimeExp = 'IF(DATEDIFF(NOW(), f.nextduedate) > 0, 0, 1)';
        $overdueExp = 'IF(DATEDIFF(NOW(), f.nextduedate) > 0, 1, 0)';
        foreach ($allStatuses as $status) {
            $s = $status;
            $status = urlencode($status);
            // These statuses are constant, and should never change
            if (array_search($status, array('PEND', 'NEW', 'DRAFT', 'EN', 'CLOSED'))) {
                $summary->addSelect(
                    "SUM(IF(f.status = '$s' $sourceCondition, $ontimeExp, 0)) singleOntime$status"
                );
                $summary->addSelect(
                    "SUM(IF(f.status = '$s' $sourceCondition, $overdueExp, 0)) singleOverdue$status"
                );
            } else { // These are statuses relating to workflow when finding status is EA or MSA, which are dynamic
                $summary->addSelect(
                    "SUM(IF(evaluation.nickname = '$s' $sourceCondition, $ontimeExp, 0)) singleOntime$status"
                );
                $summary->addSelect(
                    "SUM(IF(evaluation.nickname = '$s' $sourceCondition, $overdueExp, 0)) singleOverdue$status"
                );
            }
        }
        $summary->addSelect("SUM(IF(f.status = 'CLOSED' $sourceCondition, 1, 0)) closed");

        if (isset($source)) {
            $summary->addSelect("SUM(IF(f.sourceId = $sourceId, 1, 0)) singleTotal");
        } else {
            $summary->addSelect("COUNT(f.id) singleTotal");
        }

        return $summary->execute();
    }

    /**
     * Prepares the summary data array returned from Doctrine for use 
     * 
     * @param array $organizations 
     * @return array 
     */
    private function _prepareSummaryData($organizations)
    {
        // Remove model names from array keys
        foreach ($organizations as &$organization) {
            foreach ($organization as $k => $v) {
                if (strstr($k, '_')) {
                    $organization[substr($k, 1)] = $v;
                    unset($organization[$k]);
                }
            }

            // Store counts in arrays for YUI data table
            $organization['children'] = array();
            $organization['single_ontime'] = array();
            $organization['single_overdue'] = array();
            $organization['all_ontime'] = array();
            $organization['all_overdue'] = array();

            $keys = array(
                'all_ontime' => array(),
                'all_overdue' => array(),
                'single_ontime' => array(),
                'single_overdue' => array()
            );

            $findingStatuses = Finding::getAllStatuses();

            $reportStatuses = array(
                'ontime' => 'all_ontime', 
                'overdue' => 'all_overdue', 
                'singleOntime' => 'single_ontime', 
                'singleOverdue' => 'single_overdue'
            );

            foreach ($findingStatuses as $status) {
                foreach ($reportStatuses as $key => $report) {
                    $keys[$report][$status] = $key . urlencode($status);
                }
            }

            $keys['all_ontime']['TOTAL'] = 'total';
            $keys['single_ontime']['TOTAL'] = 'singleTotal';

            unset($keys['all_overdue']['CLOSED']);
            unset($keys['single_overdue']['CLOSED']);

            // Loop through the keys and rename them as defined in the array above
            foreach ($keys as $list => $category) {
                foreach ($category as $k => $v) {
                    $organization[$list][$k] = $organization[$v];
                    unset($organization[$v]);
                }
            }
        }
        return $organizations;
    }
}
