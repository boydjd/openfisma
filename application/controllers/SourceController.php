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
 * @author    Ryan Yang <ryan@users.sourceforge.net>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id:$
 * @package   Controller
 */
 
/**
 * Handles CRUD for finding source objects.
 *
 * @package   Controller
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 */
class SourceController extends SecurityController
{
    private $_paging = array(
        'startIndex' => 0,
        'count' => 20
    );
    
    /**
     * Invoked before each Action
     */
    public function preDispatch()
    {
        $this->_paging['startIndex'] = $this->_request->getParam('startIndex', 0);
    }

    /**
     * Returns the standard form for creating, reading, and updating sources.
     *
     * @return Zend_Form
     */
    private function _getSourceForm()
    {
        $form = Fisma_Form_Manager::loadForm('source');
        return Fisma_Form_Manager::prepareForm($form);
    }

    /**
     *  render the searching boxes and keep the searching criteria
     */
    public function searchbox()
    {
        Fisma_Acl::requirePrivilege('finding_sources', 'read');
        $value = trim($this->_request->getParam('keywords'));
        $this->view->assign('keywords', $value);
        $this->render('searchbox');
    }

    /**
     * show the list page, not for data
     */
    public function listAction()
    {
        Fisma_Acl::requirePrivilege('finding_sources', 'read');

        $value = trim($this->_request->getParam('keywords'));
        $this->searchbox();
        
        $link = '';
        empty($value) ? $link .='' : $link .= '/keywords/' . $value;
        $this->view->assign('pageInfo', $this->_paging);
        $this->view->assign('keywords', $value);
        $this->render('list');
    }
    
    /**
     * list the sources from the search, 
     * if search none, it list all sources
     *
     */
    public function searchAction()
    {
        Fisma_Acl::requirePrivilege('finding_sources', 'read');
        $this->_helper->layout->setLayout('ajax');
        $this->_helper->viewRenderer->setNoRender();
        
        $sortBy = $this->_request->getParam('sortby', 'name');
        $order  = $this->_request->getParam('order', 'ASC');
        $value  = $this->_request->getParam('value'); 
        
        $query  = Doctrine_Query::create()
                    ->select('*')->from('Source')
                    ->orderBy("$sortBy $order")
                    ->limit($this->_paging['count'])
                    ->offset($this->_paging['startIndex']);

        if (!empty($value)) {
            $this->_helper->searchQuery($value, 'source');
            $cache = $this->getHelper('SearchQuery')->getCacheInstance();
            // get search results in ids
            $sourceIds = $cache->load($this->_me->id . '_source');
            if (!empty($sourceIds)) {
                $sourceIds = implode(',', $sourceIds);
            } else {
                // set ids as a not exist value in database if search results is none.
                $sourceIds = -1;
            }
            $query->where('id IN (' . $sourceIds . ')');
        }
        
        $totalRecords = $query->count();
        $sources      = $query->execute();
        $tableData    = array('table' => array(
                            'recordsReturned' => count($sources->toArray()),
                            'totalRecords'    => $totalRecords,
                            'startIndex'      => $this->_paging['startIndex'],
                            'sort'            => $sortBy,
                            'dir'             => $order,
                            'pageSize'        => $this->_paging['count'],
                            'records'         => $sources->toArray()
                        ));
        echo json_encode($tableData);
    }
    
    /**
     * Display a single source record with all details.
     */
    public function viewAction()
    {
        Fisma_Acl::requirePrivilege('finding_sources', 'read');

        $this->searchbox();
        
        $form   = $this->_getSourceForm();
        $id     = $this->_request->getParam('id');
        $v      = $this->_request->getParam('v', 'view');
        $source = Doctrine::getTable('Source')->find($id);
        
        if ($v == 'edit') {
            $this->view->assign('viewLink', "/panel/source/sub/view/id/$id");
            $form->setAction("/panel/source/sub/update/id/$id");
        } else {
            // In view mode, disable all of the form controls
            $this->view->assign('editLink', "/panel/source/sub/view/id/$id/v/edit");
            $form->setReadOnly(true);            
        }
        $this->view->assign('deleteLink', "/panel/source/sub/delete/id/$id");
        $form->setDefaults($source->toArray());
        $this->view->form = $form;
        $this->view->id   = $id;
        $this->render($v);
    }

     /**
     * Display the form for creating a new source.
     */
    public function createAction()
    {
        Fisma_Acl::requirePrivilege('finding_sources', 'create');

        $this->searchbox();

        // Get the source form
        $form = $this->_getSourceForm();
        $form->setAction('/panel/source/sub/save');

        // If there is data in the _POST variable, then use that to
        // pre-populate the form.
        $post = $this->_request->getPost();
        $form->setDefaults($post);

        // Assign view outputs.
        $this->view->form = $form;
        $this->render('create');
    }


    /**
     * Saves information for a newly created source.
     */
    public function saveAction()
    {
        Fisma_Acl::requirePrivilege('finding_sources', 'update');
        
        $form = $this->_getSourceForm();
        $post = $this->_request->getPost();
        
        if ($form->isValid($post)) {
            $source = new Source();
            $source->merge($form->getValues());

            if (!$source->trySave()) {
                $msg   = "Failure in creation";
                $model = self::M_WARNING;
            } else {
                $this->_helper->addNotification(Notification::SOURCE_CREATED, $this->_me->username, $source->id);
                //Create a source index
                if (is_dir(Fisma_Controller_Front::getPath('data') . '/index/source/')) {
                    $this->_helper->updateIndex('source', $source->id, $source->toArray());
                }
                $msg   = "The source is created";
                $model = self::M_NOTICE;
            }
            $this->message($msg, $model);
            $this->_forward('view', null, null, array('id' => $source->id));
        } else {
            $errorString = Fisma_Form_Manager::getErrors($form);
            // Error message
            $this->message("Unable to create source:<br>$errorString", self::M_WARNING);
            $this->_forward('create');
        }
    }

    /**
     * Delete a source
     */
    public function deleteAction()
    {
        Fisma_Acl::requirePrivilege('finding_sources', 'delete');
        
        $id = $this->_request->getParam('id', 0);
        $source = Doctrine::getTable('Source')->find($id);
        if (!$source) {
            //@todo english
            $msg   = 'Invalid source';
            $model = self::M_WARNING;
        } else {
            if (!$source->delete()) {
                //@todo english
                $msg = "Failed to delete the source";
                $model = self::M_WARNING;
            } else {
                //Delete source index
                if (is_dir(Fisma_Controller_Front::getPath('data') . '/index/source/')) {
                    $this->_helper->deleteIndex('source', $source->id);
                }

                $this->_helper->addNotification(Notification::SOURCE_DELETED, $this->_me->username, $source->id);
                // @todo english
                $msg   = "source deleted successfully";
                $model = self::M_NOTICE;
            }
        }
        $this->message($msg, $model);
        $this->_forward('list');
    }

    /**
     * Updates source information after submitting an edit form.
     */
    public function updateAction()
    {
        Fisma_Acl::requirePrivilege('finding_sources', 'update');
        
        $form = $this->_getSourceForm();
        $id   = $this->_request->getParam('id');
        $post = $this->_request->getPost();
        $formValid = $form->isValid($post);
        
        if ($form->isValid($post)) {
            $source = new Source();
            $source = $source->getTable()->find($id);
            $source->merge($form->getValues());
            if ($source->trySave()) {
                $this->_helper->addNotification(Notification::SOURCE_MODIFIED, $this->_me->username, $source->id);
                //Update source index
                if (is_dir(Fisma_Controller_Front::getPath('data') . '/index/source/')) {
                    $this->_helper->updateIndex('source', $source->id, $source->toArray());
                }
                //@todo english
                $msg   = "The source is saved";
                $model = self::M_NOTICE;
            } else {
                //@todo english
                $msg   = "Nothing changes";
                $model = self::M_WARNING;
            }
            $this->message($msg, $model);
            $this->_forward('view', null, null, array('id' => $id));
        } else {
            $errorString = Fisma_Form_Manager::getErrors($form);
            // Error message
            $this->message("Unable to update source<br>$errorString", self::M_WARNING);
            // On error, redirect back to the edit action.
            $this->_forward('view', null, null, array('id' => $id, 'v' => 'edit'));
        }
    }
}
