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
 * The network controller handles searching, displaying, creating, and updating
 * network objects.
 *
 * @package   Controller
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
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
            $type = self::M_WARNING;
        } else {
            $assets = $network->Assets->toArray();
            if (!empty($assets)) {
                $msg = 'This network can not be deleted because it is'
                     . ' already associated with one or more assets';
                $type = self::M_WARNING;
            } else {
                parent::deleteAction();
                // parent method will take care 
                // of the message and forword the page
                return;
            }
        }
        $this->message($msg, $type);
        $this->_forward('list');
    }

}
