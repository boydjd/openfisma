<?php
/**
 * Copyright (c) 2013 Endeavor Systems, Inc.
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
 * Handles CRUD for saved searches.
 *
 * @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2013 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Controller
 */
class QueryController extends Fisma_Zend_Controller_Action_Security
{
    /**
     * Set up AJAX actions
     */
    public function init()
    {
        $this->_helper->ajaxContext()
            ->addActionContext('new', 'json')
            ->addActionContext('update', 'json')
            ->addActionContext('delete', 'json')
            ->initContext();

        parent::init();
    }

    /**
     * Save a new query
     */
    public function newAction()
    {
        $request = $this->getRequest();
        $table = Doctrine::getTable('Query');
        if ($request->isPost()) {
            $model = $request->getParam('model');
            $url = $request->getParam('url');
            $name = $request->getParam('name');

            try {
                $searchTable = Doctrine::getTable($model);
                if ($searchTable instanceof Fisma_Search_Searchable) {
                    $query = new Query();
                    $query->model = $model;
                    $query->url = $url;
                    $query->name = $name;
                    $query->creatorId = CurrentUser::getAttribute('id');
                    $query->save();
                    $this->view->query = $query->toArray();
                } else {
                    $this->view->error = 'Invalid model provided.';
                }
            } catch (ErrorException $e) {
                $this->view->error = 'Invalid model provided.';
            } catch (Exception $e) {
                $this->view->error = $e->toString();
            }
        }
    }

    /**
     * Update a query
     */
    public function updateAction()
    {
        $request = $this->getRequest();
        $table = Doctrine::getTable('Query');
        if ($request->isPost()) {
            $id = $request->getParam('id');
            if ($query = $table->find($id)) {
                if ($query->creatorId == CurrentUser::getAttribute('id')) {
                    $url = $request->getParam('url');
                    try {
                        $query->url = $url;
                        $query->save();
                        $this->view->query = $query->toArray();
                    } catch (Doctrine_Exception $e) {
                        $this->view->error = $e->toString();
                    }
                } else {
                    $this->view->error = 'Queries can only be updated by their creators.';
                }
            } else {
                $this->view->error = 'Invalid query ID provided.';
            }
        }
    }

    /**
     * Delete a query
     */
    public function deleteAction()
    {
        $request = $this->getRequest();
        $table = Doctrine::getTable('Query');
        if ($request->isPost()) {
            $id = $request->getParam('id');
            if ($query = $table->find($id)) {
                if ($query->creatorId == CurrentUser::getAttribute('id')) {
                    try {
                        $query->delete();
                    } catch (Doctrine_Exception $e) {
                        $this->view->error = $e->toString();
                    }
                } else {
                    $this->view->error = 'Queries can only be deleted by their creators.';
                }
            } else {
                $this->view->error = 'Invalid query ID provided.';
            }
        }
    }
}
