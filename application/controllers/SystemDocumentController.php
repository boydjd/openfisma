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
 * @author    Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id$
 * @package   Controller
 */
 
/**
 * Handles CRUD for system documentation objects.
 *
 * @package   Controller
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 */
class SystemDocumentController extends SecurityController
{
    /**
     * Default pagination parameters
     */
    protected $_paging = array(
        'startIndex' => 0,
        'count' => 20
    );

    public function preDispatch()
    {
        /* Setting the first index of the page/table */
        $this->_paging['startIndex'] = $this->_request->getParam('startIndex', 0);
        parent::preDispatch();
    }

    /**
     * View detail information of the subject model
     */
    public function viewAction()
    {
        $document = Doctrine::getTable('SystemDocument')->find($this->getRequest()->getParam('id'));
        Fisma_Acl::requirePrivilege('Organization', 'read', $document->System->Organization->id);

        $historyQuery = Doctrine_Query::create()
                        ->from('SystemDocumentVersion v')
                        ->where('id = ?', $document->id)
                        ->orderBy('v.version desc');
        $versionHistory = $historyQuery->execute();

        $this->view->document = $document;
        $this->view->versionHistory = $versionHistory;
    }

    /**
     * List the subjects
     */
    public function listAction()
    {
        Fisma_Acl::requirePrivilege('System', 'read');
        $keywords = trim($this->_request->getParam('keywords'));
        $link = empty($keywords) ? '' :'/keywords/'.$keywords;
        $this->view->link     = $link;
        $this->view->pageInfo = $this->_paging;
        $this->view->keywords = $keywords;
        $this->render('list');
    }

    /** 
     * Search the subject 
     *
     * This outputs a json object. Allowing fulltext search from each record enpowered by lucene
     */
    public function searchAction()
    {
        Fisma_Acl::requirePrivilege('System', 'read');
        $sortBy = $this->_request->getParam('sortby', 'id');
        $order  = $this->_request->getParam('order');
        $keywords  = $this->_request->getParam('keywords'); 

        // Convert YUI column name to Doctrine column name
        $sortBy{strpos('_', $sortBy) + 1} = '.';
        
        if ($order != 'desc') {
            $order = 'asc'; //ignore other values
        }

        $query  = Doctrine_Query::create()
                  ->select('d.id, t.name, o.nickname, d.version, d.description, u.username, d.updated_at, s.id, o.id')
                  ->from('SystemDocument d')
                  ->innerJoin('d.User u')
                  ->innerJoin('d.DocumentType t')
                  ->innerJoin('d.System s')
                  ->innerJoin('s.Organization o')
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
            $ids = $this->_helper->searchQuery($keywords, strtolower('SystemDocument'));
            if (!empty($ids)) {
                $ids = implode(',', $ids);
                $query->where('id IN (' . $ids . ')');
            } else {
                //no data
                return $this->_helper->json($tableData);
            }
        }

        $totalRecords = $query->count();
        $rows = $query->execute(array(), Doctrine::HYDRATE_SCALAR);
        $tableData['table']['recordsReturned'] = count($rows);
        $tableData['table']['totalRecords'] = $totalRecords;
        $tableData['table']['records'] = $rows;
        return $this->_helper->json($tableData);
    }
}    
