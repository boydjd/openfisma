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
 * @version   $Id$
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
        'mode' => 'Sliding',
        'append' => false,
        'urlVar' => 'p',
        'path' => '',
        'currentPage' => 1,
        'perPage' => 20
    );

    /**
     * @todo english
     * Initialize this Class
     */
    public function init()
    {
        parent::init();
        $this->_source = new Source();
    }

    /**
     * @todo english
     * Invoked before each Action
     */
    public function preDispatch()
    {
        $req = $this->getRequest();
        $this->_pagingBasePath = $req->getBaseUrl() . '/panel/source/sub/list';
        $this->_paging['currentPage'] = $req->getParam('p', 1);
    }

    /**
     * Returns the standard form for creating, reading, and updating sources.
     *
     * @return Zend_Form
     */
    public function getSourceForm()
    {
        $form = Fisma_Form_Manager::loadForm('source');
        return Fisma_Form_Manager::prepareForm($form);
    }

    /**
     * Render the form for searching the sources.
     */
    public function searchbox()
    {
        $this->_acl->requirePrivilege('admin_sources', 'read');
        
        $qv = trim($this->_request->getParam('qv'));
        if (!empty($qv)) {
            //@todo english  if source index dosen't exist, then create it.
            if (!is_dir(Fisma_Controller_Front::getPath('data') . '/index/source/')) {
                $this->createIndex();
            }
            $ret = $this->_helper->searchQuery($qv, 'source');
            $count = count($ret);
        } else {
            $count = $this->_source->count();
        }

        $this->_paging['totalItems'] = $count;
        $this->_paging['fileName'] = "{$this->_pagingBasePath}/p/%d";
        $pager = & Pager::factory($this->_paging);
        $this->view->assign('qv', $qv);
        $this->view->assign('total', $count);
        $this->view->assign('links', $pager->getLinks());
        $this->render('searchbox');
    }

   /**
    * List the sources according to search criterias.
    */
    public function listAction()
    {
        $this->_acl->requirePrivilege('admin_sources', 'read');
        //Display searchbox template
        $this->searchbox();
        
        $req = $this->getRequest();
        $value = trim($req->getParam('qv'));

        $query = $this->_source->select()->from('sources', '*')
                                         ->order('name ASC')
                                         ->limitPage($this->_paging['currentPage'],
                                                     $this->_paging['perPage']);

        if (!empty($value)) {
            $cache = $this->getHelper('SearchQuery')->getCacheInstance();
            //@todo english  get search results in ids
            $sourceIds = $cache->load($this->_me->id . '_source');
            if (!empty($sourceIds)) {
                $ids = implode(',', $sourceIds);
            } else {
                //@todo english  set ids as a not exist value in database if search results is none.
                $ids = -1;
            }
            $query->where('id IN (' . $ids . ')');
        }
        $sourceList = $this->_source->fetchAll($query)->toArray();
        $this->view->assign('source_list', $sourceList);
        $this->render('list');
    }

    /**
     * Display a single source record with all details.
     */
    public function viewAction()
    {
        $this->_acl->requirePrivilege('admin_sources', 'read');
        //Display searchbox template
        $this->searchbox();
        
        $form = $this->getSourceForm();
        $id = $this->_request->getParam('id');
        $v = $this->_request->getParam('v', 'view');

        $res = $this->_source->find($id)->toArray();
        $source = $res[0];
        if ($v == 'edit') {
            $this->view->assign('viewLink', "/panel/source/sub/view/id/$id");
            $form->setAction("/panel/source/sub/update/id/$id");
        } else {
            // In view mode, disable all of the form controls
            $this->view->assign('editLink', "/panel/source/sub/view/id/$id/v/edit");
            $form->setReadOnly(true);            
        }
        $form->setDefaults($source);
        $this->view->form = $form;
        $this->view->assign('id', $id);
        $this->render($v);
    }

     /**
     * Display the form for creating a new source.
     */
    public function createAction()
    {
        $this->_acl->requirePrivilege('admin_sources', 'create');
        //Display searchbox template
        $this->searchbox();

        // Get the source form
        $form = $this->getSourceForm();
        $form->setAction('/panel/source/sub/save');

        // If there is data in the _POST variable, then use that to
        // pre-populate the form.
        $post = $this->_request->getPost();
        $form->setDefaults($post);

        // Assign view outputs.
        $this->view->form = Fisma_Form_Manager::prepareForm($form);
        $this->render('create');
    }


    /**
     * Saves information for a newly created source.
     */
    public function saveAction()
    {
        $this->_acl->requirePrivilege('admin_sources', 'update');
        
        $form = $this->getSourceForm();
        $post = $this->_request->getPost();
        $formValid = $form->isValid($post);
        if ($form->isValid($post)) {
            $source = $form->getValues();
            unset($source['save']);
            unset($source['reset']);
            $sourceId = $this->_source->insert($source);
            if (! $sourceId) {
                $msg = "Failure in creation";
                $model = self::M_WARNING;
            } else {
                $this->_notification
                     ->add(Notification::SOURCE_CREATED, $this->_me->account, $sourceId);

                //Update source index
                if (is_dir(Fisma_Controller_Front::getPath('data') . '/index/source/')) {
                    $this->_helper->updateIndex('source', $sourceId, $source);
                }

                $msg = "The source is created";
                $model = self::M_NOTICE;
            }
            $this->message($msg, $model);
            $this->_forward('view', null, null, array('id' => $sourceId));
        } else {
            $errorString = Fisma_Form_Manager::getErrors($form);
            // Error message
            $this->message("Unable to create source:<br>$errorString", self::M_WARNING);
            $this->_forward('create');
        }
    }

    /**
     * Delete a singal source
     */
    public function deleteAction()
    {
        $this->_acl->requirePrivilege('admin_sources', 'delete');
        
        $req = $this->getRequest();
        $id = $req->getParam('id');
        $db = $this->_source->getAdapter();
        $qry = $db->select()->from('poams')->where('source_id = ' . $id);
        $result = $db->fetchCol($qry);
        if (!empty($result)) {
            $msg = 'This finding source can not be deleted because it is'
                   .' already associated with one or more POAMS';
            $model = self::M_WARNING;
        } else {
            $res = $this->_source->delete('id = ' . $id);
            if (!$res) {
                $msg = "Failed to delete the finding source";
                $model = self::M_WARNING;
            } else {
                $this->_notification
                     ->add(Notification::SOURCE_DELETED,
                        $this->_me->account, $id);

                $msg = "Finding source deleted successfully";
                $model = self::M_NOTICE;
            }
        }
        $this->message($msg, $model);
        $this->_forward('list');
    }

    /**
     * Updates source information after submitting an edit form.
     */
    public function updateAction ()
    {
        $this->_acl->requirePrivilege('admin_sources', 'update');
        
        $form = $this->getSourceForm();
        $post = $this->_request->getPost();
        $formValid = $form->isValid($post);
        $source = $form->getValues();

        $id = $this->_request->getParam('id');
        $ret = $this->_source->find($id)->current();
        if (!empty($ret)) {
            $query = $ret->name . ' ' . $ret->nickname;
        }

        if ($formValid) {
            unset($source['save']);
            unset($source['reset']);
            $res = $this->_source->update($source, 'id = ' . $id);
            if ($res) {
                $this->_notification
                     ->add(Notification::SOURCE_MODIFIED, $this->_me->account, $id);

                //Update findings index
                if (is_dir(Fisma_Controller_Front::getPath('data') . '/index/finding')) {
                    $index = new Zend_Search_Lucene(Fisma_Controller_Front::getPath('data') . '/index/finding');
                    $hits = $index->find('source:'.$query);
                    foreach ($hits as $hit) {
                        $ids[] = $hit->id;
                    }
                    $data['source'] = $source['name'] . ' ' . $source['nickname'];
                    $this->_helper->updateIndex('finding', $ids, $data);
                }

                //Update Source index
                if (is_dir(Fisma_Controller_Front::getPath('data') . '/index/source')) {
                    $this->_helper->updateIndex('source', $id, $source);
                }

                $msg = "The source is saved";
                $model = self::M_NOTICE;
            } else {
                $msg = "Nothing changes";
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

    /**
     * Create finding sources Lucene Index
     */
    protected function createIndex()
    {
        $index = new Zend_Search_Lucene(Fisma_Controller_Front::getPath('data') . '/index/source', true);
        $list = $this->_source->getList(array('name', 'nickname', 'desc'));
        set_time_limit(0);
        if (!empty($list)) {
            foreach ($list as $id=>$row) {
                $doc = new Zend_Search_Lucene_Document();
                $doc->addField(Zend_Search_Lucene_Field::UnStored('key', md5($id)));
                $doc->addField(Zend_Search_Lucene_Field::UnIndexed('rowId', $id));
                $doc->addField(Zend_Search_Lucene_Field::UnStored('name', $row['name']));
                $doc->addField(Zend_Search_Lucene_Field::UnStored('nickname', $row['nickname']));
                $doc->addField(Zend_Search_Lucene_Field::UnStored('desc', $row['desc']));
                $index->addDocument($doc);
            }
            $index->optimize();
        }
    }
}
