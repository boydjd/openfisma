<?php
/**
 * Copyright (c) 2010 Endeavor Systems, Inc.
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
 * Generate charts for the security control catalog
 * 
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Controllers
 * @version    $Id$
 */
class SecurityControlChartController extends Fisma_Zend_Controller_Action_Security
{
    /**
     * Set contexts for this controller's actions
     */
    public function init()
    {
        parent::init();
        
        $this->_helper->fismaContextSwitch()
                      ->setActionContext('control-deficiencies', 'json')
                      ->initContext();
    }

    /**
     * Renders a bar chart that shows the number of open findings against each security control code.
     */
    public function controlDeficienciesAction()
    {
        $displayBy = urldecode($this->getRequest()->getParam('displaySecurityBy'));

        $rtnChart = new Fisma_Chart();
        $rtnChart
            ->setColors(array('#3366FF'))
            ->setChartType('bar')
            ->setConcatColumnLabels(false)
            ->setAxisLabelY('Number of Findings');
        
        // Dont query if there are no organizations this user can see
        $visibleOrgs = FindingTable::getOrganizationIds();
        if (empty($visibleOrgs)) {
            $this->view->chart = $rtnChart->export('array');
            return;
        }
        
        $deficienciesQuery = Doctrine_Query::create()
            ->select('COUNT(*) AS count, sc.code, SUBSTRING_INDEX(sc.code, "-", 1) fam')
            ->from('SecurityControl sc')
            ->innerJoin('sc.Findings f')
            ->innerJoin('f.ResponsibleOrganization o')
            ->where('f.status <> ?', 'CLOSED')
            ->whereIn('o.id', FindingTable::getOrganizationIds())
            ->setHydrationMode(Doctrine::HYDRATE_SCALAR);

        // What is the "Display By" drop-down box set to?
        if ($displayBy === 'Family Summary') {
            // It is set to Family Summary (default)
            $deficienciesQuery
                ->groupBy('fam')
                ->orderBy('fam');
        } else {
            // It is set to a specific family, the $displayBy value should be something like "Family: AC"
            $targetFamily = explode(': ', $displayBy);
            $targetFamily = $targetFamily[1];
            $deficienciesQuery
                ->andWhere('SUBSTRING_INDEX(sc.code, "-", 1)=?', $targetFamily)
                ->groupBy('sc.code')
                ->orderBy('sc.code');
        }

        $deficiencyQueryResult = $deficienciesQuery->execute();

        foreach ($deficiencyQueryResult as $thisElement) {
        
            if ($displayBy === 'Family Summary') {
                $columnLabel = $thisElement['sc_fam'];
            } else {
                $columnLabel = $thisElement['sc_code'];
            }
        
            $rtnChart->addColumn(
                $columnLabel,
                $thisElement['sc_count']
            );
            
        }

        // Insert quotes around VALUE in securityControl/textContains/VALUE when using Solr
        if (Fisma::configuration()->getConfig('search_backend') === 'solr') {
            $searchVar = $this->view->escape('"#ColumnLabel#"', 'url');
        } else {
            $searchVar = '#ColumnLabel#';
        }

        // Pass a string instead of an array to Fisma_Chart to set all columns to link with this URL-rule
        if ($displayBy === 'Family Summary') {
            $rtnChart->setLinks(
                '/finding/remediation/list?q=' .
                '/denormalizedStatus/textDoesNotContain/CLOSED' .
                '/securityControl/textContains/'. $searchVar
            );
        } else {
            $rtnChart->setLinks(
                '/finding/remediation/list?q=' .
                '/denormalizedStatus/textDoesNotContain/CLOSED' .
                '/securityControl/textExactMatch/#ColumnLabel#'
            );
        }
            
        // The context switch will convert this array to a JSON responce
        $this->view->chart = $rtnChart->export('array');
        
    }
}
