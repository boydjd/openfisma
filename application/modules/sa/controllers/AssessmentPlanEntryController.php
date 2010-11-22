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
 * Sa_AssessmentPlanEntryController
 *
 * @author     Andrew Reeves <andrew.reeves@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    SA
 */
class Sa_AssessmentPlanEntryController extends Fisma_Zend_Controller_Action_Object
{
    /**
     * The main name of the model.
     * 
     * This model is the main subject which the controller operates on.
     * 
     * @var string
     */
    protected $_modelName = 'AssessmentPlanEntry';

    /**
     * Return array of the collection.
     * 
     * @param Doctrine_Collections $rows The spepcific Doctrine_Collections object
     * @return array The array representation of the specified Doctrine_Collections object
     */
    public function handleCollection($rows)
    {
        $result = $rows->toArray();
        foreach ($rows as $k => $v) {
            if ($v->SaSecurityControl) {
                $result[$k]['code'] = $v->SaSecurityControl->SecurityControl->code;
            } else if ($v->SaSecurityControlEnhancement) {
                $result[$k]['code'] = $v->SaSecurityControlEnhancement->SaSecurityControl->SecurityControl->code;
                $result[$k]['enhancement'] = $v->SaSecurityControlEnhancement->SecurityControlEnhancement->number;
            }
        }
        return $result;
    }

    /**
     * Custom search action to allow filtering by SA
     *
     * @return void
     */
    public function searchAction()
    {
        $this->_acl->requirePrivilegeForClass('read', $this->getAclResourceName());
        $sortBy = $this->_request->getParam('sortby', 'id');
        $order  = $this->_request->getParam('order');
        $keywords  = html_entity_decode($this->_request->getParam('keywords')); 
        $saId = $this->_request->getParam('said');

        //filter the sortby to prevent sqlinjection
        $subjectTable = Doctrine::getTable($this->_modelName);
        if (!in_array(strtolower($sortBy), $subjectTable->getColumnNames())) {
            return $this->_helper->json('Invalid "sortBy" parameter');
        }

        $order = strtoupper($order);
        if ($order != 'DESC') {
            $order = 'ASC'; //ignore other values
        }
        
        $query  = Doctrine_Query::create()
                    ->from('AssessmentPlanEntry ape')
                    ->leftJoin('ape.SaSecurityControl sasc')
                    ->leftJoin('sasc.SecurityControl sc')
                    ->leftJoin('ape.SaSecurityControlEnhancement saSce')
                    ->leftJoin('saSce.SaSecurityControl eSasc')
                    ->leftJoin('eSasc.SecurityControl eSc')
                    ->leftJoin('saSce.SecurityControlEnhancement eSce')
                    ->orderBy("$sortBy $order")
                    ->limit($this->_paging['count'])
                    ->offset($this->_paging['startIndex']);
 
        //initialize the data rows
        $tableData    = array('table' => array(
                            'recordsReturned' => 0,
                            'totalRecords'    => 0,
                            'startIndex'      => $this->_paging['startIndex'],
                            'sort'            => $sortBy,
                            'dir'             => $order,
                            'pageSize'        => $this->_paging['count'],
                            'records'         => array()
                        ));
        if (!empty($keywords)) {
            // lucene search 
            $index = new Fisma_Index($this->_modelName);
            $ids = $index->findIds($keywords);
            if (!empty($ids)) {
                $query->whereIn('id', $ids);
            } else {
                //no data
                return $this->_helper->json($tableData);
            }
        }

        $query->andWhere('sasc.securityAuthorizationId = ? OR eSasc.securityAuthorizationId = ?', array($saId, $saId));

        $totalRecords = $query->count();
        $rows         = $this->executeSearchQuery($query);
        $rows         = $this->handleCollection($rows);
        $tableData['table']['recordsReturned'] = count($rows);
        $tableData['table']['totalRecords'] = $totalRecords;
        $tableData['table']['records'] = $rows;
        return $this->_helper->json($tableData);
    }
}
