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
 * Sa_ImplementationController
 *
 * @author     Andrew Reeves <andrew.reeves@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    SA
 */
class Sa_ImplementationController extends Fisma_Zend_Controller_Action_Object
{
    /**
     * The main name of the model.
     * 
     * This model is the main subject which the controller operates on.
     * 
     * @var string
     */
    protected $_modelName = 'SaImplementation';

    /**
     * Return array of the collection.
     * 
     * @param Doctrine_Collections $rows The spepcific Doctrine_Collections object
     * @return array The array representation of the specified Doctrine_Collections object
     */
    public function handleCollection($rows)
    {
       $result = $rows->toArray();
       foreach ($rows as $key => $record) {
           $sasca = $record->SaSecurityControlAggregate;
           if ($sasca instanceof SaSecurityControl) {
               $result[$key]['code'] = $sasca->SecurityControl->code;
           } else if ($sasca instanceof SaSecurityControlEnhancement) {
               $result[$key]['code'] = $sasca->SaSecurityControl->SecurityControl->code;
               $result[$key]['enhancement'] = $sasca->SecurityControlEnhancement->number;
           } else {
               throw new Fisma_Zend_Exception('Unknown record type. ' . get_class($sasca));
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
        $offset = $this->_request->getParam('start', 0);

        //filter the sortby to prevent sqlinjection
        $subjectTable = Doctrine::getTable($this->_modelName);
        if (!in_array(strtolower($sortBy), $subjectTable->getColumnNames())) {
            return $this->_helper->json('Invalid "sortBy" parameter');
        }

        $order = strtoupper($order);
        if ($order != 'DESC') {
            $order = 'ASC'; //ignore other values
        }
        
        $sasc = Doctrine_Query::create()
            ->from('SaSecurityControl sasc, sasc.SecurityControl sc')
            ->where('sasc.securityAuthorizationId = ?', $saId)
            ->execute();
        $sasce = Doctrine_Query::create()
            ->from(
                'SaSecurityControlEnhancement sasce, ' .
                'sasce.SecurityControlEnhancement sce, ' .
                'sasce.SaSecurityControl sasc, ' .
                'sasc.SecurityControl sc'
            )
            ->where('sasc.securityAuthorizationId = ?', $saId)
            ->execute();
        $sasca = new Doctrine_Collection('SaSecurityControlAggregate');
        $sasca->merge($sasc);
        $sasca->merge($sasce);
        $query  = Doctrine_Query::create()
            ->from('SaImplementation imp')
            ->leftJoin('imp.SaSecurityControlAggregate sasca')
            ->whereIn('sasca.id', $sasca->toKeyValueArray('id', 'id'))
            ->orderBy("$sortBy $order")
            ->limit($this->_paging['count'])
            ->offset($offset);
 
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

        $totalRecords = $query->count();
        $rows         = $this->executeSearchQuery($query);
        $rows         = $this->handleCollection($rows);
        $tableData['table']['recordsReturned'] = count($rows);
        $tableData['table']['totalRecords'] = $totalRecords;
        $tableData['totalRecords'] = $totalRecords;
        $tableData['table']['records'] = $rows;
        return $this->_helper->json($tableData);
    }
}
