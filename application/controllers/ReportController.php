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
 * @author    Ryan Yang <ryan@users.sourceforge.net>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id$
 * @package   Controller
 */

/**
 * The report controller creates the multitude of reports available in
 * OpenFISMA.
 *
 * @package   Controller
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 */
class ReportController extends SecurityController
{
    /**
     * init() - Create the additional pdf and xls contexts for this class.
     *
     * @todo Why are the contexts duplicated in init() and predispatch()? I think the init() method is the right place
     * for it.
     */
    public function init()
    {
        parent::init();
        $swCtx = $this->_helper->contextSwitch();
        if (!$swCtx->hasContext('pdf')) {
            $swCtx->addContext('pdf', array(
                'suffix' => 'pdf',
                'headers' => array(
                    'Content-Disposition' => 
                        'attachement;filename="export.pdf"',
                    'Content-Type' => 'application/pdf'
                )
            ));
        }
        if (!$swCtx->hasContext('xls')) {
            $swCtx->addContext('xls', array(
                'suffix' => 'xls',
                'headers' => array(
                    'Content-type' => 'application/vnd.ms-excel',
                    'Content-Disposition' => 'filename=Fisma_Report.xls'
                )
            ));
        }
    }
    
    /**
     * preDispatch() - Add the action contexts for this controller.
     */
    public function preDispatch()
    {
        parent::preDispatch();
        $this->req = $this->getRequest();
        $swCtx = $this->_helper->contextSwitch();
        $swCtx->addActionContext('overdue', array('pdf', 'xls'))
              ->addActionContext('plugin-report', array('pdf', 'xls'))
              ->addActionContext('fisma-quarterly', 'xls')
              ->addActionContext('fisma-annual', 'xls')
              ->initContext();
    }

    /**
     * Returns the due date for the next quarterly FISMA report
     * 
     * @return Zend_Date
     */
    public function getNextQuarterlyFismaReportDate()
    {
        // The quarterly reports are due on 3/1, 6/1, 9/1 and 12/1
        $reportDate = new Zend_Date();
        if (1 == (int)$reportDate->getDay()->toString('d')) {
            $reportDate->subMonth(1);
        }
        $reportDate->setDay(1);
        switch ((int)$reportDate->getMonth()->toString('m')) {
            case 12:
                $reportDate->addYear(1);
            case 1:
            case 2:
                $reportDate->setMonth(3);
                break;
            case 3:
            case 4:
            case 5:
                $reportDate->setMonth(6);
                break;
            case 6:
            case 7:
            case 8:
                $reportDate->setMonth(9);
                break;
            case 9:
            case 10:
            case 11:
                $reportDate->setMonth(12);
                break;
        }
        return $reportDate;
    }

    /**
     * Returns the due date for the next annual FISMA report
     * 
     * @return Zend_Date
     */
    public function getNextAnnualFismaReportDate()
    {
        // The annual report is due Oct 1 of each year
        $reportDate = new Zend_Date();
        $reportDate->setMonth(10);
        $reportDate->setDay(1);
        if (-1 == $reportDate->compare(new Zend_Date())) {
            $reportDate->addYear(1);
        }
        return $reportDate;
    }

    /**
     * fismaAction() - Genenerate fisma report
     */
    public function fismaAction()
    {
        Fisma_Acl::requirePrivilege('area', 'reports');
        
        $this->view->nextQuarterlyReportDate = $this->getNextQuarterlyFismaReportDate()->toString('Y-m-d');
        $this->view->nextAnnualReportDate = $this->getNextAnnualFismaReportDate()->toString('Y-m-d');
    }
    
    /**
     * Generate the quarterly FISMA report
     * 
     * The data in this action is calculated in roughly the same order as it is laid out in the report itself.
     */
    public function fismaQuarterlyAction()
    {
        Fisma_Acl::requirePrivilege('area', 'reports');

        // Agency Name
        $agency = Organization::getAgency();
        $this->view->agencyName = $agency->name;
        
        // Submission Date
        $this->view->submissionDate = date('Y-m-d');
        
        // Bureau Statistics
        $bureaus = Organization::getBureaus();
        $stats = array();
        foreach ($bureaus as $bureau) {
            $stats[] = $bureau->getFismaStatistics();
        }
        $this->view->stats = $stats;
    }
    
    /**
     * Generate the annual FISMA report
     */
    public function fismaAnnualAction()
    {
        Fisma_Acl::requirePrivilege('area', 'reports');

        // Agency Name
        $agency = Organization::getAgency();
        $this->view->agencyName = $agency->name;
        
        // Submission Date
        $this->view->submissionDate = date('Y-m-d');
        
        // Bureau Statistics
        $bureaus = Organization::getBureaus();
        $stats = array();
        foreach ($bureaus as $bureau) {
            $stats[] = $bureau->getFismaStatistics();
        }
        $this->view->stats = $stats;
    }
        
    /**
     * overdueAction() - Overdue report
     */
    public function overdueAction()
    {
        Fisma_Acl::requirePrivilege('area', 'reports');
        
        // Get request variables
        $req = $this->getRequest();
        $params['orgSystemId'] = $req->getParam('orgSystemId');
        $params['sourceId'] = $req->getParam('sourceId');
        $params['overdueType'] = $req->getParam('overdueType');
        $params['overdueDay'] = $req->getParam('overdueDay');
        $params['year'] = $req->getParam('year');

        $this->view->assign('source_list', Doctrine::getTable('Source')->findAll()->toKeyValueArray('id', 'name'));
        $this->view->assign('system_list', $this->_me->getOrganizations()->toKeyValueArray('id', 'name'));
        $this->view->assign('network_list', Doctrine::getTable('Network')->findAll()->toKeyValueArray('id', 'name'));
        $this->view->assign('params', $params);
        $this->view->assign('url', '/report/overdue' . $this->_helper->makeUrlParams($params));
        $isExport = $req->getParam('format');
        
        if ('search' == $req->getParam('s') || isset($isExport)) {
            // Search for overdue items according to the criteria
            $q = Doctrine_Query::create()
                    ->select('f.*')
                    ->addSelect('DATEDIFF(NOW(), f.nextDueDate) diffDay')
                    ->from('Finding f')
                    ->where('DATEDIFF(NOW(), f.nextDueDate) > 0');
            if (!empty($params['orgSystemId'])) {
                $q->andWhere('f.responsibleOrganizationId = ?', $params['orgSystemId']);
            }
            if (!empty($params['sourceId'])) {
                $q->andWhere('f.sourceId = ?', $params['sourceId']);
            }
            if ($params['overdueType'] == 'sso') {
                $q->whereIn('f.status', array('NEW', 'DRAFT', 'MSA'));
            } elseif ($params['overdueType'] == 'action') {
                $q->whereIn('f.status', array('EN', 'EA'));
            } else {
                $q->whereIn('f.status', array('NEW', 'DRAFT', 'MSA', 'EN', 'EA'));
            }
            $list = $q->execute();
            // Assign view outputs
            $this->view->assign('poam_list', $this->_helper->overdueStatistic($list));
            $this->view->criteria = $params;
            $this->view->columns = array('orgSystemName' => 'System', 'type' => 'Overdue Action Type', 'lessThan30' => '<30 Days',
                                         'moreThan30' => '30-59 Days', 'moreThan60' => '60-89 Days', 'moreThan90' => '90-119 Days',
                                         'moreThan120' => '120+ Days', 'total' => 'Total Overdue', 'average' => 'Average (days)',
                                         'max' => 'Maximum (days)');
        }
    }

    /**
     * Batch generate RAFs for each system
     */
    public function rafsAction()
    {
        Fisma_Acl::requirePrivilege('area', 'reports');
        $sid = $this->getRequest()->getParam('system_id', 0);
        $organizations = User::currentUser()->getOrganizations();
        $this->view->assign('organizations', $organizations->toKeyValueArray('id', 'name'));
        if (!empty($sid)) {
            $query = Doctrine_Query::create()
                     ->select('*')
                     ->from('Finding f')
                     ->where('threat_level IS NOT NULL')
                     ->andWhere('countermeasure_effectiveness IS NOT NULL');
            $findings = $query->execute();
            $count = count($findings);
            if ($count > 0) {
                $fname = tempnam('/tmp/', "RAFs");
                @unlink($fname);
                $rafs = new Archive_Tar($fname, true);
                $path = $this->_helper->viewRenderer
                        ->getViewScript('raf', array(
                                        'controller' => 'remediation',
                                        'suffix' => 'pdf.phtml'));
                try {
                    foreach ($findings as $finding) {
                        $poamDetail = & $this->_poam->getDetail($id);
                        $this->view->assign('poam', $poamDetail);
                        $ret = $system->find($poamDetail['system_id']);
                        $actOwner = $ret->current()->toArray();
                        $securityCategorization = $system->calcSecurityCategory($actOwner['confidentiality'],
                                                                                $actOwner['integrity'],
                                                                                $actOwner['availability']);
                        if (NULL == $securityCategorization) {
                            throw new Fisma_Exception('The security categorization for ('.$actOwner['id'].')'.
                                $actOwner['name'].' is not defined. An analysis of risk cannot be generated '.
                                'unless these values are defined.');
                        }
                        $this->view->assign('securityCategorization', $securityCategorization);
                        $rafs->addString("raf_{$id}.pdf", $this->view->render($path));
                    }
                    $this->_helper->layout->disableLayout(true);
                    $this->_helper->viewRenderer->setNoRender();
                    header("Content-type: application/octetstream");
                    header('Content-Length: ' . filesize($fname));
                    header("Content-Disposition: attachment; filename=RAFs.tgz");
                    header("Content-Transfer-Encoding: binary");
                    header("Expires: 0");
                    header("Cache-Control: must-revalidate, post-check=0,".
                        " pre-check=0");
                    header("Pragma: public");
                    echo file_get_contents($fname);
                    @unlink($fname);
                } catch (Fisma_Exception $e) {
                    if ($e instanceof Fisma_Exception) {
                        $message = $e->getMessage();
                    }
                    $this->message($message, self::M_WARNING);
                }
            } else {
                $this->view->sid = $sid;
                $this->message('There are no findings to generate RAFs for', self::M_WARNING);
                $this->_forward('report', 'panel', null, array('sub' => 'rafs', 'system_id' => ''));
            }
        }
    }
    
    /**
     * pluginAction() - Display the available plugin reports
     *
     * @todo Use Zend_Cache for the report menu
     */         
    public function pluginAction() 
    {
        Fisma_Acl::requirePrivilege('area', 'reports');
        
        // Build up report menu
        $reportsConfig = new Zend_Config_Ini(Fisma::getPath('application') . '/config/reports.conf');
        $reports = $reportsConfig->toArray();
        $this->view->assign('reports', $reports);
    }

    /**
     * pluginReportAction() - Execute and display the specified plug-in report
     */         
    public function pluginReportAction()
    {
        // Verify a plugin report name was passed to this action
        $reportName = $this->getRequest()->getParam('name');
        if (!isset($reportName)) {
            $this->_forward('plugin');
            return;
        }
        
        // Verify that the user has permission to run this report
        $reportConfig = new Zend_Config_Ini(Fisma::getPath('application') . '/config/reports.conf', $reportName);
        if ($this->_me->username != 'root') {
            $reportRoles = $reportConfig->roles;
            $report = $reportConfig->toArray();
            $reportRoles = $report['roles'];
            if (!is_array($reportRoles)) {
                $reportRoles = array($reportRoles);
            }
            $userRolesQuery = Doctrine_Query::create()
                              ->select('u.id, r.nickname')
                              ->from('User u')
                              ->innerJoin('u.Roles r')
                              ->where('u.id = ?', User::currentUser()->id)
                              ->setHydrationMode(Doctrine::HYDRATE_SCALAR);
            $userRolesResult = $userRolesQuery->execute();
            $userRoles = array();
            $hasRole = false;
            foreach ($userRolesResult as $key => $result) {
                if (in_array($result['r_nickname'], $reportRoles)) {
                    $hasRole = true;
                }
            }
            if (!$hasRole) {
                throw new Fisma_Exception("User \"{$this->_me->username}\" does not have permission to view"
                                          . " the \"$reportName\" plug-in report.");
            }
        }
        
        // Execute the report script
        $reportScriptFile = Fisma::getPath('application') . "/config/reports/$reportName.sql";
        $reportScriptFileHandle = fopen($reportScriptFile, 'r');
        if (!$reportScriptFileHandle) {
            throw new Fisma_Exception("Unable to load plug-in report SQL file: $reportScriptFile");
        }
        $reportScript = '';
        while (!feof($reportScriptFileHandle)) {
            $reportScript .= fgets($reportScriptFileHandle);
        }
        $myOrganizations = array();
        foreach ($this->_me->getOrganizations() as $organization) {
            $myOrganizations[] = $organization->id;
        }
        if (empty($myOrganizations)) {
            $msg = "The report could not be created because this user does not have access to any organizations.";
            $this->message($msg, self::M_WARNING);
            $this->_forward('plugin');
            return;
        }
        $reportScript = str_replace('##ORGANIZATIONS##', implode(',', $myOrganizations), $reportScript);
        $dbh = Doctrine_Manager::connection()->getDbh(); 
        $rawResults = $dbh->query($reportScript, PDO::FETCH_ASSOC);
        $reportData = array();
        foreach ($rawResults as $rawResult) {
            $reportData[] = $rawResult;
        }
        
        // Render the report results
        if (isset($reportData[0])) {
            $columns = array_keys($reportData[0]);
        } else {
            $msg = "The report could not be created because the report query did not return any data.";
            $this->message($msg, self::M_WARNING);
            $this->_forward('plugin');
            return;
        }
        
        $this->view->assign('title', $reportConfig->title);
        $this->view->assign('columns', $columns);
        $this->view->assign('rows', $reportData);
        $this->view->assign('url', "/panel/report/sub/plugin-report/name/$reportName");
    }
}
