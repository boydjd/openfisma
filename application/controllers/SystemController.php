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
 * Handles CRUD for "system" objects.
 *
 * @package   Controller
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 */
class SystemController extends SecurityController
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
        $req = $this->getRequest();
        $this->_paging['startIndex'] = $req->getParam('startIndex', 0);
    }

    /**
     * Returns the standard form for creating, reading, and updating systems.
     *
     * @return Zend_Form
     */
    private function _getSystemForm()
    {
        $form = Fisma_Form_Manager::loadForm('system');
        $organizationTreeObject = Doctrine::getTable('Organization')->getTree();
        $q = Doctrine_Query::create()
                ->select('o.*')
                ->from('Organization o')
                ->where('o.orgType != ?', 'system');
        $organizationTreeObject->setBaseQuery($q);
        $organizationTree = $organizationTreeObject->fetchTree();
        if (!empty($organizationTree)) {
            foreach ($organizationTree as $organization) {
                $value = $organization['id'];
                $text = str_repeat('--', $organization['level']) . $organization['name'];
                $form->getElement('organization_id')->addMultiOptions(array($value => $text));
            }
        } else {
            $form->getElement('organization_id')->addMultiOptions(array(0 => 'NONE'));
        }
        
        $systemTable = Doctrine::getTable('System');
        
        $array = $systemTable->getEnumValues('confidentiality');
        $form->getElement('confidentiality')->addMultiOptions(array_combine($array, $array));
        
        $array = $systemTable->getEnumValues('integrity');
        $form->getElement('integrity')->addMultiOptions(array_combine($array, $array));
        
        $array = $systemTable->getEnumValues('availability');
        $form->getElement('availability')->addMultiOptions(array_combine($array, $array));
        
        $type = $systemTable->getEnumValues('type');
        $form->getElement('type')->addMultiOptions(array_combine($type, $type));
        
        return Fisma_Form_Manager::prepareForm($form);
    }

    /**
     * show the list page, not for data
     */     
    public function listAction()
    {
        Fisma_Acl::requirePrivilege('systems', 'read');
        
        $this->searchbox();
        
        $visibility = trim($this->_request->getParam('sh'));
        $value = trim($this->_request->getParam('keywords'));
        
        $link = '';
        empty($value) ? $link .='' : $link .= '/keywords/' . $value;
        empty($visibility) ? $link .='' : $link .= '/sh/' . $visibility;
        $this->view->assign('link', $link);
        $this->view->assign('pageInfo', $this->_paging);
        $this->render('list');
    }
    
    /**
     * list the systems from the search, 
     * if search none, it list all systems
     *
     */
    public function searchAction()
    {
        Fisma_Acl::requirePrivilege('systems', 'read');
        $this->_helper->layout->setLayout('ajax');
        $this->_helper->viewRenderer->setNoRender();
        
        $visibility = trim($this->_request->getParam('sh'));
        $value = trim($this->_request->getParam('keywords'));
        
        $sortBy = $this->_request->getParam('sortby', 'name');
        if (Doctrine::getTable('Organization')->getColumnDefinition($sortBy)) {
            $sortBy = 'o.' . $sortBy;
        } elseif (Doctrine::getTable('System')->getColumnDefinition($sortBy)) {
            $sortBy = 's.' . $sortBy;
        } else {
            /** 
             * @todo english 
             */
            throw new Fisma_Exception('invalid page');
        }
        
        $order = $this->_request->getParam('order', 'ASC');
        if (!in_array(strtolower($order), array('asc', 'desc'))) {
            /** 
             * @todo english 
             */
            throw new Fisma_Exception('invalid page');
        }
        
        $q = Doctrine_Query::create()
             ->select('o.id, o.name, o.nickname, s.type, s.confidentiality, s.integrity, s.availability, s.fipsCategory')
             ->from('Organization o')
             ->leftJoin('o.System s')
             ->where('o.orgType = ?', 'system')
             ->orderBy("$sortBy $order")
             ->limit($this->_paging['count'])
             ->offset($this->_paging['startIndex'])
             ->setHydrationMode(Doctrine::HYDRATE_SCALAR);

        if (!empty($value)) {
            $this->_helper->searchQuery($value, 'system');
            $cache = $this->getHelper('SearchQuery')->getCacheInstance();
            // get search results in ids
            $systemIds = $cache->load($this->_me->id . '_system');
            if (empty($systemIds)) {
                // set ids as a not exist value in database if search results is none.
                $systemIds = array(-1);
            }
            $q->whereIn('u.id', $systemIds);
        }

        $totalRecords = $q->count();
        $organizations = $q->execute();

        $tableData = array('table' => array(
            'recordsReturned' => count($organizations),
            'totalRecords' => $totalRecords,
            'startIndex' => $this->_paging['startIndex'],
            'sort' => $sortBy,
            'dir' => $order,
            'pageSize' => $this->_paging['count'],
            'records' => $organizations
        ));

        echo json_encode($tableData);
    }
    
    /**
     *  Render the form for searching input box the systems.
     */
    public function searchbox()
    {
        Fisma_Acl::requirePrivilege('systems', 'read');

        $visibility = $this->_request->getParam('sh');
        $keywords = $this->_request->getParam('keywords');
        $this->view->assign('visibility', $visibility);
        $this->view->assign('keywords', $keywords);
        $this->render('searchbox');
    }

    /**
     * Display the form for creating a new system.
     */
    public function createAction()
    {
        Fisma_Acl::requirePrivilege('systems', 'create');
        $form = $this->_getSystemForm('system');
        $sysValues = $this->_request->getPost();
        
        if ($sysValues) {
            if ($form->isValid($sysValues)) {
                $sysValues = $form->getValues();
                $system = new System();
                $system->merge($sysValues);
                
                if (!$system->trySave()) {
                    /** @todo english */ 
                    $msg = "Failure in creation";
                    $model = self::M_WARNING;
                } else {
                    $organization = $system->Organization[0];
                    $organization->getNode()->insertAsLastChildOf($organization->getTable()->find($sysValues['organization_id']));

                    //Create a system index
                    if (is_dir(Fisma::getPath('data') . '/index/system/')) {
                        $this->_helper->updateIndex('system', $system->id, $system->toArray());
                    }
                    /** @todo english */ 
                    $msg = "The system is created";
                    $model = self::M_NOTICE;
                }
                $this->message($msg, $model);
                $this->_forward('create', null, null, array('id' => $system->id));
                return;
            } else {
                $errorString = Fisma_Form_Manager::getErrors($form);
                /** @todo english */ 
                $this->message("Unable to create system:<br>$errorString", self::M_WARNING);
            }
        }
        $this->searchbox();
        $this->view->title = "Create ";
        $this->view->form = $form;
        $this->render('create');
    }

    /**
     *  Delete a specified system.
     */
    public function deleteAction()
    {
        Fisma_Acl::requirePrivilege('systems', 'delete');
        $id = $this->_request->getParam('id');
        $system = Doctrine::getTable('System')->find($id);
        if ($system) {
            // System table holds only attributes and will not be retrived since OrgSystem has been soft deleted.
            if ($system->Organization[0]->delete()) {
                //Delete this system index
                if (is_dir(Fisma::getPath('data') . '/index/system/')) {
                    $this->_helper->deleteIndex('system', $id);
                }
                //Delete this organization index
                if (is_dir(Fisma::getPath('data') . '/index/organization/')) {
                    $this->_helper->deleteIndex('organization', $system->Organization[0]->id);
                }
                /**
                 * @todo english
                 */
                $msg = "System deleted successfully";
                $model = self::M_NOTICE;
            } else {
                /**
                 * @todo english
                 */
                $msg = "Failed to delete the System";
                $model = self::M_WARNING;
            }
            $this->message($msg, $model);
        }
        $this->_forward('list');
    }
    
    /**
     * Display a single system record with all details.
     */
    public function viewAction()
    {
        Fisma_Acl::requirePrivilege('systems', 'read');
        $this->searchbox();
        
        $form = $this->_getSystemForm();
        $id = $this->_request->getParam('id');
        $v = $this->_request->getParam('v', 'view');

        $organization = Doctrine::getTable('Organization')->find($id);
        if (!$organization) {
            throw new Fisma_Exception("Invalid organizationd ID: '$id'");
        } else {
            $system = array();
            $system['name'] = $organization->name;
            $system['nickname'] = $organization->nickname;
            $system['description'] = $organization->description;
        }
        
        if ($v == 'edit') {
            $this->view->assign('viewLink',
                                "/panel/system/sub/view/id/$id");
            $form->setAction("/panel/system/sub/update/id/$id");
        } else {
            // In view mode, disable all of the form controls
            $this->view->assign('editLink',
                                "/panel/system/sub/view/id/$id/v/edit");
            $form->setReadOnly(true);            
        }
        $this->view->assign('deleteLink', "/panel/system/sub/delete/id/$id");
        $form->setDefaults($system);
        $this->view->form = $form;
        $this->view->assign('id', $id);
        $this->render($v);
    }

    /**
     * Updates system information after submitting an edit form.
     */
    public function updateAction ()
    {
        Fisma_Acl::requirePrivilege('systems', 'update');
        
        $form = $this->_getSystemForm();
        $formValid = $form->isValid($_POST);
        $id = $this->_request->getParam('id');
        
        if (empty($id)) {
            throw new Exception_General("The system posted is not a valid system");
        }
        if ($formValid) {
            $isModify = false;
            $system = new System();
            $system = $system->getTable()->find($id);
            $sysValues = $form->getValues();
            $system->merge($sysValues);
            
            if ($system->isModified()) {
                $system->save();
                $organization = $system->Organization[0]->getNode();
                if ($sysValues['organization_id'] != $organization->getParent()->id) {
                    $organization->moveAsLastChildOf(Doctrine::getTable('Organization')->find($sysValues['organization_id']));
                    $isModify = true;
                }

                //Update findings index
                if (is_dir(Fisma::getPath('data') . '/index/finding')) {
                    $index = new Zend_Search_Lucene(Fisma::getPath('data') . '/index/finding');
                    $hits = $index->find('system:'.$query);
                    $ids = array();
                    foreach ($hits as $hit) {
                        $ids[] = $hit->id;
                        $x[] = $hit->rowId;
                    }
                    $this->_helper->updateIndex('finding', $ids, $data['system'] = $system->name.' ' .$system->nickname);
                }
                //Update this system index
                if (is_dir(Fisma::getPath('data') . '/index/system/')) {
                    $this->_helper->updateIndex('system', $system->id, $system->toArray());
                }
                $msg = "The system is saved";
                $model = self::M_NOTICE;
            } else {
                $msg = "Nothing changes";
                $model = self::M_WARNING;
            }
            $this->message($msg, $model);
            $this->_forward('view', null, null, array('id' => $system->id));
        } else {
            $errorString = Fisma_Form_Manager::getErrors($form);
            /** @todo english */ 
            $this->message("Unable to update system:<br>$errorString", self::M_WARNING);
            // On error, redirect back to the edit action.
            $this->_forward('view', null, null, array('id' => $id, 'v' => 'edit'));
        }
    }
}
