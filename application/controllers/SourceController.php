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

    public function init()
    {
        parent::init();
        $this->_source = new Source();
    }

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
        $form = Form_Manager::loadForm('source');
        return Form_Manager::prepareForm($form);
    }

    /**
     * Render the form for searching the sources.
     */
    public function searchboxAction()
    {
        $this->_acl->requirePrivilege('admin_sources', 'read');
        
        $req = $this->getRequest();
        $fid = $req->getParam('fid');
        $qv = $req->getParam('qv');
        $query = $this->_source->select()->from(array(
            's' => 'sources'
        ), array(
            'count' => 'COUNT(s.id)'
        ))->order('s.name ASC');
        if (!empty($qv)) {
            $query->where("$fid = ?", $qv);
            $this->_pagingBasePath .= '/fid/'.$fid.'/qv/'.$qv;
        }
        $res = $this->_source->fetchRow($query)->toArray();
        $count = $res['count'];
        $this->_paging['totalItems'] = $count;
        $this->_paging['fileName'] = "{$this->_pagingBasePath}/p/%d";
        $pager = & Pager::factory($this->_paging);
        $this->view->assign('fid', $fid);
        $this->view->assign('qv', $qv);
        $this->view->assign('total', $count);
        $this->view->assign('links', $pager->getLinks());
    }

   /**
    * List the sources according to search criterias.
    */
    public function listAction()
    {
        $this->_acl->requirePrivilege('admin_sources', 'read');
        
        $req = $this->getRequest();
        $field = $req->getParam('fid');
        $value = trim($req->getParam('qv'));
        $query = $this->_source->select()->from('sources', '*');
        if (!empty($value)) {
            $query->where("$field = ?", $value);
        }
        $query->order('name ASC')->limitPage($this->_paging['currentPage'],
            $this->_paging['perPage']);
        $sourceList = $this->_source->fetchAll($query)->toArray();
        $this->view->assign('source_list', $sourceList);
    }

    /**
     * Display a single source record with all details.
     */
    public function viewAction()
    {
        $this->_acl->requirePrivilege('admin_sources', 'read');
        
        $form = $this->getSourceForm();
        $id = $this->_request->getParam('id');
        $v = $this->_request->getParam('v');

        $res = $this->_source->find($id)->toArray();
        $source = $res[0];
        if ($v == 'edit') {
            $this->view->assign('viewLink', "/panel/source/sub/view/id/$id");
            $form->setAction("/panel/source/sub/update/id/$id");
        } else {
            // In view mode, disable all of the form controls
            $this->view->assign('editLink', "/panel/source/sub/view/id/$id/v/edit");
            foreach ($form->getElements() as $element) {
                $element->setAttrib('disabled', 'disabled');
            }
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

        // Get the source form
        $form = $this->getSourceForm();
        $form->setAction('/panel/source/sub/save');

        // If there is data in the _POST variable, then use that to
        // pre-populate the form.
        $post = $this->_request->getPost();
        $form->setDefaults($post);

        // Assign view outputs.
        $this->view->form = Form_Manager::prepareForm($form);
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
            unset($source['submit']);
            unset($source['reset']);
            $sourceId = $this->_source->insert($source);
            if (! $sourceId) {
                $msg = "Failure in creation";
                $model = self::M_WARNING;
            } else {
                $this->_notification
                     ->add(Notification::SOURCE_CREATED, $this->_me->account, $sourceId);
                $msg = "The source is created";
                $model = self::M_NOTICE;
            }
            $this->message($msg, $model);
            $this->_forward('view', null, null, array('id' => $sourceId));
        } else {
            $errorString = Form_Manager::getErrors($form);
            // Error message
            $this->message("Unable to create source:<br>$errorString", self::M_WARNING);
            $this->_forward('create');
        }
    }

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
            unset($source['submit']);
            unset($source['reset']);
            $res = $this->_source->update($source, 'id = ' . $id);
            if ($res) {
                $this->_notification
                     ->add(Notification::SOURCE_MODIFIED, $this->_me->account, $id);

                //Update findings index
                if (is_dir(APPLICATION_ROOT . '/data/index/finding')) {
                    $index = new Zend_Search_Lucene(APPLICATION_ROOT . '/data/index/finding');
                    $hits = $index->find('source:'.$query);
                    foreach ($hits as $hit) {
                        $ids[] = $hit->id;
                    }
                    $data['source'] = $source['name'] . ' ' . $source['nickname'];
                    Config_Fisma::updateIndex('finding', $ids, $data);
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
            $errorString = Form_Manager::getErrors($form);
            // Error message
            $this->message("Unable to update source<br>$errorString", self::M_WARNING);
            // On error, redirect back to the edit action.
            $this->_forward('view', null, null, array('id' => $id, 'v' => 'edit'));
        }
    }

    /**
     * Create finding sources Lucene Index
     *
     * @return Object Zend_Search_Lucene
     */
    protected function getIndex()
    {
        if (is_dir(APPLICATION_ROOT . '/data/index/sources')) {
            $index = new Zend_Search_Lucene(APPLICATION_ROOT . '/data/index/sources');
        } else {
            $index = new Zend_Search_Lucene(APPLICATION_ROOT . '/data/index/sources', true);
            $list = $this->_source->getList(array('name', 'nickname'));
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
        return $index;
    }
}
