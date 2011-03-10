<?php
/**
 * Copyright (c) 2011 Endeavor Systems, Inc.
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
 * Provides server-side persistence to the Fisma Storage API
 * 
 * @author     Andrew Reeves <andrew.reeves@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Controller
 */
class StorageController extends Fisma_Zend_Controller_Action_Security
{
    /**
     * Set JSON contexts.
     */
    public function init()
    {
        parent::init();
        $this->_helper->contextSwitch
                      ->setActionContext('sync', 'json')
                      ->initContext();
    }
       
    /**
     * Synchronize storage values.
     */
    public function syncAction()
    {
        $userId = CurrentUser::getInstance()->id;
        $namespace = $this->getRequest()->getPost('namespace');
        $updates = Zend_Json::decode($this->getRequest()->getPost('updates'));
        $set = Zend_Json::decode($this->getRequest()->getPost('set'));
        $reply = Zend_Json::decode($this->getRequest()->getPost('reply'));

        $table = Doctrine::getTable('Storage');
        $object = $table->getUserIdAndNamespaceQuery($userId, $namespace)->fetchOne();
        if (empty($object)) {
            $object = $table->create(array('userId' => $userId, 'namespace' => $namespace, 'data' => array()));
        }
        if (!empty($set)) {
            $object->data = array();
            $updates = $set;
        }
        if (!empty($updates)) {
            $object->data = array_merge($object->data, $updates);
        }
        $object->save();

        $values = array();
        if (is_array($reply)) {
            foreach ($reply as $key) {
                if (isset($object->data[$key])) {
                    $values[$key] = $object->data[$key];
                } else {
                    $values[$key] = null;
                }
            }
        } else {
            $values = $object->data;
        }
        $this->view->data = $values;
        $this->view->status = 'ok';
    }
}
