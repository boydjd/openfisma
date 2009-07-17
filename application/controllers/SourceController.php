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
class SourceController extends BaseController
{
    protected $_modelName = 'Source';
    
    /**
     * Delete a subject model
     */
    public function deleteAction()
    {
        Fisma_Acl::requirePrivilege($this->_modelName, 'delete');
        $id = $this->_request->getParam('id');
        $source = Doctrine::getTable($this->_modelName)->find($id);
        if (!$source) {
            $msg   = "Invalid {$this->_modelName} ID";
            $type = self::M_WARNING;
        } else {
            try {
                if (count($source->Findings) > 0) {
                    $msg = 'This source cannot be deleted because it is already associated with one or
                            findings';
                    $type = self::M_WARNING;
                } else {
                    Doctrine_Manager::connection()->beginTransaction();
                    $source->delete();
                    Doctrine_Manager::connection()->commit();
                    $msg   = "{$this->_modelName} deleted successfully";
                    $type = self::M_NOTICE;
                }
            } catch (Doctrine_Exception $e) {
                Doctrine_Manager::connection()->rollback();
                if (Fisma::debug()) {
                    $msg .= $e->getMessage();
                }
                $type = self::M_WARNING;
            } 
        }
        $this->message($msg, $type);
        $this->_forward('list');
    }
}
