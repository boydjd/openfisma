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
 * Sa_DashboardController 
 * 
 * @uses Fisma
 * @uses _Zend_Controller_Action_Security
 * @package 
 * @copyright (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @author Andrew Reeves <andrew.reeves@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Sa_DashboardController extends Fisma_Zend_Controller_Action_Security
{
    /**
     * The user dashboard displays important system-wide metrics, charts, and graphs
     * 
     * @return void
     */
    public function indexAction()
    {
        $dataTable = new Fisma_Yui_DataTable_Local();
        $dataTable->addColumn(new Fisma_Yui_DataTable_Column('Nickname', true));
        $dataTable->addColumn(new Fisma_Yui_DataTable_Column('Name', true));
        $dataTable->addColumn(new Fisma_Yui_DataTable_Column('Type', true));
        $dataTable->addColumn(new Fisma_Yui_DataTable_Column('SDLC Phase', true));
        $dataTable->addColumn(new Fisma_Yui_DataTable_Column('FIPS 199 Category', true));
        $dataTable->addColumn(new Fisma_Yui_DataTable_Column('Open Findings', true));
        $dataTable->addColumn(new Fisma_Yui_DataTable_Column('ATO Expiration', true));
        $dataTable->addColumn(new Fisma_Yui_DataTable_Column('Annual Due', true));
        $dataTable->addColumn(new Fisma_Yui_DataTable_Column('Id', false, null, null, 'Id', true));
        $dataTable->setData($this->_getTableData());
        $dataTable->addEventListener('cellClickEvent', 'Fisma.SecurityAuthorization.linkToSystem');
        $this->view->dataTable = $dataTable;

        // left-side chart (bar) - Finding Status chart
        $extSrcUrl = '/dashboard/chart-finding/format/json';

        $chartTotalStatus = new Fisma_Chart(380, 275, 'chartTotalStatus', $extSrcUrl);
        $chartTotalStatus
            ->setTitle('Finding Status Distribution')
            ->addWidget(
                'findingType',
                'Threat Level:',
                'combo',
                'Totals',
                array(
                    'Totals',
                    'High, Moderate, and Low',
                    'High',
                    'Moderate',
                    'Low'
                )
            );

        $this->view->chartTotalStatus = $chartTotalStatus->export();
        
        // right-side chart (pie) - Mit Strategy Distribution chart
        $chartTotalType = new Fisma_Chart(380, 275, 'chartTotalType', '/dashboard/total-type/format/json');
        $chartTotalType
            ->setTitle('Mitigation Strategy Distribution');

        $this->view->chartTotalType = $chartTotalType->export();
    }

    /**
     * _getTableData 
     * 
     * @return void
     */
    protected function _getTableData()
    {
        $records = array();

        // these queries should be combined for better performance
        $systems = Doctrine_Query::create()
            ->from('System s, s.Organization o, o.SecurityAuthorizations sas')
            ->execute();
        $openFindingsByOrgQuery = Doctrine_Query::create()
            ->from('Finding f')
            ->where('f.status <> ?', 'CLOSED')
            ->andWhere('f.responsibleOrganizationId = ?');

        foreach ($systems as $system) {
            $atoExpiration = 'N/A';
            $annualDue = 'N/A';
            if ($system->Organization->SecurityAuthorizations->count() > 0) {
                $sa = $system->Organization->SecurityAuthorizations[0];
                if (!empty($sa->atoDate)) {
                    $dt = new Zend_Date($sa->atoDate);
                    $dt->add(1, Zend_Date::YEAR);
                    $annualDue = $dt->toString(Zend_Date::DATES);
                    $dt->add(2, Zend_Date::YEAR);
                    $atoExpiration = $dt->toString(Zend_Date::DATES);
                }
            }
            $records[] = array(
                $system->Organization->nickname,
                
                $system->Organization->name,
                $system->type,
                $system->sdlcPhase,
                $system->fipsCategory,
                $openFindingsByOrgQuery->count($system->Organization->id),
                $atoExpiration,
                $annualDue,
                $system->id
            );
        }

        return $records;
    }
}
