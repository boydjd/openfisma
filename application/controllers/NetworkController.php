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
 * <http://www.gnu.org/licenses/>.
 */

/**
 * The network controller handles searching, displaying, creating, and updating
 * network objects.
 *
 * @author     Ryan Yang <ryan@users.sourceforge.net>
 * @copyright  (c) Endeavor Systems, Inc. 2009 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/content/license
 * @package    Controller
 * @version    $Id$
 */
class NetworkController extends BaseController
{
    
    protected $_modelName = 'Network';

    /**
     * Delete a network
     */
    public function deleteAction()
    {
        Fisma_Acl::requirePrivilege('network', 'delete');
        
        $id = $this->_request->getParam('id');
        $network = Doctrine::getTable('Network')->find($id);
        if (!$network) {
            $msg   = "Invalid Network ID";
            $type = 'warning';
        } else {
            $assets = $network->Assets->toArray();
            if (!empty($assets)) {
                $msg = 'This network can not be deleted because it is'
                     . ' already associated with one or more assets';
                $type = 'warning';
            } else {
                parent::deleteAction();
                // parent method will take care 
                // of the message and forword the page
                return;
            }
        }
        $this->view->priorityMessenger($msg, $type);
        $this->_forward('list');
    }

}
