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
class Finding_ReportController extends Fisma_Zend_Controller_Action_Security
{
    /**
     * Set up the context switch for Excel and PDF output
     */
    public function init()
    {
        $this->_helper->fismaContextSwitch()
                      ->addActionContext('fisma-quarterly', 'xls')
                      ->addActionContext('fisma-annual', 'xls')
                      ->initContext();

      $this->_helper->reportContextSwitch()
                    ->addActionContext('plugin-report', array('html', 'pdf', 'xls'))
                    ->addActionContext('overdue', array('html', 'pdf', 'xls'))
                    ->initContext();

        parent::init();
    }

    /**
     * Check that the user has the privilege to run reports
     */
    public function preDispatch()
    {
        parent::preDispatch();

        $this->_acl->requireArea('finding_report');
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
        $this->view->nextQuarterlyReportDate = $this->getNextQuarterlyFismaReportDate()
                                                    ->toString(Fisma_Date::FORMAT_DATE);
        $this->view->nextAnnualReportDate = $this->getNextAnnualFismaReportDate()->toString(Fisma_Date::FORMAT_DATE);
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
        $this->view->submissionDate = Zend_Date::now()->toString(Fisma_Date::FORMAT_DATE);
        
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
        $this->view->submissionDate = Zend_Date::now()->toString(Fisma_Date::FORMAT_DATE);
        
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
        $organizationId = $this->getRequest()->getParam('organizationId');
        $sourceId = $this->getRequest()->getParam('sourceId');

        if (!empty($organizationId)) {
            $organization = Doctrine::getTable('Organization')->find($organizationId);

            $this->_acl->requirePrivilegeForObject('read', $organization);
        } else {
            $this->_acl->requirePrivilegeForClass('read', 'Organization');
        }

        $organizations = $this->_me->getOrganizationsByPrivilege('finding', 'read');
        $organizationList = array('' => '') + $this->view->systemSelect($organizations);

        $sourceList = array('' => '') + Doctrine::getTable('Source')->findAll()->toKeyValueArray('id', 'nickname');
        asort($sourceList);

        // Set up the filter options in the toolbar
        $toolbarForm = Fisma_Zend_Form_Manager::loadForm('overdue_report_filters');

        $toolbarForm->getElement('organizationId')->setMultiOptions($organizationList);
        $toolbarForm->getElement('sourceId')->setMultiOptions($sourceList);

        $toolbarForm->setDefaults($this->getRequest()->getParams());

        $overdueQuery = Doctrine_Query::create()
                        ->addSelect("o.nickname a")
                        ->addSelect('f.denormalizedStatus b')
                        ->addSelect('SUM(IF(DATEDIFF(NOW(), f.nextduedate) BETWEEN 0 AND 29, 1, 0)) c')
                        ->addSelect('SUM(IF(DATEDIFF(NOW(), f.nextduedate) BETWEEN 30 AND 59, 1, 0)) d')
                        ->addSelect('SUM(IF(DATEDIFF(NOW(), f.nextduedate) BETWEEN 60 AND 89, 1, 0)) e')
                        ->addSelect('SUM(IF(DATEDIFF(NOW(), f.nextduedate) BETWEEN 90 AND 119, 1, 0)) g')
                        ->addSelect('SUM(IF(DATEDIFF(NOW(), f.nextduedate) >= 120, 1, 0)) h')
                        ->addSelect('IFNULL(COUNT(f.id), 0) i')
                        ->addSelect('IFNULL(ROUND(AVG(DATEDIFF(NOW(), f.nextduedate))), 0) j')
                        ->addSelect('IFNULL(MAX(DATEDIFF(NOW(), f.nextduedate)), 0) k')
                        ->from('Finding f')
                        ->leftJoin('f.ResponsibleOrganization o')
                        ->where('DATEDIFF(NOW(), f.nextduedate) > 0')
                        ->groupBy('o.id, f.denormalizedStatus')
                        ->setHydrationMode(Doctrine::HYDRATE_SCALAR);

        // If the user selects one organization then display that one only. Otherwise display all of this users systems.
        if (!empty($organizationId)) {
            $overdueQuery->andWhere('o.id = ?', $organizationId);
        } else {
            $overdueQuery->whereIn('o.id', $organizations->toKeyValueArray('id', 'id'));
        }

        if (!empty($sourceId)) {
            $overdueQuery->andWhere('f.sourceId = ?', $sourceId);

            $source = Doctrine::getTable('Source')->find($sourceId);
        }

        $reportData = $overdueQuery->execute();

        // Assign view outputs
        $report = new Fisma_Report();

        $report->setTitle('Overdue Report')
               ->addColumn(new Fisma_Report_Column('System', true))
               ->addColumn(new Fisma_Report_Column('Status', true))
               ->addColumn(
                   new Fisma_Report_Column(
                       '1-29 Days',
                       true,
                       'Fisma.TableFormat.overdueFinding',
                       array('from' => 1, 'to' => 29, 'source' => (isset($source) ? $source->nickname : null)),
                       false,
                       'number'
                   )
               )
               ->addColumn(
                   new Fisma_Report_Column(
                       '30-59 Days',
                       true,
                       'Fisma.TableFormat.overdueFinding',
                       array('from' => 30, 'to' => 59, 'source' => (isset($source) ? $source->nickname : null)),
                       false,
                       'number'
                   )
               )
               ->addColumn(
                   new Fisma_Report_Column(
                       '60-89 Days',
                       true,
                       'Fisma.TableFormat.overdueFinding',
                       array('from' => 60, 'to' => 89, 'source' => (isset($source) ? $source->nickname : null)),
                       false,
                       'number'
                   )
               )
               ->addColumn(
                   new Fisma_Report_Column(
                       '90-119 Days',
                       true,
                       'Fisma.TableFormat.overdueFinding',
                       array('from' => 90, 'to' => 119, 'source' => (isset($source) ? $source->nickname : null)),
                       false,
                       'number'
                   )
               )
               ->addColumn(
                   new Fisma_Report_Column(
                       '120+ Days',
                       true,
                       'Fisma.TableFormat.overdueFinding',
                       array('from' => 120, 'source' => (isset($source) ? $source->nickname : null)),
                       false,
                       'number'
                   )
               )
               ->addColumn(
                   new Fisma_Report_Column(
                       'Total Overdue',
                       true,
                       'Fisma.TableFormat.overdueFinding',
                       array('source' => (isset($source) ? $source->nickname : null)),
                       false,
                       'number'
                   )
               )
               ->addColumn(new Fisma_Report_Column('Average (Days)', true, null, null, false, 'number'))
               ->addColumn(new Fisma_Report_Column('Maximum (Days)', true, null, null, false, 'number'))
               ->setData($reportData);

        $this->_helper->reportContextSwitch()
                      ->setReport($report)
                      ->setToolbarForm($toolbarForm);
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
            $this->_redirect('/finding/report/plugin');
        }

        // Verify that the user has permission to run this report
        $reportConfig = new Zend_Config_Ini(Fisma::getPath('application') . '/config/reports.conf', $reportName);

        if ($this->_me->username != 'root') {
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

        // Load the report SQL query
        $reportScriptFile = Fisma::getPath('application') . "/config/reports/$reportName.sql";
        $reportScriptFileHandle = fopen($reportScriptFile, 'r');

        if (!$reportScriptFileHandle) {
            throw new Fisma_Zend_Exception("Unable to load plug-in report SQL file: $reportScriptFile");
        }

        $reportScript = '';

        while (!feof($reportScriptFileHandle)) {
            $reportScript .= fgets($reportScriptFileHandle);
        }

        $myOrganizations = $this->_me->getOrganizationsByPrivilege('finding', 'read')->toKeyValueArray('id', 'id');

        if (empty($myOrganizations)) {
            $msg = "The report could not be created because this user does not have access to any organizations.";
            $this->view->priorityMessenger($msg, 'warning');
            $this->_redirect('/finding/report/plugin');
            return;
        }

        // The ##ORGANIZATIONS## token should be replaced with the IDs of this users organizations
        $reportScript = str_replace('##ORGANIZATIONS##', implode(',', $myOrganizations), $reportScript);

        $dbh = Doctrine_Manager::connection()->getDbh();
        $rawResults = $dbh->query($reportScript, PDO::FETCH_ASSOC);
        $reportData = array();

        foreach ($rawResults as $rawResult) {
            $reportData[] = $rawResult;
        }

        /*
         * The column names are contained in the keys of each row. If there are no rows, then we can't really do
         * anything.
         */
        if (isset($reportData[0])) {
            $columns = array_keys($reportData[0]);
        } else {
            $msg = "The report could not be created because the report query did not return any data.";
            $this->view->priorityMessenger($msg, 'warning');
            $this->_redirect('/finding/report/plugin');
            return;
        }

        // Assign view outputs
        $report = new Fisma_Report();

        $report->setTitle($reportConfig->title)
               ->setData($reportData);

        // Add each column, and check whether an HTML formatter is required
        if (!empty($reportConfig->htmlcolumns)) {
            $htmlColumns = $reportConfig->htmlcolumns->toArray();
        } else {
            $htmlColumns = array();
        }

        foreach ($columns as $column) {
            $htmlFormat = in_array($column, $htmlColumns) ? 'Fisma.TableFormat.formatHtml' : null;

            $report->addColumn(new Fisma_Report_Column($column, true, $htmlFormat));
        }

        $this->_helper->reportContextSwitch()->setReport($report);
    }
}
