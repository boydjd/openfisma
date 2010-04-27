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
 * {@link http://www.gnu.org/licenses/}.
 */

/**
 * The remediation controller handles CRUD for findings in remediation.
 *
 * @author     Jim Chen <xhorse@users.sourceforge.net>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Controller
 * @version    $Id$
 * 
 * @todo       As part of the ongoing refactoring, this class should probably be merged with the FindingController.
 */
class RemediationController extends SecurityController
{
    /**
     * The orgSystems which are belongs to current user.
     * 
     * @var Doctrine_Collection
     */
    protected $_organizations = null;
    
    /**
     * Default pagination parameters
     * 
     * @var array
     */
    protected $_paging = array(
        'startIndex' => 0,
        'count' => 20
    );
    
    /**
     * The preDispatch hook is used to split off poam modify actions, mitigation approval actions, and evidence
     * approval actions into separate controller actions.
     * 
     * @return void
     */
    public function preDispatch() 
    {
        $request = $this->getRequest();
        $this->_paging['startIndex'] = $request->getParam('startIndex', 0);
        if ('modify' == $request->getParam('sub')) {
            // If this is a mitigation, evidence approval, or evidence upload, then redirect to the 
            // corresponding controller action
            if (isset($_POST['submit_msa'])) {
                $request->setParam('sub', null);
                $this->_forward('msa');
            } elseif (isset($_POST['submit_ea'])) {
                $request->setParam('sub', null);
                $this->_forward('evidence');
            } elseif (isset($_POST['upload_evidence'])) {
                $request->setParam('sub', null);
                $this->_forward('uploadevidence');
            }
        }
        parent::preDispatch();
    }
              
    /**
    * Create the additional PDF, XLS and RSS contexts for this class.
    * 
    * @return void
    */
    public function init()
    {
        parent::init();    
        $attach = $this->_helper->contextSwitch();
        if (!$attach->hasContext('pdf')) {
            $attach->addContext(
                'pdf',
                array(
                    'suffix' => 'pdf',
                    'headers' => array(
                        'Content-Disposition' => "attachement;filename=export.pdf",
                        'Content-Type' => 'application/pdf'
                    )
                )
            );
            $attach->addActionContext('search2', array('pdf'))
                   ->setAutoDisableLayout(true);
        }
        if (!$attach->hasContext('xls')) {
            $attach->addContext(
                'xls',
                array(
                    'suffix' => 'xls',
                    'headers' => array(
                        'Content-Disposition' => "attachement;filename=export.xls",
                        'Content-Type' => 'application/vnd.ms-excel'
                    )
                )
            );
            $attach->addActionContext('search2', array('xls'))
                   ->setAutoDisableLayout(true);
        }
        $this->_helper->contextSwitch()
                      ->addActionContext('summary-data', array('json', 'xls', 'pdf'));

        // Quick hack: disable auto-json-serialization for summary-data action
        if ('summary-data' == $this->getRequest()->getActionName()) {
            $this->_helper->contextSwitch()->setAutoJsonSerialization(false);
        }
        
        $this->_helper->contextSwitch()->initContext();
        $this->_organizations = $this->_me->getOrganizations();
    }
    
    /**
     * Default action.
     * 
     * It combines the searching and summary into one page.
     * 
     * @return void
     */
    public function indexAction()
    {
        Fisma_Acl::requirePrivilegeForClass('read', 'Finding');
        
        $this->_helper->actionStack('searchbox', 'Remediation');
        $this->_helper->actionStack('summary', 'Remediation');
    }

    /**
     * Presents the view which contains the summary table. The summary table loads summary data
     * asynchronously by invoking the summaryDataAction().
     * 
     * @return void
     */
    public function summaryAction()
    {
        Fisma_Acl::requirePrivilegeForClass('read', 'Finding');
                
        $mitigationEvaluationQuery = Doctrine_Query::create()
                                     ->from('Evaluation e')
                                     ->where('approvalGroup = \'action\'')
                                     ->orderBy('e.precedence');
        $this->view->mitigationEvaluations = $mitigationEvaluationQuery->execute();
        
        $evidenceEvaluationQuery = Doctrine_Query::create()
                                     ->from('Evaluation e')
                                     ->where('approvalGroup = \'evidence\'')
                                     ->orderBy('e.precedence');
        $this->view->evidenceEvaluations = $evidenceEvaluationQuery->execute();
        
        $this->view->findingSources = Doctrine::getTable('Source')->findAll();
    }
    
    /**
     * Invoked asynchronously to load data for the summary table.
     * 
     * @return void
     */
    public function summaryDataAction() 
    {
        Fisma_Acl::requirePrivilegeForClass('read', 'Finding');

        $type = $this->getRequest()->getParam('type');
        $source = $this->getRequest()->getParam('sourceId');        
        $format = $this->_helper->contextSwitch()->getCurrentContext();
        // Prepare summary data

        // Get user organizations
        $organizationsQuery = User::currentUser()->getOrganizationsQuery();
        $organizationsQuery->select('o.id');
        $organizationsQuery->setHydrationMode(Doctrine::HYDRATE_NONE);
        $organizations = $organizationsQuery->execute();

        foreach ($organizations as $k => $v) {
            $organizations[] = $v[0];
            unset($organizations[$k]);
        }

        // Get finding summary counts
        $organizations = $this->_getSummaryCounts($organizations, $type, $source);
        $organizations = $this->_prepareSummaryData($organizations);

        // For excel and PDF requests, return a table format. For JSON requests, return a hierarchical
        // format
        if ('pdf' == $format || 'xls' == $format) {
            $allStatuses = Finding::getAllStatuses();
            array_unshift($allStatuses, 'Organization/Information System');
            array_push($allStatuses, 'TOTAL');
            $this->view->columns = $allStatuses;

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
                        $tableData[] = $overdueRow;
                    }                    
                }
            }
            $this->view->tableData = $tableData;
        } else {
            // Decide whether the response can be gzipped
            $acceptEncodingHeader = $this->getRequest()->getHeader('Accept-Encoding');
            $gzipEncode = (strstr($acceptEncodingHeader, 'gzip') !== false);
            $this->view->gzipEncode = $gzipEncode;
            if ('json' == $this->getRequest()->getParam('format')) {
                $this->getResponse()->setHeader('Content-Encoding', 'gzip', true);
            }

            // Assign children to parents accordingly
            $temp = array(array());
            foreach ($organizations as $n => $a) {
                $d = $a['level']+1;
                $temp[$d-1]['children'][] = &$organizations[$n];
                $temp[$d] = &$organizations[$n];
            }
            $organizations = $temp[0]['children'];

            $this->view->summaryData = $organizations;
        } 
    }

    /**
     * Returns summary counts for organizations
     *
     * @param array $organization Array of organizations to get counts for
     * @param string $type Type of findings to get counts for
     * @param int $source Finding source ids to get counts for
     * @return array
     */
    private function _getSummaryCounts($organization, $type, $source)
    {
        // Doctrine won't let me paramaterize within a somewhat complex statement, so we'll just protect against
        // injection by using sprintf.
        $source = (!empty($source)) ? sprintf("%d", $source) : $source;
        $sourceCondition = (!empty($source)) ? "AND finding.sourceID = $source" : "";

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

        if (!empty($source))
            $summary->addSelect("SUM(IF(finding.sourceId = $source, 1, 0)) total");
        else
            $summary->addSelect("COUNT(finding.id) total");

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

        $summary->leftJoin('node.System system')
            ->leftJoin('finding.CurrentEvaluation evaluation')
            ->leftJoin('Organization parent')
            ->where('node.lft BETWEEN parent.lft and parent.rgt')
            ->groupBy('parent.nickname')
            ->orderBy('parent.lft')
            ->setHydrationMode(Doctrine::HYDRATE_SCALAR);

        if (!empty($organization))
            $summary->andWhereIn('node.id', $organization);
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

    /**
     * Parse and translate the URL to criterias
     * which can be used by searchBoxAction method and searchAction method.
     *
     * @return array The criterias dealt
     */
    private function _parseCriteria()
    {
        $params = array('responsibleOrganizationId' => 0, 'sourceId' => 0, 'type' => '',
                        'status' => '', 'ids' => '', 'assetOwner' => 0,
                        'estDateBegin' => '', 'estDateEnd' => '',
                        'createdDateBegin' => '', 'createdDateEnd' => '',
                        'ontime' => '', 'sortby' => '', 'dir'=> '', 'keywords' => '', 'expanded' => null);
        $req = $this->getRequest();
        $tmp = $req->getParams();
        foreach ($params as $k => &$v) {
            if (isset($tmp[$k])) {
                $v = $tmp[$k];
            }
        }
        if (is_numeric($params['responsibleOrganizationId'])) {
            $params['responsibleOrganizationId'] = $params['responsibleOrganizationId'];
        }
        if (is_numeric($params['sourceId'])) {
            $params['sourceId'] = $params['sourceId'];
        }
        if (is_numeric($params['assetOwner'])) {
            $params['assetOwner'] = $params['assetOwner'];
        }

        $message = '';
        if (!empty($params['estDateBegin']) && Zend_Date::isDate($params['estDateBegin'], 'Y-m-d')) {
            $params['estDateBegin'] = new Zend_Date($params['estDateBegin'], 'Y-m-d');
        } else if (!empty($params['estDateBegin'])) {
            $message = 'Estimated Completion Date From: ' . $params['estDateBegin']
                     . ' is not of the format YYYY-MM-DD.<br>';
            $params['estDateBegin'] = '';
        } else {
            $params['estDateBegin'] = '';
        }

        if (!empty($params['estDateEnd']) && Zend_Date::isDate($params['estDateEnd'], 'Y-m-d')) {
            $params['estDateEnd'] = new Zend_Date($params['estDateEnd'], 'Y-m-d');
        } else if (!empty($params['estDateEnd'])) {
            $message = $message . 'Estimated Completion Date To: ' . $params['estDateEnd']
                     . ' is not of the format YYYY-MM-DD.<br>';
            $params['estDateEnd'] = '';
        } else {
            $params['estDateEnd'] = '';
        }

        if (!empty($params['createdDateBegin']) && Zend_Date::isDate($params['createdDateBegin'], 'Y-m-d')) {
            $params['createdDateBegin'] = new Zend_Date($params['createdDateBegin'], 'Y-m-d');
        } else if (!empty($params['createdDateBegin'])) {
            $message = $message . 'Date Created From: ' . $params['createdDateBegin']
                     . ' is not of the format YYYY-MM-DD.<br>';
            $params['createdDateBegin'] = '';
        } else {
            $params['createdDateBegin'] = '';
        }

        if (!empty($params['createdDateEnd']) && Zend_Date::isDate($params['createdDateEnd'], 'Y-m-d')) {
            $params['createdDateEnd'] = new Zend_Date($params['createdDateEnd'], 'Y-m-d');
        } else if (!empty($params['createdDateEnd'])) {
            $message = $message . 'Date Created To: ' . $params['createdDateEnd']
                     . 'is not of the format YYYY-MM-DD.';
            $params['createdDateEnd'] = '';
        } else {
            $params['createdDateEnd'] = '';
        }

        if (!empty($message)) {
            $this->view->priorityMessenger($message, 'warning');
        }

        return $params;
    }
    
    /**
     * Get the columns(title) which were displayed on page, PDF, Excel
     * 
     * @return array The two dimension array which includes column id in index and the label, sortable and 
     * hidden of the column in value.
     */
    private function _getColumns()
    {
        // Set up the data for the columns in the search results table
        $me = Doctrine::getTable('User')->find($this->_me->id);
        
        try {
            $cookie = Fisma_Cookie::get($_COOKIE, 'search_columns_pref');
            $visibleColumns = $cookie;
        } catch(Fisma_Exception $e) {
            if (empty($me->searchColumnsPref)) {
                $me->searchColumnsPref = $visibleColumns = 66037;
                $me->save();
            } else {
                $visibleColumns = $me->searchColumnsPref;
            }
        }

        $columns = array(
            'id' => array('label' => 'ID', 
                          'sortable' => true, 
                          'hidden' => ($visibleColumns & 1) == 0),
            'sourceNickname' => array('label' => 'Source', 
                                       'sortable' => true, 
                                       'hidden' => ($visibleColumns & (1 << 1)) == 0),
            'systemNickname' => array('label' => 'System', 
                                       'sortable' => true, 
                                       'hidden' => ($visibleColumns & (1 << 2)) == 0),
            'assetName' => array('label' => 'Asset', 
                                  'sortable' => true, 
                                  'hidden' => ($visibleColumns & (1 << 3)) == 0),
            'type' => array('label' => 'Type', 
                            'sortable' => true, 
                            'hidden' => ($visibleColumns & (1 << 4)) == 0),
            'status' => array('label' => 'Status', 
                              'sortable' => true, 
                              'hidden' => ($visibleColumns & (1 << 5)) == 0),
            'duetime' => array('label' => 'On Time?', 
                               'sortable' => false, 
                               'hidden' => ($visibleColumns & (1 << 6)) == 0),
            'description' => array('label' => 'Description', 
                                    'sortable' => false, 
                                    'hidden' => ($visibleColumns & (1 << 7)) == 0),
            'recommendation' => array('label' => 'Recommendation', 
                                        'sortable' => false, 
                                        'hidden' => ($visibleColumns & (1 << 8)) == 0),
            'mitigationStrategy' => array('label' => 'Course of Action', 
                                      'sortable' => false, 
                                      'hidden' => ($visibleColumns & (1 << 9)) == 0),
            'securityControl' => array('label' => 'Security Control', 
                                'sortable' => true, 
                                'hidden' => ($visibleColumns & (1 << 10)) == 0),
            'threatLevel' => array('label' => 'Threat Level', 
                                    'sortable' => true, 
                                    'hidden' => ($visibleColumns & (1 << 11)) == 0),
            'threat' => array('label' => 'Threat Description', 
                                     'sortable' => false, 
                                     'hidden' => ($visibleColumns & (1 << 12)) == 0),
            'countermeasuresEffectiveness' => array('label' => 'Countermeasure Effectiveness', 
                                              'sortable' => true, 
                                              'hidden' => ($visibleColumns & (1 << 13)) == 0),
            'countermeasures' => array('label' => 'Countermeasure Description', 
                                'sortable' => false, 
                                'hidden' => ($visibleColumns & (1 << 14)) == 0),
            'attachments' => array('label' => 'Attachments', 
                                   'sortable' => false, 
                                   'hidden' => ($visibleColumns & (1 << 15)) == 0),
            'currentEcd' => array('label' => 'Expected Completion Date', 
                                           'sortable' => true, 
                                           'hidden' => ($visibleColumns & (1 << 16)) == 0)
        );
        return $columns;
    }
    
    /**
    * Do the real searching work. It's a thin wrapper of poam model's search method.
    * 
    * @return void
    */
    public function searchAction()
    {
        Fisma_Acl::requirePrivilegeForClass('read', 'Finding');
        
        $params = $this->_parseCriteria();
        
        // These variables go into the search view
        $link = $this->_helper->makeUrlParams($params);
        $this->view->assign('link', $link);
        $this->view->assign('attachUrl', '/remediation/search2' . $link);
        Fisma_Cookie::set('lastSearchUrl', "/panel/remediation/sub/searchbox$link");
        $this->view->assign('columns', $this->_getColumns());

        // These variables go into the search box view
        $systemList = array();
        foreach ($this->_organizations as $system) {
            $systemList[$system->id] = "$system->nickname - $system->name";
        }
        asort($systemList);
        $this->view->assign('params', $params);
        $this->view->assign('systems', $systemList);
        $this->view->assign('sources', Doctrine::getTable('Source')->findAll()->toKeyValueArray('id', 'name'));
        $this->view->assign('pageInfo', $this->_paging);

        $this->render('searchbox');
        $this->render('search');
    }
    
    /**
     * This is is a stub provided for compatibility purposes in response to OFJ-464.
     * 
     * @todo remove me in 2.6+
     * 
     * @return void
     */
    public function searchboxAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_forward('search');
    }
    
    /**
     * View details of a finding object
     * 
     * @return void
     */
    public function viewAction()
    {
        $id = $this->_request->getParam('id');

        $finding = $this->_getFinding($id);
        $this->view->finding = $finding;
        
        Fisma_Acl::requirePrivilegeForObject('read', $finding);

        $tabView = new Fisma_Yui_TabView('FindingView', $id);

        $tabView->addTab("Finding $id", "/remediation/finding/id/$id");
        $tabView->addTab("Mitigation Strategy", "/remediation/mitigation-strategy/id/$id");
        $tabView->addTab("Risk Analysis", "/remediation/risk-analysis/id/$id");
        $tabView->addTab("Security Control", "/remediation/security-control/id/$id");
        $tabView->addTab("Comments (" . $finding->getComments()->count() . ")", "/remediation/comments/id/$id");
        $tabView->addTab("Artifacts (" . $finding->Evidence->count() . ")", "/remediation/artifacts/id/$id");
        $tabView->addTab("Audit Log", "/remediation/audit-log/id/$id");

        $this->view->tabView = $tabView;
    }

    /**
     * Add a comment to a specified finding
     */
    public function addCommentAction()
    {
        $id = $this->getRequest()->getParam('id');
        $finding = Doctrine::getTable('Finding')->find($id);

        Fisma_Acl::requirePrivilegeForObject('comment', $finding);
        
        $comment = $this->getRequest()->getParam('comment');
        
        if ('' != trim(strip_tags($comment))) {
            $finding->getComments()->addComment($comment);
        } else {
            $this->view->priorityMessenger('Comment field is blank', 'warning');
        }
        
        $this->_redirect("/panel/remediation/sub/view/id/$id");
    }

    /**
     * Display comments for this finding
     */
    public function commentsAction()
    {
        $id = $this->_request->getParam('id');
        $this->view->assign('id', $id);
        $finding = Doctrine::getTable('Finding')->find($id);

        Fisma_Acl::requirePrivilegeForObject('read', $finding);

        $comments = $finding->getComments()->fetch(Doctrine::HYDRATE_ARRAY);

        $this->view->showAddCommentForm = Fisma_Acl::hasPrivilegeForObject('comment', $finding);

        $this->view->assign('comments', $comments);
    }
    
    /**
     * Modify the finding
     * 
     * @return void
     */
    public function modifyAction()
    {
        // ACL for finding objects is handled inside the finding listener, because it has to do some
        // very fine-grained error checking
        
        $id = $this->_request->getParam('id');
        $findingData = $this->_request->getPost('finding', array());

        $this->_forward('view', null, null, array('id' => $id));

        if (isset($findingData['currentEcd'])) {
            if (Zend_Validate::is($findingData['currentEcd'], 'Date')) {
                $date = new Zend_Date();
                $ecd  = new Zend_Date($findingData['currentEcd']);

                if ($ecd->isEarlier($date)) {
                    $error = 'Expected completion date has been set before the current date.'
                             . 'Make sure that this is correct.';
                    $this->view->priorityMessenger($error, 'notice');
                }
            } else {
                $error = 'Expected completion date provided is not a valid date. Unable to update finding.';
                $this->view->priorityMessenger($error, 'warning');
                return;
            }
        }

        $finding = $this->_getFinding($id);

        try {
            Doctrine_Manager::connection()->beginTransaction();
            $finding->merge($findingData);
            $finding->save();
            Doctrine_Manager::connection()->commit();
        } catch (Doctrine_Exception $e) {
            Doctrine_Manager::connection()->rollback();
            $message = "Error: Unable to update finding. ";
            if (Fisma::debug()) {
                $message .= $e->getMessage();
            }
            $model = 'warning';
            $this->view->priorityMessenger($message, $model);
        }
    }

    /**
     * Mitigation Strategy Approval Process
     * 
     * @return void
     */
    public function msaAction()
    {
        $id       = $this->_request->getParam('id');
        $do       = $this->_request->getParam('do');
        $decision = $this->_request->getPost('decision');

        $finding  = $this->_getFinding($id);
        if (!empty($decision)) {
            Fisma_Acl::requirePrivilegeForObject($finding->CurrentEvaluation->Privilege->action, $finding);
        }
       
        try {
            Doctrine_Manager::connection()->beginTransaction();

            if ('submitmitigation' == $do) {
                Fisma_Acl::requirePrivilegeForObject('mitigation_strategy_submit', $finding);
                $finding->submitMitigation(User::currentUser());
            }
            if ('revisemitigation' == $do) {
                Fisma_Acl::requirePrivilegeForObject('mitigation_strategy_revise', $finding);
                $finding->reviseMitigation(User::currentUser());
            }

            if ('APPROVED' == $decision) {
                $comment = $this->_request->getParam('comment');
                $finding->approve(User::currentUser(), $comment);
            }

            if ('DENIED' == $decision) {
                $comment = $this->_request->getParam('comment');
                $finding->deny(User::currentUser(), $comment);
            }
            Doctrine_Manager::connection()->commit();
        } catch (Doctrine_Connection_Exception $e) {
            Doctrine_Manager::connection()->rollback();
            $message = 'Failure in this operation. '
                     . $e->getPortableMessage() 
                     . ' ('
                     . $e->getPortableCode()
                     . ')';
            if (Fisma::debug()) {
                $message .= $e->getMessage();
            }
            $model = 'warning';
            $this->view->priorityMessenger($message, $model);
        }
        $this->_forward('view', null, null, array('id' => $id));
    }

    /**
     * Upload evidence
     * 
     * @return void
     */
    public function uploadevidenceAction()
    {
        $id = $this->_request->getParam('id');
        $finding = $this->_getFinding($id);

        Fisma_Acl::requirePrivilegeForObject('upload_evidence', $finding);

        define('EVIDENCE_PATH', Fisma::getPath('data') . '/uploads/evidence');
        $file = $_FILES['evidence'];

        try {
            if ($file['error'] != UPLOAD_ERR_OK) {
              if ($file['error'] == UPLOAD_ERR_INI_SIZE) {
                $message = "The uploaded file is larger than is allowed by the server.";
              } elseif ($file['error'] == UPLOAD_ERR_PARTIAL) {
                $message = "The uploaded file was only partially received.";
              } else {
                $message = "An error occurred while processing the uploaded file.";
              }
              throw new Fisma_Exception($message);
            }

            if (!$file['name']) {
                $message = "You did not select a file to upload. Please select a file and try again.";
                throw new Fisma_Exception($message);
            }

            $extension = explode('.', $file['name']);
            $extension = end($extension);

            /** @todo cleanup */
            if (in_array(strtolower($extension), array('exe', 'php', 'phtml', 'php5', 'php4', 'js', 'css'))) {
                $message = 'This file type is not allowed.';
                throw new Fisma_Exception($message);
            }

            if (!file_exists(EVIDENCE_PATH)) {
                mkdir(EVIDENCE_PATH, 0755);
            }
            if (!file_exists(EVIDENCE_PATH .'/'. $id)) {
                mkdir(EVIDENCE_PATH .'/'. $id, 0755);
            }
            $nowStr = date('Y-m-d-his', strtotime(Fisma::now()));
            $count = 0;
            $filename = preg_replace('/^(.*)\.(.*)$/', '$1-' . $nowStr . '.$2', $file['name'], 2, $count);
            $absFile = EVIDENCE_PATH ."/{$id}/{$filename}";
            if ($count > 0) {
                if (move_uploaded_file($file['tmp_name'], $absFile)) {
                    chmod($absFile, 0755);
                } else {
                    $message = 'The file upload failed due to a server configuration error.' 
                             . ' Please contact the administrator.';
                    $logger = Fisma::getLogInstance();
                    $logger->log('Failed in move_uploaded_file(). ' . $absFile . "\n" . $file['error'], Zend_Log::ERR);
                    throw new Fisma_Exception($message);
                }
            } else {
                throw new Fisma_Exception('The filename is not valid');
            }

            $finding->uploadEvidence($filename, User::currentUser());
        } catch (Fisma_Exception $e) {
            $this->view->priorityMessenger($e->getMessage(), 'warning');
        }
        $this->_forward('view', null, null, array('id' => $id));
    }
    
    /**
     * Download evidence
     * 
     * @return void
     */
    public function downloadevidenceAction()
    {
        $id = $this->_request->getParam('id');
        $evidence = Doctrine::getTable('Evidence')->find($id);
        if (empty($evidence)) {
            throw new Fisma_Exception('Invalid evidence ID');
        }

        // There is no ACL defined for evidence objects, access is only based on the associated finding:
        Fisma_Acl::requirePrivilegeForObject('read', $evidence->Finding);

        $fileName = $evidence->filename;
        $filePath = Fisma::getPath('data') . '/uploads/evidence/'. $evidence->findingId . '/';

        if (file_exists($filePath . $fileName)) {
            $this->_helper->layout->disableLayout(true);
            $this->_helper->viewRenderer->setNoRender();
            ob_end_clean();
            header('Expires: ' . gmdate('D, d M Y H:i:s', time()+31536000) . ' GMT');
            header('Content-type: application/octet-stream');
            header('Content-Disposition: attachment; filename=' . urlencode($fileName));
            header('Content-Length: ' . filesize($filePath . $fileName));
            header('Pragma: ');
            $fp = fopen($filePath . $fileName, 'rb');
            while (!feof($fp)) {
                $buffer = fgets($fp, 4096);
                echo $buffer;
            }
            fclose($fp);
        } else {
            throw new Fisma_Exception('The requested file could not be found');
        }
    }
    
    /**
     * Handle the evidence evaluations
     * 
     * @return void
     */
    public function evidenceAction()
    {
        $id       = $this->_request->getParam('id');
        $decision = $this->_request->getPost('decision');

        $finding  = $this->_getFinding($id);

        if (!empty($decision)) {
            Fisma_Acl::requirePrivilegeForObject($finding->CurrentEvaluation->Privilege->action, $finding);
        }

        try {
            Doctrine_Manager::connection()->beginTransaction();
            if ('APPROVED' == $decision) {
                $comment = $this->_request->getParam('comment');
                $finding->approve(User::currentUser(), $comment);
            }

            if ('DENIED' == $decision) {
                $comment = $this->_request->getParam('comment');
                $finding->deny(User::currentUser(), $comment);
            }
            Doctrine_Manager::connection()->commit();
        } catch (Doctrine_Exception $e) {
            Doctrine_Manager::connection()->rollback();
            $message = "Failure in this operation. ";
            if (Fisma::debug()) {
                $message .= $e->getMessage();
            }
            $model = 'warning';
            $this->view->priorityMessenger($message, $model);
        }
        $this->_forward('view', null, null, array('id' => $id));
    }

    /**
     * Generate RAF report
     *
     * It can handle different format of RAF report.
     * 
     * @return void
     */
    public function rafAction()
    {
        $id = $this->_request->getParam('id');
        $finding = $this->_getFinding($id);

        Fisma_Acl::requirePrivilegeForObject('read', $finding);

        try {
            if ($finding->threat == '' ||
                empty($finding->threatLevel) ||
                $finding->countermeasures == '' ||
                empty($finding->countermeasuresEffectiveness)) {
                throw new Fisma_Exception("The Threat or Countermeasures Information is not "
                    ."completed. An analysis of risk cannot be generated, unless these values are defined.");
            }
            
            $system = $finding->ResponsibleOrganization->System;
            if (NULL == $system->fipsCategory) {
                throw new Fisma_Exception('The security categorization for ' .
                     '(' . $finding->responsibleOrganizationId . ')' . 
                     $finding->ResponsibleOrganization->name . ' is not defined. An analysis of ' .
                     'risk cannot be generated unless these values are defined.');
            }
            $this->view->securityCategorization = $system->fipsCategory;
        } catch (Fisma_Exception $e) {
            if ($e instanceof Fisma_Exception) {
                $message = $e->getMessage();
            }
            $this->view->priorityMessenger($message, 'warning');
            $this->_forward('view', null, null, array('id' => $id));
            return;
        }
        $this->_helper->contextSwitch()
               ->setHeader('pdf', 'Content-Disposition', "attachement;filename={$id}_raf.pdf")
               ->addActionContext('raf', array('pdf'))
               ->initContext();
        $this->view->finding = $finding;
    }
    
    /**
     * Display basic data about the finding and the affected asset
     * 
     * @return void
     */
    function findingAction() 
    {
        $this->_viewFinding();
        $this->view->keywords = $this->_request->getParam('keywords');
        $this->_helper->layout->setLayout('ajax');
    }

    /**
     * Fields for defining the mitigation strategy
     * 
     * @return void
     */
    function mitigationStrategyAction() 
    {
        $this->_viewFinding();
        $this->_helper->layout->setLayout('ajax');
    }

    /**
     * Display fields related to risk analysis such as threats and countermeasures
     * 
     * @return void
     */
    function riskAnalysisAction() 
    {
        $this->_viewFinding();
        $this->view->keywords = $this->_request->getParam('keywords');
        $this->_helper->layout->setLayout('ajax');
    }

    /**
     * Display fields related to risk analysis such as threats and countermeasures
     * 
     * @return void
     */
    function artifactsAction() 
    {
        $this->_viewFinding();
        $this->_helper->layout->setLayout('ajax');
    }
        
    /**
     * Display the audit log associated with a finding
     * 
     * @return void
     */
    function auditLogAction() 
    {
        $this->_viewFinding();
        $this->_helper->layout->setLayout('ajax');
        
        $logs = $this->view->finding->getAuditLog()->fetch(Doctrine::HYDRATE_SCALAR);
        
        // Convert log messages from plain text to HTML
        foreach ($logs as &$log) {
            $log['o_message'] = $this->view->textToHtml($log['o_message']);
        }

        $this->view->columns = array('Timestamp', 'User', 'Message');
        $this->view->rows = $logs;
    }
    
    /**
     * Real searching worker, to return searching results for page, PDF, Excel
     * 
     * @return void
     */
    public function search2Action() 
    {
        Fisma_Acl::requirePrivilegeForClass('read', 'Finding');
        
        /* @todo A hack to translate column names in the data table to column names
         * which can be sorted... this could probably be done in a much better way.
         */
        $columnMap = array(
            'sourceNickname' => 's.nickname',
            'systemNickname' => 'ro.nickname',
            'assetName' => 'a.name',
            'securityControl' => 'sc.code'
        );
        
        $tableData = array(
            'recordsReturned' => 0,
            'totalRecords' => $total = 0,
            'startIndex' => $this->_paging['startIndex'],
            'sort' => null,
            'dir' => 'asc',
            'pageSize' => $this->_paging['count'],
            'records' => array()
        );
        
        // JSON requests are handled differently from PDF and XLS requests, so we need
        // to determine which request type this is.
        $format = $this->_request->getParam('format');
        
        $params = $this->_parseCriteria();
        
        if (in_array($params['sortby'], array_keys($columnMap))) {
            $params['sortby'] = $columnMap[$params['sortby']];
        } elseif (in_array($params['sortby'], array_keys($this->_getColumns()))) {
            $params['sortby'] = 'f.' . $params['sortby'];
        } else {
            $params['sortby'] = 'f.id';
        }
        
        if (strtoupper($params['dir']) == 'DESC') {
            $params['dir'] = 'DESC';
        } else {
            $params['dir'] = 'ASC';
        }
        
        if (!empty($params['status'])) {
            $now = new Zend_Date();
            switch ($params['status']) {
                case 'TOTAL': $params['status'] = array('NEW', 'DRAFT', 'MSA', 'EN', 'EA', 'CLOSED');
                    break;
                case 'NOT-CLOSED': $params['status'] = array('NEW', 'DRAFT', 'MSA', 'EN', 'EA');
                    break;
                case 'NOUP-30': $params['status'] = array('DRAFT', 'MSA', 'EN', 'EA');
                     $params['modify_ts'] = $now->subDay(30);
                    break;
                case 'NOUP-60':
                     $params['status'] = array('DRAFT', 'MSA', 'EN', 'EA');
                     $params['modify_ts'] = $now->subDay(60);
                    break;
                case 'NOUP-90':
                     $params['status'] = array('DRAFT', 'MSA', 'EN', 'EA');
                     $params['modify_ts'] = $now->subDay(90);
                    break;
                case 'NEW':  case 'DRAFT':  case 'EN': case 'CLOSED': default : 
                    break;
            }
        }
        if ($params['ids']) {
            $params['ids'] = explode(',', $params['ids']);
        }
        // Use Zend Lucene to find all POAM ids which match the keyword query
        if (!empty($params['keywords'])) {
            if (preg_match('/^[0-9, ]+$/', $params['keywords'])) {
                // if the query contains only numbers and commas and whitespace, then interpret it as a list of 
                // ids to search for
                $params['ids'] = explode(',', $params['keywords']);
            } else {
                // Otherwise, interpret it as a lucene query
                try {
                    $index = new Fisma_Index('Finding');
                    $poamIds = $index->findIds($params['keywords']);
                    $tableData['highlightWords'] = $index->getHighlightWords();
                } catch (Zend_Search_Lucene_Exception $e) {
                    $tableData['exception'] = $e->getMessage();
                }
                // Even though it isn't rendered in the view, the highlight words need to be exported to the view...
                // due the stupid design of this class
                $this->view->keywords = $tableData['highlightWords'];
                // Merge keyword results with filter results
                if ($params['ids'] && $poamIds) {
                    $params['ids'] = array_intersect($poamIds, $params['ids']);
                    if (!$params['ids']) {
                        $list = array();
                    }
                } elseif ($poamIds) {
                    $params['ids'] = $poamIds;
                } else {
                    $list = array();
                }
            }
        }
        
        if (!isset($list)) {
            $list = $this->_getResults($params, $format, $total);
        }
        
        if ($format == 'pdf' || $format == 'xls') {
            $this->view->columnPreference = Doctrine::getTable('User')
                                            ->find($this->_me->id)
                                            ->searchColumnsPref;
            $this->view->columns = $this->_getColumns();
            /**
             * @todo to support free sorting in exporting PDF and Excel like in datatable
             */
            $this->view->list = $list;
        } else {
            $this->_helper->contextSwitch()
                          ->addActionContext('search2', 'json')
                          ->initContext();
            $tableData['recordsReturned'] = count($list);
            $tableData['totalRecords'] = $total;
            $tableData['sort'] = $params['sortby'];
            $tableData['dir'] = $params['dir'];
            $tableData['records'] = $list;
            $this->view->assign('findings', $tableData);
        }
    }
    
    /**
     * Analyze the criterias and merge the DQL query for getting results
     * 
     * @param array $params The specified filter criterias
     * @param string $format The specified output format which is json or xls or pdf
     * @param int $total The total number of found rows
     * @return array $list The corresponding results
     */
    private function _getResults($params, $format, &$total)
    {
        $list = array();
        $q = Doctrine_Query::create()
             ->select()
             ->from('Finding f')
             ->leftJoin('f.ResponsibleOrganization ro')
             ->leftJoin('f.Source s')
             ->leftJoin('f.Asset a')
             ->leftJoin('f.SecurityControl sc')
             ->leftJoin('f.CurrentEvaluation ce')
             ->whereIn('f.responsibleOrganizationId', $this->_me->getOrganizations()->toKeyValueArray('id', 'id'))
             ->orderBy($params['sortby'] . ' ' . $params['dir']);

        foreach ($params as $k => $v) {
            if ($v) {
                if ($k == 'estDateBegin') {
                    $v = $v->toString('Y-m-d H:i:s');
                    $q->andWhere("f.currentEcd > ?", $v);
                } elseif ($k == 'estDateEnd') {
                    $v = $v->addDay(1);
                    $v = $v->toString('Y-m-d H:i:s');
                    $q->andWhere("f.currentEcd < ?", $v);
                } elseif ($k == 'createdDateBegin') {
                    $v = $v->toString('Y-m-d H:i:s');
                    $q->andWhere("f.createdTs > ?", $v);
                } elseif ($k == 'createdDateEnd') {
                    $v = $v->addDay(1);
                    $v = $v->toString('Y-m-d H:i:s');
                    $q->andWhere("f.createdTs < ?", $v);
                } elseif ($k == 'status') {
                    if (is_array($v)) {
                        $q->andWhereIn("f.status", $v);
                    } elseif (in_array($v, array('NEW', 'DRAFT', 'EN', 'CLOSED'))) {
                        $q->andWhere("f.status = ?", $v);
                    } else {
                        $q->andWhere("ce.nickname = ?", $v);
                    }
                } elseif ($k == 'modify_ts') {
                    $v = $v->toString('Y-m-d H:i:s');
                    $q->andWhere("f.modifiedTs < ?", $v);
                } elseif ($k == 'ontime') {
                    if ($v == 'ontime') {
                        $q->andWhere('DATEDIFF(NOW(), f.nextDueDate) <= 0');
                    } else {
                        $q->andWhere('DATEDIFF(NOW(), f.nextDueDate) > 0');
                    }
                } elseif ($k == 'ids') {
                    $sqlPart = array();
                    foreach ($v as $id) {
                        if (is_numeric($id)) {
                            $sqlPart[] = 'f.id = ' . $id;
                        }
                    }
                    if (!empty($sqlPart)) {
                        $q->andWhere(implode(' OR ', $sqlPart));
                    }
                } elseif ($k == 'expanded') {
                    // Intentionally falls through. This is a consequence of bad design in this method. The 
                    // 'expanded' variable is not literally added to the query, but is actually just
                    // a modifier for the responsibleOrganizationId parameter.
                    ;
                } elseif ($k == 'responsibleOrganizationId') {
                    if ('false' == $params['expanded']) {
                        $o = Doctrine::getTable('Organization')->find($v);
                        $q->addWhere('ro.lft >= ? AND ro.rgt <= ?', array($o->lft, $o->rgt));
                    } else {
                        $q->addWhere('ro.id = ?', $v);
                    }
                } elseif ($k != 'keywords' && $k != 'dir' && $k != 'sortby') {
                    $q->andWhere("f.$k = ?", $v);
                } 
            }
        }
        if ($format == 'json') {
            $q->limit($this->_paging['count'])->offset($this->_paging['startIndex']);
        }

        // The total number of found rows is appended to the list of finding. 
        $total = $q->count();
        $results = $q->execute();
        
        foreach ($results as $result) {
            $row = array();
            $row['id'] = $result->id;
            $row['type'] = $result->type;
            if ($result->CurrentEvaluation) {
                $row['status'] = $result->CurrentEvaluation->nickname;
            } else {
                $row['status'] = $result->status;
            }
            $row['threatLevel'] = $result->threatLevel;
            if (empty($result->currentEcd) || $result->currentEcd == '0000-00-00') {
                if ($result->currentEcd != '0000-00-00') {
                    $row['currentEcd'] = $result->currentEcd;
                } else {
                    $row['currentEcd'] = '';
                }
            } else {
                $row['currentEcd'] = $result->currentEcd;
            }
            $row['countermeasuresEffectiveness'] = $result->countermeasuresEffectiveness;
            
            $source = $result->Source;
            $row['sourceNickname'] = $source ? $result->Source->nickname : '';
            $responsibleOrganization = $result->ResponsibleOrganization;
            $row['systemNickname'] = $responsibleOrganization ? $result->ResponsibleOrganization->nickname : '';
            $securityControl = $result->SecurityControl;
            $row['securityControl'] = $securityControl ? $result->SecurityControl->code : '';
            $asset = $result->Asset;
            $row['assetName'] = $asset ? $result->Asset->name : '';
            // select the finding whether have attachments
            $row['attachments'] = count($result->Evidence) > 0 ? 'Y' : 'N';

            if (is_null($result->nextDueDate)) {
                $row['duetime'] = 'N/A';
            } elseif (date('Ymd', strtotime($result->nextDueDate)) >= date('Ymd', time())) {
                $row['duetime'] = 'On time';
            } else {
                $row['duetime'] = 'Overdue';
            }
            if ($format == 'pdf' || $format == 'xls') {
                $row['description'] = strip_tags(html_entity_decode($result->description));
                $row['recommendation'] = strip_tags(html_entity_decode($result->recommendation));
                $row['mitigationStrategy'] = strip_tags(html_entity_decode($result->mitigationStrategy));
                $row['threat'] = strip_tags(html_entity_decode($result->threat));
                $row['countermeasures'] = strip_tags(html_entity_decode($result->countermeasures));
            } else {
                $row['description'] = $this->view->ShowLongText(
                    strip_tags($result->description), 
                    $this->view->keywords
                );
                $row['recommendation'] = $this->view->ShowLongText(
                    strip_tags($result->recommendation), 
                    $this->view->keywords
                );
                $row['mitigationStrategy'] = $this->view->ShowLongText(
                    strip_tags($result->mitigationStrategy), 
                    $this->view->keywords
                );
                $row['threat'] = $this->view->ShowLongText(strip_tags($result->threat), $this->view->keywords);
                $row['countermeasures'] = $this->view->ShowLongText(
                    strip_tags($result->countermeasures), 
                    $this->view->keywords
                );
            }
            $list[] = $row;
        }
        return $list;
    }
    
    /**
     * Display the NIST SP 800-53 control mapping and related information
     * 
     * @return void
     */
    function securityControlAction() 
    {
        $this->_viewFinding();
        $this->_helper->layout->setLayout('ajax');
    }
    
    /** 
     * Renders the form for uploading artifacts.
     * 
     * @return void
     */
    function uploadFormAction() 
    {
        $this->_helper->layout()->disableLayout();
    }

    /**
     * Get the finding and assign it to view
     * 
     * @return void
     */
    private function _viewFinding()
    {
        $id = $this->_request->getParam('id');
        $finding = $this->_getFinding($id);
        $orgNickname = $finding->ResponsibleOrganization->nickname;

        // Check that the user is permitted to view this finding
        Fisma_Acl::requirePrivilegeForObject('read', $finding);

        $this->view->finding = $finding;
    }

    /**
     * Check and get a specified finding
     *
     * @param int $id The specified finding id
     * @return Finding The found finding
     * @throws Fisma_Exception if the specified finding id is not found
     */
    private function _getFinding($id)
    {
        $finding = Doctrine::getTable('Finding')->find($id);

        if (false == $finding) {
             throw new Fisma_Exception("FINDING($findingId) is not found. Make sure a valid ID is specified.");
        }
        
        return $finding;
    }
}
