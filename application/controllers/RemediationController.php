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
 * @author    Jim Chen <xhorse@users.sourceforge.net>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id$
 * @package   Controller
 */

/**
 * The remediation controller handles CRUD for findings in remediation.
 *
 * @package   Controller
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 *
 * @todo As part of the ongoing refactoring, this class should probably be
 * merged with the FindingController.
 */
class RemediationController extends SecurityController
{
    /**
     * The orgSystems which are belongs to current user.
     * 
     */
    protected $_organizations = null;
    
    /**
     * Default paginate parameters
     */
    protected $_paging = array(
        'startIndex' => 0,
        'count' => 20
    );
    
    /**
     * The preDispatch hook is used to split off poam modify actions, mitigation approval actions, and evidence
     * approval actions into separate controller actions.
     * 
     * @param Zend_Controller_Request_Abstract $request          
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
    * init() - Create the additional PDF, XLS and RSS contexts for this class.
    *
    */
    public function init()
    {
        parent::init();    
        $attach = $this->_helper->contextSwitch();
        if (!$attach->hasContext('pdf')) {
            $attach->addContext('pdf',
                array('suffix' => 'pdf',
                   'headers' => array(
                        'Content-Disposition' => "attachement;filename=export.pdf",
                        'Content-Type' => 'application/pdf')))
                   ->addActionContext('search2', array('pdf'))
                   ->setAutoDisableLayout(true);
        }
        if (!$attach->hasContext('xls')) {
            $attach->addContext('xls',
                array('suffix' => 'xls',
                   'headers' => array(
                        'Content-Disposition' => "attachement;filename=export.xls",
                        'Content-Type' => 'application/vnd.ms-excel')))
                   ->addActionContext('search2', array('xls'))->setAutoDisableLayout(true);
        }
        $this->_helper->contextSwitch()
                      ->addActionContext('summary-data', 'json')
                      ->addActionContext('summary-data', 'xls')
                      ->addActionContext('summary-data', 'pdf')                                            
                      ->initContext();
        $this->_organizations = $this->_me->getOrganizations();
    }
    
    /**
     *  Default action.
     *
     *  It combines the searching and summary into one page.
     */
    
    public function indexAction()
    {
        Fisma_Acl::requirePrivilege('finding', 'read', '*');
        
        $this->_helper->actionStack('searchbox', 'Remediation');
        $this->_helper->actionStack('summary', 'Remediation');
    }

    /**
     * Presents the view which contains the summary table. The summary table loads summary data
     * asynchronously by invoking the summaryDataAction().
     */    
    public function summaryAction()
    {
        Fisma_Acl::requirePrivilege('finding', 'read', '*');
        
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
     */
    public function summaryDataAction() {
        Fisma_Acl::requirePrivilege('finding', 'read', '*');
        
        // Doctrine supports the idea of using a base query when populating a tree. In our case, the base
        // query selects all Organizations which the user has access to.
        if ('root' == Zend_Auth::getInstance()->getIdentity()->username) {
            $userOrgQuery = Doctrine_Query::create()
                            ->select('o.name, o.nickname, o.orgType, s.type')
                            ->from('Organization o')
                            ->leftJoin('o.System s');
        } else {
            $userOrgQuery = Doctrine_Query::create()
                            ->select('o.name, o.nickname, o.orgType, s.type')
                            ->from('Organization o')
                            ->innerJoin('o.Users u')
                            ->leftJoin('o.System s')
                            ->where('u.id = ?', $this->_me->id);
        }
        $orgTree = Doctrine::getTable('Organization')->getTree();
        $orgTree->setBaseQuery($userOrgQuery);
        $organizations = $orgTree->fetchTree();
        $orgTree->resetBaseQuery();
            

        // For excel and PDF requests, return a table format. For JSON requests, return a hierarchical
        // format
        $type = $this->getRequest()->getParam('type');
        $source = $this->getRequest()->getParam('source');        
        $format = $this->_helper->contextSwitch()->getCurrentContext();
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
                $indentAmount = $organization->level * 3;
                $orgName = str_pad($organization->name, 
                                   $indentAmount + strlen($organization->name),
                                   ' ',
                                   STR_PAD_LEFT);

                $counts = $organization->getSummaryCounts($type, $source);
                // Decide whether to show rolled up counts or single row counts
                if (in_array($organization->id, $collapsedRows)) {
                    // Show rolled up row counts
                    $ontimeRow = array_merge(array($orgName), 
                                             array_values($counts['all_ontime']));
                    $tableData[] = $ontimeRow;
                    
                    // If there are overdues, then create another row for overdues
                    if (array_sum($counts['all_overdue']) > 0) {
                        $overdueRow = array_merge(array("$orgName (Overdue Items)"), 
                                                  array_values($counts['all_overdue']));
                        $tableData[] = $overdueRow;
                    }
                } elseif (in_array($organization->id, $expandedRows)) {
                    // Show single row counts
                    $ontimeRow = array_merge(array($orgName), 
                                             array_values($counts['single_ontime']));
                    $tableData[] = $ontimeRow;
                    
                    // If there are overdues, then create another row for overdues
                    if (array_sum($counts['single_overdue']) > 0) {
                        $overdueRow = array_merge(array("$orgName (Overdue Items)"), 
                                                  array_values($counts['single_overdue']));
                        $tableData[] = $overdueRow;
                    }                    
                }
            }

            $this->view->tableData = $tableData;
        } else {
            $organizations = $this->toHierarchy($organizations, $type, $source);

            $this->view->summaryData = $organizations;
        } 
    }

    /**
     * This is duplicated from the organization controller. it would be nice to consolidate
     * this into the organization class. Doctrine should do this at v2.0, but if not, we 
     * should do it ourselves.
     * 
     * @todo see if the organization model's function can be used instead
     */
    public function toHierarchy($collection, $type, $source) 
    { 
        // Trees mapped 
        $trees = array(); 
        $l = 0; 
        if (count($collection) > 0) { 
            // Node Stack. Used to help building the hierarchy 
            $rootLevel = $collection[0]->level;
            
            $stack = array(); 
            foreach ($collection as $node) { 
                $item = $item = ($node instanceof Doctrine_Record) ? $node->toArray() : $node;
                $item['level'] -= $rootLevel;
                $item['label'] = $item['nickname'] . ' - ' . $item['name'];
                $item['orgType'] = $node->getType();
                $item['orgTypeLabel'] = $node->getOrgTypeLabel();

                $summaryCounts = $node->getSummaryCounts($type, $source);
                $item = array_merge($item, $summaryCounts);
                
                $item['children'] = array();
                // Number of stack items 
                $l = count($stack); 
                // Check if we're dealing with different levels 
                while ($l > 0 && $stack[$l - 1]['level'] >= $item['level']) { 
                    array_pop($stack); 
                    $l--; 
                } 
                // Stack is empty (we are inspecting the root) 
                if ($l == 0) { 
                    // Assigning the root node 
                    $i = count($trees); 
                    $trees[$i] = $item; 
                    $stack[] = & $trees[$i]; 
                } else { 
                    // Add node to parent 
                    $i = count($stack[$l - 1]['children']); 
                    $stack[$l - 1]['children'][$i] = $item; 
                    $stack[] = & $stack[$l - 1]['children'][$i]; 
                } 
            } 
        } 
        return $trees; 
    }    
    
    /**
     * parse and translate the URL to criterias
     * which can be used by searchBoxAction method and searchAction method.
     *
     * @return array $params the criterias dealt
     */
    private function _parseCriteria()
    {
        $params = array('responsibleOrganizationId' => 0, 'sourceId' => 0, 'type' => '',
                        'status' => '', 'ids' => '', 'assetOwner' => 0,
                        'estDateBegin' => '', 'estDateEnd' => '',
                        'createdDateBegin' => '', 'createdDateEnd' => '',
                        'ontime' => '', 'sortby' => '', 'dir'=> '', 'keywords' => '');
        $req = $this->getRequest();
        $tmp = $req->getParams();
        foreach ($params as $k => &$v) {
            if (isset($tmp[$k])) {
                if ('keywords' == $k) {
                    $v = trim($tmp[$k], '"\'');
                } else {
                    $v = $tmp[$k];
                }
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
        if (!empty($params['estDateBegin'])) {
            $params['estDateBegin'] = new Zend_Date($params['estDateBegin'], 'Y-m-d');
        }
        if (!empty($params['estDateEnd'])) {
            $params['estDateEnd'] = new Zend_Date($params['estDateEnd'], 'Y-m-d');
        }
        if (!empty($params['createdDateBegin'])) {
            $params['createdDateBegin'] = new Zend_Date($params['createdDateBegin'], 'Y-m-d');
        }
        if (!empty($params['createdDateEnd'])) {
            $params['createdDateEnd'] = new Zend_Date($params['createdDateEnd'], 'Y-m-d');
        }
        return $params;
    }
    
    /**
     * get the columns(title) which were displayed on page, PDF, Excel
     *
     */
    private function _getColumns(){
        // Set up the data for the columns in the search results table
        $me = Doctrine::getTable('User')->find($this->_me->id);
        if (isset($_COOKIE['search_columns_pref'])) {
            $visibleColumns = $_COOKIE['search_columns_pref'];
        } elseif (empty($me->searchColumnsPref)) {
            $me->searchColumnsPref = $visibleColumns = 66037;
            $me->save();
        } else {
            $visibleColumns = $me->searchColumnsPref;
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
    * Do the real searching work. It's a thin wrapper
    * of poam model's search method.
    */
    public function searchAction()
    {
        Fisma_Acl::requirePrivilege('finding', 'read', '*');
        $params = $this->_parseCriteria();
        $link = $this->_helper->makeUrlParams($params);
        $this->view->assign('link', $link);
        $this->view->assign('attachUrl', '/remediation/search2' . $link);
        $this->view->assign('columns', $this->_getColumns());
        $this->view->assign('pageInfo', $this->_paging);
        $this->render();
    }
    
    /**
     * Accept the criterias dealt by parseCriteria method,
     * return the values to advance search page or basic search page.
     * when the criterias cantain the param 'keywords',
     * then this method will render the basic search box,
     * else render the advance search box
     *
     * Basic search url would be 
     * /panel/remediation/sub/searchbox/s/search/responsibleOrganizationId/1/type/CAP/status/DRAFT...
     * Advanced search url would be /panel/remediation/sub/searchbox/s/search/keywords/firewal
     * User use advanced search to search the basic search results,the url would be 
     *  /panel/remediation/sub/searchbox/s/search/keywords/firewal/responsibleOrganizationId/1/type/CAP...
     *
     */
    public function searchboxAction()
    {
        Fisma_Acl::requirePrivilege('finding', 'read', '*');
        
        $params = $this->_parseCriteria();
        $this->view->assign('params', $params);
        $this->view->assign('systems', $this->_organizations->toKeyValueArray('id', 'name'));
        $this->view->assign('sources', Doctrine::getTable('Source')->findAll()->toKeyValueArray('id', 'name'));
        $this->_helper->actionStack('search', 'Remediation');
        $this->render();
    }
    
    /**
     * Get remediation detail info
     */
    public function viewAction()
    {
        $this->_viewFinding();
        $this->view->keywords =  $this->_request->getParam('keywords');
    }
    
    /**
     * Modify the finding
     *
     */
    public function modifyAction()
    {
        // ACL for finding objects is handled inside the finding listener, because it has to do some
        // very fine-grained error checking
        
        $id          = $this->_request->getParam('id');
        $findingData = $this->_request->getPost('finding', array());

        $finding     = $this->_getFinding($id);

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
            $model = self::M_WARNING;
            $this->message($message, $model);
        }
        $this->_forward('view', null, null, array('id' => $id));
    }

    /**
     * Mitigation Strategy Approval Process
     */
    public function msaAction()
    {
        $id       = $this->_request->getParam('id');
        $do       = $this->_request->getParam('do');
        $decision = $this->_request->getPost('decision');

        $finding  = $this->_getFinding($id);
        if (!empty($decision)) {
//            var_dump($finding->toArray());die;
            Fisma_Acl::requirePrivilege('finding', $finding->CurrentEvaluation->Privilege->action);
        }
       
        try {
            Doctrine_Manager::connection()->beginTransaction();

            if ('submitmitigation' == $do) {
                Fisma_Acl::requirePrivilege('finding', 'mitigation_strategy_submit', $finding->ResponsibleOrganization->nickname);
                $finding->submitMitigation(User::currentUser());
            }
            if ('revisemitigation' == $do) {
                Fisma_Acl::requirePrivilege('finding', 'mitigation_strategy_revise', $finding->ResponsibleOrganization->nickname);
                $finding->reviseMitigation(User::currentUser());
            }

            if ('APPROVED' == $decision) {
                $finding->approve(User::currentUser());
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
            $model = self::M_WARNING;
            $this->message($message, $model);
        }
        $this->_forward('view', null, null, array('id' => $id));
    }

    /**
     * Upload evidence 
     */
    public function uploadevidenceAction()
    {
        $id = $this->_request->getParam('id');
        $finding = $this->_getFinding($id);

        Fisma_Acl::requirePrivilege('finding', 'upload_evidence', $finding->ResponsibleOrganization->nickname);

        define('EVIDENCE_PATH', Fisma::getPath('data') . '/uploads/evidence');
        $file = $_FILES['evidence'];

        try {
            if (!$file['name']) {
                $message = "You did not select a file to upload. Please select a file and try again.";
                throw new Fisma_Exception($message);
            }

            $extension = end(explode(".",$file['name']));
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
                    $message = 'Failed in move_uploaded_file(). ' . $absFile . "\n" . $file['error'];
                    throw new Fisma_Exception($message);
                }
            } else {
                throw new Fisma_Exception('The filename is not valid');
            }

            $finding->uploadEvidence($filename, User::currentUser());
        } catch (Fisma_Exception $e) {
            $this->message($e->getMessage(), self::M_WARNING);
        }
        $this->_forward('view', null, null, array('id' => $id));
    }
    
    /**
     * Download evidence
     */
    public function downloadevidenceAction()
    {
        $id = $this->_request->getParam('id');
        $evidence = Doctrine::getTable('Evidence')->find($id);
        if (empty($evidence)) {
            throw new Fisma_Exception('Invalid evidence ID');
        }

        Fisma_Acl::requirePrivilege('finding', 'read_evidence', $evidence->Finding->ResponsibleOrganization->nickname);

        if (!in_array($evidence->Finding->ResponsibleOrganization, $this->_me->getOrganizations()->toArray())
            && 'root' != $this->_me->username) {
            throw new Fisma_Exception_InvalidPrivilege('You do not have permission to view this file');
        }

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
     *  Handle the evidence evaluations
     */
    public function evidenceAction()
    {
        $id       = $this->_request->getParam('id');
        $decision = $this->_request->getPost('decision');

        $finding  = $this->_getFinding($id);

        if (!empty($decision)) {
            Fisma_Acl::requirePrivilege('finding', $finding->CurrentEvaluation->Privilege->action, $finding->ResponsibleOrganization->nickname);
        }

        try {
            Doctrine_Manager::connection()->beginTransaction();
            if ('APPROVED' == $decision) {
                $finding->approve(User::currentUser());
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
            $model = self::M_WARNING;
            $this->message($message, $model);
        }
        $this->_forward('view', null, null, array('id' => $id));
    }

    /**
     *  Generate RAF report
     *
     *  It can handle different format of RAF report.
     */
    public function rafAction()
    {
        $id = $this->_request->getParam('id');
        $finding = $this->_getFinding($id);

        Fisma_Acl::requirePrivilege('finding', 'read', $finding->ResponsibleOrganization->nickname);

        try {
            if ($finding->threat == '' ||
                $finding->threatLevel == 'NONE' ||
                $finding->countermeasures == '' ||
                $finding->countermeasuresEffectiveness == 'NONE') {
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
            $this->message($message, self::M_WARNING);
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
     * Get keywords from basic search query for highlight
     *
     * Basic search query is a complicated format string, system should pick-up available keywords to highlight
     */
    protected function getKeywords($query)
    {
        $keywords = '';
        $keywords = strtolower($query);

        //delete not contain keyword (-keyword, NOT keyword)
        $keywords = preg_replace('/-[A-Za-z0-9]+$/', '', $keywords);
        $keywords = preg_replace('/not\s+[A-Za-z0-9]+$/', '', $keywords);

        //delete Zend_Search_Lucene query keywords
        $searchKeys = array(' and ', ' or ', ' not ', ' to ', '+', '-', '&&', '~', '||', '!', '*', '?', '"', "'");
        foreach ($searchKeys as $row) {
            $keywords = str_replace($row, ' ', $keywords);
        }
        
        //delete multi-spaces
        $keywords = preg_replace('/\s{2,}/', ' ', $keywords);

        //delete search field
        $keywords = explode(' ', trim($keywords));
        foreach ($keywords as &$word) {
            $word = preg_replace('/^.+:/', '', $word);
        }
        
        $keywords = implode(',', $keywords);
        return $keywords;
    }
    
    /**
     * Display basic data about the finding and the affected asset
     */
    function findingAction() {
        $this->_viewFinding();
        $this->view->keywords = $this->_request->getParam('keywords');
        $this->_helper->layout->setLayout('ajax');
    }

    /**
     * Fields for defining the mitigation strategy
     */
    function mitigationStrategyAction() {
        $this->_viewFinding();
        $this->_helper->layout->setLayout('ajax');
    }

    /**
     * Display fields related to risk analysis such as threats and countermeasures
     */
    function riskAnalysisAction() {
        $this->_viewFinding();
        $this->view->keywords = $this->_request->getParam('keywords');
        $this->_helper->layout->setLayout('ajax');
    }

    /**
     * Display fields related to risk analysis such as threats and countermeasures
     */
    function artifactsAction() {
        $this->_viewFinding();
        $this->_helper->layout->setLayout('ajax');
    }
        
    /**
     * Display the audit log associated with a finding
     */
    function auditLogAction() {
        $this->_viewFinding();
        $this->_helper->layout->setLayout('ajax');
        
        $auditQuery = Doctrine_Query::create()
                      ->select('a.createdTs, u.username, a.description')
                      ->from('AuditLog a')
                      ->innerJoin('a.User u')
                      ->where('a.findingId = ?', $this->getRequest()->getParam('id'))
                      ->orderBy('a.createdTs DESC')
                      ->setHydrationMode(Doctrine::HYDRATE_SCALAR);
        $auditLogs = $auditQuery->execute();
        $this->view->auditLogs = $auditLogs;
    }
    
    /**
     * Real searching worker, to return searching results for page, PDF, Excel
     *
     */
    public function search2Action() {
        Fisma_Acl::requirePrivilege('finding', 'read', '*');
        
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
            $now = new Zend_Date(null, 'Y-m-d');
            switch ($params['status']) {
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
            $poamIds = Fisma_Lucene::search($params['keywords'], 'finding');
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
            $this->view->assign('keywords', $this->getKeywords($params['keywords']));
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
     * analyze the criterias and merge the DQL query for getting results
     * 
     * @param array $params criterias
     * @param string $format json xls pdf
     * @return array $list results
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
            } elseif(date('Ymd', strtotime($result->nextDueDate)) >= date('Ymd', time())) {
                $row['duetime'] = 'On time';
            } else {
                $row['duetime'] = 'Due time';
            }
            if ($format == 'pdf' || $format == 'xls') {
                $row['description'] = strip_tags(html_entity_decode($result->description));
                $row['recommendation'] = strip_tags(html_entity_decode($result->recommendation));
                $row['mitigationStrategy'] = strip_tags(html_entity_decode($result->mitigationStrategy));
                $row['threat'] = strip_tags(html_entity_decode($result->threat));
                $row['countermeasures'] = strip_tags(html_entity_decode($result->countermeasures));
            } else {
                $row['description'] = $this->view->ShowLongText(strip_tags($result->description), 
                                                                $this->view->keywords);
                $row['recommendation'] = $this->view->ShowLongText(strip_tags($result->recommendation), 
                                                                   $this->view->keywords);
                $row['mitigationStrategy'] = $this->view->ShowLongText(strip_tags($result->mitigationStrategy), 
                                                                       $this->view->keywords);
                $row['threat'] = $this->view->ShowLongText(strip_tags($result->threat), $this->view->keywords);
                $row['countermeasures'] = $this->view->ShowLongText(strip_tags($result->countermeasures), 
                                                                    $this->view->keywords);
            }
            $list[] = $row;
        }
        return $list;
    }
    
    /**
     * Display the NIST SP 800-53 control mapping and related information
     */
    function securityControlAction() 
    {
        $this->_viewFinding();
        $this->_helper->layout->setLayout('ajax');
    }
    
    /** 
     * Renders the form for uploading artifacts.
     */
    function uploadFormAction() {
        $this->_helper->layout()->disableLayout();
    }

    /**
     * Get the finding and assign it to view
     *
     */
    private function _viewFinding()
    {
        $id = $this->_request->getParam('id');
        $this->view->finding = $this->_getFinding($id);
    }

    /**
     * Check and get a specific finding
     *
     * @param int $id
     * @param return Zend_Record $finding
     * @throw 
     */
    private function _getFinding($id)
    {
        $finding = new Finding();
        $finding = $finding->getTable()->find($id);
        if (false == $finding) {
             throw new Fisma_Exception("FINDING($findingId) is not found. Make sure a valid ID is specified.");
        }

        return $finding;
    }
}
