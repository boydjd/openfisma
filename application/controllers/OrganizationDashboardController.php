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
 * Controller for the dashboard of they system inventory module
 * 
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Controllers
 * @version    $Id$
 */
class OrganizationDashboardController extends SecurityController
{
    /**
     * The number of months for which an Authority To Operate (ATO) is valid and current. 
     * 
     * After this period elapses, the ATO is considered expired.
     */
    const ATO_PERIOD_MONTHS = 36;

    /**
     * The number of months for which a Self-Assessment is valid and current. 
     * 
     * After this period elapses, the Self-Assessment is considered expired.
     */
    const SELF_ASSESSMENT_PERIOD_MONTHS = 12;

    /**
     * The number months for which a contingency plan test is valid and current.
     * 
     * After this period elapses, the Self-Assessment is considered expired.
     */    
    const SELF_CPLAN_PERIOD_MONTHS = 12;

    /**
     * The threshold (as a percentage) for which a metric becomes green.
     * 
     * Anything below this is yellow or red.
     */
    const METRIC_GREEN_THRESHOLD = 95.0;

    /**
     * The threshold (as a percentage) for which a metric becomes yellow. 
     * 
     * Anything below this is red.
     */
    const METRIC_YELLOW_THRESHOLD = 80.0;
    
    /**
     * Verify that this module is enabled
     */
    public function preDispatch()
    {
        Fisma_Zend_Acl::requireArea('system_inventory');

        if (!$this->_hasParam('format')) {
            $this->_helper->layout->setLayout('layout');

            $this->_helper->actionStack('header', 'panel');
        }
    }

    /**
     * Displays summary statistics and charts
     */
    public function indexAction()
    {
        $metricsQuery = Doctrine_Query::create()
                       ->from('System s')
                       ->select('COUNT(*) AS total_systems')
                       ->addSelect(
                           'ROUND(AVG(IF(DATE_ADD(s.securityAuthorizationDt, INTERVAL ' 
                           . self::ATO_PERIOD_MONTHS 
                           . ' MONTH) > NOW(), 1, 0)) * 100, 1) AS current_atos'
                       )
                       ->addSelect(
                           'ROUND(AVG(IF(DATE_ADD(s.controlAssessmentDt, INTERVAL ' 
                           . self::SELF_ASSESSMENT_PERIOD_MONTHS 
                           . ' MONTH) > NOW(), 1, 0)) * 100, 1) AS current_self_assessment'
                       )
                       ->addSelect(
                           'ROUND(AVG(IF(DATE_ADD(s.contingencyPlanTestDt, INTERVAL ' 
                           . self::SELF_CPLAN_PERIOD_MONTHS 
                           . ' MONTH) > NOW(), 1, 0)) * 100, 1) AS contingency_plan_tests'
                       )
                       ->addSelect(
                           'ROUND(SUM(IF(s.hasPii = \'YES\' AND s.piaUrl IS NOT NULL, 1, 0)) / '
                           . 'SUM(IF(s.hasPii = \'YES\' OR s.hasPii IS NULL, 1, 0)) * 100, 1) AS current_pias'
                       )
                       ->setHydrationMode(Doctrine::HYDRATE_ARRAY);
        
        // This query returns one row because it uses aggregate functions and no GROUP BY clause
        $metrics = $metricsQuery->execute();
        $metrics = $metrics[0];
                
        // Add metadata for each metric which is returned in the previous query
        $metrics['total_systems'] = array(
            'title' => 'Total Number of Systems', 
            'value' => $metrics['total_systems'],
            'color' => '',
            'suffix' => ''
        );

        $metrics['current_atos'] = array(
            'title' => 'Current ATO', 
            'value' => $metrics['current_atos'],
            'color' => $this->_getColorForPercentage($metrics['current_atos']),
            'suffix' => '%'
        );
        
        $metrics['current_self_assessment'] = array(
            'title' => 'Current 800-53 Self-Assessment', 
            'value' => $metrics['current_self_assessment'],
            'color' => $this->_getColorForPercentage($metrics['current_self_assessment']),
            'suffix' => '%'
        );

        $metrics['contingency_plan_tests'] = array(
            'title' => 'Contingency Plans Tested', 
            'value' => $metrics['contingency_plan_tests'],
            'color' => $this->_getColorForPercentage($metrics['contingency_plan_tests']),
            'suffix' => '%'
        );

        $metrics['current_pias'] = array(
            'title' => 'Completed PIA', 
            'value' => $metrics['current_pias'],
            'color' => $this->_getColorForPercentage($metrics['current_pias']),
            'suffix' => '%'
        );

        $this->view->metrics = $metrics;
    }
    
    /**
     * Returns a color name based on the specified threshold
     * 
     * @param float $percentage
     */
    private function _getColorForPercentage($percentage)
    {
        if ($percentage >= self::METRIC_GREEN_THRESHOLD) {
            return 'dashboardGreen';
        } elseif ($percentage >= self::METRIC_YELLOW_THRESHOLD) {
            return 'dashboardYellow';
        } else {
            return 'dashboardRed';
        }
    }
}
