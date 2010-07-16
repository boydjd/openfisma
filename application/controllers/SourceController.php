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
 * Handles CRUD for finding source objects.
 *
 * @author     Ryan Yang <ryan@users.sourceforge.net>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Controller
 * @version    $Id$
 */
class SourceController extends Fisma_Zend_Controller_Action_Base
{
    /**
     * The main name of the model.
     * 
     * This model is the main subject which the controller operates on.
     * 
     * @var string
     */
    protected $_modelName = 'Source';
    
    /**
     * Delete a subject model
     * 
     * @return void
     */
    public function deleteAction()
    {
        $id = $this->_request->getParam('id');
        $source = Doctrine::getTable($this->_modelName)->find($id);
        if (!$source) {
            $msg   = "Invalid {$this->_modelName} ID";
            $type = 'warning';
        } else {
            $this->_acl->requirePrivilegeForObject('delete', $source);
            
            try {
                if (count($source->Findings) > 0) {
                    $msg = 'This source cannot be deleted because it is already associated with one or
                            findings';
                    $type = 'warning';
                } else {
                    Doctrine_Manager::connection()->beginTransaction();
                    $source->delete();
                    Doctrine_Manager::connection()->commit();
                    $msg   = "{$this->_modelName} deleted successfully";
                    $type = 'notice';
                }
            } catch (Doctrine_Exception $e) {
                Doctrine_Manager::connection()->rollback();
                if (Fisma::debug()) {
                    $msg .= $e->getMessage();
                }
                $type = 'warning';
            } 
        }
        $this->view->priorityMessenger($msg, $type);
        $this->_forward('list');
    }
}
