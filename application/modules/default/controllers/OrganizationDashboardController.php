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
 */
class OrganizationDashboardController extends Fisma_Zend_Controller_Action_Security
{
    /**
     * The threshold (as a percentage) for which a metric becomes green.
     * 
     * Anything below this is yellow or red.
     */
    const METRIC_GREEN_THRESHOLD = 90.0;

    /**
     * The threshold (as a percentage) for which a metric becomes yellow. 
     * 
     * Anything below this is red.
     */
    const METRIC_YELLOW_THRESHOLD = 70.0;
    
    /**
     * Verify that this module is enabled
     */
    public function preDispatch()
    {
        parent::preDispatch();

        $this->_acl->requireArea('system_inventory');
    }

    /**
     * Displays summary statistics and charts
     */
    public function indexAction()
    {
        $userOrganizations = $this->_me->getOrganizationsByPrivilege('finding', 'read')->toKeyValueArray('id', 'id');

        if (!empty($userOrganizations)) {
            $metricsQuery = Doctrine_Query::create()
                           ->from('Organization o')
                           ->innerJoin('o.System s')
                           ->addSelect('COUNT(s.id) AS total_systems')
                           ->addSelect(
                               'ROUND(AVG(IF(DATE_ADD(s.securityAuthorizationDt, INTERVAL ' 
                               . System::ATO_PERIOD_MONTHS 
                               . ' MONTH) > NOW(), 1, 0)) * 100, 1) AS current_atos'
                           )
                           ->addSelect(
                               'ROUND(AVG(IF(DATE_ADD(s.controlAssessmentDt, INTERVAL ' 
                               . System::SELF_ASSESSMENT_PERIOD_MONTHS 
                               . ' MONTH) > NOW(), 1, 0)) * 100, 1) AS current_self_assessment'
                           )
                           ->addSelect(
                               'ROUND(AVG(IF(DATE_ADD(s.contingencyPlanTestDt, INTERVAL ' 
                               . System::SELF_CPLAN_PERIOD_MONTHS 
                               . ' MONTH) > NOW(), 1, 0)) * 100, 1) AS contingency_plan_tests'
                           )
                           ->addSelect(
                               "ROUND(SUM(IF(s.piaRequired = 'YES' AND s.piaUrl IS NOT NULL AND s.piaUrl <> '', 1, 0)) "
                               . "/ SUM(IF(s.piaRequired = 'YES' OR s.piaRequired IS NULL, 1, 0)) * 100, 1) "
                               . "AS current_pias"
                           )
                           ->whereIn('o.id', $userOrganizations)
                           ->andWhere('s.sdlcPhase <> ?', 'disposal')
                           ->setHydrationMode(Doctrine::HYDRATE_SCALAR);
            
            // This query returns one row because it uses aggregate functions and no GROUP BY clause
            $metrics = $metricsQuery->execute();
            $metrics = $metrics[0];
                    
            // Add metadata for each metric which is returned in the previous query
            $metrics['s_total_systems'] = array(
                'title' => 'Total Number of Systems', 
                'value' => $metrics['s_total_systems'],
                'color' => '',
                'suffix' => ''
            );

            $metrics['s_current_atos'] = array(
                'title' => 'Current ATO', 
                'value' => $metrics['s_current_atos'],
                'color' => $this->_getColorForPercentage($metrics['s_current_atos']),
                'suffix' => '%'
            );
            
            $metrics['s_current_self_assessment'] = array(
                'title' => 'Current 800-53 Self-Assessment', 
                'value' => $metrics['s_current_self_assessment'],
                'color' => $this->_getColorForPercentage($metrics['s_current_self_assessment']),
                'suffix' => '%'
            );

            $metrics['s_contingency_plan_tests'] = array(
                'title' => 'Contingency Plans Tested', 
                'value' => $metrics['s_contingency_plan_tests'],
                'color' => $this->_getColorForPercentage($metrics['s_contingency_plan_tests']),
                'suffix' => '%'
            );

            $metrics['s_current_pias'] = array(
                'title' => 'Completed PIA', 
                'value' => $metrics['s_current_pias'],
                'color' => $this->_getColorForPercentage($metrics['s_current_pias']),
                'suffix' => '%'
            );

            $this->view->metrics = $metrics;
        } else {
            $this->view->metrics = array();
        }
        
        // Create dashboard charts
        $fipsCategoryChart = new Fisma_Chart(300, 300, 'fipsCategoryChart');
        $fipsCategoryChart
            ->setExternalSource('/organization-chart/fips-category/format/json')
            ->setTitle('FIPS-199 Categorizations');
            
        $this->view->fipsCategoryChart = $fipsCategoryChart->export();
        
        $agencyContractorChart = new Fisma_Chart(300, 300, 'agencyContractorChart');
        $agencyContractorChart
            ->setExternalSource('/organization-chart/agency-contractor/format/json')
            ->setTitle('Agency & Contractor Systems');
            
        $this->view->agencyContractorChart = $agencyContractorChart->export();
        
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
