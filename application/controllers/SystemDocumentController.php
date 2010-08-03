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
 * Handles CRUD for system documentation objects.
 *
 * @author     Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Controller
 * @version    $Id$
 */
class SystemDocumentController extends Fisma_Zend_Controller_Action_Security
{
    /**
     * Default pagination parameters
     * 
     * @var array
     */
    protected $_paging = array(
        'startIndex' => 0,
        'count' => 20
    );

    /**
     * Invoked before each Actions
     * 
     * @return void
     */
    public function preDispatch()
    {
        /* Setting the first index of the page/table */
        $this->_paging['startIndex'] = $this->_request->getParam('startIndex', 0);
        parent::preDispatch();
    }

    /**
     * View detail information of the subject model
     * 
     * @return void
     */
    public function viewAction()
    {
        $document = Doctrine::getTable('SystemDocument')->find($this->getRequest()->getParam('id'));
        $organization = $document->System->Organization;
        
        // There are no access control privileges for system documents, access is based on the associated organization
        $this->_acl->requirePrivilegeForObject('read', $organization);

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
     * 
     * @return void
     */
    public function listAction()
    {
        $this->_acl->requirePrivilegeForClass('read', 'Organization');
        
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
     * 
     * @return string The json encoded table data
     */
    public function searchAction()
    {
        $this->_acl->requirePrivilegeForClass('read', 'Organization');

        $sortBy = $this->_request->getParam('sortby', 'o_nickname');
        $order  = $this->_request->getParam('order');
        $keywords  = html_entity_decode($this->_request->getParam('keywords')); 

        // Convert YUI column name to Doctrine column name
        $sortBy{strpos('_', $sortBy) + 1} = '.';
        
        if ($order != 'desc') {
            $order = 'asc'; //ignore other values
        }

        $organizationIds = CurrentUser::getInstance()
                           ->getOrganizationsByPrivilege('organization', 'read')
                           ->toKeyValueArray('id', 'id');
        
        $query  = Doctrine_Query::create()
                  ->select(
                      'd.id, 
                      t.name, 
                      bureau.nickname, 
                      o.nickname, 
                      d.version, 
                      d.description, 
                      u.username, 
                      d.updated_at, 
                      s.id, 
                      o.id'
                  )
                  ->from('SystemDocument d')
                  ->innerJoin('d.User u')
                  ->innerJoin('d.DocumentType t')
                  ->innerJoin('d.System s')
                  ->innerJoin('s.Organization o')
                  ->leftJoin('Organization bureau')
                  ->whereIn('o.id', $organizationIds)
                  ->andWhere('bureau.orgType = ?', 'bureau')
                  ->andWhere('o.lft BETWEEN bureau.lft and bureau.rgt')
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
            $index = new Fisma_Index('SystemDocument');
            $ids = $index->findIds($keywords);
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

    /**
     * Download the specified system document
     * 
     * @return void
     * @throws Fisma_Zend_Exception if requested file doesn`t exist
     */
    public function downloadAction()
    {
        $id = $this->getRequest()->getParam('id');
        $version = $this->getRequest()->getParam('version');
        $document = Doctrine::getTable('SystemDocument')->find($id);
        
        // Documents don't have their own privileges, access control is based on the associated organization
        $this->_acl->requirePrivilegeForObject('read', $document->System->Organization);

        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $document = Doctrine::getTable('SystemDocument')->find($id);

        if (isset($version)) {
            $versionInfo = $document->getAuditLog()->getVersion($document, $version);
            // This is awkward. Doctrine's Versionable returns versions as arrays, not objects.
            // So we have to create a temporary object in order to execute the required logic.
            $document = new SystemDocument();
            $document->merge($versionInfo[0]);
        }

        if (is_null($document)) {
            throw new Fisma_Zend_Exception("Requested file does not exist.");
        }

        // Stream file to user's browser. Unset cache headers to false to avoid IE7/SSL errors.        
        $this->getResponse()
             ->setHeader('Content-Type', $document->mimeType)
             ->setHeader('Cache-Control', null, true)
             ->setHeader('Pragma', null, true)
             ->setHeader('Content-Disposition', "attachment; filename=\"$document->fileName\"");

        $path = $document->getPath();

        $result = readfile($path);
         
        // Notice that 0 is an acceptable result, while FALSE is not, so use === instead of ==.
        if (false === $result) {
            throw new Fisma_Zend_Exception("Unable to read file $path");
        }
    }
}    
