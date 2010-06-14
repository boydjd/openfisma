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
 * A controller for the finding module's reports
 * 
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Controller
 * @version    $Id$
 */
class FindingReportController extends SecurityController
{
    /**
     * Set up the context switch for Excel and PDF output
     */
    public function init()
    {
        $this->_helper->fismaContextSwitch()
                      ->addActionContext('overdue', array('pdf', 'xls'))
                      ->addActionContext('plugin-report', array('pdf', 'xls'))
                      ->addActionContext('fisma-quarterly', 'xls')
                      ->addActionContext('fisma-annual', 'xls')
                      ->initContext();
        
        parent::init();
    }
    
    /**
     * Check that the user has the privilege to run reports
     */
    public function preDispatch()
    {
        $this->_acl->requireArea('finding_report');

        // Add header/footer to any action which expects an HTML response
        if (!$this->_hasParam('format')) {
            $this->_helper->actionStack('header', 'panel');
        }
    }

    /**
     * Returns the due date for the next quarterly FISMA report
     * 
     * @return Zend_Date The next quarterly OpenFISMA report date
     */
    public function getNextQuarterlyFismaReportDate()
    {
        // OFJ-463 Due to a bug in ZF we need to temporarily modify the date format while running this method
        $currentDateOptions = Zend_Date::setOptions();
        $currentDateFormat = $currentDateOptions['format_type'];
        Zend_Date::setOptions(array('format_type' => 'iso'));
        
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
        
        // OFJ-463 Continued from above
        Zend_Date::setOptions(array('format_type' => $currentDateFormat));
        
        return $reportDate;
    }

    /**
     * Returns the due date for the next annual FISMA report
     * 
     * @return Zend_Date The next annual OpenFISMA report date
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
     * Genenerate fisma report
     * 
     * @return void
     */
    public function fismaAction()
    {        
        $this->view->nextQuarterlyReportDate = $this->getNextQuarterlyFismaReportDate()->toString('Y-m-d');
        $this->view->nextAnnualReportDate = $this->getNextAnnualFismaReportDate()->toString('Y-m-d');
    }
    
    /**
     * Generate the quarterly FISMA report
     * 
     * The data in this action is calculated in roughly the same order as it is laid out in the report itself.
     * 
     * @return void
     */
    public function fismaQuarterlyAction()
    {
        // Agency Name
        $agency = Organization::getAgency();
        $this->view->agencyName = $agency->name;
        
        // Submission Date
        $this->view->submissionDate = date('Y-m-d');
        
        // Bureau Statistics
        $bureaus = Organization::getBureaus();
        $stats = array();
        foreach ($bureaus as $bureau) {
            $bureauStats = $bureau->getFismaStatistics();
            $stats[$bureauStats['name']] = $bureauStats;
        }

        // Sort by bureau name (which is the array key)
        ksort($stats);

        $this->view->stats = $stats;
    }

    /**
     * Generate the annual FISMA report
     * 
     * @return void
     */
    public function fismaAnnualAction()
    {
        // Agency Name
        $agency = Organization::getAgency();
        $this->view->agencyName = $agency->name;
        
        // Submission Date
        $this->view->submissionDate = date('Y-m-d');
        
        // Bureau Statistics
        $bureaus = Organization::getBureaus();
        $stats = array();
        foreach ($bureaus as $bureau) {
            $bureauStats = $bureau->getFismaStatistics();
            $stats[$bureauStats['name']] = $bureauStats;
        }

        // Sort by bureau name (which is the array key)
        ksort($stats);

        $this->view->stats = $stats;
    }
        
    /**
     * Overdue report
     * 
     * @return void
     */
    public function overdueAction()
    {        
        // Get request variables
        $req = $this->getRequest();
        $params['orgSystemId'] = $req->getParam('orgSystemId');
        $params['sourceId'] = $req->getParam('sourceId');
        $params['overdueType'] = $req->getParam('overdueType');
        $params['overdueDay'] = $req->getParam('overdueDay');
        $params['year'] = $req->getParam('year');

        if (!empty($params['orgSystemId'])) {
            $organization = Doctrine::getTable('Organization')->find($params['orgSystemId']);
            $this->_acl->requirePrivilegeForObject('read', $organization);
        } else {
            $this->_acl->requirePrivilegeForClass('read', 'Organization');
        }

        $this->view->assign('sourceList', Doctrine::getTable('Source')->findAll()->toKeyValueArray('id', 'name'));
        $this->view->assign(
            'systemList', 
            $this->_me->getOrganizationsByPrivilege('finding', 'read')->toKeyValueArray('id', 'name')
        );
        $this->view->assign('networkList', Doctrine::getTable('Network')->findAll()->toKeyValueArray('id', 'name'));
        $this->view->assign('params', $params);
        $this->view->assign('url', '/finding-report/overdue' . $this->_helper->makeUrlParams($params));
        $isExport = $req->getParam('format');

        if ('search' == $req->getParam('s') || isset($isExport)) {
            $q = Doctrine_Query::create()
                 ->select('f.id') // unused, but Doctrine requires a field to be selected from the parent object
                 ->addSelect("CONCAT_WS(' - ', o.nickname, o.name) orgSystemName")
                 ->addSelect(
                     "IF(f.status IN ('NEW', 'DRAFT', 'MSA'), 'Mitigation Strategy', IF(f.status IN ('EN', 'EA'),
                     'Corrective Action', NULL)) actionType"
                 )
                 ->addSelect('SUM(IF(DATEDIFF(NOW(), f.nextduedate) BETWEEN 0 AND 29, 1, 0)) lessThan30')
                 ->addSelect('SUM(IF(DATEDIFF(NOW(), f.nextduedate) BETWEEN 30 AND 59, 1, 0)) moreThan30')
                 ->addSelect('SUM(IF(DATEDIFF(NOW(), f.nextduedate) BETWEEN 60 AND 89, 1, 0)) moreThan60')
                 ->addSelect('SUM(IF(DATEDIFF(NOW(), f.nextduedate) BETWEEN 90 AND 119, 1, 0)) moreThan90')
                 ->addSelect('SUM(IF(DATEDIFF(NOW(), f.nextduedate) >= 120, 1, 0)) moreThan120')
                 ->addSelect('IFNULL(COUNT(f.id), 0) total')
                 ->addSelect('IFNULL(ROUND(AVG(DATEDIFF(NOW(), f.nextduedate))), 0) average')
                 ->addSelect('IFNULL(MAX(DATEDIFF(NOW(), f.nextduedate)), 0) max')
                 ->from('Finding f')
                 ->leftJoin('f.ResponsibleOrganization o')
                 ->where('f.nextduedate < NOW()');

            if (!empty($params['orgSystemId'])) {
                $q->andWhere('f.responsibleOrganizationId = ?', $params['orgSystemId']);
            } else {
                $organizations = $this->_me->getOrganizationsByPrivilege('finding', 'read')
                    ->toKeyValueArray('id', 'id');
                $q->whereIn('f.responsibleOrganizationId', $organizations);    
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

            $q->groupBy('orgSystemName, actionType');
            $q->setHydrationMode(Doctrine::HYDRATE_ARRAY);
            $list = $q->execute();

            // Assign view outputs
            $this->view->assign('poamList', $list);
            $this->view->criteria = $params;
            $this->view->columns = array('orgSystemName' => 'System', 
                                         'actionType' => 'Overdue Action Type', 
                                         'lessThan30' => '< 30 Days',
                                         'moreThan30' => '30-59 Days', 
                                         'moreThan60' => '60-89 Days', 
                                         'moreThan90' => '90-119 Days',
                                         'moreThan120' => '120+ Days', 
                                         'total' => 'Total Overdue', 
                                         'average' => 'Average (days)',
                                         'max' => 'Maximum (days)');
        }
    }
    
    /**
     * Display the available plugin reports
     * 
     * @return void
     * @todo Use Zend_Cache for the report menu
     */         
    public function pluginAction() 
    {        
        // Build up report menu
        $reportsConfig = new Zend_Config_Ini(Fisma::getPath('application') . '/config/reports.conf');
        $reports = $reportsConfig->toArray();
        
        // Filter unauthorized plugin report items since actually user does not have rights to visit it.
        if ($this->_me->username != 'root') {
            $userRolesResult = $this->_me->getRoles();
            $userRoleNicknames = array();
            foreach ($userRolesResult as $row) {
                $userRoleNicknames[] = $row['r_nickname'];
            }
            foreach ($reports as $reportName => $report) {
                $roleNicknameIntersection = array_intersect($userRoleNicknames, $report['roles']);
                if (empty($roleNicknameIntersection)) {
                    unset($reports[$reportName]);
                }
            }
        }
        
        $this->view->assign('reports', $reports);
    }

    /**
     * Execute and display the specified plug-in report
     * 
     * @return void
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
                              ->where('u.id = ?', CurrentUser::getInstance()->id)
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
                throw new Fisma_Zend_Exception("User \"{$this->_me->username}\" does not have permission to view"
                                          . " the \"$reportName\" plug-in report.");
            }
        }
        
        // Execute the report script
        $reportScriptFile = Fisma::getPath('application') . "/config/reports/$reportName.sql";
        $reportScriptFileHandle = fopen($reportScriptFile, 'r');
        if (!$reportScriptFileHandle) {
            throw new Fisma_Zend_Exception("Unable to load plug-in report SQL file: $reportScriptFile");
        }
        $reportScript = '';
        while (!feof($reportScriptFileHandle)) {
            $reportScript .= fgets($reportScriptFileHandle);
        }
        $myOrganizations = array();
        foreach ($this->_me->getOrganizationsByPrivilege('finding', 'read') as $organization) {
            $myOrganizations[] = $organization->id;
        }
        if (empty($myOrganizations)) {
            $msg = "The report could not be created because this user does not have access to any organizations.";
            $this->view->priorityMessenger($msg, 'warning');
            $this->_redirect('/finding-report/plugin');
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
            $this->view->priorityMessenger($msg, 'warning');
            $this->_redirect('/finding-report/plugin');
            return;
        }
        
        $this->view->assign('title', $reportConfig->title);
        $this->view->assign('columns', $columns);
        $this->view->assign('rows', $reportData);
        $this->view->assign('url', "/finding-report/plugin-report/name/$reportName");
    }
}
