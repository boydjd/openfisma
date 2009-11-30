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
 * <http://www.gnu.org/licenses/>.
 */

/**
 * displaying account logs.
 *
 * @author     Ryan Yang <ryan@users.sourceforge.net>
 * @copyright  (c) Endeavor Systems, Inc. 2009 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Controller
 * @version    $Id$
 */
class LogController extends BaseController
{
    protected $_modelName = 'AccountLog';
    
    /**
     * handle the records from searchAction if necessary
     *
     * @param Doctrine_Collections $logs
     * @return array $array
     */
    public function handleCollection($logs)
    {
        $array = array();
        foreach ($logs as $key=>$log) {
            $array[$key] = $log->toArray();
            $array[$key]['username'] = $log->User->nameFirst . ' ' . $log->User->nameLast;
        }
        return $array;
    }
    
    /** 
     * Search the subject 
     *
     * This outputs a json object. Allowing fulltext search from each record enpowered by lucene
     */
    public function searchAction()
    {
        Fisma_Acl::requirePrivilege($this->_modelName, 'read');
        $sortBy = $this->_request->getParam('sortby', 'id');
        $order  = $this->_request->getParam('order');

        //filter the sortby to prevent sqlinjection
        $subjectTable = Doctrine::getTable($this->_modelName);
        if (!in_array(strtolower($sortBy), $subjectTable->getColumnNames()) 
            && strtolower($sortBy) != 'username') {
            return $this->_helper->json('Invalid "sortBy" parameter');
        } elseif (strtolower($sortBy) == 'username') {
            $sortBy = 'u.username';
        } else {
            $sortBy = 'al.' . $sortBy;
        }

        $order = strtoupper($order);
        if ($order != 'DESC') {
            $order = 'ASC'; //ignore other values
        }
        
        $query  = Doctrine_Query::create()
                    ->select('*')
                    ->from('AccountLog al')
                    ->leftJoin('al.User u')
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
        
        $totalRecords = $query->count();
        $rows         = $query->execute();
        $rows         = $this->handleCollection($rows);
        $tableData['table']['recordsReturned'] = count($rows);
        $tableData['table']['totalRecords'] = $totalRecords;
        $tableData['table']['records'] = $rows;
        return $this->_helper->json($tableData);
    }
}
